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
try {
    (new WP_User($user_id))->set_role('shop_manager');
    wp_set_current_user($user_id);
    $order = wc_create_order();
    $order->save();

    $capabilities = current_user_can('manage_woocommerce')
        && current_user_can('edit_shop_orders')
        && current_user_can('edit_shop_order', $order->get_id());
    ob_start();
    WSL_Admin::metabox($order);
    $metabox = (string) ob_get_clean();
    $single = str_contains($metabox, 'پیش‌نمایش و چاپ لیبل');
    $bulk = array_key_exists('wsl_print_labels', WSL_Admin::bulk_action([]));

    if (!$capabilities || !$single || !$bulk) {
        throw new RuntimeException('Shop manager print access failed.');
    }
    echo "SHOP_MANAGER_ACCESS=PASS\n";
} finally {
    if ($order instanceof WC_Order) {
        $order->delete(true);
    }
    wp_delete_user($user_id);
}
