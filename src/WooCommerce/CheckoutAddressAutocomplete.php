<?php

namespace PetScript\RxCheckout\WooCommerce;

use PetScript\RxCheckout\Support\Settings;

/**
 * Google Places autocomplete on the WooCommerce checkout billing/shipping
 * street address fields, so city/state/ZIP/country are filled from the
 * selected place instead of being typed (and mistyped) by hand.
 *
 * Only active when a Google Maps API key is configured. Targets the classic
 * checkout field IDs (billing_address_1 / shipping_address_1); the block
 * checkout renders its own React fields and is not covered here.
 */
final class CheckoutAddressAutocomplete
{
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        if (!function_exists('is_checkout') || !is_checkout()) {
            return;
        }

        $key = Settings::googleMapsApiKey();

        if ($key === '') {
            return;
        }

        wp_enqueue_script(
            'ps-rxc-gmaps',
            'https://maps.googleapis.com/maps/api/js?key=' . rawurlencode($key) . '&libraries=places',
            [],
            null,
            true
        );

        wp_enqueue_script(
            'ps-rxc-checkout-address',
            PS_RXC_URL . 'assets/build/checkout-address.js',
            ['ps-rxc-gmaps'],
            (string) filemtime(PS_RXC_DIR . '/assets/build/checkout-address.js'),
            true
        );
    }
}
