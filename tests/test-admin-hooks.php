<?php
require_once __DIR__ . '/bootstrap.php';
$GLOBALS['wsl_actions'] = [];
$GLOBALS['wsl_filters'] = [];
if (!function_exists('add_action')) { function add_action($hook, $callback, $priority = 10, $args = 1) { $GLOBALS['wsl_actions'][] = $hook; } }
if (!function_exists('add_filter')) { function add_filter($hook, $callback, $priority = 10, $args = 1) { $GLOBALS['wsl_filters'][] = $hook; } }
if (!class_exists('WC_Order')) { class WC_Order { public function get_id(): int { return 42; } } }
if (!function_exists('current_user_can')) { function current_user_can($cap, ...$args) { return true; } }
if (!function_exists('admin_url')) { function admin_url($path='') { return 'https://example.test/wp-admin/' . $path; } }
if (!class_exists('WSL_Print_Controller')) { class WSL_Print_Controller { public static function create_url(array $ids): string { return 'https://example.test/print'; } } }

require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-admin.php';
WSL_Admin::register();
wsl_assert(in_array('add_meta_boxes', $GLOBALS['wsl_actions'], true), 'order metabox hook');
wsl_assert(in_array('bulk_actions-edit-shop_order', $GLOBALS['wsl_filters'], true), 'legacy bulk action');
wsl_assert(in_array('bulk_actions-woocommerce_page_wc-orders', $GLOBALS['wsl_filters'], true), 'HPOS bulk action');
wsl_assert(in_array('handle_bulk_actions-edit-shop_order', $GLOBALS['wsl_filters'], true), 'legacy bulk handler');
wsl_assert(in_array('handle_bulk_actions-woocommerce_page_wc-orders', $GLOBALS['wsl_filters'], true), 'HPOS bulk handler');
ob_start();
WSL_Admin::metabox(new WC_Order([]));
$metabox = (string) ob_get_clean();
wsl_assert(str_contains($metabox, '<a '), 'single-order action is a link');
wsl_assert(!str_contains($metabox, '<form'), 'single-order action does not nest a form');
