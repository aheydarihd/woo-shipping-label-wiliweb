<?php
require_once __DIR__ . '/bootstrap.php';

if (!class_exists('WC_Order')) {
    class WC_Order {
        public function __construct(private array $data) {}
        public function __call(string $name, array $args) {
            $key = preg_replace('/^get_/', '', $name);
            return $this->data[$key] ?? '';
        }
        public function get_id(): int { return (int) ($this->data['id'] ?? 0); }
        public function get_shipping_first_name(): string { return (string) ($this->data['shipping_first_name'] ?? ''); }
        public function get_shipping_last_name(): string { return (string) ($this->data['shipping_last_name'] ?? ''); }
        public function get_shipping_state(): string { return (string) ($this->data['shipping_state'] ?? ''); }
        public function get_shipping_city(): string { return (string) ($this->data['shipping_city'] ?? ''); }
        public function get_shipping_address_1(): string { return (string) ($this->data['shipping_address_1'] ?? ''); }
        public function get_shipping_address_2(): string { return (string) ($this->data['shipping_address_2'] ?? ''); }
        public function get_shipping_postcode(): string { return (string) ($this->data['shipping_postcode'] ?? ''); }
        public function get_billing_first_name(): string { return (string) ($this->data['billing_first_name'] ?? ''); }
        public function get_billing_last_name(): string { return (string) ($this->data['billing_last_name'] ?? ''); }
        public function get_billing_state(): string { return (string) ($this->data['billing_state'] ?? ''); }
        public function get_billing_city(): string { return (string) ($this->data['billing_city'] ?? ''); }
        public function get_billing_address_1(): string { return (string) ($this->data['billing_address_1'] ?? ''); }
        public function get_billing_address_2(): string { return (string) ($this->data['billing_address_2'] ?? ''); }
        public function get_billing_postcode(): string { return (string) ($this->data['billing_postcode'] ?? ''); }
        public function get_billing_phone(): string { return (string) ($this->data['billing_phone'] ?? ''); }
        public function get_billing_email(): string { return (string) ($this->data['billing_email'] ?? ''); }
        public function get_order_number(): string { return (string) ($this->data['order_number'] ?? $this->get_id()); }
        public function get_shipping_method(): string { return (string) ($this->data['shipping_method'] ?? ''); }
        public function get_payment_method_title(): string { return (string) ($this->data['payment_method_title'] ?? ''); }
        public function get_status(): string { return (string) ($this->data['status'] ?? 'processing'); }
        public function get_customer_note(): string { return (string) ($this->data['customer_note'] ?? ''); }
        public function get_total(): string { return (string) ($this->data['total'] ?? ''); }
        public function get_items(): array { return $this->data['items'] ?? []; }
        public function get_date_created() { return $this->data['date_created'] ?? null; }
    }
}
if (!function_exists('wc_get_order_status_name')) { function wc_get_order_status_name($s) { return 'status:' . $s; } }
if (!function_exists('wc_price')) { function wc_price($v) { return 'WC_PRICE[' . $v . ']'; } }
if (!function_exists('wp_strip_all_tags')) { function wp_strip_all_tags($v) { return strip_tags((string) $v); } }
if (!function_exists('wp_date')) { function wp_date($format, $timestamp = null) { return '1405/08/15'; } }

final class WSL_Test_Item {
    public function __construct(private string $name, private int $qty) {}
    public function get_name(): string { return $this->name; }
    public function get_quantity(): int { return $this->qty; }
}

require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-order-data.php';

$order = new WC_Order([
    'id' => 42,
    'order_number' => '1042',
    'shipping_first_name' => 'علی',
    'shipping_last_name' => 'رضایی',
    'shipping_state' => 'تهران',
    'shipping_city' => '',
    'shipping_address_1' => 'خیابان آزادی',
    'shipping_postcode' => '۱۲۳۴۵۶۷۸۹۰',
    'billing_city' => 'تهران',
    'billing_phone' => '۰۹۱۲۱۲۳۴۵۶۷',
    'billing_email' => 'ali@example.com',
    'shipping_method' => 'پست پیشتاز',
    'payment_method_title' => 'پرداخت آنلاین',
    'items' => [new WSL_Test_Item('کالای تست', 2)],
]);
$settings = ['sender' => ['name' => 'فروشگاه', 'phone' => '0211234', 'province' => 'تهران', 'city' => 'تهران', 'address' => 'بازار', 'postcode' => '111', 'website' => 'example.com', 'logo_id' => 0]];
$data = WSL_Order_Data::from_order($order, $settings);
wsl_assert_same('علی رضایی', $data['recipient']['name'], 'shipping name');
wsl_assert_same('تهران', $data['recipient']['city'], 'billing city fallback');
wsl_assert_same('09121234567', $data['recipient']['phone'], 'phone digit normalization');
wsl_assert_same('1234567890', $data['recipient']['postcode'], 'postcode digit normalization');
wsl_assert_same('فروشگاه', $data['sender']['name'], 'sender name');
wsl_assert_same('1042', $data['optional']['order_number'], 'order number');
wsl_assert_same('کالای تست × 2', $data['optional']['items'][0], 'item quantity');
