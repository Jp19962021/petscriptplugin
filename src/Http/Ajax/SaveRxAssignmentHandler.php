<?php

namespace PetScript\RxCheckout\Http\Ajax;

use PetScript\RxCheckout\Domain\Cart\PrescriptionCartChecker;
use PetScript\RxCheckout\Domain\Clinic\ClinicRepository;
use PetScript\RxCheckout\Domain\Patient\PatientRepository;
use PetScript\RxCheckout\Domain\RxAssignment\ItemAssignmentStore;
use PetScript\RxCheckout\Domain\RxAssignment\RxAssignment;
use PetScript\RxCheckout\Support\Config;

final class SaveRxAssignmentHandler extends AbstractAjaxHandler
{
    public function __construct(
        private readonly PatientRepository $patients,
        private readonly ClinicRepository $clinics,
        private readonly ItemAssignmentStore $assignments,
        private readonly PrescriptionCartChecker $checker,
    ) {
    }

    public function action(): string
    {
        return 'ps_rxc_save_assignment';
    }

    protected function respond(int $customerId, array $input): void
    {
        $cartItemKey = sanitize_text_field($input['cart_item_key'] ?? '');
        $patientId = $this->absintOrNull($input['patient_id'] ?? null);
        $clinicId = $this->absintOrNull($input['clinic_id'] ?? null);
        $approvalMethod = sanitize_text_field($input['approval_method'] ?? '');

        // Ship-to/bill-to are no longer surfaced in the UI — always the
        // customer per product decision. Still validated against the
        // whitelist below (defense in depth) rather than trusted blindly.
        $shipToType = sanitize_text_field($input['ship_to_type'] ?? 'patient') ?: 'patient';
        $billToType = sanitize_text_field($input['bill_to_type'] ?? 'patient') ?: 'patient';

        $validKeys = array_column($this->checker->rxRequiredCartItems(), 'key');

        if ($cartItemKey === '' || !in_array($cartItemKey, $validKeys, true)) {
            wp_send_json_error(['message' => __('This item is no longer in your cart.', 'petscript-rx-checkout')], 422);
        }

        if ($patientId === null || $this->patients->find($patientId, $customerId) === null) {
            wp_send_json_error(['message' => __('Please select a valid patient.', 'petscript-rx-checkout')], 422);
        }

        if ($clinicId === null || $this->clinics->find($clinicId, $customerId) === null) {
            wp_send_json_error(['message' => __('Please select a valid clinic.', 'petscript-rx-checkout')], 422);
        }

        if (!in_array($approvalMethod, Config::APPROVAL_METHODS, true)) {
            wp_send_json_error(['message' => __('Invalid approval method.', 'petscript-rx-checkout')], 422);
        }

        if (!in_array($shipToType, Config::SHIP_BILL_TYPES, true) || !in_array($billToType, Config::SHIP_BILL_TYPES, true)) {
            wp_send_json_error(['message' => __('Invalid shipping/billing preference.', 'petscript-rx-checkout')], 422);
        }

        $assignment = new RxAssignment($patientId, $clinicId, $approvalMethod, $shipToType, $billToType);
        $this->assignments->set($cartItemKey, $assignment);

        wp_send_json_success(['assignment' => $assignment->toArray()]);
    }
}
