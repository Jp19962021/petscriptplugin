<?php

namespace PetScript\RxCheckout\Support;

final class Settings
{
    private const OPTION_BASE_URL = 'ps_rxc_pharmacy_base_url';

    private const OPTION_API_KEY = 'ps_rxc_pharmacy_api_key';

    private const OPTION_TEST_MODE = 'ps_rxc_test_mode';

    public static function pharmacyBaseUrl(): string
    {
        $value = get_option(self::OPTION_BASE_URL, '');

        return is_string($value) ? untrailingslashit($value) : '';
    }

    public static function updatePharmacyBaseUrl(string $url): void
    {
        update_option(self::OPTION_BASE_URL, untrailingslashit(sanitize_text_field($url)));
    }

    public static function pharmacyApiKey(): string
    {
        if (defined('PS_RXC_PHARMACY_API_KEY') && PS_RXC_PHARMACY_API_KEY !== '') {
            return (string) PS_RXC_PHARMACY_API_KEY;
        }

        $value = get_option(self::OPTION_API_KEY, '');

        return is_string($value) ? $value : '';
    }

    public static function updatePharmacyApiKey(string $key): void
    {
        // Never overwrite a real key with the masked preview shown in the settings UI.
        if (str_contains($key, '•')) {
            return;
        }

        update_option(self::OPTION_API_KEY, sanitize_text_field($key));
    }

    public static function maskedApiKey(): string
    {
        $key = self::pharmacyApiKey();

        if ($key === '') {
            return '';
        }

        $tail = substr($key, -4);

        return str_repeat('•', 12) . $tail;
    }

    public static function isTestMode(): bool
    {
        return get_option(self::OPTION_TEST_MODE, 'no') === 'yes';
    }

    public static function updateTestMode(bool $enabled): void
    {
        update_option(self::OPTION_TEST_MODE, $enabled ? 'yes' : 'no');
    }

    public static function externalOrdersEndpoint(): string
    {
        $base = self::pharmacyBaseUrl();

        if ($base === '') {
            return '';
        }

        return $base . '/wp-json/petscript/v1/external/orders';
    }
}
