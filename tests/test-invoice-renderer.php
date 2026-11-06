<?php
require_once __DIR__ . '/bootstrap.php';

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
if (!defined('WSL_VERSION')) define('WSL_VERSION', 'test');
if (!defined('WSL_URL')) define('WSL_URL', '/plugin/');
if (!function_exists('language_attributes')) { function language_attributes(): void { echo 'lang="fa-IR"'; } }
if (!function_exists('bloginfo')) { function bloginfo($key): void { echo 'UTF-8'; } }
if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value, ...$args) {
        $GLOBALS['wsl_invoice_filter_log'][] = ['hook' => $hook, 'args' => $args];
        if ($hook === 'wsl_invoice_title') return 'فاکتور ویژه';
        if ($hook === 'wsl_invoice_logo_url') return 'https://example.test/logo-filtered.png';
        if ($hook === 'wsl_invoice_html') return $value . '<!--invoice-filtered-->';
        return $value;
    }
}

require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-layout.php';
require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-settings.php';
require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-renderer.php';
require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-invoice-renderer.php';

$money = static fn(float $raw, string $formatted): array => ['raw' => $raw, 'formatted' => $formatted];
$invoice = [
    'order' => [
        'id' => 42, 'number' => '<script>1042</script>', 'date' => '1405/08/15',
        'status' => 'در حال انجام', 'payment_method' => 'پرداخت آنلاین',
        'shipping_method' => 'پست پیشتاز', 'barcode' => '1042',
    ],
    'seller' => [
        'name' => 'فروشگاه <b>ویلی وب</b>', 'phone' => '02112345678',
        'address' => 'تهران، بازار بزرگ', 'postcode' => '1111111111',
        'website' => 'wiliweb.com', 'logo_id' => 5,
    ],
    'buyer' => [
        'name' => 'علی رضایی', 'phone' => '09121234567', 'email' => 'ali@example.test',
        'province' => 'تهران', 'city' => 'تهران', 'address' => 'خیابان آزادی', 'postcode' => '1234567890',
    ],
    'items' => [[
        'name' => 'کفش ورزشی', 'image_url' => 'https://example.test/product.jpg', 'sku' => 'SHOE-RED-42',
        'variation' => 'رنگ: قرمز، سایز: ۴۲', 'quantity' => 2,
        'unit_price' => $money(100000, '۱۰۰٬۰۰۰ تومان'),
        'subtotal' => $money(200000, '۲۰۰٬۰۰۰ تومان'),
        'discount' => $money(20000, '۲۰٬۰۰۰ تومان'),
        'total' => $money(180000, '۱۸۰٬۰۰۰ تومان'),
        'tax' => $money(0, '۰ تومان'),
    ]],
    'totals' => [
        'subtotal' => $money(200000, '۲۰۰٬۰۰۰ تومان'),
        'discount' => $money(20000, '۲۰٬۰۰۰ تومان'),
        'shipping' => $money(30000, '۳۰٬۰۰۰ تومان'),
        'tax' => $money(0, '۰ تومان'),
        'refund' => $money(10000, '۱۰٬۰۰۰ تومان'),
        'grand_total' => $money(200000, '۲۰۰٬۰۰۰ تومان'),
    ],
    'notes' => ['customer' => '', 'footer' => 'از خرید شما سپاسگزاریم'],
];

$settings = WSL_Settings::defaults();
$settings['invoice']['fields'] = array_fill_keys(WSL_Settings::invoice_field_keys(), true);
$GLOBALS['wsl_invoice_filter_log'] = [];
$modern = WSL_Invoice_Renderer::invoice($invoice, $settings);
wsl_assert(str_contains($modern, 'wsl-invoice--modern'), 'modern template class');
wsl_assert(str_contains($modern, 'فاکتور ویژه'), 'filtered invoice title');
wsl_assert(str_contains($modern, 'https://example.test/logo-filtered.png'), 'filtered logo printed');
wsl_assert(str_contains($modern, 'wsl-invoice__seller'), 'seller block');
wsl_assert(str_contains($modern, 'wsl-invoice__buyer'), 'buyer block');
wsl_assert(str_contains($modern, 'https://example.test/product.jpg'), 'product image');
wsl_assert(str_contains($modern, 'SHOE-RED-42'), 'SKU printed');
wsl_assert(str_contains($modern, 'رنگ: قرمز، سایز: ۴۲'), 'variation printed');
wsl_assert(str_contains($modern, '>2<'), 'quantity printed');
wsl_assert(str_contains($modern, '۲۰۰٬۰۰۰ تومان'), 'grand total printed');
wsl_assert(str_contains($modern, '&lt;script&gt;1042&lt;/script&gt;'), 'order number escaped');
wsl_assert(str_contains($modern, 'فروشگاه &lt;b&gt;ویلی وب&lt;/b&gt;'), 'seller name escaped');
wsl_assert(!str_contains($modern, '<script>1042</script>'), 'unsafe order HTML absent');
wsl_assert(!str_contains($modern, 'یادداشت مشتری'), 'empty customer note omitted');
wsl_assert(str_contains($modern, 'wsl-invoice-barcode'), 'barcode scoped');
wsl_assert(str_contains($modern, '<!--invoice-filtered-->'), 'final HTML filter applied');

$hooks = array_column($GLOBALS['wsl_invoice_filter_log'], 'hook');
foreach (['wsl_invoice_data', 'wsl_invoice_title', 'wsl_invoice_logo_url', 'wsl_invoice_html'] as $hook) {
    wsl_assert(in_array($hook, $hooks, true), 'invoice filter fired: ' . $hook);
}
foreach ($GLOBALS['wsl_invoice_filter_log'] as $entry) {
    wsl_assert(count($entry['args']) >= 1, 'invoice filter receives invoice/settings context: ' . $entry['hook']);
}

$no_sku = $settings;
$no_sku['invoice']['fields']['sku'] = false;
wsl_assert(!str_contains(WSL_Invoice_Renderer::invoice($invoice, $no_sku), 'SHOE-RED-42'), 'disabled SKU hidden');
wsl_assert(str_contains(WSL_Invoice_Renderer::invoice($invoice, $no_sku), 'کفش ورزشی'), 'unrelated item data remains');

$classic_settings = $settings;
$classic_settings['invoice']['template'] = 'classic';
$classic = WSL_Invoice_Renderer::invoice($invoice, $classic_settings);
wsl_assert(str_contains($classic, 'wsl-invoice--classic'), 'classic template class');
wsl_assert(str_contains($classic, 'wsl-invoice__signature'), 'classic signature space');

$classic_hidden = $classic_settings;
foreach (['seller_contact', 'seller_address', 'buyer_phone', 'buyer_email', 'buyer_address', 'buyer_postcode'] as $field) {
    $classic_hidden['invoice']['fields'][$field] = false;
}
$classic_hidden_html = WSL_Invoice_Renderer::invoice($invoice, $classic_hidden);
wsl_assert(!str_contains($classic_hidden_html, '02112345678'), 'classic disabled seller contact hidden');
wsl_assert(!str_contains($classic_hidden_html, 'تهران، بازار بزرگ'), 'classic disabled seller address hidden');
wsl_assert(!str_contains($classic_hidden_html, '09121234567'), 'classic disabled buyer phone hidden');
wsl_assert(!str_contains($classic_hidden_html, 'ali@example.test'), 'classic disabled buyer email hidden');
wsl_assert(!str_contains($classic_hidden_html, 'خیابان آزادی'), 'classic disabled buyer address hidden');
wsl_assert(!str_contains($classic_hidden_html, '1234567890'), 'classic disabled buyer postcode hidden');

$invoice_with_note = $invoice;
$invoice_with_note['notes']['customer'] = 'لطفاً عصر ارسال شود';
$classic_details = WSL_Invoice_Renderer::invoice($invoice_with_note, $classic_settings);
wsl_assert(str_contains($classic_details, 'در حال انجام'), 'classic order status printed');
wsl_assert(str_contains($classic_details, 'ali@example.test'), 'classic buyer email printed');
wsl_assert(str_contains($classic_details, '1234567890'), 'classic buyer postcode printed');
wsl_assert(str_contains($classic_details, 'لطفاً عصر ارسال شود'), 'classic customer note printed');

$unsafe_template = $settings;
$unsafe_template['invoice']['template'] = '../../evil';
wsl_assert(str_contains(WSL_Invoice_Renderer::invoice($invoice, $unsafe_template), 'wsl-invoice--modern'), 'unsafe template falls back to modern');

ob_start();
WSL_Invoice_Renderer::page([$invoice, $invoice], $settings, 1);
$page = (string) ob_get_clean();
wsl_assert_same(2, substr_count($page, 'class="wsl-invoice-document"'), 'two invoice document roots');
wsl_assert(str_contains($page, 'یک سفارش'), 'skipped order alert');
wsl_assert(str_contains($page, 'invoice-print.css'), 'invoice print stylesheet loaded');
