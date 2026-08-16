<?php

namespace PetScript\RxCheckout\Http\Ajax;

use PetScript\RxCheckout\Domain\Clinic\ClinicRepository;

final class DeleteClinicHandler extends AbstractAjaxHandler
{
    public function __construct(private readonly ClinicRepository $clinics)
    {
    }

    public function action(): string
    {
        return 'ps_rxc_delete_clinic';
    }

    protected function respond(int $customerId, array $input): void
    {
        $id = $this->absintOrNull($input['id'] ?? null);

        if ($id === null || !$this->clinics->delete($id, $customerId)) {
            wp_send_json_error(['message' => __('Unable to delete the clinic.', 'petscript-rx-checkout')], 404);
        }

        wp_send_json_success();
    }
}
