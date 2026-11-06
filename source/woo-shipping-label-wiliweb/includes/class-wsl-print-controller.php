<?php
defined('ABSPATH') || defined('WSL_TESTING') || exit;

final class WSL_Print_Controller {
    private const TOKEN_PREFIX = 'wsl_print_';
    private const REQUEST_TTL = 600;

    public static function register(): void {
        add_action('admin_post_wsl_prepare_print', [self::class, 'prepare']);
        add_action('admin_post_wsl_render_print', [self::class, 'render']);
    }

    public static function parse_ids($raw): array {
        $values = is_array($raw) ? $raw : explode(',', (string) $raw);
        $ids = [];
        foreach ($values as $value) {
            if (is_scalar($value) && ctype_digit((string) $value) && (int) $value > 0) {
                $ids[] = (int) $value;
            }
        }
        return array_values(array_unique($ids));
    }

    public static function create_url(array $ids, string $document = 'label'): string {
        $ids = self::parse_ids($ids);
        if (!$ids || !in_array($document, ['label', 'invoice'], true)) {
            return '';
        }
        $request = self::create_request($ids, null, $document);
        return add_query_arg(
            [
                'action'     => 'wsl_render_print',
                'payload'    => $request['payload'],
                'signature'  => $request['signature'],
                '_wsl_nonce' => wp_create_nonce('wsl_render_' . $request['signature']),
            ],
            admin_url('admin-post.php')
        );
    }

    public static function create_request(array $ids, ?int $expires = null, string $document = 'label'): array {
        $data = [
            'ids' => self::parse_ids($ids),
            'uid' => get_current_user_id(),
            'exp' => $expires ?? (time() + self::REQUEST_TTL),
            'document' => $document,
        ];
        $payload = rtrim(strtr(base64_encode((string) json_encode($data)), '+/', '-_'), '=');
        return [
            'payload' => $payload,
            'signature' => hash_hmac('sha256', $payload, wp_salt('nonce')),
        ];
    }

    public static function verify_document_request($payload, $signature): array {
        $payload = is_scalar($payload) ? (string) $payload : '';
        $signature = is_scalar($signature) ? strtolower((string) $signature) : '';
        if ($payload === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $payload) || !preg_match('/^[a-f0-9]{64}$/', $signature)) {
            return [];
        }
        $expected = hash_hmac('sha256', $payload, wp_salt('nonce'));
        if (!hash_equals($expected, $signature)) {
            return [];
        }
        $encoded = strtr($payload, '-_', '+/');
        $padding = strlen($encoded) % 4;
        if ($padding) {
            $encoded .= str_repeat('=', 4 - $padding);
        }
        $decoded = base64_decode($encoded, true);
        $data = is_string($decoded) ? json_decode($decoded, true) : null;
        if (!is_array($data) || (int) ($data['uid'] ?? 0) !== get_current_user_id() || (int) ($data['exp'] ?? 0) < time()) {
            return [];
        }
        $ids = self::parse_ids($data['ids'] ?? []);
        $document = isset($data['document']) ? (string) $data['document'] : 'label';
        if (!$ids || !in_array($document, ['label', 'invoice'], true)) {
            return [];
        }
        return ['ids' => $ids, 'document' => $document];
    }

    public static function verify_request($payload, $signature): array {
        $request = self::verify_document_request($payload, $signature);
        return $request['ids'] ?? [];
    }

    public static function sanitize_token($raw): string {
        return preg_replace('/[^A-Za-z0-9]/', '', (string) $raw) ?: '';
    }

    public static function prepare(): void {
        self::authorize('wsl_prepare_print');
        $ids = self::parse_ids(wp_unslash($_POST['order_ids'] ?? ''));
        if (!$ids) {
            wp_die(esc_html__('هیچ سفارش معتبری برای چاپ انتخاب نشده است.', 'woo-shipping-label-wiliweb'), '', ['response' => 400]);
        }
        wp_safe_redirect(self::create_url($ids));
        exit;
    }

    public static function render(): void {
        if (!current_user_can('edit_shop_orders') && !current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('شما اجازه چاپ اسناد سفارش‌ها را ندارید.', 'woo-shipping-label-wiliweb'), '', ['response' => 403]);
        }
        $payload = wp_unslash($_GET['payload'] ?? '');
        $signature = strtolower(preg_replace('/[^a-fA-F0-9]/', '', (string) wp_unslash($_GET['signature'] ?? '')) ?: '');
        if ($payload !== '' || $signature !== '') {
            check_admin_referer('wsl_render_' . $signature, '_wsl_nonce');
            $request = self::verify_document_request($payload, $signature);
            $ids = $request['ids'] ?? [];
            $document = $request['document'] ?? '';
        } else {
            $token = self::sanitize_token(wp_unslash($_GET['token'] ?? ''));
            check_admin_referer('wsl_render_' . $token, '_wsl_nonce');
            $key = self::TOKEN_PREFIX . get_current_user_id() . '_' . $token;
            $ids = get_transient($key);
            $document = 'label';
        }
        if (!is_array($ids) || !$ids) {
            wp_die(esc_html__('درخواست چاپ منقضی یا نامعتبر است.', 'woo-shipping-label-wiliweb'), '', ['response' => 400]);
        }
        $settings = WSL_Settings::get();
        $module = $document === 'invoice' ? 'invoice' : 'labels';
        if (!WSL_Settings::module_enabled($settings, $module)) {
            wp_die(esc_html__('این بخش چاپ در تنظیمات افزونه غیرفعال است.', 'woo-shipping-label-wiliweb'), '', ['response' => 403]);
        }
        if ($document === 'invoice' && !class_exists('WSL_Invoice_Renderer')) {
            wp_die(esc_html__('موتور چاپ فاکتور در دسترس نیست.', 'woo-shipping-label-wiliweb'), '', ['response' => 503]);
        }
        $entries = [];
        $skipped = 0;
        foreach (self::parse_ids($ids) as $id) {
            $order = wc_get_order($id);
            if (!$order || !current_user_can('edit_shop_order', $id)) {
                $skipped++;
                continue;
            }
            $entries[] = $document === 'invoice'
                ? WSL_Order_Data::invoice_from_order($order, $settings)
                : WSL_Order_Data::from_order($order, $settings);
        }
        nocache_headers();
        header('X-Robots-Tag: noindex, nofollow', true);
        if ($document === 'invoice') {
            WSL_Invoice_Renderer::page($entries, $settings, $skipped);
        } else {
            WSL_Renderer::page($entries, $settings, $skipped);
        }
        exit;
    }

    private static function authorize(string $action): void {
        if (!current_user_can('edit_shop_orders') && !current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('دسترسی غیرمجاز است.', 'woo-shipping-label-wiliweb'), '', ['response' => 403]);
        }
        check_admin_referer($action, '_wsl_nonce');
    }
}
