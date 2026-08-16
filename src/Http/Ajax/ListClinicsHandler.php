<?php

namespace PetScript\RxCheckout\Http\Ajax;

use PetScript\RxCheckout\Domain\Clinic\ClinicRepository;

final class ListClinicsHandler extends AbstractAjaxHandler
{
    public function __construct(private readonly ClinicRepository $clinics)
    {
    }

    public function action(): string
    {
        return 'ps_rxc_list_clinics';
    }

    protected function respond(int $customerId, array $input): void
    {
        $clinics = array_map(
            static fn ($clinic) => $clinic->toArray(),
            $this->clinics->forCustomer($customerId)
        );

        wp_send_json_success(['clinics' => $clinics]);
    }
}
