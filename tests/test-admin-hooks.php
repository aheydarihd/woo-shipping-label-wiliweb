<?php
require_once __DIR__ . '/bootstrap.php';
$GLOBALS['wsl_actions'] = [];
$GLOBALS['wsl_filters'] = [];
if (!function_exists('add_action')) { function add_action($hook, $callback, $priority = 10, $args = 1) { $GLOBALS['wsl_actions'][] = $hook; } }
if (!function_exists('add_filter')) { function add_filter($hook, $callback, $priority = 10, $args = 1) { $GLOBALS['wsl_filters'][] = $hook; } }
if (!class_exists('WC_Order')) { class WC_Order { public function get_id(): int { return 42; } } }
if (!function_exists('current_user_can')) { function current_user_can($cap, ...$args) { return true; } }
if (!function_exists('admin_url')) { function admin_url($path='') { return 'https://example.test/wp-admin/' . $path; } }
if (!function_exists('add_query_arg')) { function add_query_arg($key, $value, $url) { return $url . '?' . rawurlencode((string) $key) . '=' . rawurlencode((string) $value); } }
if (!class_exists('WSL_Print_Controller')) {
    class WSL_Print_Controller {
        public static function create_url(array $ids, string $document = 'label'): string {
            $GLOBALS['wsl_admin_print_calls'][] = ['ids' => $ids, 'document' => $document];
            return 'https://example.test/print?document=' . $document;
        }
        public static function create_bulk_url(array $ids, string $document = 'label'): string {
            $GLOBALS['wsl_admin_bulk_print_calls'][] = ['ids' => $ids, 'document' => $document];
            return 'https://example.test/wp-admin/admin.php?wsl_bulk_print=1&document=' . $document;
        }
    }
}

require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-layout.php';
require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-settings.php';
require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-admin.php';
$document_from_url = static function (string $url): string {
    parse_str((string) parse_url(html_entity_decode($url), PHP_URL_QUERY), $query);
    if (isset($query['document'])) {
        return (string) $query['document'];
    }
    if (!empty($query['payload']) && !empty($query['signature']) && method_exists(WSL_Print_Controller::class, 'verify_document_request')) {
        $request = WSL_Print_Controller::verify_document_request($query['payload'], $query['signature']);
        return (string) ($request['document'] ?? '');
    }
    return '';
};
$settings = WSL_Settings::defaults();
$GLOBALS['wsl_test_options'][WSL_Settings::OPTION] = $settings;
WSL_Admin::register();
wsl_assert(in_array('add_meta_boxes', $GLOBALS['wsl_actions'], true), 'order metabox hook');
wsl_assert(in_array('bulk_actions-edit-shop_order', $GLOBALS['wsl_filters'], true), 'legacy bulk action');
wsl_assert(in_array('bulk_actions-woocommerce_page_wc-orders', $GLOBALS['wsl_filters'], true), 'HPOS bulk action');
wsl_assert(in_array('handle_bulk_actions-edit-shop_order', $GLOBALS['wsl_filters'], true), 'legacy bulk handler');
wsl_assert(in_array('handle_bulk_actions-woocommerce_page_wc-orders', $GLOBALS['wsl_filters'], true), 'HPOS bulk handler');
ob_start();
WSL_Admin::metabox(new WC_Order(['id' => 42]));
$metabox = (string) ob_get_clean();
wsl_assert(str_contains($metabox, '<a '), 'single-order action is a link');
wsl_assert(!str_contains($metabox, '<form'), 'single-order action does not nest a form');
wsl_assert(str_contains($metabox, 'چاپ لیبل'), 'label action shown');
wsl_assert(str_contains($metabox, 'چاپ فاکتور'), 'invoice action shown');
preg_match_all('/href="([^"]+)"/', $metabox, $links);
$single_documents = array_map($document_from_url, $links[1] ?? []);
wsl_assert(in_array('label', $single_documents, true), 'single label URL uses label document');
wsl_assert(in_array('invoice', $single_documents, true), 'single invoice URL uses invoice document');
$bulk = WSL_Admin::bulk_action([]);
wsl_assert_same('چاپ لیبل پستی', $bulk['wsl_print_labels'] ?? '', 'label bulk action');
wsl_assert_same('چاپ فاکتور فروش', $bulk['wsl_print_invoices'] ?? '', 'invoice bulk action');

$settings['modules']['invoice_enabled'] = false;
$GLOBALS['wsl_test_options'][WSL_Settings::OPTION] = $settings;
ob_start();
WSL_Admin::metabox(new WC_Order(['id' => 42]));
$label_only = (string) ob_get_clean();
wsl_assert(str_contains($label_only, 'چاپ لیبل'), 'label remains when invoice disabled');
wsl_assert(!str_contains($label_only, 'چاپ فاکتور'), 'disabled invoice hidden');
$label_bulk = WSL_Admin::bulk_action([]);
wsl_assert(isset($label_bulk['wsl_print_labels']), 'label bulk remains enabled');
wsl_assert(!isset($label_bulk['wsl_print_invoices']), 'disabled invoice bulk hidden');
$before_disabled = count($GLOBALS['wsl_admin_print_calls'] ?? []);
$disabled_redirect = WSL_Admin::handle_bulk('https://example.test/orders', 'wsl_print_invoices', [42]);
wsl_assert(str_contains($disabled_redirect, 'wsl_error=disabled'), 'disabled invoice bulk rejected');
wsl_assert_same($before_disabled, count($GLOBALS['wsl_admin_print_calls'] ?? []), 'disabled invoice creates no print URL');

$settings['modules']['invoice_enabled'] = true;
$settings['modules']['labels_enabled'] = false;
$GLOBALS['wsl_test_options'][WSL_Settings::OPTION] = $settings;
ob_start();
WSL_Admin::metabox(new WC_Order(['id' => 42]));
$invoice_only = (string) ob_get_clean();
wsl_assert(!str_contains($invoice_only, 'چاپ لیبل'), 'disabled label hidden');
wsl_assert(str_contains($invoice_only, 'چاپ فاکتور'), 'invoice remains when label disabled');
$invoice_redirect = WSL_Admin::handle_bulk('https://example.test/orders', 'wsl_print_invoices', [42, 43]);
wsl_assert_same('invoice', $document_from_url($invoice_redirect), 'bulk invoice document forwarded');
wsl_assert(str_contains($invoice_redirect, 'wsl_bulk_print=1'), 'bulk redirect uses action-safe endpoint marker');
wsl_assert_same('https://example.test/orders', WSL_Admin::handle_bulk('https://example.test/orders', 'unsupported', [42]), 'unsupported bulk action ignored');
unset($GLOBALS['wsl_test_options'][WSL_Settings::OPTION]);
