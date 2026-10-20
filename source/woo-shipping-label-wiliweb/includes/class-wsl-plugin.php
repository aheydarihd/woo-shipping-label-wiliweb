<?php
defined('ABSPATH') || exit;

final class WSL_Plugin {
    public static function boot(): void {
        WSL_GitHub_Updater::register();
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [self::class, 'dependency_notice']);
            return;
        }
        WSL_Print_Controller::register();
        if (is_admin()) {
            WSL_Admin::register();
            WSL_Settings_Page::register();
        }
    }

    public static function dependency_notice(): void {
        if (current_user_can('activate_plugins')) {
            echo '<div class="notice notice-error"><p>' . esc_html__('افزونه لیبل پستی WiliWeb برای اجرا به ووکامرس نیاز دارد.', 'woo-shipping-label-wiliweb') . '</p></div>';
        }
    }
}
