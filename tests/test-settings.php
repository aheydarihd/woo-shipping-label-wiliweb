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
