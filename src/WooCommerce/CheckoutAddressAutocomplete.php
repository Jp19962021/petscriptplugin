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
 *
 * Does NOT enqueue the Google Maps script itself. This site also runs
 * other address-autocomplete plugins that load maps.googleapis.com/maps/api/js
 * on their own; loading it a second time re-registers its Web Components
 * and throws console errors (and can break autocomplete on this field
 * entirely). Instead the API key is handed to checkout-address.js, which
 * checks at runtime whether Maps is already loading/loaded before ever
 * adding its own script tag.
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
            'ps-rxc-checkout-address',
            PS_RXC_URL . 'assets/build/checkout-address.js',
            [],
            (string) filemtime(PS_RXC_DIR . '/assets/build/checkout-address.js'),
            true
        );

        wp_localize_script('ps-rxc-checkout-address', 'psRxcCheckoutAddress', [
            'apiKey' => $key,
        ]);
    }
}
