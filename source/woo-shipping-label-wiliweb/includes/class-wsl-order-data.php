<?php
defined('ABSPATH') || defined('WSL_TESTING') || exit;

final class WSL_Order_Data {
    public static function from_order(WC_Order $order, array $settings): array {
        $shipping_name = trim($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name());
        $billing_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        $recipient = [
            'name' => self::fallback($shipping_name, $billing_name),
            'phone' => self::digits($order->get_billing_phone()),
            'province' => self::state_name(self::fallback($order->get_shipping_state(), $order->get_billing_state())),
            'city' => self::fallback($order->get_shipping_city(), $order->get_billing_city()),
            'address' => self::address(
                self::fallback($order->get_shipping_address_1(), $order->get_billing_address_1()),
                self::fallback($order->get_shipping_address_2(), $order->get_billing_address_2())
            ),
            'postcode' => self::digits(self::fallback($order->get_shipping_postcode(), $order->get_billing_postcode())),
        ];
        $sender_defaults = ['name' => '', 'phone' => '', 'province' => '', 'city' => '', 'address' => '', 'postcode' => '', 'website' => '', 'logo_id' => 0];
        $sender = array_merge($sender_defaults, is_array($settings['sender'] ?? null) ? $settings['sender'] : []);
        $sender['phone'] = self::digits($sender['phone']);
        $sender['postcode'] = self::digits($sender['postcode']);
        $items = [];
        foreach ($order->get_items() as $item) {
            $items[] = wp_strip_all_tags($item->get_name()) . ' × ' . (int) $item->get_quantity();
        }
        $date = $order->get_date_created();
        $optional = [
            'order_number' => (string) $order->get_order_number(),
            'order_date' => $date && method_exists($date, 'getTimestamp') ? wp_date('Y/m/d', $date->getTimestamp()) : '',
            'print_date' => wp_date('Y/m/d H:i'),
            'order_status' => wc_get_order_status_name($order->get_status()),
            'shipping_method' => $order->get_shipping_method(),
            'payment_method' => $order->get_payment_method_title(),
            'items' => $items,
            'order_total' => $order->get_total() !== '' ? wp_strip_all_tags(wc_price($order->get_total())) : '',
            'customer_note' => $order->get_customer_note(),
            'recipient_email' => $order->get_billing_email(),
            'website' => (string) $sender['website'],
            'logo_id' => (int) $sender['logo_id'],
            'barcode' => (string) $order->get_order_number(),
        ];
        return ['order_id' => $order->get_id(), 'recipient' => $recipient, 'sender' => $sender, 'optional' => $optional];
    }

    public static function invoice_from_order(WC_Order $order, array $settings): array {
        $shipping_name = trim($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name());
        $billing_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        $currency = (string) $order->get_currency();
        $sender_defaults = ['name' => '', 'phone' => '', 'province' => '', 'city' => '', 'address' => '', 'postcode' => '', 'website' => '', 'logo_id' => 0];
        $sender = array_merge($sender_defaults, is_array($settings['sender'] ?? null) ? $settings['sender'] : []);
        $invoice_settings = is_array($settings['invoice'] ?? null) ? $settings['invoice'] : [];
        $date = $order->get_date_created();

        $items = [];
        foreach ($order->get_items() as $item) {
            $quantity = (int) $item->get_quantity();
            $subtotal = (float) $item->get_subtotal();
            $total = (float) $item->get_total();
            $product = $item->get_product();
            $image_id = 0;
            $sku = '';
            if ($product) {
                $image_id = (int) $product->get_image_id();
                $sku = sanitize_text_field((string) $product->get_sku());
                if (!$image_id && method_exists($product, 'get_parent_id')) {
                    $parent_id = (int) $product->get_parent_id();
                    $parent = $parent_id && function_exists('wc_get_product') ? wc_get_product($parent_id) : false;
                    if ($parent) {
                        $image_id = (int) $parent->get_image_id();
                    }
                }
            }
            $variation = [];
            if (method_exists($item, 'get_formatted_meta_data')) {
                foreach ($item->get_formatted_meta_data('', true) as $meta) {
                    $key = (string) ($meta->key ?? '');
                    if ($key === '' || str_starts_with($key, '_')) {
                        continue;
                    }
                    $display_key = wp_strip_all_tags((string) ($meta->display_key ?? $key));
                    $display_value = wp_strip_all_tags((string) ($meta->display_value ?? ''));
                    if ($display_value !== '') {
                        $variation[] = $display_key !== '' ? $display_key . ': ' . $display_value : $display_value;
                    }
                }
            }
            $items[] = [
                'name' => wp_strip_all_tags((string) $item->get_name()),
                'image_url' => $image_id ? (string) (wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail') ?: '') : '',
                'sku' => $sku,
                'variation' => implode('، ', $variation),
                'quantity' => $quantity,
                'unit_price' => self::money($quantity > 0 ? $subtotal / $quantity : 0, $currency),
                'subtotal' => self::money($subtotal, $currency),
                'discount' => self::money(max(0, $subtotal - $total), $currency),
                'total' => self::money($total, $currency),
                'tax' => self::money((float) $item->get_total_tax(), $currency),
            ];
        }

        $fees = [];
        foreach ($order->get_items('fee') as $fee) {
            $fees[] = [
                'name' => wp_strip_all_tags((string) $fee->get_name()),
                'total' => self::money((float) $fee->get_total(), $currency),
                'tax' => self::money((float) $fee->get_total_tax(), $currency),
            ];
        }

        $seller_address = implode('، ', array_filter([
            trim((string) $sender['province']),
            trim((string) $sender['city']),
            trim((string) $sender['address']),
        ]));

        return [
            'order' => [
                'id' => $order->get_id(),
                'number' => (string) $order->get_order_number(),
                'date' => $date && method_exists($date, 'getTimestamp') ? wp_date('Y/m/d', $date->getTimestamp()) : '',
                'status' => wc_get_order_status_name($order->get_status()),
                'payment_method' => wp_strip_all_tags((string) $order->get_payment_method_title()),
                'shipping_method' => wp_strip_all_tags((string) $order->get_shipping_method()),
                'barcode' => (string) $order->get_order_number(),
            ],
            'seller' => [
                'name' => sanitize_text_field($sender['name']),
                'phone' => self::digits($sender['phone']),
                'address' => sanitize_text_field($seller_address),
                'postcode' => self::digits($sender['postcode']),
                'website' => sanitize_text_field($sender['website']),
                'logo_id' => absint($invoice_settings['logo_id'] ?? 0) ?: absint($sender['logo_id']),
            ],
            'buyer' => [
                'name' => self::fallback($billing_name, $shipping_name),
                'phone' => self::digits($order->get_billing_phone()),
                'email' => sanitize_text_field($order->get_billing_email()),
                'province' => self::state_name(self::fallback($order->get_billing_state(), $order->get_shipping_state())),
                'city' => self::fallback($order->get_billing_city(), $order->get_shipping_city()),
                'address' => self::address(
                    self::fallback($order->get_billing_address_1(), $order->get_shipping_address_1()),
                    self::fallback($order->get_billing_address_2(), $order->get_shipping_address_2())
                ),
                'postcode' => self::digits(self::fallback($order->get_billing_postcode(), $order->get_shipping_postcode())),
            ],
            'items' => $items,
            'totals' => [
                'subtotal' => self::money((float) $order->get_subtotal(), $currency),
                'discount' => self::money((float) $order->get_discount_total(), $currency),
                'shipping' => self::money((float) $order->get_shipping_total(), $currency),
                'fees' => $fees,
                'tax' => self::money((float) $order->get_total_tax(), $currency),
                'refund' => self::money(abs((float) $order->get_total_refunded()), $currency),
                'grand_total' => self::money((float) $order->get_total(), $currency),
            ],
            'notes' => [
                'customer' => wp_strip_all_tags((string) $order->get_customer_note()),
                'footer' => wp_strip_all_tags((string) ($invoice_settings['footer_note'] ?? '')),
            ],
        ];
    }

    private static function money($value, string $currency = ''): array {
        $raw = (float) $value;
        return [
            'raw' => $raw,
            'formatted' => wp_strip_all_tags((string) wc_price($raw, $currency !== '' ? ['currency' => $currency] : [])),
        ];
    }

    private static function fallback($primary, $fallback): string {
        $primary = trim((string) $primary);
        return $primary !== '' ? $primary : trim((string) $fallback);
    }

    private static function address($line1, $line2): string {
        return trim(implode('، ', array_filter([trim((string) $line1), trim((string) $line2)])));
    }

    private static function digits($value): string {
        return strtr(trim((string) $value), [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);
    }

    private static function state_name(string $state): string {
        if (function_exists('WC') && WC() && isset(WC()->countries)) {
            $states = WC()->countries->get_states('IR');
            return $states[$state] ?? $state;
        }
        return $state;
    }
}
