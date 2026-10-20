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

    public static function defaults(): array {
        $fields = array_fill_keys(self::field_keys(), false);
        foreach (array_slice(self::field_keys(), 0, 12) as $key) {
            $fields[$key] = true;
        }
        foreach (['order_number', 'order_date', 'shipping_method', 'payment_method', 'website', 'logo', 'barcode'] as $key) {
            $fields[$key] = true;
        }
        return [
            'template' => 'table',
            'layout' => WSL_Layout::preset(4),
            'sender' => ['name' => '', 'phone' => '', 'province' => '', 'city' => '', 'address' => '', 'postcode' => '', 'website' => '', 'logo_id' => 0],
            'fields' => $fields,
            'accent' => '#155eef',
            'font_size' => 11,
            'show_border' => true,
        ];
    }

    public static function sanitize(array $input): array {
        $defaults = self::defaults();
        $output = $defaults;
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
        return $output;
    }

    public static function get(): array {
        $stored = get_option(self::OPTION, null);
        if (!is_array($stored)) {
            return self::defaults();
        }
        return self::sanitize($stored);
    }
}
