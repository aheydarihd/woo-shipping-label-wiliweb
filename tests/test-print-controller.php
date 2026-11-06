<?php
require_once __DIR__ . '/bootstrap.php';
if (!defined('MINUTE_IN_SECONDS')) define('MINUTE_IN_SECONDS', 60);
if (!function_exists('wp_generate_password')) { function wp_generate_password($length, $special=false, $extra=false) { return 'FixedTokenABC123'; } }
if (!function_exists('get_current_user_id')) { function get_current_user_id() { return (int) ($GLOBALS['wsl_test_user_id'] ?? 7); } }
if (!function_exists('admin_url')) { function admin_url($path='') { return 'https://example.test/wp-admin/' . $path; } }
if (!function_exists('wp_create_nonce')) { function wp_create_nonce($action) { return 'nonce-' . $action; } }
if (!function_exists('wp_salt')) { function wp_salt($scheme='auth') { return 'unit-test-secret-' . $scheme; } }
if (!function_exists('add_query_arg')) {
    function add_query_arg($args, $value = null, $url = null) {
        if (!is_array($args)) {
            $args = [(string) $args => $value];
        } else {
            $url = $value;
        }
        return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($args);
    }
}
require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-print-controller.php';

wsl_assert_same([12, 13, 14], WSL_Print_Controller::parse_ids('12,13,bad,14,13'), 'valid unique IDs');
wsl_assert_same([7, 8], WSL_Print_Controller::parse_ids(['7', '0', '8']), 'array IDs');
wsl_assert_same([], WSL_Print_Controller::parse_ids('bad'), 'invalid IDs empty');
$url = WSL_Print_Controller::create_url([12, 13]);
wsl_assert(!str_contains($url, '&amp;'), 'redirect URL must not contain HTML entities');
parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
wsl_assert(!empty($query['payload']), 'signed request contains payload');
wsl_assert((bool) preg_match('/^[a-f0-9]{64}$/', $query['signature'] ?? ''), 'signed request contains HMAC signature');
wsl_assert_same('nonce-wsl_render_' . ($query['signature'] ?? ''), $query['_wsl_nonce'] ?? '', 'nonce uses signature');
wsl_assert_same([12, 13], WSL_Print_Controller::verify_request($query['payload'] ?? '', $query['signature'] ?? ''), 'signed request restores IDs without transient storage');
$tampered = substr((string) ($query['payload'] ?? ''), 0, -1) . 'x';
wsl_assert_same([], WSL_Print_Controller::verify_request($tampered, $query['signature'] ?? ''), 'tampered payload rejected');
$expired = WSL_Print_Controller::create_request([12], time() - 1);
wsl_assert_same([], WSL_Print_Controller::verify_request($expired['payload'], $expired['signature']), 'expired payload rejected');
wsl_assert_same('1aZWhH3zrABC', WSL_Print_Controller::sanitize_token('1aZWhH3zrABC'), 'received token preserves case');
wsl_assert_same('AbC123', WSL_Print_Controller::sanitize_token('AbC-123!@#'), 'received token rejects punctuation without changing case');

$default_request = WSL_Print_Controller::verify_document_request($query['payload'] ?? '', $query['signature'] ?? '');
wsl_assert_same('label', $default_request['document'] ?? '', 'default URL is a label request');
wsl_assert_same([12, 13], $default_request['ids'] ?? [], 'default document request restores IDs');

$invoice_url = WSL_Print_Controller::create_url([12], 'invoice');
parse_str((string) parse_url($invoice_url, PHP_URL_QUERY), $invoice_query);
$invoice_request = WSL_Print_Controller::verify_document_request($invoice_query['payload'] ?? '', $invoice_query['signature'] ?? '');
wsl_assert_same('invoice', $invoice_request['document'] ?? '', 'invoice URL stores document type');
wsl_assert_same([12], $invoice_request['ids'] ?? [], 'invoice request restores IDs');
wsl_assert(!str_contains($invoice_url, '&amp;'), 'invoice URL must not contain HTML entities');

$encode_request = static function (array $data): array {
    $payload = rtrim(strtr(base64_encode((string) json_encode($data)), '+/', '-_'), '=');
    return [$payload, hash_hmac('sha256', $payload, wp_salt('nonce'))];
};
[$old_payload, $old_signature] = $encode_request(['ids' => [44], 'uid' => 7, 'exp' => time() + 60]);
$old_request = WSL_Print_Controller::verify_document_request($old_payload, $old_signature);
wsl_assert_same(['ids' => [44], 'document' => 'label'], $old_request, 'old payload defaults to label');

[$unknown_payload, $unknown_signature] = $encode_request(['ids' => [12], 'uid' => 7, 'exp' => time() + 60, 'document' => 'template.php']);
wsl_assert_same([], WSL_Print_Controller::verify_document_request($unknown_payload, $unknown_signature), 'unknown document rejected');
[$empty_payload, $empty_signature] = $encode_request(['ids' => [], 'uid' => 7, 'exp' => time() + 60, 'document' => 'invoice']);
wsl_assert_same([], WSL_Print_Controller::verify_document_request($empty_payload, $empty_signature), 'empty document request rejected');

$GLOBALS['wsl_test_user_id'] = 8;
wsl_assert_same([], WSL_Print_Controller::verify_document_request($invoice_query['payload'] ?? '', $invoice_query['signature'] ?? ''), 'wrong user rejected');
$GLOBALS['wsl_test_user_id'] = 7;

wsl_assert_same('', WSL_Print_Controller::create_url([12], 'template.php'), 'unknown document URL rejected');
