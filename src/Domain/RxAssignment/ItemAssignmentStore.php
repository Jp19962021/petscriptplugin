<?php

namespace PetScript\RxCheckout\Domain\RxAssignment;

use PetScript\RxCheckout\Support\Config;

/**
 * Maps WooCommerce cart_item_key => RxAssignment, since each prescription
 * line in the cart can be for a different patient/clinic.
 */
final class ItemAssignmentStore
{
    /**
     * @return array<string, RxAssignment>
     */
    public function all(): array
    {
        if (!WC()->session) {
            return [];
        }

        $raw = WC()->session->get(Config::SESSION_ASSIGNMENTS_KEY);

        if (!is_array($raw)) {
            return [];
        }

        $assignments = [];

        foreach ($raw as $cartItemKey => $data) {
            $assignment = RxAssignment::fromArray((array) $data);

            if ($assignment !== null) {
                $assignments[$cartItemKey] = $assignment;
            }
        }

        return $assignments;
    }

    public function get(string $cartItemKey): ?RxAssignment
    {
        return $this->all()[$cartItemKey] ?? null;
    }

    public function set(string $cartItemKey, RxAssignment $assignment): void
    {
        if (!WC()->session) {
            return;
        }

        $raw = WC()->session->get(Config::SESSION_ASSIGNMENTS_KEY);
        $raw = is_array($raw) ? $raw : [];
        $raw[$cartItemKey] = $assignment->toArray();

        WC()->session->set(Config::SESSION_ASSIGNMENTS_KEY, $raw);
    }

    public function clear(): void
    {
        if (!WC()->session) {
            return;
        }

        WC()->session->set(Config::SESSION_ASSIGNMENTS_KEY, null);
    }

    public function isCompleteFor(string $cartItemKey): bool
    {
        $assignment = $this->get($cartItemKey);

        if ($assignment === null) {
            return false;
        }

        return in_array($assignment->approvalMethod, Config::APPROVAL_METHODS, true)
            && in_array($assignment->shipToType, Config::SHIP_BILL_TYPES, true)
            && in_array($assignment->billToType, Config::SHIP_BILL_TYPES, true);
    }
}
