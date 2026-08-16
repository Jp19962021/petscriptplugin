<?php

namespace PetScript\RxCheckout\Http\Ajax;

use PetScript\RxCheckout\Support\Config;

abstract class AbstractAjaxHandler
{
    abstract public function action(): string;

    abstract protected function respond(int $customerId, array $input): void;

    public function register(): void
    {
        // Intentionally not registered for `nopriv` — every action in this
        // plugin requires an authenticated, verified customer.
        add_action('wp_ajax_' . $this->action(), [$this, 'handle']);
    }

    public function handle(): void
    {
        check_ajax_referer(Config::NONCE_ACTION, 'nonce');

        $customerId = get_current_user_id();

        if ($customerId === 0) {
            wp_send_json_error(['message' => __('You must be logged in.', 'petscript-rx-checkout')], 401);
        }

        $this->respond($customerId, wp_unslash($_POST));
    }

    protected function absintOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return absint($value);
    }
}
