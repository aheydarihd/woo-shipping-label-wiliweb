<?php
require_once __DIR__ . '/bootstrap.php';

if (!class_exists('WC_Order')) {
    class WC_Order {
        public function __construct(private array $data) {}
        public function __call(string $name, array $args) {
            return $this->data[preg_replace('/^get_/', '', $name)] ?? '';
        }
        public function get_id(): int { return (int) ($this->data['id'] ?? 0); }
        public function get_order_number(): string { return (string) ($this->data['order_number'] ?? $this->get_id()); }
        public function get_items(): array { return $this->data['items'] ?? []; }
        public function get_date_created() { return $this->data['date_created'] ?? null; }
    }
}
if (!function_exists('wc_get_order_status_name')) { function wc_get_order_status_name($status) { return 'status:' . $status; } }
if (!function_exists('wc_price')) { function wc_price($value) { return 'WC_PRICE[' . $value . ']'; } }
if (!function_exists('wp_strip_all_tags')) { function wp_strip_all_tags($value) { return strip_tags((string) $value); } }
if (!function_exists('wp_date')) { function wp_date($format, $timestamp = null) { return '1405/08/15'; } }
if (!function_exists('wc_get_product')) {
    function wc_get_product($id) { return $GLOBALS['wsl_test_products'][(int) $id] ?? false; }
}

final class WSL_Invoice_Test_Date {
    public function getTimestamp(): int { return 1793923200; }
}

final class WSL_Invoice_Test_Product {
    public function __construct(
        private int $id,
        private int $image_id,
        private string $sku = '',
        private int $parent_id = 0
    ) {}
    public function get_id(): int { return $this->id; }
    public function get_image_id(): int { return $this->image_id; }
    public function get_sku(): string { return $this->sku; }
    public function get_parent_id(): int { return $this->parent_id; }
}

final class WSL_Invoice_Test_Item {
    public function __construct(
        private string $name,
        private int $quantity,
        private string $subtotal,
        private string $total,
        private string $tax,
        private $product,
        private array $meta = []
    ) {}
    public function get_name(): string { return $this->name; }
    public function get_quantity(): int { return $this->quantity; }
    public function get_subtotal(): string { return $this->subtotal; }
    public function get_total(): string { return $this->total; }
    public function get_total_tax(): string { return $this->tax; }
    public function get_product() { return $this->product; }
    public function get_formatted_meta_data(string $hideprefix = '_', bool $include_all = false): array { return $this->meta; }
}

$GLOBALS['wsl_test_images'] = [501 => 'https://example.test/variation.jpg', 502 => 'https://example.test/parent.jpg'];
$parent = new WSL_Invoice_Test_Product(100, 502, 'PARENT-SKU');
$variation_with_image = new WSL_Invoice_Test_Product(101, 501, 'VAR-RED', 100);
$variation_without_image = new WSL_Invoice_Test_Product(102, 0, 'VAR-BLUE', 100);
$GLOBALS['wsl_test_products'] = [100 => $parent];

$public_meta = (object) ['key' => 'pa_color', 'display_key' => 'رنگ', 'display_value' => '<b>قرمز</b>'];
$private_meta = (object) ['key' => '_internal', 'display_key' => 'مخفی', 'display_value' => 'نباید باشد'];
$items = [
    new WSL_Invoice_Test_Item('<b>پیراهن</b>', 2, '300000', '250000', '25000', $variation_with_image, [$public_meta, $private_meta]),
    new WSL_Invoice_Test_Item('پیراهن آبی', 1, '180000', '180000', '0', $variation_without_image),
    new WSL_Invoice_Test_Item('<script>bad()</script>محصول حذف‌شده', 1, '0', '0', '0', false),
];

$order = new WC_Order([
    'id' => 77,
    'order_number' => 'INV-77',
    'date_created' => new WSL_Invoice_Test_Date(),
    'status' => 'processing',
    'payment_method_title' => 'درگاه آنلاین',
    'shipping_method' => 'پست پیشتاز',
    'shipping_first_name' => 'سارا',
    'shipping_last_name' => 'محمدی',
    'shipping_state' => 'تهران',
    'shipping_city' => '',
    'shipping_address_1' => 'خیابان ولیعصر',
    'shipping_address_2' => 'پلاک ۱۲',
    'shipping_postcode' => '۱۲۳۴۵۶۷۸۹۰',
    'billing_city' => 'تهران',
    'billing_phone' => '۰۹۱۲۱۲۳۴۵۶۷',
    'billing_email' => 'sara@example.test',
    'subtotal' => '480000',
    'discount_total' => '50000',
    'shipping_total' => '30000',
    'total_tax' => '25000',
    'total_refunded' => '10000',
    'total' => '475000',
    'customer_note' => '<b>بعدازظهر ارسال شود</b>',
    'items' => $items,
]);
$settings = [
    'sender' => [
        'name' => 'فروشگاه تست', 'phone' => '۰۲۱۱۲۳۴', 'province' => 'تهران', 'city' => 'تهران',
        'address' => 'بازار بزرگ', 'postcode' => '۱۱۱۱۱۱۱۱۱۱', 'website' => 'example.test', 'logo_id' => 88,
    ],
    'invoice' => ['logo_id' => 99, 'footer_note' => '<b>سپاس از خرید شما</b>'],
];

require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-order-data.php';

$invoice = WSL_Order_Data::invoice_from_order($order, $settings);
wsl_assert_same(['order', 'seller', 'buyer', 'items', 'totals', 'notes'], array_keys($invoice), 'stable invoice sections');
wsl_assert_same(77, $invoice['order']['id'], 'order ID');
wsl_assert_same('INV-77', $invoice['order']['number'], 'order number');
wsl_assert_same('1405/08/15', $invoice['order']['date'], 'order date');
wsl_assert_same('status:processing', $invoice['order']['status'], 'order status');
wsl_assert_same('درگاه آنلاین', $invoice['order']['payment_method'], 'payment method');
wsl_assert_same('پست پیشتاز', $invoice['order']['shipping_method'], 'shipping method');
wsl_assert_same('INV-77', $invoice['order']['barcode'], 'barcode value');

wsl_assert_same(99, $invoice['seller']['logo_id'], 'invoice logo wins over sender logo');
wsl_assert_same('0211234', $invoice['seller']['phone'], 'seller phone normalized');
wsl_assert_same('تهران، تهران، بازار بزرگ', $invoice['seller']['address'], 'seller address combined');
wsl_assert_same('سارا محمدی', $invoice['buyer']['name'], 'shipping buyer name');
wsl_assert_same('تهران', $invoice['buyer']['city'], 'billing city fallback');
wsl_assert_same('09121234567', $invoice['buyer']['phone'], 'buyer phone normalized');
wsl_assert_same('1234567890', $invoice['buyer']['postcode'], 'buyer postcode normalized');

wsl_assert_same('پیراهن', $invoice['items'][0]['name'], 'item name stripped');
wsl_assert_same('https://example.test/variation.jpg', $invoice['items'][0]['image_url'], 'variation image preferred');
wsl_assert_same('VAR-RED', $invoice['items'][0]['sku'], 'variation SKU');
wsl_assert_same('رنگ: قرمز', $invoice['items'][0]['variation'], 'public variation metadata');
wsl_assert(!str_contains($invoice['items'][0]['variation'], 'مخفی'), 'private metadata hidden');
wsl_assert_same(2, $invoice['items'][0]['quantity'], 'item quantity');
wsl_assert_same('WC_PRICE[150000]', $invoice['items'][0]['unit_price']['formatted'], 'unit price uses wc_price');
wsl_assert_same('WC_PRICE[50000]', $invoice['items'][0]['discount']['formatted'], 'line discount uses wc_price');
wsl_assert_same('https://example.test/parent.jpg', $invoice['items'][1]['image_url'], 'parent image fallback');
wsl_assert_same('', $invoice['items'][2]['image_url'], 'deleted product image empty');
wsl_assert_same('bad()محصول حذف‌شده', $invoice['items'][2]['name'], 'deleted product still printable and sanitized');

wsl_assert_same(480000.0, $invoice['totals']['subtotal']['raw'], 'subtotal raw');
wsl_assert_same('WC_PRICE[480000]', $invoice['totals']['subtotal']['formatted'], 'subtotal formatted by WooCommerce');
wsl_assert_same(10000.0, $invoice['totals']['refund']['raw'], 'refund raw');
wsl_assert_same('WC_PRICE[475000]', $invoice['totals']['grand_total']['formatted'], 'grand total formatted');
wsl_assert_same('بعدازظهر ارسال شود', $invoice['notes']['customer'], 'customer note sanitized');
wsl_assert_same('سپاس از خرید شما', $invoice['notes']['footer'], 'footer note sanitized');

$free_order = new WC_Order([
    'id' => 78, 'order_number' => 'FREE-78', 'total' => '0', 'subtotal' => '0',
    'discount_total' => '0', 'shipping_total' => '0', 'total_tax' => '0', 'total_refunded' => '0',
    'items' => [new WSL_Invoice_Test_Item('هدیه', 0, '0', '0', '0', false)],
]);
$free = WSL_Order_Data::invoice_from_order($free_order, $settings);
wsl_assert_same(0.0, $free['items'][0]['unit_price']['raw'], 'zero quantity avoids division by zero');
wsl_assert_same('WC_PRICE[0]', $free['totals']['grand_total']['formatted'], 'free order total remains visible');

unset($GLOBALS['wsl_test_images'], $GLOBALS['wsl_test_products']);
