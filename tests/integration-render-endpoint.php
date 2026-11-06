<?php
declare(strict_types=1);

$root = 'E:/Codex/Wiliweb/woo-crm-v2/tmp/runtime/wordpress/';
require $root . 'wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugin = 'woo-shipping-label-wiliweb/woo-shipping-label-wiliweb.php';
$activation = activate_plugin($plugin);
if (is_wp_error($activation)) {
    fwrite(STDERR, 'ACTIVATION_FAIL ' . $activation->get_error_message() . PHP_EOL);
    exit(1);
}

$admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ids']);
if (!$admins) {
    fwrite(STDERR, "ADMIN_FIXTURE_MISSING\n");
    exit(1);
}
wp_set_current_user((int) $admins[0]);

$order = wc_create_order();
$order->set_billing_first_name('گیرنده');
$order->set_billing_last_name('آزمایشی');
$order->set_billing_phone('09121234567');
$order->set_billing_city('تهران');
$order->set_billing_address_1('نشانی آزمون endpoint');
$order->set_billing_postcode('1234567890');
$order->save();

$mode = getenv('WSL_ENDPOINT_MODE') ?: 'label';
$previous_settings = get_option(WSL_Settings::OPTION, null);
if ($mode === 'invoice-disabled') {
    $disabled_settings = WSL_Settings::get();
    $disabled_settings['modules']['invoice_enabled'] = false;
    update_option(WSL_Settings::OPTION, $disabled_settings);
}
if ($mode === 'old-label') {
    $data = ['ids' => [$order->get_id()], 'uid' => get_current_user_id(), 'exp' => time() + 600];
    $payload = rtrim(strtr(base64_encode((string) wp_json_encode($data)), '+/', '-_'), '=');
    $signature = hash_hmac('sha256', $payload, wp_salt('nonce'));
    $query = [
        'action' => 'wsl_render_print',
        'payload' => $payload,
        'signature' => $signature,
        '_wsl_nonce' => wp_create_nonce('wsl_render_' . $signature),
    ];
} else {
    $document = ($mode === 'invoice' || str_starts_with($mode, 'invoice-')) ? 'invoice' : 'label';
    $url = WSL_Print_Controller::create_url([$order->get_id()], $document);
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
}
if (empty($query['payload']) || empty($query['signature']) || isset($query['token'])) {
    fwrite(STDERR, "SIGNED_REQUEST_CREATE=FAIL\n");
    $order->delete(true);
    exit(1);
}
$_GET = $query;
$_REQUEST = $query;

$GLOBALS['wsl_endpoint_html'] = '';
ob_start(static function (string $buffer): string {
    $GLOBALS['wsl_endpoint_html'] .= $buffer;
    return '';
});
register_shutdown_function(static function () use ($order, $mode, $previous_settings): void {
    $html = (string) ($GLOBALS['wsl_endpoint_html'] ?? '');
    if (ob_get_level() > 0) {
        $html .= (string) ob_get_contents();
        ob_end_clean();
    }
    $fresh = wc_get_order($order->get_id());
    if ($fresh) {
        $fresh->delete(true);
    }
    if ($previous_settings === null) {
        delete_option(WSL_Settings::OPTION);
    } else {
        update_option(WSL_Settings::OPTION, $previous_settings);
    }
    if ($mode === 'invoice-disabled') {
        $valid = str_contains($html, 'این بخش چاپ در تنظیمات افزونه غیرفعال است');
    } elseif ($mode === 'invoice') {
        $valid = str_contains($html, 'wsl-invoice-document')
            && str_contains($html, 'گیرنده آزمایشی')
            && !str_contains($html, 'درخواست چاپ منقضی یا نامعتبر است');
    } else {
        $valid = str_contains($html, 'wsl-document')
            && str_contains($html, 'گیرنده آزمایشی')
            && !str_contains($html, 'پیوندی که شما دنبال می‌کردید منقضی شده است');
    }
    $label = strtoupper(str_replace('-', '_', $mode));
    fwrite($valid ? STDOUT : STDERR, $valid ? "ENDPOINT_{$label}=PASS\n" : "ENDPOINT_{$label}=FAIL\n");
    if (!$valid) {
        fwrite(STDERR, substr(wp_strip_all_tags($html), 0, 1200) . PHP_EOL);
        http_response_code(500);
    }
});

WSL_Print_Controller::render();
