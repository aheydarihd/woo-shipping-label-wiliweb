<?php defined('ABSPATH') || defined('WSL_TESTING') || exit; ?>
<article class="wsl-invoice wsl-invoice--modern">
    <header class="wsl-invoice__header">
        <div class="wsl-invoice__brand">
            <?php if (WSL_Invoice_Renderer::enabled($settings, 'logo') && $logo_url !== '') : ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($invoice['seller']['name'] ?? ''); ?>">
            <?php endif; ?>
            <div><strong><?php echo esc_html($invoice['seller']['name'] ?? ''); ?></strong><small><?php echo esc_html($invoice['seller']['website'] ?? ''); ?></small></div>
        </div>
        <div class="wsl-invoice__heading">
            <h1><?php echo esc_html($title); ?></h1>
            <?php echo WSL_Invoice_Renderer::row($settings, 'order_number', 'شماره سفارش', $invoice['order']['number'] ?? ''); ?>
            <?php echo WSL_Invoice_Renderer::row($settings, 'order_date', 'تاریخ', $invoice['order']['date'] ?? ''); ?>
            <?php echo WSL_Invoice_Renderer::row($settings, 'order_status', 'وضعیت', $invoice['order']['status'] ?? ''); ?>
        </div>
    </header>

    <div class="wsl-invoice__parties">
        <section class="wsl-invoice-party wsl-invoice__seller">
            <h2>اطلاعات فروشنده</h2>
            <?php echo WSL_Invoice_Renderer::row($settings, 'seller_contact', 'تلفن', $invoice['seller']['phone'] ?? ''); ?>
            <?php echo WSL_Invoice_Renderer::row($settings, 'seller_address', 'نشانی', $invoice['seller']['address'] ?? ''); ?>
            <?php echo WSL_Invoice_Renderer::row($settings, 'seller_address', 'کدپستی', $invoice['seller']['postcode'] ?? ''); ?>
        </section>
        <section class="wsl-invoice-party wsl-invoice__buyer">
            <h2>اطلاعات خریدار</h2>
            <div class="wsl-invoice-row"><span>نام</span><strong><?php echo esc_html($invoice['buyer']['name'] ?? ''); ?></strong></div>
            <?php echo WSL_Invoice_Renderer::row($settings, 'buyer_phone', 'موبایل', $invoice['buyer']['phone'] ?? ''); ?>
            <?php echo WSL_Invoice_Renderer::row($settings, 'buyer_email', 'ایمیل', $invoice['buyer']['email'] ?? ''); ?>
            <?php echo WSL_Invoice_Renderer::row($settings, 'buyer_address', 'نشانی', WSL_Invoice_Renderer::buyer_address($invoice['buyer'] ?? [])); ?>
            <?php echo WSL_Invoice_Renderer::row($settings, 'buyer_postcode', 'کدپستی', $invoice['buyer']['postcode'] ?? ''); ?>
        </section>
    </div>

    <table class="wsl-invoice__items">
        <thead><tr>
            <th class="wsl-invoice__index">ردیف</th>
            <?php if (WSL_Invoice_Renderer::enabled($settings, 'product_image')) : ?><th class="wsl-invoice__image">تصویر</th><?php endif; ?>
            <th>شرح محصول</th>
            <?php if (WSL_Invoice_Renderer::enabled($settings, 'quantity')) : ?><th>تعداد</th><?php endif; ?>
            <?php if (WSL_Invoice_Renderer::enabled($settings, 'unit_price')) : ?><th>قیمت واحد</th><?php endif; ?>
            <?php if (WSL_Invoice_Renderer::enabled($settings, 'line_discount')) : ?><th>تخفیف</th><?php endif; ?>
            <?php if (WSL_Invoice_Renderer::enabled($settings, 'line_total')) : ?><th>مبلغ</th><?php endif; ?>
        </tr></thead>
        <tbody>
        <?php foreach (($invoice['items'] ?? []) as $index => $item) : ?>
            <tr class="wsl-invoice-item">
                <td class="wsl-invoice__index"><?php echo esc_html($index + 1); ?></td>
                <?php if (WSL_Invoice_Renderer::enabled($settings, 'product_image')) : ?><td class="wsl-invoice__image"><?php if (($item['image_url'] ?? '') !== '') : ?><img src="<?php echo esc_url($item['image_url']); ?>" alt=""><?php else : ?><span class="wsl-invoice__placeholder">—</span><?php endif; ?></td><?php endif; ?>
                <td><strong><?php echo esc_html($item['name'] ?? ''); ?></strong>
                    <?php if (WSL_Invoice_Renderer::enabled($settings, 'sku') && ($item['sku'] ?? '') !== '') : ?><small>شناسه: <?php echo esc_html($item['sku']); ?></small><?php endif; ?>
                    <?php if (WSL_Invoice_Renderer::enabled($settings, 'variation') && ($item['variation'] ?? '') !== '') : ?><small><?php echo esc_html($item['variation']); ?></small><?php endif; ?>
                </td>
                <?php if (WSL_Invoice_Renderer::enabled($settings, 'quantity')) : ?><td><?php echo esc_html($item['quantity'] ?? 0); ?></td><?php endif; ?>
                <?php if (WSL_Invoice_Renderer::enabled($settings, 'unit_price')) : ?><td><?php echo esc_html($item['unit_price']['formatted'] ?? ''); ?></td><?php endif; ?>
                <?php if (WSL_Invoice_Renderer::enabled($settings, 'line_discount')) : ?><td><?php echo esc_html($item['discount']['formatted'] ?? ''); ?></td><?php endif; ?>
                <?php if (WSL_Invoice_Renderer::enabled($settings, 'line_total')) : ?><td><strong><?php echo esc_html($item['total']['formatted'] ?? ''); ?></strong></td><?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="wsl-invoice__footer-grid">
        <div class="wsl-invoice__details">
            <?php echo WSL_Invoice_Renderer::row($settings, 'payment_method', 'روش پرداخت', $invoice['order']['payment_method'] ?? ''); ?>
            <?php echo WSL_Invoice_Renderer::row($settings, 'shipping_method', 'روش ارسال', $invoice['order']['shipping_method'] ?? ''); ?>
            <?php echo WSL_Invoice_Renderer::row($settings, 'customer_note', 'یادداشت مشتری', $invoice['notes']['customer'] ?? ''); ?>
            <?php echo WSL_Invoice_Renderer::row($settings, 'footer_note', 'پیام فروشگاه', $invoice['notes']['footer'] ?? ''); ?>
        </div>
        <aside class="wsl-invoice__totals">
            <?php echo WSL_Invoice_Renderer::total_row($settings, 'subtotal', 'جمع جزء', $invoice['totals']['subtotal'] ?? []); ?>
            <?php echo WSL_Invoice_Renderer::total_row($settings, 'discount_total', 'تخفیف', $invoice['totals']['discount'] ?? []); ?>
            <?php echo WSL_Invoice_Renderer::total_row($settings, 'shipping_total', 'هزینه ارسال', $invoice['totals']['shipping'] ?? []); ?>
            <?php echo WSL_Invoice_Renderer::fee_rows($settings, $invoice['totals']['fees'] ?? []); ?>
            <?php echo WSL_Invoice_Renderer::total_row($settings, 'tax_total', 'مالیات', $invoice['totals']['tax'] ?? []); ?>
            <?php echo WSL_Invoice_Renderer::total_row($settings, 'refund_total', 'بازپرداخت', $invoice['totals']['refund'] ?? []); ?>
            <?php echo WSL_Invoice_Renderer::total_row($settings, 'grand_total', 'مبلغ نهایی', $invoice['totals']['grand_total'] ?? [], true); ?>
        </aside>
    </div>
    <?php if (WSL_Invoice_Renderer::enabled($settings, 'barcode') && ($invoice['order']['barcode'] ?? '') !== '') : ?><div class="wsl-invoice-barcode"><?php echo WSL_Renderer::barcode((string) $invoice['order']['barcode']); ?></div><?php endif; ?>
    <?php if (WSL_Invoice_Renderer::enabled($settings, 'signature')) : ?><div class="wsl-invoice__signature"><span>مهر و امضای فروشنده</span><span>امضای خریدار</span></div><?php endif; ?>
</article>
