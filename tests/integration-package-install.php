<?php
declare(strict_types=1);

$root = 'E:/Codex/Wiliweb/woo-crm-v2/tmp/runtime/wordpress/';
$package = dirname(__DIR__) . '/dist/woo-shipping-label-wiliweb-1.1.1-beta.2.zip';

require $root . 'wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

$plugin = 'woo-shipping-label-wiliweb/woo-shipping-label-wiliweb.php';
$wasActive = is_plugin_active($plugin);

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
if (($data['Version'] ?? '') !== '1.1.1-beta.2') {
    fwrite(STDERR, 'PACKAGE_INSTALL=FAIL unexpected version ' . ($data['Version'] ?? '') . "\n");
    exit(1);
}
if ($wasActive && !is_plugin_active($plugin)) {
    fwrite(STDERR, "PACKAGE_INSTALL=FAIL active plugin was deactivated\n");
    exit(1);
}

echo "PACKAGE_INSTALL=PASS overwrite=1 active=" . ($wasActive ? '1' : '0') . " version=1.1.1-beta.2\n";
