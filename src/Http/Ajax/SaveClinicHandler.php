<?php

namespace PetScript\RxCheckout\Http\Ajax;

use PetScript\RxCheckout\Domain\Clinic\ClinicRepository;
use PetScript\RxCheckout\Support\Config;

final class SaveClinicHandler extends AbstractAjaxHandler
{
    public function __construct(private readonly ClinicRepository $clinics)
    {
    }

    public function action(): string
    {
        return 'ps_rxc_save_clinic';
    }

    protected function respond(int $customerId, array $input): void
    {
        $id = $this->absintOrNull($input['id'] ?? null);

        if ($id !== null && $this->clinics->find($id, $customerId) === null) {
            wp_send_json_error(['message' => __('Clinic not found.', 'petscript-rx-checkout')], 404);
        }

        if (empty($input['name'])) {
            wp_send_json_error(['message' => __('Clinic name is required.', 'petscript-rx-checkout')], 422);
        }

        if (empty($input['vet_first_name']) || empty($input['vet_last_name'])) {
            wp_send_json_error(['message' => __("Veterinarian's first and last name are required.", 'petscript-rx-checkout')], 422);
        }

        // Every customer-created clinic enters the admin approval queue. It is
        // immediately usable for this customer's own orders (find() allows
        // pending own clinics) — approval only controls directory publishing.
        $status = $id === null ? Config::CLINIC_STATUS_PENDING : null;

        $clinic = $this->clinics->save($customerId, $input, $id, $status);

        wp_send_json_success(['clinic' => $clinic->toArray()]);
    }
}
