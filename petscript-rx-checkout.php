<?php
/**
 * Plugin Name: PetScript Rx Checkout
 * Description: Requires pet and veterinarian information before checkout on prescription products, and sends the Rx to PetScript Pharmacy.
 * Version: 1.1.0
 * Author: PetScript
 * Text Domain: petscript-rx-checkout
 * Requires Plugins: woocommerce
 * Requires PHP: 8.1
 */

namespace PetScript\RxCheckout;

if (!defined('ABSPATH')) {
    exit;
}

define('PS_RXC_FILE', __FILE__);
define('PS_RXC_DIR', __DIR__);
define('PS_RXC_URL', plugin_dir_url(__FILE__));
define('PS_RXC_VERSION', '1.1.0');

$autoload = PS_RXC_DIR . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
} else {
    require_once PS_RXC_DIR . '/src/autoload.php';
}

register_activation_hook(__FILE__, [Install\Installer::class, 'activate']);

add_action('plugins_loaded', static function (): void {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', static function (): void {
            echo '<div class="notice notice-error"><p>' .
                esc_html__('PetScript Rx Checkout requires WooCommerce to be active.', 'petscript-rx-checkout') .
                '</p></div>';
        });
        return;
    }

    Plugin::instance()->boot();
});
