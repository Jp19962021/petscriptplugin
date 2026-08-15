<?php

namespace PetScript\RxCheckout\Integration;

use PetScript\RxCheckout\Support\Config;
use WC_Order;
use WC_Order_Item_Product;

/**
 * Builds the exact JSON contract expected by
 * POST /wp-json/petscript/v1/external/orders on the petscript-Pharmacy site
 * (see ExternalOrderApiController::store there).
 *
 * That endpoint accepts only ONE patient + ONE clinic per call, but a single
 * WooCommerce order can contain prescription lines for different pets/clinics
 * (each line carries its own snapshot, see OrderSnapshot). So instead of one
 * payload per order, this groups lines by their group_key and returns one
 * payload per group — the caller (OrderSubmitter) sends one POST per group.
 */
final class PayloadMapper
{
    /**
     * @return array<int, array{group_key: string, payload: array, item_ids: int[]}>
     */
    public function buildPayloadsForOrder(WC_Order $order): array
    {
        $groups = [];

        foreach ($order->get_items('line_item') as $itemId => $item) {
            /** @var WC_Order_Item_Product $item */
            $groupKey = $item->get_meta(Config::ITEM_META_GROUP_KEY);

            if (!$groupKey) {
                continue;
            }

            $groups[$groupKey]['item_ids'][] = $itemId;
            $groups[$groupKey]['items'][] = $item;
            $groups[$groupKey]['patient'] ??= json_decode((string) $item->get_meta(Config::ITEM_META_PATIENT_SNAPSHOT), true) ?: [];
            $groups[$groupKey]['clinic'] ??= json_decode((string) $item->get_meta(Config::ITEM_META_CLINIC_SNAPSHOT), true) ?: [];
            $groups[$groupKey]['approval'] ??= json_decode((string) $item->get_meta(Config::ITEM_META_APPROVAL), true) ?: [];
        }

        $result = [];

        foreach ($groups as $groupKey => $group) {
            $result[] = [
                'group_key' => $groupKey,
                'item_ids' => $group['item_ids'],
                'payload' => $this->buildPayload($order, $group['patient'], $group['clinic'], $group['approval'], $group['items']),
            ];
        }

        return $result;
    }

    /**
     * @param WC_Order_Item_Product[] $items
     */
    private function buildPayload(WC_Order $order, array $patient, array $clinic, array $approval, array $items): array
    {
        return [
            'client' => [
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'address_line1' => $order->get_billing_address_1(),
                'city' => $order->get_billing_city(),
                'state' => $order->get_billing_state(),
                'postal_code' => $order->get_billing_postcode(),
                'country' => $order->get_billing_country(),
            ],
            'patient' => [
                'name' => $patient['name'] ?? '',
                'species' => $patient['species'] ?? '',
                'breed' => $patient['breed'] ?? null,
                'gender' => $patient['sex'] ?? null,
                'birthdate' => $patient['birthdate'] ?? null,
                'weight_lbs' => $patient['weight_lbs'] ?? null,
                'medications' => $patient['medications'] ?? null,
                'allergies' => $patient['allergies'] ?? null,
                'pre_existing_conditions' => $patient['pre_existing_conditions'] ?? null,
            ],
            'vet_clinic' => [
                'name' => $clinic['name'] ?? '',
                'phone' => $clinic['phone'] ?? null,
                'address' => $clinic['address'] ?? null,
                'city' => $clinic['city'] ?? null,
                'state' => $clinic['state'] ?? null,
                'postal_code' => $clinic['postal_code'] ?? null,
                'country' => $clinic['country'] ?? null,
            ],
            'approval_method' => $approval['approval_method'] ?? null,
            'ship_to_type' => $approval['ship_to_type'] ?? null,
            'bill_to_type' => $approval['bill_to_type'] ?? null,
            'external_order_id' => $order->get_order_number() . '-' . substr(md5(($patient['name'] ?? '') . ($clinic['name'] ?? '')), 0, 6),
            'items' => $this->buildItems($items),
        ];
    }

    /**
     * @param WC_Order_Item_Product[] $items
     */
    private function buildItems(array $items): array
    {
        $lines = [];

        foreach ($items as $item) {
            $product = $item->get_product();

            $lines[] = [
                // NOTE: product/variation IDs are local to this store and do
                // NOT match petscript-Pharmacy's own WooCommerce catalog.
                // `sku` is always included so Pharmacy can resolve by SKU
                // if it can't trust product_id across sites — confirm with
                // the Pharmacy team before relying on product_id in prod.
                'product_id' => $item->get_product_id(),
                'variation_id' => $item->get_variation_id() ?: null,
                'product_name' => $item->get_name(),
                'variation_name' => $product instanceof \WC_Product_Variation ? wc_get_formatted_variation($product, true) : null,
                'sku' => $product ? $product->get_sku() : null,
                'quantity' => $item->get_quantity(),
                'unit_price' => $item->get_quantity() > 0 ? round($item->get_total() / $item->get_quantity(), 2) : 0,
                'refills' => null,
            ];
        }

        return $lines;
    }
}
