<?php

namespace PetScript\RxCheckout\Http\Ajax;

use PetScript\RxCheckout\Domain\Clinic\ClinicRepository;

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

        $clinic = $this->clinics->save($customerId, $input, $id);

        wp_send_json_success(['clinic' => $clinic->toArray()]);
    }
}
