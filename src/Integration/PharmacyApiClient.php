<?php

namespace PetScript\RxCheckout\Integration;

use PetScript\RxCheckout\Support\Settings;

final class PharmacyApiClient
{
    public function submitExternalOrder(array $payload): PharmacyApiResult
    {
        $endpoint = Settings::externalOrdersEndpoint();
        $apiKey = Settings::pharmacyApiKey();

        if ($endpoint === '' || $apiKey === '') {
            return PharmacyApiResult::failure(
                __('PetScript Rx Checkout is not configured (missing Pharmacy URL/API key).', 'petscript-rx-checkout')
            );
        }

        if (Settings::isTestMode()) {
            if (function_exists('wc_get_logger')) {
                wc_get_logger()->info('TEST MODE — payload not sent: ' . wp_json_encode($payload), ['source' => 'petscript-rx-checkout']);
            }

            return PharmacyApiResult::success('TEST-' . wp_generate_password(8, false));
        }

        $response = wp_remote_post($endpoint, [
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-PetScript-Key' => $apiKey,
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            return PharmacyApiResult::failure($response->get_error_message());
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status >= 200 && $status < 300 && !empty($body['success'])) {
            return PharmacyApiResult::success(isset($body['rx_formatted']) ? (string) $body['rx_formatted'] : (string) ($body['rx_id'] ?? ''));
        }

        $message = $body['message'] ?? sprintf('HTTP %d', $status);

        if (!empty($body['errors']) && is_array($body['errors'])) {
            $message .= ' — ' . wp_json_encode($body['errors']);
        }

        return PharmacyApiResult::failure((string) $message);
    }
}
