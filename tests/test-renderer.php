<?php
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-layout.php';
require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-settings.php';
require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-renderer.php';

$settings = WSL_Settings::defaults();
$data = [
    'order_id' => 42,
    'recipient' => ['name'=>'علی رضایی','phone'=>'0912','province'=>'تهران','city'=>'تهران','address'=>'آزادی','postcode'=>'123'],
    'sender' => ['name'=>'فروشگاه','phone'=>'021','province'=>'تهران','city'=>'تهران','address'=>'بازار','postcode'=>'111','website'=>'example.com','logo_id'=>0],
    'optional' => ['order_number'=>'1042','order_date'=>'1405/06/21','print_date'=>'','order_status'=>'در حال انجام','shipping_method'=>'پست','payment_method'=>'آنلاین','items'=>[],'order_total'=>'','customer_note'=>'','recipient_email'=>'','website'=>'example.com','logo_id'=>0,'barcode'=>'1042'],
];
$html = WSL_Renderer::label($data, $settings);
wsl_assert(str_contains($html, 'wsl-label--table'), 'table template');
wsl_assert(str_contains($html, 'علی رضایی'), 'recipient printed');
wsl_assert(str_contains($html, 'فروشگاه'), 'sender printed');
$settings['fields']['payment_method'] = false;
$html = WSL_Renderer::label($data, $settings);
wsl_assert(!str_contains($html, 'آنلاین'), 'disabled field hidden');
$settings['template'] = 'simple';
wsl_assert(str_contains(WSL_Renderer::label($data, $settings), 'wsl-label--simple'), 'simple template');
wsl_assert_same(2, count(WSL_Renderer::sheets(array_fill(0, 5, $data), $settings)), 'five labels create two sheets');

$GLOBALS['wsl_test_images'][77] = 'https://example.test/postal-logo.png';
$postal_data = $data;
$postal_data['recipient'] = ['name'=>'<script>alert(1)</script> علی رضایی','phone'=>'09120001122','province'=>'تهران','city'=>'تهران','address'=>'خیابان آزادی، کوچه نمونه، پلاک ۱۲','postcode'=>'1234567890'];
$postal_data['sender'] = ['name'=>'فروشگاه ویلی وب','phone'=>'02112345678','province'=>'تهران','city'=>'تهران','address'=>'بازار بزرگ، پلاک ۱','postcode'=>'1111111111','website'=>'wiliweb.com','logo_id'=>77];
$postal_data['optional']['logo_id'] = 77;
$postal_data['optional']['print_date'] = '1405/08/18 12:30';
$postal_data['optional']['order_date'] = '1405/08/17';
$postal_data['optional']['order_status'] = 'آماده ارسال';
$postal_data['optional']['payment_method'] = 'پرداخت آنلاین';
$postal_data['optional']['items'] = ['دفتر برنامه‌ریزی × 2'];
$postal_data['optional']['order_total'] = '850,000 تومان';
$postal_data['optional']['customer_note'] = 'قبل از ارسال تماس بگیرید';
$postal_data['optional']['recipient_email'] = 'ali@example.test';
$postal_settings = WSL_Settings::defaults();
$postal_settings['template'] = 'postal';
foreach (['order_date', 'order_status', 'payment_method', 'items', 'order_total', 'customer_note', 'recipient_email'] as $field) {
    $postal_settings['fields'][$field] = true;
}
$postal = WSL_Renderer::label($postal_data, $postal_settings);
wsl_assert(str_contains($postal, 'wsl-label--postal'), 'postal template root');
foreach (['wsl-postal__inner', 'wsl-postal__recipient', 'wsl-postal__sender', 'wsl-postal__footer', 'wsl-postal__logo', 'wsl-postal__barcode'] as $class) {
    wsl_assert(str_contains($postal, $class), 'postal region: ' . $class);
}
wsl_assert(str_contains($postal, 'https://example.test/postal-logo.png'), 'postal logo uses sender logo');
wsl_assert(str_contains($postal, 'wsl-barcode'), 'postal barcode rendered');
foreach (['1405/08/17', 'آماده ارسال', 'پرداخت آنلاین', 'دفتر برنامه‌ریزی × 2', '850,000 تومان', 'قبل از ارسال تماس بگیرید', 'ali@example.test'] as $optionalValue) {
    wsl_assert(str_contains($postal, $optionalValue), 'enabled postal optional field: ' . $optionalValue);
}
wsl_assert(str_contains($postal, '&lt;script&gt;alert(1)&lt;/script&gt; علی رضایی'), 'postal recipient escaped');
wsl_assert(!str_contains($postal, '<script>alert(1)</script>'), 'unsafe postal HTML absent');

$postal_hidden = $postal_settings;
foreach (['recipient_phone', 'recipient_address', 'sender_phone', 'sender_address', 'logo', 'barcode', 'order_date', 'order_status', 'payment_method', 'items', 'order_total', 'customer_note', 'recipient_email'] as $field) {
    $postal_hidden['fields'][$field] = false;
}
$postal_hidden_html = WSL_Renderer::label($postal_data, $postal_hidden);
wsl_assert(!str_contains($postal_hidden_html, '09120001122'), 'disabled recipient phone hidden from postal');
wsl_assert(!str_contains($postal_hidden_html, 'خیابان آزادی'), 'disabled recipient address hidden from postal');
wsl_assert(!str_contains($postal_hidden_html, '02112345678'), 'disabled sender phone hidden from postal');
wsl_assert(!str_contains($postal_hidden_html, 'بازار بزرگ'), 'disabled sender address hidden from postal');
wsl_assert(!str_contains($postal_hidden_html, 'postal-logo.png'), 'disabled postal logo hidden');
wsl_assert(!str_contains($postal_hidden_html, 'wsl-barcode'), 'disabled postal barcode hidden');
foreach (['1405/08/17', 'آماده ارسال', 'پرداخت آنلاین', 'دفتر برنامه‌ریزی × 2', '850,000 تومان', 'قبل از ارسال تماس بگیرید', 'ali@example.test'] as $optionalValue) {
    wsl_assert(!str_contains($postal_hidden_html, $optionalValue), 'disabled postal optional field hidden: ' . $optionalValue);
}
wsl_assert(str_contains($postal_hidden_html, 'فروشگاه ویلی وب'), 'postal remains readable with disabled fields');

$postal_without_logo = $postal_data;
$postal_without_logo['optional']['logo_id'] = 0;
$postal_without_logo_html = WSL_Renderer::label($postal_without_logo, $postal_settings);
wsl_assert(!str_contains($postal_without_logo_html, '<img'), 'postal missing logo has no broken image');
wsl_assert(str_contains($postal_without_logo_html, 'فروشگاه ویلی وب'), 'postal missing logo keeps sender identity');
unset($GLOBALS['wsl_test_images'][77]);
