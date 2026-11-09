<?php defined('ABSPATH') || defined('WSL_TESTING') || exit; ?>
<article class="wsl-label wsl-label--postal">
    <div class="wsl-postal__inner">
        <div class="wsl-postal__body">
            <section class="wsl-postal__recipient">
                <div class="wsl-postal__section-title"><span>مشخصات گیرنده</span><small>RECIPIENT</small></div>
                <div class="wsl-postal__recipient-fields">
                    <?php echo WSL_Renderer::row($settings, 'recipient_name', 'نام کامل', $label['recipient']['name'] ?? ''); ?>
                    <?php echo WSL_Renderer::row($settings, 'recipient_province', 'استان', $label['recipient']['province'] ?? ''); ?>
                    <?php echo WSL_Renderer::row($settings, 'recipient_city', 'شهر', $label['recipient']['city'] ?? ''); ?>
                    <?php echo WSL_Renderer::row($settings, 'recipient_address', 'نشانی', $label['recipient']['address'] ?? ''); ?>
                    <?php echo WSL_Renderer::row($settings, 'recipient_postcode', 'کدپستی', $label['recipient']['postcode'] ?? ''); ?>
                    <?php echo WSL_Renderer::row($settings, 'recipient_phone', 'تلفن', $label['recipient']['phone'] ?? ''); ?>
                </div>
                <?php if (WSL_Renderer::enabled($settings, 'barcode') && ($label['optional']['barcode'] ?? '') !== '') : ?>
                    <div class="wsl-postal__barcode"><?php echo WSL_Renderer::barcode((string) $label['optional']['barcode']); ?></div>
                <?php endif; ?>
            </section>
            <section class="wsl-postal__sender">
                <?php if (WSL_Renderer::enabled($settings, 'logo') && !empty($label['optional']['logo_id'])) :
                    $logo = wp_get_attachment_image_url((int) $label['optional']['logo_id'], 'medium'); ?>
                    <?php if ($logo) : ?><div class="wsl-postal__logo"><img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($label['sender']['name'] ?? ''); ?>"></div><?php endif; ?>
                <?php endif; ?>
                <div class="wsl-postal__section-title"><span>مشخصات فرستنده</span><small>SENDER</small></div>
                <div class="wsl-postal__sender-fields">
                    <?php echo WSL_Renderer::row($settings, 'sender_name', 'نام', $label['sender']['name'] ?? ''); ?>
                    <?php echo WSL_Renderer::row($settings, 'sender_province', 'استان', $label['sender']['province'] ?? ''); ?>
                    <?php echo WSL_Renderer::row($settings, 'sender_city', 'شهر', $label['sender']['city'] ?? ''); ?>
                    <?php echo WSL_Renderer::row($settings, 'sender_address', 'نشانی', $label['sender']['address'] ?? ''); ?>
                    <?php echo WSL_Renderer::row($settings, 'sender_postcode', 'کدپستی', $label['sender']['postcode'] ?? ''); ?>
                    <?php echo WSL_Renderer::row($settings, 'sender_phone', 'تلفن', $label['sender']['phone'] ?? ''); ?>
                    <?php echo WSL_Renderer::row($settings, 'website', 'وب‌سایت', $label['optional']['website'] ?? ''); ?>
                </div>
            </section>
        </div>
        <footer class="wsl-postal__footer">
            <?php echo WSL_Renderer::row($settings, 'shipping_method', 'روش ارسال', $label['optional']['shipping_method'] ?? ''); ?>
            <?php echo WSL_Renderer::row($settings, 'order_number', 'شماره سفارش', $label['optional']['order_number'] ?? ''); ?>
            <?php echo WSL_Renderer::row($settings, 'print_date', 'تاریخ چاپ', $label['optional']['print_date'] ?? ''); ?>
        </footer>
    </div>
</article>
