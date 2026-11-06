<?php defined('ABSPATH') || defined('WSL_TESTING') || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php esc_html_e('پیش‌نمایش فاکتور فروش', 'woo-shipping-label-wiliweb'); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url(WSL_URL . 'assets/css/invoice-print.css?ver=' . WSL_VERSION); ?>">
    <style>:root{--wsl-invoice-accent:<?php echo esc_attr($settings['invoice']['accent'] ?? '#155eef'); ?>}</style>
</head>
<body class="wsl-invoice-page">
    <div class="wsl-toolbar wsl-invoice-toolbar"><div><strong><?php echo esc_html(count($invoices)); ?> فاکتور آماده چاپ</strong><span>کاغذ A4، مقیاس ۱۰۰٪ و حاشیه مرورگر روی «هیچ» باشد.</span></div><button type="button" data-wsl-print>چاپ فاکتورها</button></div>
    <?php if ($skipped > 0) : ?><div class="wsl-alert"><?php echo esc_html($skipped === 1 ? 'یک سفارش' : $skipped . ' سفارش'); ?> به‌دلیل دسترسی یا اعتبار نامناسب حذف شد.</div><?php endif; ?>
    <main class="wsl-invoice-stack">
        <?php foreach ($invoices as $invoice) : ?><section class="wsl-invoice-document"><?php echo WSL_Invoice_Renderer::invoice($invoice, $settings); ?></section><?php endforeach; ?>
    </main>
    <script src="<?php echo esc_url(WSL_URL . 'assets/js/print.js?ver=' . WSL_VERSION); ?>"></script>
</body>
</html>
