<?php

namespace PetScript\RxCheckout\Http\Ajax;

use PetScript\RxCheckout\Domain\Patient\PatientRepository;

final class DeletePatientHandler extends AbstractAjaxHandler
{
    public function __construct(private readonly PatientRepository $patients)
    {
    }

    public function action(): string
    {
        return 'ps_rxc_delete_patient';
    }

    protected function respond(int $customerId, array $input): void
    {
        $id = $this->absintOrNull($input['id'] ?? null);

        if ($id === null || !$this->patients->delete($id, $customerId)) {
            wp_send_json_error(['message' => __('Unable to delete the patient.', 'petscript-rx-checkout')], 404);
        }

        wp_send_json_success();
    }
}
