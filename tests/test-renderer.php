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
