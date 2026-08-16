<?php

namespace PetScript\RxCheckout\WooCommerce;

use PetScript\RxCheckout\Domain\Clinic\ClinicRepository;
use PetScript\RxCheckout\Domain\Patient\PatientRepository;
use PetScript\RxCheckout\Domain\RxAssignment\ItemAssignmentStore;
use PetScript\RxCheckout\Support\Config;
use WC_Order;
use WC_Order_Item_Product;

/**
 * Freezes the chosen pet/clinic onto each prescription order LINE as an
 * immutable snapshot, so a later edit to the customer's saved pet/clinic
 * never changes what was actually submitted for a past order, and so
 * different lines in the same order can carry different patients/clinics.
 */
final class OrderSnapshot
{
    public function __construct(
        private readonly ItemAssignmentStore $assignments,
        private readonly PatientRepository $patients,
        private readonly ClinicRepository $clinics,
    ) {
    }

    public function register(): void
    {
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'attachSnapshot'], 10, 4);
        add_action('woocommerce_checkout_order_processed', [$this, 'clearSession']);
    }

    public function attachSnapshot(WC_Order_Item_Product $item, string $cartItemKey, array $values, WC_Order $order): void
    {
        $assignment = $this->assignments->get($cartItemKey);

        if ($assignment === null) {
            return;
        }

        $customerId = $order->get_customer_id();
        $patient = $this->patients->find($assignment->patientId, $customerId);
        $clinic = $this->clinics->find($assignment->clinicId, $customerId);

        if (!$patient || !$clinic) {
            return;
        }

        $item->add_meta_data(Config::ITEM_META_PATIENT_SNAPSHOT, wp_json_encode($patient->toArray()));
        $item->add_meta_data(Config::ITEM_META_CLINIC_SNAPSHOT, wp_json_encode($clinic->toArray()));
        $item->add_meta_data(Config::ITEM_META_APPROVAL, wp_json_encode($assignment->toArray()));
        $item->add_meta_data(Config::ITEM_META_GROUP_KEY, $assignment->groupKey());
    }

    public function clearSession(): void
    {
        $this->assignments->clear();
    }
}
