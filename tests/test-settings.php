<?php
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-layout.php';
require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-settings.php';

$defaults = WSL_Settings::defaults();
wsl_assert_same(4, $defaults['layout']['per_page'], 'default four labels');
wsl_assert_same('portrait', $defaults['layout']['orientation'], 'default portrait orientation');
wsl_assert($defaults['fields']['recipient_name'], 'recipient name enabled');
wsl_assert($defaults['fields']['sender_address'], 'sender address enabled');
$clean = WSL_Settings::sanitize([
    'template' => 'bad',
    'layout' => ['per_page' => 8],
    'accent' => 'javascript:bad',
    'font_size' => 99,
    'sender' => ['name' => '<b>فروشگاه</b>'],
    'fields' => ['order_number' => '1'],
]);
wsl_assert_same('table', $clean['template'], 'template whitelist');
wsl_assert_same(8, $clean['layout']['per_page'], 'layout selection');
wsl_assert_same('#155eef', $clean['accent'], 'safe color fallback');
wsl_assert_same(16, $clean['font_size'], 'font clamp');
wsl_assert_same('فروشگاه', $clean['sender']['name'], 'sender sanitized');
wsl_assert($clean['fields']['order_number'], 'selected optional enabled');
wsl_assert(!$clean['fields']['payment_method'], 'missing optional disabled');
$first_run = WSL_Settings::get();
wsl_assert($first_run['fields']['order_number'], 'first run keeps recommended fields enabled');
$custom = WSL_Settings::sanitize(['layout' => ['per_page'=>4,'margin_top'=>8,'margin_right'=>7,'margin_bottom'=>8,'margin_left'=>7,'gap_horizontal'=>3,'gap_vertical'=>3,'label_width'=>96.5,'label_height'=>137.5]]);
wsl_assert_same(8.0, $custom['layout']['margin_top'], 'custom margin accepted');
wsl_assert_same(96.5, $custom['layout']['label_width'], 'custom width accepted');
$horizontal = WSL_Settings::sanitize(['layout' => ['per_page' => 4, 'orientation' => 'landscape']]);
wsl_assert_same('landscape', $horizontal['layout']['orientation'], 'horizontal labels saved');

$invoice_keys = WSL_Settings::invoice_field_keys();
wsl_assert(in_array('fee_total', $invoice_keys, true), 'invoice fee visibility setting exists');
wsl_assert_same(true, $defaults['modules']['labels_enabled'], 'labels module enabled by default');
wsl_assert_same(true, $defaults['modules']['invoice_enabled'], 'invoice module enabled by default');
wsl_assert_same('modern', $defaults['invoice']['template'], 'modern invoice is default');
wsl_assert_same('فاکتور فروش', $defaults['invoice']['title'], 'default invoice title');
wsl_assert_same('#155eef', $defaults['invoice']['accent'], 'default invoice accent');
wsl_assert_same('از خرید شما سپاسگزاریم', $defaults['invoice']['footer_note'], 'default invoice footer');
wsl_assert_same(0, $defaults['invoice']['logo_id'], 'default invoice logo');
foreach ($invoice_keys as $key) {
    wsl_assert(array_key_exists($key, $defaults['invoice']['fields']), 'invoice field default exists: ' . $key);
    wsl_assert(is_bool($defaults['invoice']['fields'][$key]), 'invoice field default is boolean: ' . $key);
}

$invoice_clean = WSL_Settings::sanitize([
    'modules' => ['invoice_enabled' => '1'],
    'invoice' => [
        'template' => 'unsafe-template',
        'title' => '<b>فاکتور ویژه</b>',
        'footer_note' => '<script>bad()</script>سپاس',
        'accent' => 'not-a-color',
        'logo_id' => '-42',
        'fields' => ['order_number' => '1'],
    ],
]);
wsl_assert_same('modern', $invoice_clean['invoice']['template'], 'invoice template whitelist');
wsl_assert_same('فاکتور ویژه', $invoice_clean['invoice']['title'], 'invoice title sanitized');
wsl_assert_same('bad()سپاس', $invoice_clean['invoice']['footer_note'], 'invoice footer sanitized');
wsl_assert_same('#155eef', $invoice_clean['invoice']['accent'], 'invoice accent fallback');
wsl_assert_same(42, $invoice_clean['invoice']['logo_id'], 'invoice logo normalized');
wsl_assert($invoice_clean['invoice']['fields']['order_number'], 'selected invoice field enabled');
wsl_assert(!$invoice_clean['invoice']['fields']['product_image'], 'missing submitted invoice field disabled');

$classic = WSL_Settings::sanitize(['invoice' => ['template' => 'classic']]);
wsl_assert_same('classic', $classic['invoice']['template'], 'classic invoice template allowed');
wsl_assert(!WSL_Settings::module_enabled($defaults, 'unknown'), 'unknown module disabled');

$GLOBALS['wsl_test_options'][WSL_Settings::OPTION] = [
    'template' => 'simple',
    'layout' => WSL_Layout::preset(2, 'landscape'),
    'sender' => ['name' => 'فروشگاه قدیمی'],
    'fields' => ['recipient_name' => true, 'barcode' => false],
    'accent' => '#123456',
    'font_size' => 12,
    'show_border' => false,
];
$upgraded = WSL_Settings::get();
wsl_assert_same('simple', $upgraded['template'], 'old template retained');
wsl_assert_same(2, $upgraded['layout']['per_page'], 'old layout retained');
wsl_assert_same('فروشگاه قدیمی', $upgraded['sender']['name'], 'old sender retained');
wsl_assert_same('#123456', $upgraded['accent'], 'old accent retained');
wsl_assert_same(true, $upgraded['modules']['labels_enabled'], 'old settings gain labels module');
wsl_assert_same(true, $upgraded['modules']['invoice_enabled'], 'old settings gain invoice module');
wsl_assert_same('modern', $upgraded['invoice']['template'], 'old settings gain invoice defaults');
unset($GLOBALS['wsl_test_options'][WSL_Settings::OPTION]);
