<?php

namespace PetScript\RxCheckout\WooCommerce;

use PetScript\RxCheckout\Integration\PayloadMapper;
use PetScript\RxCheckout\Support\Config;
use WC_Order;

final class OrderAdminColumn
{
    private const RETRY_ACTION = 'ps_rxc_retry_send';

    public function __construct(
        private readonly OrderSubmitter $submitter,
        private readonly PayloadMapper $mapper,
    ) {
    }

    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('admin_post_' . self::RETRY_ACTION, [$this, 'handleRetry']);
    }

    public function addMetaBox(): void
    {
        $screen = class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')
            && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id('shop-order')
            : 'shop_order';

        add_meta_box(
            'ps_rxc_status',
            __('PetScript Rx Checkout', 'petscript-rx-checkout'),
            [$this, 'renderMetaBox'],
            $screen,
            'side'
        );
    }

    public function renderMetaBox(\WP_Post|WC_Order $post): void
    {
        $order = $post instanceof WC_Order ? $post : wc_get_order($post->ID);

        if (!$order) {
            return;
        }

        $groups = $this->mapper->buildPayloadsForOrder($order);

        if ($groups === []) {
            echo '<p>' . esc_html__('This order does not require a prescription.', 'petscript-rx-checkout') . '</p>';

            return;
        }

        $sent = json_decode((string) $order->get_meta(Config::ORDER_META_SENT_GROUPS), true);
        $sent = is_array($sent) ? $sent : [];

        echo '<ul style="margin:0;">';

        foreach ($groups as $group) {
            $patientName = $group['payload']['patient']['name'] ?? __('(unnamed patient)', 'petscript-rx-checkout');
            $status = $sent[$group['group_key']] ?? null;

            echo '<li style="margin-bottom:.75em; padding-bottom:.75em; border-bottom:1px solid #eee;">';
            printf('<strong>%s</strong><br>', esc_html($patientName));

            if (isset($status['rx_id'])) {
                printf(
                    '%s <strong>%s</strong>',
                    esc_html__('Sent. Rx:', 'petscript-rx-checkout'),
                    esc_html((string) $status['rx_id'])
                );
            } else {
                if (isset($status['message'])) {
                    printf('<span style="color:#b32d2e;">%s</span><br>', esc_html((string) $status['message']));
                } else {
                    echo esc_html__('Not yet sent to PetScript Pharmacy.', 'petscript-rx-checkout');
                }

                echo '<br>';

                $url = wp_nonce_url(
                    admin_url('admin-post.php?action=' . self::RETRY_ACTION . '&order_id=' . $order->get_id() . '&group=' . rawurlencode($group['group_key'])),
                    self::RETRY_ACTION . '_' . $order->get_id()
                );

                printf(
                    '<a href="%s" class="button button-small">%s</a>',
                    esc_url($url),
                    esc_html__('Retry send', 'petscript-rx-checkout')
                );
            }

            echo '</li>';
        }

        echo '</ul>';
    }

    public function handleRetry(): void
    {
        $orderId = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;

        check_admin_referer(self::RETRY_ACTION . '_' . $orderId);

        if (!current_user_can('edit_shop_orders')) {
            wp_die(esc_html__('You do not have permission to do this.', 'petscript-rx-checkout'));
        }

        $order = wc_get_order($orderId);

        if ($order) {
            $this->submitter->submit($order);
        }

        wp_safe_redirect(wp_get_referer() ?: admin_url());
        exit;
    }
}
