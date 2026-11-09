<?php
/**
 * Plugin Name: فاکتور ساز ویلی وب
 * Plugin URI: https://wiliweb.com
 * Description: صدور و چاپ مدیریتی فاکتور فروش و لیبل پستی سفارش‌های ووکامرس با قالب فارسی و تنظیمات مستقل.
 * Version: 1.1.1-beta.1
 * Author: WiliWeb
 * Author URI: https://wiliweb.com
 * Update URI: https://github.com/aheydarihd/woo-shipping-label-wiliweb
 * Text Domain: woo-shipping-label-wiliweb
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * WC requires at least: 9.0
 * WC tested up to: 11.1
 */

defined('ABSPATH') || exit;

define('WSL_VERSION', '1.1.1-beta.1');
define('WSL_FILE', __FILE__);
define('WSL_PATH', plugin_dir_path(__FILE__));
define('WSL_URL', plugin_dir_url(__FILE__));

require_once WSL_PATH . 'includes/class-wsl-layout.php';
require_once WSL_PATH . 'includes/class-wsl-settings.php';
require_once WSL_PATH . 'includes/class-wsl-order-data.php';
require_once WSL_PATH . 'includes/class-wsl-renderer.php';
require_once WSL_PATH . 'includes/class-wsl-invoice-renderer.php';
require_once WSL_PATH . 'includes/class-wsl-print-controller.php';
require_once WSL_PATH . 'includes/class-wsl-admin.php';
require_once WSL_PATH . 'includes/class-wsl-settings-page.php';
require_once WSL_PATH . 'includes/class-wsl-github-updater.php';
require_once WSL_PATH . 'includes/class-wsl-plugin.php';

add_action('before_woocommerce_init', static function (): void {
    if (class_exists(Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});
add_action('plugins_loaded', [WSL_Plugin::class, 'boot']);
