<?php

namespace PetScript\RxCheckout\Http\Ajax;

use PetScript\RxCheckout\Domain\Clinic\ClinicRepository;

final class SearchClinicsHandler extends AbstractAjaxHandler
{
    public function __construct(private readonly ClinicRepository $clinics)
    {
    }

    public function action(): string
    {
        return 'ps_rxc_search_clinics';
    }

    protected function respond(int $customerId, array $input): void
    {
        $query = sanitize_text_field($input['q'] ?? '');
        $zip = sanitize_text_field($input['zip'] ?? '');

        $clinics = array_map(
            static fn ($clinic) => $clinic->toArray(),
            $this->clinics->search($customerId, $query, $zip)
        );

        wp_send_json_success(['clinics' => $clinics]);
    }
}
