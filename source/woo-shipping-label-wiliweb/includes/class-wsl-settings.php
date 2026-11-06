<?php
defined('ABSPATH') || defined('WSL_TESTING') || exit;

final class WSL_Settings {
    public const OPTION = 'wsl_settings';

    public static function field_keys(): array {
        return [
            'recipient_name', 'recipient_phone', 'recipient_province', 'recipient_city',
            'recipient_address', 'recipient_postcode', 'sender_name', 'sender_phone',
            'sender_province', 'sender_city', 'sender_address', 'sender_postcode',
            'order_number', 'order_date', 'print_date', 'order_status', 'shipping_method',
            'payment_method', 'items', 'order_total', 'customer_note', 'recipient_email',
            'website', 'logo', 'barcode',
        ];
    }

    public static function invoice_field_keys(): array {
        return [
            'logo', 'seller_contact', 'seller_address', 'buyer_phone', 'buyer_email',
            'buyer_address', 'buyer_postcode', 'order_number', 'order_date', 'order_status',
            'payment_method', 'shipping_method', 'product_image', 'sku', 'variation',
            'unit_price', 'quantity', 'line_discount', 'line_total', 'subtotal',
            'discount_total', 'shipping_total', 'tax_total', 'refund_total', 'grand_total',
            'customer_note', 'footer_note', 'signature', 'barcode',
        ];
    }

    public static function defaults(): array {
        $fields = array_fill_keys(self::field_keys(), false);
        $invoice_fields = array_fill_keys(self::invoice_field_keys(), true);
        foreach (array_slice(self::field_keys(), 0, 12) as $key) {
            $fields[$key] = true;
        }
        foreach (['order_number', 'order_date', 'shipping_method', 'payment_method', 'website', 'logo', 'barcode'] as $key) {
            $fields[$key] = true;
        }
        $invoice_fields['customer_note'] = false;
        $invoice_fields['signature'] = false;
        return [
            'modules' => [
                'labels_enabled' => true,
                'invoice_enabled' => true,
            ],
            'template' => 'table',
            'layout' => WSL_Layout::preset(4),
            'sender' => ['name' => '', 'phone' => '', 'province' => '', 'city' => '', 'address' => '', 'postcode' => '', 'website' => '', 'logo_id' => 0],
            'fields' => $fields,
            'accent' => '#155eef',
            'font_size' => 11,
            'show_border' => true,
            'invoice' => [
                'template' => 'modern',
                'title' => 'فاکتور فروش',
                'footer_note' => 'از خرید شما سپاسگزاریم',
                'accent' => '#155eef',
                'logo_id' => 0,
                'fields' => $invoice_fields,
            ],
        ];
    }

    public static function sanitize(array $input): array {
        $defaults = self::defaults();
        $output = $defaults;
        $output['modules']['labels_enabled'] = !empty($input['modules']['labels_enabled']);
        $output['modules']['invoice_enabled'] = !empty($input['modules']['invoice_enabled']);
        $output['template'] = in_array(($input['template'] ?? ''), ['table', 'simple'], true) ? $input['template'] : 'table';
        $layout = WSL_Layout::validate(is_array($input['layout'] ?? null) ? $input['layout'] : []);
        $output['layout'] = $layout instanceof WP_Error ? $defaults['layout'] : $layout;
        $color = sanitize_hex_color($input['accent'] ?? '');
        $output['accent'] = $color ?: $defaults['accent'];
        $output['font_size'] = max(8, min(16, absint($input['font_size'] ?? $defaults['font_size'])));
        $output['show_border'] = !empty($input['show_border']);
        foreach ($defaults['sender'] as $key => $value) {
            if ($key === 'logo_id') {
                $output['sender'][$key] = absint($input['sender'][$key] ?? 0);
            } else {
                $output['sender'][$key] = sanitize_text_field($input['sender'][$key] ?? '');
            }
        }
        foreach (self::field_keys() as $key) {
            $output['fields'][$key] = !empty($input['fields'][$key]);
        }
        $invoice = is_array($input['invoice'] ?? null) ? $input['invoice'] : [];
        $output['invoice']['template'] = in_array(($invoice['template'] ?? ''), ['modern', 'classic'], true) ? $invoice['template'] : $defaults['invoice']['template'];
        $output['invoice']['title'] = sanitize_text_field($invoice['title'] ?? $defaults['invoice']['title']);
        $output['invoice']['footer_note'] = sanitize_text_field($invoice['footer_note'] ?? $defaults['invoice']['footer_note']);
        $invoice_color = sanitize_hex_color($invoice['accent'] ?? '');
        $output['invoice']['accent'] = $invoice_color ?: $defaults['invoice']['accent'];
        $output['invoice']['logo_id'] = absint($invoice['logo_id'] ?? 0);
        foreach (self::invoice_field_keys() as $key) {
            $output['invoice']['fields'][$key] = !empty($invoice['fields'][$key]);
        }
        return $output;
    }

    public static function get(): array {
        $stored = get_option(self::OPTION, null);
        if (!is_array($stored)) {
            return self::defaults();
        }
        return self::sanitize(array_replace_recursive(self::defaults(), $stored));
    }

    public static function module_enabled(array $settings, string $module): bool {
        $keys = [
            'labels' => 'labels_enabled',
            'invoice' => 'invoice_enabled',
        ];
        return isset($keys[$module]) && !empty($settings['modules'][$keys[$module]]);
    }
}
