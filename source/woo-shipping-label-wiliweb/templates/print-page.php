<?php defined('ABSPATH') || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php esc_html_e('پیش‌نمایش لیبل‌های پستی', 'woo-shipping-label-wiliweb'); ?></title>
    <?php if (function_exists('get_template_directory_uri')) : ?>
        <link rel="stylesheet" href="<?php echo esc_url(trailingslashit(get_template_directory_uri()) . 'style.css'); ?>">
        <?php if (function_exists('get_stylesheet_uri') && get_stylesheet_uri() !== trailingslashit(get_template_directory_uri()) . 'style.css') : ?>
            <link rel="stylesheet" href="<?php echo esc_url(get_stylesheet_uri()); ?>">
        <?php endif; ?>
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo esc_url(WSL_URL . 'assets/css/print.css?ver=' . WSL_VERSION); ?>">
    <style>
        :root{
            --wsl-columns:<?php echo (int) $layout['columns']; ?>;
            --wsl-rows:<?php echo (int) $layout['rows']; ?>;
            --wsl-margin-top:<?php echo esc_attr($layout['margin_top']); ?>mm;
            --wsl-margin-right:<?php echo esc_attr($layout['margin_right']); ?>mm;
            --wsl-margin-bottom:<?php echo esc_attr($layout['margin_bottom']); ?>mm;
            --wsl-margin-left:<?php echo esc_attr($layout['margin_left']); ?>mm;
            --wsl-gap-x:<?php echo esc_attr($layout['gap_horizontal']); ?>mm;
            --wsl-gap-y:<?php echo esc_attr($layout['gap_vertical']); ?>mm;
            --wsl-label-width:<?php echo esc_attr($layout['label_width']); ?>mm;
            --wsl-label-height:<?php echo esc_attr($layout['label_height']); ?>mm;
            --wsl-accent:<?php echo esc_attr($settings['accent']); ?>;
            --wsl-font-size:<?php echo (int) $settings['font_size']; ?>px;
            --wsl-logo-width:<?php echo (int) ($settings['logo_width'] ?? 42); ?>mm;
            --wsl-border:<?php echo !empty($settings['show_border']) ? '1px solid #273244' : '0'; ?>;
        }
    </style>
</head>
<body>
    <div class="wsl-toolbar">
        <div>
            <strong><?php echo esc_html(count($labels)); ?> لیبل آماده چاپ</strong>
            <span>کاغذ A4، مقیاس ۱۰۰٪ و حاشیه مرورگر روی «هیچ» باشد.</span>
        </div>
        <button type="button" data-wsl-print>چاپ لیبل‌ها</button>
    </div>
    <?php if ($skipped > 0) : ?><div class="wsl-alert"><?php echo esc_html($skipped); ?> سفارش به‌دلیل دسترسی یا اعتبار نامناسب حذف شد.</div><?php endif; ?>
    <?php foreach ($labels as $entry) :
        $missing = array_keys(array_filter($entry['recipient'], static fn($value) => trim((string) $value) === ''));
        if ($missing) : ?><div class="wsl-alert">سفارش <?php echo esc_html($entry['optional']['order_number']); ?> اطلاعات گیرنده ناقص دارد.</div><?php endif;
    endforeach; ?>
    <main class="wsl-document">
        <?php foreach ($sheets as $sheet) : ?>
            <section class="wsl-sheet">
                <?php foreach ($sheet as $label) { echo WSL_Renderer::label($label, $settings); } ?>
            </section>
        <?php endforeach; ?>
    </main>
    <script src="<?php echo esc_url(WSL_URL . 'assets/js/print.js?ver=' . WSL_VERSION); ?>"></script>
</body>
</html>
