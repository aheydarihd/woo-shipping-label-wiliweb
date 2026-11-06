<?php
declare(strict_types=1);

$root = 'E:/Codex/Wiliweb/woo-crm-v2/tmp/runtime/wordpress/';
require $root . 'wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

$plugin = 'woo-shipping-label-wiliweb/woo-shipping-label-wiliweb.php';
$activation = activate_plugin($plugin);
if (is_wp_error($activation)) {
    fwrite(STDERR, 'ACTIVATION_FAIL ' . $activation->get_error_message() . PHP_EOL);
    exit(1);
}

$login = 'wsl_shop_manager_' . strtolower(wp_generate_password(8, false, false));
$user_id = wp_create_user($login, wp_generate_password(24), $login . '@example.test');
if (is_wp_error($user_id)) {
    fwrite(STDERR, 'USER_CREATE_FAIL ' . $user_id->get_error_message() . PHP_EOL);
    exit(1);
}

$order = null;
$subscriber_id = 0;
$previous_settings = get_option(WSL_Settings::OPTION, null);
try {
    (new WP_User($user_id))->set_role('shop_manager');
    wp_set_current_user($user_id);
    WSL_Settings_Page::register(); // CLI bootstrap is not an admin request, so register the admin-only hooks explicitly.
    $settings_capability = apply_filters('option_page_capability_wsl_settings_group', 'manage_options');
    if ($settings_capability !== 'manage_woocommerce' || !current_user_can($settings_capability)) {
        throw new RuntimeException('Shop manager settings submission capability failed.');
    }
    $settings = WSL_Settings::defaults();
    update_option(WSL_Settings::OPTION, $settings);
    $order = wc_create_order();
    $order->save();

    $capabilities = current_user_can('manage_woocommerce')
        && current_user_can('edit_shop_orders')
        && current_user_can('edit_shop_order', $order->get_id());
    ob_start();
    WSL_Admin::metabox($order);
    $metabox = (string) ob_get_clean();
    $single = str_contains($metabox, 'چاپ لیبل') && str_contains($metabox, 'چاپ فاکتور');
    $bulk_actions = WSL_Admin::bulk_action([]);
    $bulk = array_key_exists('wsl_print_labels', $bulk_actions) && array_key_exists('wsl_print_invoices', $bulk_actions);

    if (!$capabilities || !$single || !$bulk) {
        throw new RuntimeException('Shop manager print access failed.');
    }

    $settings['modules']['invoice_enabled'] = false;
    update_option(WSL_Settings::OPTION, $settings);
    ob_start();
    WSL_Admin::metabox($order);
    $disabled_metabox = (string) ob_get_clean();
    $disabled_bulk = WSL_Admin::bulk_action([]);
    if (str_contains($disabled_metabox, 'چاپ فاکتور') || !str_contains($disabled_metabox, 'چاپ لیبل') || isset($disabled_bulk['wsl_print_invoices'])) {
        throw new RuntimeException('Disabled invoice module remains exposed.');
    }

    $subscriber_login = 'wsl_subscriber_' . strtolower(wp_generate_password(8, false, false));
    $subscriber_id = wp_create_user($subscriber_login, wp_generate_password(24), $subscriber_login . '@example.test');
    if (is_wp_error($subscriber_id)) {
        throw new RuntimeException($subscriber_id->get_error_message());
    }
    (new WP_User($subscriber_id))->set_role('subscriber');
    wp_set_current_user($subscriber_id);
    ob_start();
    WSL_Admin::metabox($order);
    $subscriber_metabox = (string) ob_get_clean();
    if ($subscriber_metabox !== '' || current_user_can('manage_woocommerce') || current_user_can('edit_shop_orders')) {
        throw new RuntimeException('Lower role gained print access.');
    }
    echo "SHOP_MANAGER_ACCESS=PASS\n";
} finally {
    if ($order instanceof WC_Order) {
        $order->delete(true);
    }
    if ($subscriber_id && !is_wp_error($subscriber_id)) {
        wp_delete_user((int) $subscriber_id);
    }
    wp_delete_user($user_id);
    if ($previous_settings === null) {
        delete_option(WSL_Settings::OPTION);
    } else {
        update_option(WSL_Settings::OPTION, $previous_settings);
    }
}
