<?php defined('ABSPATH') || defined('WSL_TESTING') || exit; ?>
<article class="wsl-label wsl-label--table">
    <header class="wsl-label__header">
        <div>
            <?php if (WSL_Renderer::enabled($settings, 'logo') && !empty($label['optional']['logo_id'])) :
                $logo = wp_get_attachment_image_url($label['optional']['logo_id'], 'medium'); ?>
                <?php if ($logo) : ?><img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($label['sender']['name']); ?>"><?php endif; ?>
            <?php endif; ?>
            <strong><?php echo esc_html($label['sender']['name']); ?></strong>
        </div>
        <?php echo WSL_Renderer::row($settings, 'order_number', 'شماره سفارش', $label['optional']['order_number']); ?>
    </header>
    <div class="wsl-table">
        <section class="wsl-party wsl-party--recipient">
            <h2>گیرنده</h2>
            <?php echo WSL_Renderer::row($settings, 'recipient_name', 'نام', $label['recipient']['name']); ?>
            <?php echo WSL_Renderer::row($settings, 'recipient_phone', 'موبایل', $label['recipient']['phone']); ?>
            <?php echo WSL_Renderer::row($settings, 'recipient_province', 'استان', $label['recipient']['province']); ?>
            <?php echo WSL_Renderer::row($settings, 'recipient_city', 'شهر', $label['recipient']['city']); ?>
            <?php echo WSL_Renderer::row($settings, 'recipient_address', 'نشانی', $label['recipient']['address']); ?>
            <?php echo WSL_Renderer::row($settings, 'recipient_postcode', 'کدپستی', $label['recipient']['postcode']); ?>
        </section>
        <section class="wsl-party wsl-party--sender">
            <h2>فرستنده</h2>
            <?php echo WSL_Renderer::row($settings, 'sender_name', 'نام', $label['sender']['name']); ?>
            <?php echo WSL_Renderer::row($settings, 'sender_phone', 'تلفن', $label['sender']['phone']); ?>
            <?php echo WSL_Renderer::row($settings, 'sender_province', 'استان', $label['sender']['province']); ?>
            <?php echo WSL_Renderer::row($settings, 'sender_city', 'شهر', $label['sender']['city']); ?>
            <?php echo WSL_Renderer::row($settings, 'sender_address', 'نشانی', $label['sender']['address']); ?>
            <?php echo WSL_Renderer::row($settings, 'sender_postcode', 'کدپستی', $label['sender']['postcode']); ?>
        </section>
    </div>
    <div class="wsl-meta">
        <?php echo WSL_Renderer::row($settings, 'shipping_method', 'ارسال', $label['optional']['shipping_method']); ?>
        <?php echo WSL_Renderer::row($settings, 'payment_method', 'پرداخت', $label['optional']['payment_method']); ?>
        <?php echo WSL_Renderer::row($settings, 'order_status', 'وضعیت', $label['optional']['order_status']); ?>
        <?php echo WSL_Renderer::row($settings, 'order_date', 'تاریخ سفارش', $label['optional']['order_date']); ?>
        <?php echo WSL_Renderer::row($settings, 'print_date', 'تاریخ چاپ', $label['optional']['print_date']); ?>
        <?php echo WSL_Renderer::row($settings, 'items', 'اقلام', $label['optional']['items']); ?>
        <?php echo WSL_Renderer::row($settings, 'order_total', 'مبلغ', $label['optional']['order_total']); ?>
        <?php echo WSL_Renderer::row($settings, 'customer_note', 'یادداشت', $label['optional']['customer_note']); ?>
        <?php echo WSL_Renderer::row($settings, 'recipient_email', 'ایمیل', $label['optional']['recipient_email']); ?>
        <?php echo WSL_Renderer::row($settings, 'website', 'وب‌سایت', $label['optional']['website']); ?>
    </div>
    <?php if (WSL_Renderer::enabled($settings, 'barcode') && $label['optional']['barcode'] !== '') : ?>
        <div class="wsl-barcode-wrap"><?php echo WSL_Renderer::barcode($label['optional']['barcode']); ?></div>
    <?php endif; ?>
</article>
