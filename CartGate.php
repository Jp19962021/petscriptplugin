<?php

namespace PetScript\RxCheckout\WooCommerce;

use PetScript\RxCheckout\Domain\Cart\PrescriptionCartChecker;
use PetScript\RxCheckout\Domain\RxAssignment\ItemAssignmentStore;

/**
 * The real, server-side checkout block. Unlike the previous plugin (whose
 * equivalent hook was left commented out and relied only on a JS
 * MutationObserver), this runs on every cart validation pass — including
 * during woocommerce_check_cart_items() called from process_checkout() —
 * and cannot be bypassed by disabling JavaScript or posting directly to
 * /checkout/.
 */
final class CartGate
{
    public function __construct(
        private readonly PrescriptionCartChecker $checker,
        private readonly ItemAssignmentStore $assignments,
    ) {
    }

    public function register(): void
    {
        add_action('woocommerce_check_cart_items', [$this, 'validate']);
    }

    public function validate(): void
    {
        $items = $this->checker->rxRequiredCartItems();

        if ($items === []) {
            return;
        }

        $incomplete = !is_user_logged_in()
            ? $items
            : array_filter($items, fn ($item) => !$this->assignments->isCompleteFor($item['key']));

        if ($incomplete === []) {
            return;
        }

        // The cart page already communicates this via the inline banner and
        // per-product buttons (see CartNotice), so a second, native WC error
        // notice there would just be visual noise. This hook still needs to
        // run there so the SAME check fires again during actual checkout
        // submission — where wc_add_notice() is what makes WooCommerce
        // actually abort process_checkout(). So: skip the visible notice on
        // the cart page, but keep enforcing everywhere else.
        if (function_exists('is_cart') && is_cart()) {
            return;
        }

        if (!is_user_logged_in()) {
            wc_add_notice(
                __('One or more items in your cart require a prescription. Please log in or create an account to continue.', 'petscript-rx-checkout'),
                'error'
            );

            return;
        }

        wc_add_notice(
            __('Please add prescription information for all items that require it before checking out. Return to your cart to complete it.', 'petscript-rx-checkout'),
            'error'
        );
    }
}
