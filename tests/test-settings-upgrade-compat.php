<?php
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-layout.php';
require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-settings.php';

$v106 = [
    'template' => 'simple',
    'layout' => ['per_page' => 2, 'orientation' => 'landscape', 'margin_top' => 6.5, 'margin_right' => 5.5, 'margin_bottom' => 6.5, 'margin_left' => 5.5, 'gap_horizontal' => 2.5, 'gap_vertical' => 4.5, 'label_width' => 199.0, 'label_height' => 137.5],
    'sender' => ['name' => 'فروشگاه قدیمی', 'phone' => '02112345678', 'province' => 'تهران', 'city' => 'تهران', 'address' => 'نشانی ذخیره‌شده', 'postcode' => '1111111111', 'website' => 'old.example', 'logo_id' => 321],
    'fields' => array_replace(array_fill_keys(WSL_Settings::field_keys(), false), ['recipient_name' => true, 'sender_name' => true, 'order_number' => true, 'print_date' => false, 'barcode' => false]),
    'accent' => '#123456',
    'font_size' => 14,
    'show_border' => false,
];
$GLOBALS['wsl_test_options'][WSL_Settings::OPTION] = $v106;
$loaded106 = WSL_Settings::get();
wsl_assert_same($v106, $GLOBALS['wsl_test_options'][WSL_Settings::OPTION], 'reading v1.0.6 settings never rewrites stored option');
wsl_assert_same('simple', $loaded106['template'], 'v1.0.6 template preserved');
foreach ($v106['layout'] as $key => $value) {
    wsl_assert_same($value, $loaded106['layout'][$key] ?? null, 'v1.0.6 custom A4 layout preserved: ' . $key);
}
wsl_assert_same($v106['sender'], $loaded106['sender'], 'v1.0.6 sender and logo preserved');
wsl_assert_same('#123456', $loaded106['accent'], 'v1.0.6 accent preserved');
wsl_assert_same(14, $loaded106['font_size'], 'v1.0.6 font size preserved');
wsl_assert_same(false, $loaded106['show_border'], 'v1.0.6 border preference preserved');
wsl_assert_same(false, $loaded106['fields']['barcode'], 'v1.0.6 disabled field remains disabled');
wsl_assert_same(42, $loaded106['logo_width'], 'new logo width receives non-destructive default');

$v110 = WSL_Settings::defaults();
unset($v110['logo_width']);
$v110['modules'] = ['labels_enabled' => true, 'invoice_enabled' => false];
$v110['template'] = 'table';
$v110['sender']['name'] = 'سایت دوم';
$v110['sender']['logo_id'] = 654;
$v110['fields']['print_date'] = true;
$v110['fields']['recipient_email'] = false;
$v110['invoice']['template'] = 'classic';
$v110['invoice']['title'] = 'فاکتور اختصاصی';
$v110['invoice']['footer_note'] = 'پیام اختصاصی سایت';
$v110['invoice']['accent'] = '#654321';
$v110['invoice']['logo_id'] = 987;
$v110['invoice']['fields']['product_image'] = false;
$v110['invoice']['fields']['customer_note'] = true;
$GLOBALS['wsl_test_options'][WSL_Settings::OPTION] = $v110;
$loaded110 = WSL_Settings::get();
wsl_assert_same($v110, $GLOBALS['wsl_test_options'][WSL_Settings::OPTION], 'reading v1.1.0 settings never rewrites stored option');
wsl_assert_same(false, $loaded110['modules']['invoice_enabled'], 'v1.1.0 disabled module preserved');
wsl_assert_same('سایت دوم', $loaded110['sender']['name'], 'v1.1.0 sender preserved');
wsl_assert_same(654, $loaded110['sender']['logo_id'], 'v1.1.0 label logo preserved');
wsl_assert_same(true, $loaded110['fields']['print_date'], 'v1.1.0 print date preference preserved');
wsl_assert_same(false, $loaded110['fields']['recipient_email'], 'v1.1.0 disabled label field preserved');
wsl_assert_same('classic', $loaded110['invoice']['template'], 'v1.1.0 invoice template preserved');
wsl_assert_same('فاکتور اختصاصی', $loaded110['invoice']['title'], 'v1.1.0 invoice title preserved');
wsl_assert_same('پیام اختصاصی سایت', $loaded110['invoice']['footer_note'], 'v1.1.0 invoice footer preserved');
wsl_assert_same('#654321', $loaded110['invoice']['accent'], 'v1.1.0 invoice accent preserved');
wsl_assert_same(987, $loaded110['invoice']['logo_id'], 'v1.1.0 invoice logo preserved');
wsl_assert_same(false, $loaded110['invoice']['fields']['product_image'], 'v1.1.0 invoice field preference preserved');
wsl_assert_same(true, $loaded110['invoice']['fields']['customer_note'], 'v1.1.0 invoice note preference preserved');
wsl_assert_same(42, $loaded110['logo_width'], 'v1.1.0 receives new logo width default only in runtime');

unset($GLOBALS['wsl_test_options'][WSL_Settings::OPTION]);
