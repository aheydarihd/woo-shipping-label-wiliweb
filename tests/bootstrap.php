<?php
declare(strict_types=1);

define('WSL_TESTING', true);

if (!class_exists('WP_Error')) {
    class WP_Error {
        public function __construct(public string $code = '', public string $message = '') {}
        public function get_error_code(): string { return $this->code; }
    }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($value): string {
        return trim(strip_tags((string) $value));
    }
}
if (!function_exists('sanitize_hex_color')) {
    function sanitize_hex_color($value): ?string {
        return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $value) ? strtolower((string) $value) : null;
    }
}
if (!function_exists('absint')) {
    function absint($value): int { return abs((int) $value); }
}
if (!function_exists('get_option')) {
    function get_option($key, $default = false) {
        return array_key_exists($key, $GLOBALS['wsl_test_options'] ?? [])
            ? $GLOBALS['wsl_test_options'][$key]
            : $default;
    }
}
if (!function_exists('esc_html')) { function esc_html($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('esc_attr')) { function esc_attr($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('esc_url')) { function esc_url($v): string { return (string) $v; } }
if (!function_exists('wp_get_attachment_image_url')) {
    function wp_get_attachment_image_url($id, $size) {
        return $GLOBALS['wsl_test_images'][(int) $id] ?? '';
    }
}
if (!function_exists('__')) { function __($v, $domain = null): string { return (string) $v; } }
if (!function_exists('esc_html__')) { function esc_html__($v, $domain = null): string { return esc_html($v); } }
if (!function_exists('esc_html_e')) { function esc_html_e($v, $domain = null): void { echo esc_html($v); } }

function wsl_assert(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function wsl_assert_same($expected, $actual, string $message): void {
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' expected=' . var_export($expected, true) . ' actual=' . var_export($actual, true));
    }
}
