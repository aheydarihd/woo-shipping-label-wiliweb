<?php defined('ABSPATH') || defined('WSL_TESTING') || exit; ?>
<article class="wsl-label wsl-label--simple">
    <div class="wsl-simple__order"><?php echo WSL_Renderer::row($settings, 'order_number', 'سفارش', $label['optional']['order_number']); ?></div>
    <section>
        <h2>گیرنده</h2>
        <?php echo WSL_Renderer::row($settings, 'recipient_name', 'نام', $label['recipient']['name']); ?>
        <?php echo WSL_Renderer::row($settings, 'recipient_phone', 'موبایل', $label['recipient']['phone']); ?>
        <?php echo WSL_Renderer::row($settings, 'recipient_province', 'استان', $label['recipient']['province']); ?>
        <?php echo WSL_Renderer::row($settings, 'recipient_city', 'شهر', $label['recipient']['city']); ?>
        <?php echo WSL_Renderer::row($settings, 'recipient_address', 'نشانی', $label['recipient']['address']); ?>
        <?php echo WSL_Renderer::row($settings, 'recipient_postcode', 'کدپستی', $label['recipient']['postcode']); ?>
    </section>
    <section class="wsl-simple__sender">
        <h2>فرستنده</h2>
        <?php echo WSL_Renderer::row($settings, 'sender_name', 'نام', $label['sender']['name']); ?>
        <?php echo WSL_Renderer::row($settings, 'sender_phone', 'تلفن', $label['sender']['phone']); ?>
        <?php echo WSL_Renderer::row($settings, 'sender_address', 'نشانی', trim($label['sender']['province'].'، '.$label['sender']['city'].'، '.$label['sender']['address'], '، ')); ?>
        <?php echo WSL_Renderer::row($settings, 'sender_postcode', 'کدپستی', $label['sender']['postcode']); ?>
    </section>
    <div class="wsl-meta">
        <?php echo WSL_Renderer::row($settings, 'shipping_method', 'ارسال', $label['optional']['shipping_method']); ?>
        <?php echo WSL_Renderer::row($settings, 'payment_method', 'پرداخت', $label['optional']['payment_method']); ?>
        <?php echo WSL_Renderer::row($settings, 'items', 'اقلام', $label['optional']['items']); ?>
    </div>
    <?php if (WSL_Renderer::enabled($settings, 'barcode') && $label['optional']['barcode'] !== '') : ?>
        <div class="wsl-barcode-wrap"><?php echo WSL_Renderer::barcode($label['optional']['barcode']); ?></div>
    <?php endif; ?>
</article>
