<?php

namespace PetScript\RxCheckout\Domain\Cart;

use PetScript\RxCheckout\Support\Config;

final class PrescriptionCartChecker
{
    public function cartRequiresPrescription(): bool
    {
        return $this->rxRequiredCartItems() !== [];
    }

    /**
     * @return array<int, array{key: string, product_id: int, name: string, quantity: int}>
     */
    public function rxRequiredCartItems(): array
    {
        if (!function_exists('WC') || !WC()->cart) {
            return [];
        }

        $items = [];

        foreach (WC()->cart->get_cart() as $cartItemKey => $cartItem) {
            if (!$this->productRequiresPrescription((int) $cartItem['product_id'])) {
                continue;
            }

            $product = $cartItem['data'];

            $items[] = [
                'key' => $cartItemKey,
                'product_id' => (int) $cartItem['product_id'],
                'name' => $product ? $product->get_name() : '',
                'quantity' => (int) $cartItem['quantity'],
            ];
        }

        return $items;
    }

    public function productRequiresPrescription(int $productId): bool
    {
        $product = wc_get_product($productId);

        if (!$product) {
            return false;
        }

        // Variations don't carry their own tags; resolve to the parent product.
        $checkId = $product->is_type('variation') ? (int) $product->get_parent_id() : $productId;

        return has_term(Config::PRESCRIPTION_TAG_SLUG, 'product_tag', $checkId);
    }
}
