<?php
declare(strict_types=1);

$root = 'E:/Codex/Wiliweb/woo-crm-v2/tmp/runtime/wordpress/';
$package = dirname(__DIR__) . '/dist/woo-shipping-label-wiliweb-1.2.0.zip';

require $root . 'wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

$plugin = 'woo-shipping-label-wiliweb/woo-shipping-label-wiliweb.php';
$wasActive = is_plugin_active($plugin);
$previousSettings = get_option('wsl_settings', null);
$siteSettings = [
    'modules' => ['labels_enabled' => true, 'invoice_enabled' => false],
    'template' => 'simple',
    'layout' => ['paper' => 'A4', 'per_page' => 2, 'orientation' => 'landscape', 'columns' => 1, 'rows' => 2, 'margin_top' => 6.5, 'margin_right' => 5.5, 'margin_bottom' => 6.5, 'margin_left' => 5.5, 'gap_horizontal' => 2.5, 'gap_vertical' => 4.5, 'label_width' => 199.0, 'label_height' => 137.5],
    'sender' => ['name' => 'تنظیمات اختصاصی سایت', 'phone' => '02112345678', 'province' => 'تهران', 'city' => 'تهران', 'address' => 'نشانی محفوظ', 'postcode' => '1111111111', 'website' => 'site.example', 'logo_id' => 321],
    'fields' => ['recipient_name' => true, 'recipient_phone' => false, 'sender_name' => true, 'order_number' => true, 'print_date' => true, 'barcode' => false],
    'accent' => '#123456',
    'font_size' => 14,
    'show_border' => false,
    'invoice' => ['template' => 'classic', 'title' => 'فاکتور سایت', 'footer_note' => 'پیام سایت', 'accent' => '#654321', 'logo_id' => 987, 'fields' => ['product_image' => false, 'customer_note' => true]],
];
update_option('wsl_settings', $siteSettings);

if (!WP_Filesystem()) {
    fwrite(STDERR, "PACKAGE_INSTALL=FAIL filesystem unavailable\n");
    exit(1);
}

$upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
$result = $upgrader->install($package, ['overwrite_package' => true]);
if (is_wp_error($result)) {
    fwrite(STDERR, 'PACKAGE_INSTALL=FAIL ' . $result->get_error_code() . ': ' . $result->get_error_message() . "\n");
    exit(1);
}
if ($result !== true || !is_file(WP_PLUGIN_DIR . '/' . $plugin)) {
    $skinError = $upgrader->skin->get_errors();
    $message = is_wp_error($skinError) && $skinError->has_errors() ? $skinError->get_error_message() : 'unknown installer result';
    fwrite(STDERR, "PACKAGE_INSTALL=FAIL {$message}\n");
    exit(1);
}

$data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin, false, false);
if (($data['Version'] ?? '') !== '1.2.0') {
    fwrite(STDERR, 'PACKAGE_INSTALL=FAIL unexpected version ' . ($data['Version'] ?? '') . "\n");
    exit(1);
}
if ($wasActive && !is_plugin_active($plugin)) {
    fwrite(STDERR, "PACKAGE_INSTALL=FAIL active plugin was deactivated\n");
    exit(1);
}
if (get_option('wsl_settings', null) !== $siteSettings) {
    fwrite(STDERR, "PACKAGE_INSTALL=FAIL saved site settings changed during replacement\n");
    exit(1);
}

if ($previousSettings === null) {
    delete_option('wsl_settings');
} else {
    update_option('wsl_settings', $previousSettings);
}

echo "PACKAGE_INSTALL=PASS overwrite=1 active=" . ($wasActive ? '1' : '0') . " settings=preserved version=1.2.0\n";
