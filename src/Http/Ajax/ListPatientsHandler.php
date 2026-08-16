<?php

namespace PetScript\RxCheckout\Http\Ajax;

use PetScript\RxCheckout\Domain\Patient\PatientRepository;

final class ListPatientsHandler extends AbstractAjaxHandler
{
    public function __construct(private readonly PatientRepository $patients)
    {
    }

    public function action(): string
    {
        return 'ps_rxc_list_patients';
    }

    protected function respond(int $customerId, array $input): void
    {
        $patients = array_map(
            static fn ($patient) => $patient->toArray(),
            $this->patients->forCustomer($customerId)
        );

        wp_send_json_success(['patients' => $patients]);
    }
}
