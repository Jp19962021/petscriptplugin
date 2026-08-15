<?php

namespace PetScript\RxCheckout\Http\Ajax;

use PetScript\RxCheckout\Domain\Patient\PatientRepository;

final class SavePatientHandler extends AbstractAjaxHandler
{
    public function __construct(private readonly PatientRepository $patients)
    {
    }

    public function action(): string
    {
        return 'ps_rxc_save_patient';
    }

    protected function respond(int $customerId, array $input): void
    {
        $id = $this->absintOrNull($input['id'] ?? null);

        if ($id !== null && $this->patients->find($id, $customerId) === null) {
            wp_send_json_error(['message' => __('Patient not found.', 'petscript-rx-checkout')], 404);
        }

        if (empty($input['name']) || empty($input['species'])) {
            wp_send_json_error(['message' => __('Name and species are required.', 'petscript-rx-checkout')], 422);
        }

        $patient = $this->patients->save($customerId, $input, $id);

        wp_send_json_success(['patient' => $patient->toArray()]);
    }
}
