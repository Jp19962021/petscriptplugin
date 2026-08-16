<?php

namespace PetScript\RxCheckout\WooCommerce;

use PetScript\RxCheckout\Integration\PayloadMapper;
use PetScript\RxCheckout\Integration\PharmacyApiClient;
use PetScript\RxCheckout\Support\Config;
use WC_Order;

final class OrderSubmitter
{
    private const TRIGGER_STATUSES = ['processing', 'completed'];

    public function __construct(
        private readonly PayloadMapper $mapper,
        private readonly PharmacyApiClient $client,
    ) {
    }

    public function register(): void
    {
        add_action('woocommerce_order_status_changed', [$this, 'maybeSubmit'], 10, 4);
    }

    public function maybeSubmit(int $orderId, string $from, string $to, WC_Order $order): void
    {
        if (!in_array($to, self::TRIGGER_STATUSES, true)) {
            return;
        }

        $this->submit($order);
    }

    /**
     * Sends one Rx submission per (patient, clinic, approval) group found on
     * the order's prescription lines. Already-sent groups are skipped, so
     * this is safe to call again from the admin "retry" action.
     */
    public function submit(WC_Order $order): void
    {
        $groups = $this->mapper->buildPayloadsForOrder($order);

        if ($groups === []) {
            return;
        }

        $sent = $this->getSentGroups($order);
        $changed = false;

        foreach ($groups as $group) {
            if (isset($sent[$group['group_key']]['rx_id'])) {
                continue;
            }

            $result = $this->client->submitExternalOrder($group['payload']);
            $changed = true;

            $patientName = $group['payload']['patient']['name'] ?? '';

            if ($result->success) {
                $sent[$group['group_key']] = ['rx_id' => $result->rxId, 'status' => 'sent'];
                $order->add_order_note(sprintf(
                    /* translators: 1: patient name, 2: Rx id returned by PetScript Pharmacy */
                    __('Rx for %1$s successfully sent to PetScript Pharmacy (Rx #%2$s).', 'petscript-rx-checkout'),
                    $patientName !== '' ? $patientName : __('(unnamed patient)', 'petscript-rx-checkout'),
                    $result->rxId ?? '—'
                ));
            } else {
                $sent[$group['group_key']] = ['status' => 'error', 'message' => $result->message];
                $order->add_order_note(sprintf(
                    /* translators: 1: patient name, 2: error message */
                    __('Error sending the Rx for %1$s to PetScript Pharmacy: %2$s', 'petscript-rx-checkout'),
                    $patientName !== '' ? $patientName : __('(unnamed patient)', 'petscript-rx-checkout'),
                    $result->message
                ));
            }
        }

        if ($changed) {
            $order->update_meta_data(Config::ORDER_META_SENT_GROUPS, wp_json_encode($sent));
            $order->save();
        }
    }

    /**
     * @return array<string, array{rx_id?: string, status: string, message?: string}>
     */
    private function getSentGroups(WC_Order $order): array
    {
        $raw = json_decode((string) $order->get_meta(Config::ORDER_META_SENT_GROUPS), true);

        return is_array($raw) ? $raw : [];
    }
}
