<?php

namespace PetScript\RxCheckout\Admin;

use PetScript\RxCheckout\Support\Settings;

final class SettingsPage
{
    private const SLUG = 'petscript-rx-checkout';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'maybeSave']);
    }

    public function addMenu(): void
    {
        add_submenu_page(
            'woocommerce',
            __('PetScript Rx Checkout', 'petscript-rx-checkout'),
            __('Rx Checkout', 'petscript-rx-checkout'),
            'manage_woocommerce',
            self::SLUG,
            [$this, 'render']
        );
    }

    public function maybeSave(): void
    {
        if (!isset($_POST['ps_rxc_settings_nonce']) || !current_user_can('manage_woocommerce')) {
            return;
        }

        check_admin_referer('ps_rxc_settings_save', 'ps_rxc_settings_nonce');

        Settings::updatePharmacyBaseUrl((string) ($_POST['ps_rxc_base_url'] ?? ''));
        Settings::updatePharmacyApiKey((string) ($_POST['ps_rxc_api_key'] ?? ''));
        Settings::updateTestMode(!empty($_POST['ps_rxc_test_mode']));

        add_action('admin_notices', static function (): void {
            echo '<div class="notice notice-success"><p>' .
                esc_html__('Settings saved.', 'petscript-rx-checkout') .
                '</p></div>';
        });
    }

    public function render(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $baseUrl = Settings::pharmacyBaseUrl();
        $maskedKey = Settings::maskedApiKey();
        $testMode = Settings::isTestMode();
        $endpoint = Settings::externalOrdersEndpoint();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('PetScript Rx Checkout', 'petscript-rx-checkout'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('ps_rxc_settings_save', 'ps_rxc_settings_nonce'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="ps_rxc_base_url"><?php esc_html_e('PetScript Pharmacy base URL', 'petscript-rx-checkout'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="ps_rxc_base_url" name="ps_rxc_base_url"
                                   class="regular-text" value="<?php echo esc_attr($baseUrl); ?>"
                                   placeholder="https://pharmacy.example.com" />
                            <?php if ($endpoint !== '') : ?>
                                <p class="description"><?php echo esc_html($endpoint); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ps_rxc_api_key"><?php esc_html_e('API Key (X-PetScript-Key)', 'petscript-rx-checkout'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="ps_rxc_api_key" name="ps_rxc_api_key"
                                   class="regular-text" value="<?php echo esc_attr($maskedKey); ?>"
                                   autocomplete="off" />
                            <?php if (defined('PS_RXC_PHARMACY_API_KEY') && PS_RXC_PHARMACY_API_KEY !== '') : ?>
                                <p class="description">
                                    <?php esc_html_e('Currently overridden by the PS_RXC_PHARMACY_API_KEY constant in wp-config.php.', 'petscript-rx-checkout'); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Test mode', 'petscript-rx-checkout'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ps_rxc_test_mode" value="1" <?php checked($testMode); ?> />
                                <?php esc_html_e('Log the payload instead of sending it to Pharmacy.', 'petscript-rx-checkout'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
