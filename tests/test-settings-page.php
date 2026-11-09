<?php
require_once __DIR__ . '/bootstrap.php';

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
if (!defined('WSL_VERSION')) define('WSL_VERSION', 'test');
if (!defined('WSL_URL')) define('WSL_URL', '/plugin/');
if (!function_exists('add_action')) { function add_action($hook, $callback, $priority = 10, $args = 1) {} }
if (!function_exists('add_filter')) { function add_filter($hook, $callback, $priority = 10, $args = 1) { $GLOBALS['wsl_filters'][] = $hook; } }
if (!function_exists('current_user_can')) { function current_user_can($capability, ...$args) { return true; } }
if (!function_exists('add_submenu_page')) {
    function add_submenu_page(...$args) { $GLOBALS['wsl_submenu_args'] = $args; return 'woocommerce_page_wsl-settings'; }
}
if (!function_exists('register_setting')) { function register_setting(...$args) { $GLOBALS['wsl_registered_setting'] = $args; } }
if (!function_exists('settings_fields')) { function settings_fields($group): void { echo '<input type="hidden" data-settings-group="' . esc_attr($group) . '">'; } }
if (!function_exists('submit_button')) { function submit_button($text): void { echo '<button type="submit">' . esc_html($text) . '</button>'; } }
if (!function_exists('checked')) { function checked($checked, $current = true): void { if ((string) $checked === (string) $current) echo ' checked'; } }
if (!function_exists('selected')) { function selected($selected, $current = true): void { if ((string) $selected === (string) $current) echo ' selected'; } }
if (!function_exists('wp_unslash')) { function wp_unslash($value) { return $value; } }

require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-layout.php';
require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-settings.php';
require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-settings-page.php';

WSL_Settings_Page::register();
wsl_assert(in_array('option_page_capability_wsl_settings_group', $GLOBALS['wsl_filters'] ?? [], true), 'settings save capability filter registered');
wsl_assert_same('manage_woocommerce', WSL_Settings_Page::settings_capability(), 'shop manager can submit settings');
WSL_Settings_Page::menu();
wsl_assert_same('woocommerce', $GLOBALS['wsl_submenu_args'][0] ?? '', 'settings under WooCommerce');
wsl_assert_same('فاکتور ساز ویلی وب', $GLOBALS['wsl_submenu_args'][1] ?? '', 'settings page title');
wsl_assert_same('فاکتور ساز ویلی وب', $GLOBALS['wsl_submenu_args'][2] ?? '', 'settings menu title');
wsl_assert_same('manage_woocommerce', $GLOBALS['wsl_submenu_args'][3] ?? '', 'settings capability');
wsl_assert_same('general', WSL_Settings_Page::current_tab('bad-tab'), 'invalid tab falls back to general');
foreach (['general', 'invoice', 'labels'] as $tab) {
    wsl_assert_same($tab, WSL_Settings_Page::current_tab($tab), 'allowed tab: ' . $tab);
}

$_GET['tab'] = 'invoice';
ob_start();
WSL_Settings_Page::page();
$html = (string) ob_get_clean();
$option = WSL_Settings::OPTION;
wsl_assert(str_contains($html, 'data-wsl-panel="invoice"'), 'invoice panel rendered');
wsl_assert(str_contains($html, 'value="modern"'), 'modern invoice template');
wsl_assert(str_contains($html, 'value="classic"'), 'classic invoice template');
wsl_assert(str_contains($html, 'value="table"'), 'table label template retained');
wsl_assert(str_contains($html, 'value="simple"'), 'simple label template retained');
wsl_assert(str_contains($html, 'value="postal"'), 'postal label template control');
wsl_assert(str_contains($html, 'data-wsl-label-template'), 'label template controls expose JS hook');
wsl_assert(str_contains($html, 'name="' . $option . '[modules][labels_enabled]"'), 'labels module switch');
wsl_assert(str_contains($html, 'name="' . $option . '[modules][invoice_enabled]"'), 'invoice module switch');
foreach (WSL_Settings::invoice_field_keys() as $key) {
    wsl_assert(str_contains($html, 'name="' . $option . '[invoice][fields][' . $key . ']"'), 'invoice field control: ' . $key);
}
wsl_assert(str_contains($html, 'name="' . $option . '[invoice][fields][fee_total]"'), 'fee visibility control');
foreach (['template', 'accent', 'font_size', 'show_border'] as $key) {
    wsl_assert(str_contains($html, 'name="' . $option . '[' . $key . ']'), 'legacy label option retained: ' . $key);
}
foreach (['per_page', 'orientation', 'label_width', 'label_height', 'margin_top', 'margin_right', 'margin_bottom', 'margin_left', 'gap_horizontal', 'gap_vertical'] as $key) {
    wsl_assert(str_contains($html, 'name="' . $option . '[layout][' . $key . ']"'), 'label layout control retained: ' . $key);
}
foreach (['name', 'phone', 'province', 'city', 'address', 'postcode', 'website', 'logo_id'] as $key) {
    wsl_assert(str_contains($html, 'name="' . $option . '[sender][' . $key . ']"'), 'sender control retained: ' . $key);
}
foreach (WSL_Settings::field_keys() as $key) {
    wsl_assert(str_contains($html, 'name="' . $option . '[fields][' . $key . ']"'), 'label field control retained: ' . $key);
}
unset($_GET['tab']);
