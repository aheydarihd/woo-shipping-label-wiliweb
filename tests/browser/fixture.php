<?php
declare(strict_types=1);
define('WSL_TESTING', true);
define('ABSPATH', __DIR__ . '/');
define('WSL_VERSION', '1.0.0');
define('WSL_URL', '../../source/woo-shipping-label-wiliweb/');
class WP_Error { public function __construct(public string $code='', public string $message='') {} }
function absint($v): int { return abs((int)$v); }
function esc_html($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function esc_attr($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function esc_url($v): string { return (string)$v; }
function esc_html_e($v, $d=null): void { echo esc_html($v); }
function language_attributes(): void { echo 'lang="fa-IR"'; }
function bloginfo($key): void { echo 'UTF-8'; }
function wp_get_attachment_image_url($id,$size) { return ''; }
function apply_filters($hook,$value,...$args) { return $value; }

require dirname(__DIR__, 2) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-layout.php';
require dirname(__DIR__, 2) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-renderer.php';
require dirname(__DIR__, 2) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-invoice-renderer.php';
$document = ($_GET['document'] ?? 'label') === 'invoice' ? 'invoice' : 'label';
$template = ($_GET['template'] ?? 'table');
if ($document === 'invoice') {
    $invoiceTemplate = $template === 'classic' ? 'classic' : 'modern';
    $itemCount = max(1, min(60, (int) ($_GET['items'] ?? 3)));
    $invoiceCount = max(1, min(5, (int) ($_GET['invoices'] ?? 1)));
    $invoiceFields = array_fill_keys(['logo','seller_contact','seller_address','buyer_phone','buyer_email','buyer_address','buyer_postcode','order_number','order_date','order_status','payment_method','shipping_method','product_image','sku','variation','unit_price','quantity','line_discount','line_total','subtotal','discount_total','shipping_total','tax_total','refund_total','grand_total','customer_note','footer_note','signature','barcode'], true);
    $settings = ['invoice' => ['template' => $invoiceTemplate, 'title' => 'فاکتور فروش', 'accent' => '#155eef', 'fields' => $invoiceFields]];
    $money = static fn($value): array => ['raw' => (float) $value, 'formatted' => number_format((float) $value) . ' تومان'];
    $image = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120"><rect width="120" height="120" fill="#e8eefc"/><circle cx="60" cy="60" r="30" fill="#155eef"/></svg>');
    $items = [];
    for ($i = 1; $i <= $itemCount; $i++) {
        $items[] = ['name' => 'محصول نمونه شماره ' . $i, 'image_url' => $image, 'sku' => 'SKU-' . $i, 'variation' => 'رنگ: آبی، اندازه: استاندارد', 'quantity' => 2, 'unit_price' => $money(100000 + $i), 'subtotal' => $money(200000 + $i), 'discount' => $money(10000), 'total' => $money(190000 + $i), 'tax' => $money(0)];
    }
    $invoices = [];
    for ($i = 1; $i <= $invoiceCount; $i++) {
        $invoices[] = [
            'order' => ['id' => $i, 'number' => (string) (1040 + $i), 'date' => '1405/08/15', 'status' => 'در حال انجام', 'payment_method' => 'پرداخت آنلاین', 'shipping_method' => 'پست پیشتاز', 'barcode' => (string) (1040 + $i)],
            'seller' => ['name' => 'فروشگاه نمونه', 'phone' => '02112345678', 'address' => 'تهران، بازار بزرگ، پلاک ۱۲', 'postcode' => '1111111111', 'website' => 'example.com', 'logo_id' => 0],
            'buyer' => ['name' => 'علی رضایی', 'phone' => '09121234567', 'email' => 'ali@example.com', 'province' => 'تهران', 'city' => 'تهران', 'address' => 'خیابان آزادی، کوچه نمونه، پلاک ۳۴', 'postcode' => '1234567890'],
            'items' => $items,
            'totals' => ['subtotal' => $money(600000), 'discount' => $money(30000), 'shipping' => $money(40000), 'tax' => $money(0), 'refund' => $money(0), 'grand_total' => $money(610000)],
            'notes' => ['customer' => 'ارسال در ساعات اداری', 'footer' => 'از خرید شما سپاسگزاریم'],
        ];
    }
    WSL_Invoice_Renderer::page($invoices, $settings, 0);
    return;
}
$count = in_array((int)($_GET['count'] ?? 4), [1,2,4,5,6,8], true) ? (int)$_GET['count'] : 4;
$perPage = $count === 5 ? 4 : $count;
$template = $template === 'simple' ? 'simple' : 'table';
$orientation = ($_GET['orientation'] ?? '') === 'landscape' ? 'landscape' : 'portrait';
$fields = array_fill_keys(['recipient_name','recipient_phone','recipient_province','recipient_city','recipient_address','recipient_postcode','sender_name','sender_phone','sender_province','sender_city','sender_address','sender_postcode','order_number','order_date','print_date','order_status','shipping_method','payment_method','items','order_total','customer_note','recipient_email','website','logo','barcode'], true);
$settings = ['template'=>$template,'layout'=>WSL_Layout::preset($perPage, $orientation),'fields'=>$fields,'accent'=>'#155eef','font_size'=>11,'show_border'=>true];
$labels = [];
for($i=1;$i<=$count;$i++){
    $labels[]=['order_id'=>$i,'recipient'=>['name'=>"علی رضایی {$i}",'phone'=>'09121234567','province'=>'تهران','city'=>'تهران','address'=>'خیابان آزادی، کوچه نمونه، پلاک ۱۲، واحد ۳','postcode'=>'1234567890'],'sender'=>['name'=>'فروشگاه نمونه','phone'=>'02112345678','province'=>'تهران','city'=>'تهران','address'=>'بازار بزرگ، پلاک ۱','postcode'=>'1111111111','website'=>'example.com','logo_id'=>0],'optional'=>['order_number'=>(string)(1000+$i),'order_date'=>'1405/06/21','print_date'=>'1405/06/21 12:00','order_status'=>'در حال انجام','shipping_method'=>'پست پیشتاز','payment_method'=>'پرداخت آنلاین','items'=>['محصول نمونه × 2'],'order_total'=>'2,500,000 تومان','customer_note'=>'','recipient_email'=>'ali@example.com','website'=>'example.com','logo_id'=>0,'barcode'=>(string)(1000+$i)]];
}
WSL_Renderer::page($labels,$settings,0);
