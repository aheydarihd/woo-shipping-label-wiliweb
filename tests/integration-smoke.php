<?php
declare(strict_types=1);

$root = 'E:/Codex/Wiliweb/woo-crm-v2/tmp/runtime/wordpress/';
require $root . 'wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugin = 'woo-shipping-label-wiliweb/woo-shipping-label-wiliweb.php';
$result = activate_plugin($plugin);
if (is_wp_error($result)) {
    fwrite(STDERR, $result->get_error_message() . PHP_EOL);
    exit(1);
}
if (!is_plugin_active($plugin)) {
    fwrite(STDERR, "Plugin is inactive\n");
    exit(1);
}

$settings = WSL_Settings::sanitize([
    'template' => 'table',
    'layout' => ['per_page' => 4],
    'sender' => ['name' => 'فروشگاه آزمایشی', 'phone' => '02100000000', 'province' => 'تهران', 'city' => 'تهران', 'address' => 'نشانی تست', 'postcode' => '1111111111'],
    'fields' => array_fill_keys(WSL_Settings::field_keys(), '1'),
]);
update_option(WSL_Settings::OPTION, $settings);
if (WSL_Settings::get()['layout']['per_page'] !== 4) {
    fwrite(STDERR, "Settings round trip failed\n");
    exit(1);
}

$order = wc_create_order();
$order->set_billing_first_name('علی');
$order->set_billing_last_name('رضایی');
$order->set_billing_phone('09121234567');
$order->set_billing_state('THR');
$order->set_billing_city('تهران');
$order->set_billing_address_1('خیابان آزادی');
$order->set_billing_postcode('1234567890');
$order->save();
$data = WSL_Order_Data::from_order($order, $settings);
if ($data['recipient']['name'] !== 'علی رضایی' || !str_contains(WSL_Renderer::label($data, $settings), 'خیابان آزادی')) {
    fwrite(STDERR, "Order rendering failed\n");
    exit(1);
}
$admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ids']);
if (!$admins) {
    fwrite(STDERR, "Administrator fixture missing\n");
    exit(1);
}
wp_set_current_user((int) $admins[0]);
$print_url = WSL_Print_Controller::create_url([$order->get_id()]);
if (str_contains($print_url, '&amp;')) {
    fwrite(STDERR, "Print URL contains HTML entities\n");
    exit(1);
}
parse_str((string) parse_url($print_url, PHP_URL_QUERY), $print_query);
if (($print_query['action'] ?? '') !== 'wsl_render_print' || empty($print_query['payload']) || empty($print_query['signature']) || isset($print_query['token']) || !wp_verify_nonce($print_query['_wsl_nonce'] ?? '', 'wsl_render_' . $print_query['signature']) || WSL_Print_Controller::verify_request($print_query['payload'], $print_query['signature']) !== [$order->get_id()]) {
    fwrite(STDERR, "Print URL query or nonce failed\n");
    exit(1);
}
ob_start();
WSL_Admin::metabox($order);
$metabox = (string) ob_get_clean();
if (!str_contains($metabox, '<a ') || str_contains($metabox, '<form')) {
    fwrite(STDERR, "Single-order print control failed\n");
    exit(1);
}
wp_delete_post($order->get_id(), true);

echo 'ACTIVE WordPress=' . get_bloginfo('version') . ' WooCommerce=' . WC_VERSION . ' storage=' . (get_option('woocommerce_custom_orders_table_enabled') === 'yes' ? 'HPOS' : 'CPT') . ' layout=4 order-render=PASS print-url=PASS single-link=PASS' . PHP_EOL;
