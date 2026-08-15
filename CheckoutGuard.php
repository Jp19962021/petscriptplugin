<?php

namespace PetScript\RxCheckout\WooCommerce;

use PetScript\RxCheckout\Domain\Cart\PrescriptionCartChecker;

/**
 * Sends guests straight to My Account instead of letting them hit the
 * generic cart-notice block at checkout, since prescription products
 * require an account for the reusable pet/clinic catalog.
 */
final class CheckoutGuard
{
    public function __construct(private readonly PrescriptionCartChecker $checker)
    {
    }

    public function register(): void
    {
        add_action('template_redirect', [$this, 'maybeRedirectGuest']);
    }

    public function maybeRedirectGuest(): void
    {
        if (is_user_logged_in() || !function_exists('is_checkout') || !is_checkout() || is_wc_endpoint_url('order-received')) {
            return;
        }

        if (!$this->checker->cartRequiresPrescription()) {
            return;
        }

        wc_add_notice(
            __('Prescription items require an account. Please log in or register to continue.', 'petscript-rx-checkout'),
            'error'
        );

        wp_safe_redirect(wc_get_page_permalink('myaccount'));
        exit;
    }
}
