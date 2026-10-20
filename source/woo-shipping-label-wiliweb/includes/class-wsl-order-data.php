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
