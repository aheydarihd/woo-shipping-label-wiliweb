<?php
defined('ABSPATH') || defined('WSL_TESTING') || exit;

final class WSL_Settings_Page {
    public static function register(): void {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_init', [self::class, 'settings']);
        add_action('admin_enqueue_scripts', [self::class, 'assets']);
        add_filter('option_page_capability_wsl_settings_group', [self::class, 'settings_capability']);
    }

    public static function settings_capability(): string {
        return 'manage_woocommerce';
    }

    public static function menu(): void {
        add_submenu_page('woocommerce', __('فاکتور ساز ویلی وب', 'woo-shipping-label-wiliweb'), __('فاکتور ساز ویلی وب', 'woo-shipping-label-wiliweb'), 'manage_woocommerce', 'wsl-settings', [self::class, 'page']);
    }

    public static function settings(): void {
        register_setting('wsl_settings_group', WSL_Settings::OPTION, ['type' => 'array', 'sanitize_callback' => [WSL_Settings::class, 'sanitize'], 'default' => WSL_Settings::defaults()]);
    }

    public static function assets(string $hook): void {
        if ($hook !== 'woocommerce_page_wsl-settings') {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_style('wsl-admin', WSL_URL . 'assets/css/admin.css', [], WSL_VERSION);
        wp_enqueue_script('wsl-admin', WSL_URL . 'assets/js/admin.js', [], WSL_VERSION, true);
    }

    public static function current_tab($raw = null): string {
        $tab = is_scalar($raw) ? (string) $raw : 'general';
        return in_array($tab, ['general', 'invoice', 'labels'], true) ? $tab : 'general';
    }

    public static function page(): void {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        $settings = WSL_Settings::get();
        $tab = self::current_tab(wp_unslash($_GET['tab'] ?? 'general'));
        $label_logo_url = !empty($settings['sender']['logo_id']) ? wp_get_attachment_image_url((int) $settings['sender']['logo_id'], 'medium') : '';
        $invoice_logo_id = (int) ($settings['invoice']['logo_id'] ?: $settings['sender']['logo_id']);
        $invoice_logo_url = $invoice_logo_id ? wp_get_attachment_image_url($invoice_logo_id, 'medium') : '';
        ?>
        <div class="wrap wsl-admin" dir="rtl">
            <div class="wsl-hero"><div><span class="wsl-eyebrow">WiliWeb Commerce Tools</span><h1>فاکتور ساز ویلی وب</h1><p>فاکتور فروش و لیبل پستی سفارش‌ها را از یک پنل حرفه‌ای مدیریت و چاپ کنید.</p></div><span>نسخه <?php echo esc_html(WSL_VERSION); ?></span></div>
            <nav class="wsl-tabs" aria-label="بخش‌های تنظیمات"><?php foreach (['general' => 'تنظیمات عمومی', 'invoice' => 'فاکتور فروش', 'labels' => 'لیبل پستی'] as $key => $label) : ?><a class="<?php echo $tab === $key ? 'is-active' : ''; ?>" href="<?php echo esc_url('?page=wsl-settings&tab=' . $key); ?>"><?php echo esc_html($label); ?></a><?php endforeach; ?></nav>
            <form action="options.php" method="post">
                <?php settings_fields('wsl_settings_group'); ?>
                <div data-wsl-panel="general"<?php echo $tab === 'general' ? '' : ' hidden'; ?>><?php self::render_general($settings); ?></div>
                <div data-wsl-panel="invoice"<?php echo $tab === 'invoice' ? '' : ' hidden'; ?>><?php self::render_invoice($settings, (string) $invoice_logo_url); ?></div>
                <div data-wsl-panel="labels"<?php echo $tab === 'labels' ? '' : ' hidden'; ?>><?php self::render_labels($settings, (string) $label_logo_url); ?></div>
                <div class="wsl-savebar"><div><strong>تنظیمات فروشگاه</strong><span>تغییرات پس از ذخیره روی چاپ‌های بعدی اعمال می‌شوند.</span></div><?php submit_button('ذخیره تنظیمات', 'primary', 'submit', false); ?></div>
            </form>
        </div>
        <?php
    }

    private static function render_general(array $settings): void {
        ?>
        <section class="wsl-card wsl-card--intro"><div><span class="wsl-section-icon">⚙</span><h2>ماژول‌های فعال</h2><p>هر بخش را مستقل روشن یا خاموش کنید؛ تنظیمات بخش خاموش‌شده نگه داشته می‌شود.</p></div></section>
        <section class="wsl-module-grid">
            <?php foreach (['invoice_enabled' => ['فاکتور فروش', 'چاپ تکی و گروهی فاکتور حرفه‌ای همراه تصویر و جزئیات محصولات', 'فاکتور'], 'labels_enabled' => ['لیبل پستی', 'چاپ لیبل گیرنده و فرستنده با چیدمان دقیق روی کاغذ A4', 'لیبل']] as $key => $module) : ?>
                <label class="wsl-module-card"><input type="checkbox" name="<?php echo WSL_Settings::OPTION; ?>[modules][<?php echo esc_attr($key); ?>]" value="1" <?php checked(!empty($settings['modules'][$key])); ?>><span class="wsl-module-card__body"><span class="wsl-module-card__badge"><?php echo esc_html($module[2]); ?></span><strong><?php echo esc_html($module[0]); ?></strong><small><?php echo esc_html($module[1]); ?></small><i></i></span></label>
            <?php endforeach; ?>
        </section>
        <section class="wsl-card wsl-business-note"><div><h2>آماده توسعه فروشگاه</h2><p>ساختار افزونه برای اضافه‌شدن پیش‌فاکتور، رسید انبار و نسخه حرارتی در نسخه‌های بعدی آماده است؛ بدون تغییر پوشه یا اختلال در آپدیت‌های فعلی.</p></div><span>سازگار با HPOS</span></section>
        <?php
    }

    private static function render_invoice(array $settings, string $logo_url): void {
        $invoice = $settings['invoice'];
        $field_groups = [
            'سربرگ و سفارش' => ['logo' => 'لوگو', 'order_number' => 'شماره سفارش', 'order_date' => 'تاریخ سفارش', 'order_status' => 'وضعیت سفارش', 'barcode' => 'بارکد سفارش'],
            'فروشنده و خریدار' => ['seller_contact' => 'تماس فروشنده', 'seller_address' => 'نشانی فروشنده', 'buyer_phone' => 'موبایل خریدار', 'buyer_email' => 'ایمیل خریدار', 'buyer_address' => 'نشانی خریدار', 'buyer_postcode' => 'کدپستی خریدار'],
            'جدول محصولات' => ['product_image' => 'تصویر محصول', 'sku' => 'شناسه SKU', 'variation' => 'ویژگی‌های محصول', 'unit_price' => 'قیمت واحد', 'quantity' => 'تعداد', 'line_discount' => 'تخفیف ردیف', 'line_total' => 'مبلغ ردیف'],
            'مبالغ و پایین فاکتور' => ['subtotal' => 'جمع جزء', 'discount_total' => 'تخفیف سفارش', 'shipping_total' => 'هزینه ارسال', 'fee_total' => 'هزینه‌های جانبی', 'tax_total' => 'مالیات', 'refund_total' => 'بازپرداخت', 'grand_total' => 'مبلغ نهایی', 'payment_method' => 'روش پرداخت', 'shipping_method' => 'روش ارسال', 'customer_note' => 'یادداشت مشتری', 'footer_note' => 'پیام فروشگاه', 'signature' => 'محل مهر و امضا'],
        ];
        ?>
        <section class="wsl-card"><div class="wsl-card-heading"><div><span class="wsl-eyebrow">قالب چاپ A4</span><h2>ظاهر فاکتور</h2><p>یک طرح فروشگاهی مدرن یا نسخه رسمی و کم‌رنگ را انتخاب کنید.</p></div></div>
            <div class="wsl-template-grid wsl-invoice-templates"><label class="wsl-template"><input type="radio" name="<?php echo WSL_Settings::OPTION; ?>[invoice][template]" value="modern" <?php checked($invoice['template'], 'modern'); ?> data-wsl-invoice-template><span><i class="wsl-template-preview is-modern"></i><b>مدرن فروشگاهی</b><small>برندمحور، تصویری و مناسب آنلاین‌شاپ</small></span></label><label class="wsl-template"><input type="radio" name="<?php echo WSL_Settings::OPTION; ?>[invoice][template]" value="classic" <?php checked($invoice['template'], 'classic'); ?> data-wsl-invoice-template><span><i class="wsl-template-preview is-classic"></i><b>رسمی کلاسیک</b><small>خطی، متراکم و مناسب بایگانی اداری</small></span></label></div>
            <div class="wsl-fields"><label><span>عنوان فاکتور</span><input type="text" name="<?php echo WSL_Settings::OPTION; ?>[invoice][title]" value="<?php echo esc_attr($invoice['title']); ?>"></label><label><span>رنگ برند</span><input type="color" name="<?php echo WSL_Settings::OPTION; ?>[invoice][accent]" value="<?php echo esc_attr($invoice['accent']); ?>"></label><label class="wsl-field-wide"><span>پیام پایین فاکتور</span><input type="text" name="<?php echo WSL_Settings::OPTION; ?>[invoice][footer_note]" value="<?php echo esc_attr($invoice['footer_note']); ?>"></label></div>
        </section>
        <?php self::logo_picker('invoice-logo', WSL_Settings::OPTION . '[invoice][logo_id]', (int) $invoice['logo_id'], $logo_url, 'لوگوی اختصاصی فاکتور', 'اگر انتخاب نشود، لوگوی بخش فرستنده روی فاکتور استفاده می‌شود.'); ?>
        <?php foreach ($field_groups as $title => $fields) : ?><section class="wsl-card"><h2><?php echo esc_html($title); ?></h2><div class="wsl-switches"><?php foreach ($fields as $key => $label) : ?><label><input type="checkbox" name="<?php echo WSL_Settings::OPTION; ?>[invoice][fields][<?php echo esc_attr($key); ?>]" value="1" <?php checked(!empty($invoice['fields'][$key])); ?>><span><?php echo esc_html($label); ?></span></label><?php endforeach; ?></div></section><?php endforeach; ?>
        <section class="wsl-card"><div class="wsl-preview-heading"><div><span class="wsl-eyebrow">پیش‌نمایش نمونه</span><h2>نمای کلی فاکتور</h2></div><small>اطلاعات این پیش‌نمایش نمونه است.</small></div><div class="wsl-invoice-admin-preview" data-wsl-invoice-preview><header><i></i><div><b>فاکتور فروش</b><span>#1042</span></div></header><div class="wsl-invoice-admin-preview__parties"><span></span><span></span></div><div class="wsl-invoice-admin-preview__table"><i></i><i></i><i></i></div><footer><span></span><strong></strong></footer></div></section>
        <?php
    }

    private static function render_labels(array $settings, string $logo_url): void {
        $labels = ['recipient_name'=>'نام گیرنده','recipient_phone'=>'موبایل گیرنده','recipient_province'=>'استان گیرنده','recipient_city'=>'شهر گیرنده','recipient_address'=>'نشانی گیرنده','recipient_postcode'=>'کدپستی گیرنده','sender_name'=>'نام فرستنده','sender_phone'=>'تلفن فرستنده','sender_province'=>'استان فرستنده','sender_city'=>'شهر فرستنده','sender_address'=>'نشانی فرستنده','sender_postcode'=>'کدپستی فرستنده','order_number'=>'شماره سفارش','order_date'=>'تاریخ سفارش','order_status'=>'وضعیت سفارش','shipping_method'=>'روش ارسال','payment_method'=>'روش پرداخت','items'=>'اقلام سفارش','order_total'=>'مبلغ سفارش','customer_note'=>'یادداشت مشتری','recipient_email'=>'ایمیل گیرنده','website'=>'وب‌سایت','logo'=>'لوگو','barcode'=>'بارکد'];
        ?>
        <section class="wsl-card"><h2>قالب و چیدمان لیبل</h2><div class="wsl-template-grid"><label class="wsl-template"><input type="radio" name="<?php echo WSL_Settings::OPTION; ?>[template]" value="table" <?php checked($settings['template'], 'table'); ?> data-wsl-label-template><span><b>جدولی</b><small>تفکیک واضح فرستنده و گیرنده</small></span></label><label class="wsl-template"><input type="radio" name="<?php echo WSL_Settings::OPTION; ?>[template]" value="simple" <?php checked($settings['template'], 'simple'); ?> data-wsl-label-template><span><b>ساده</b><small>فشرده و کم‌مصرف</small></span></label><label class="wsl-template"><input type="radio" name="<?php echo WSL_Settings::OPTION; ?>[template]" value="postal" <?php checked($settings['template'], 'postal'); ?> data-wsl-label-template><span><b>پستی رنگی بزرگ</b><small>دو لیبل درشت با حاشیه پستی روی هر A4</small></span></label></div>
            <div class="wsl-layout-options"><label>تعداد لیبل در هر A4<select name="<?php echo WSL_Settings::OPTION; ?>[layout][per_page]" data-wsl-layout><?php foreach ([1,2,4,6,8] as $count) : ?><option value="<?php echo esc_attr($count); ?>" <?php selected($settings['layout']['per_page'], $count); ?>><?php echo esc_html($count); ?> لیبل</option><?php endforeach; ?></select></label><fieldset class="wsl-orientation"><legend>جهت خود لیبل</legend><label><input type="radio" name="<?php echo WSL_Settings::OPTION; ?>[layout][orientation]" value="portrait" data-wsl-orientation <?php checked($settings['layout']['orientation'], 'portrait'); ?>><span>عمودی</span></label><label><input type="radio" name="<?php echo WSL_Settings::OPTION; ?>[layout][orientation]" value="landscape" data-wsl-orientation <?php checked($settings['layout']['orientation'], 'landscape'); ?>><span>افقی</span></label></fieldset></div>
            <details class="wsl-advanced"><summary>تنظیم دقیق اندازه‌ها (میلی‌متر)</summary><div class="wsl-fields"><?php foreach (['label_width'=>'عرض لیبل','label_height'=>'ارتفاع لیبل','margin_top'=>'حاشیه بالا','margin_right'=>'حاشیه راست','margin_bottom'=>'حاشیه پایین','margin_left'=>'حاشیه چپ','gap_horizontal'=>'فاصله افقی','gap_vertical'=>'فاصله عمودی'] as $key=>$title) : ?><label><span><?php echo esc_html($title); ?></span><input type="number" min="0" max="297" step="0.1" data-wsl-size="<?php echo esc_attr($key); ?>" name="<?php echo WSL_Settings::OPTION; ?>[layout][<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($settings['layout'][$key]); ?>"></label><?php endforeach; ?></div></details><div class="wsl-preview" data-wsl-preview data-count="<?php echo (int) $settings['layout']['per_page']; ?>"></div>
        </section>
        <section class="wsl-card"><h2>اطلاعات فرستنده</h2><div class="wsl-fields"><?php foreach (['name'=>'نام فروشگاه یا فرستنده','phone'=>'شماره تماس','province'=>'استان','city'=>'شهر','address'=>'نشانی کامل','postcode'=>'کدپستی','website'=>'وب‌سایت'] as $key=>$title) : ?><label><span><?php echo esc_html($title); ?></span><input type="text" name="<?php echo WSL_Settings::OPTION; ?>[sender][<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($settings['sender'][$key]); ?>"></label><?php endforeach; ?></div></section>
        <?php self::logo_picker('sender-logo', WSL_Settings::OPTION . '[sender][logo_id]', (int) $settings['sender']['logo_id'], $logo_url, 'لوگوی فرستنده و لیبل', 'تصویر را مستقیم از کتابخانه رسانه انتخاب کنید.'); ?>
        <section class="wsl-card"><h2>موارد قابل نمایش روی لیبل</h2><div class="wsl-switches"><?php foreach ($labels as $key=>$title) : ?><label class="<?php echo str_starts_with($key, 'recipient_') || str_starts_with($key, 'sender_') ? 'is-core' : ''; ?>"><input type="checkbox" name="<?php echo WSL_Settings::OPTION; ?>[fields][<?php echo esc_attr($key); ?>]" value="1" <?php checked(!empty($settings['fields'][$key])); ?>><span><?php echo esc_html($title); ?></span></label><?php endforeach; ?></div></section>
        <section class="wsl-card"><h2>ظاهر چاپ لیبل</h2><div class="wsl-fields"><label><span>رنگ اصلی</span><input type="color" name="<?php echo WSL_Settings::OPTION; ?>[accent]" value="<?php echo esc_attr($settings['accent']); ?>"></label><label><span>اندازه متن</span><input type="number" min="8" max="16" name="<?php echo WSL_Settings::OPTION; ?>[font_size]" value="<?php echo (int) $settings['font_size']; ?>"></label><label><span>عرض لوگوی لیبل (میلی‌متر)</span><input type="number" min="15" max="60" step="1" name="<?php echo WSL_Settings::OPTION; ?>[logo_width]" value="<?php echo (int) $settings['logo_width']; ?>"></label><label class="wsl-check"><input type="checkbox" name="<?php echo WSL_Settings::OPTION; ?>[fields][print_date]" value="1" <?php checked(!empty($settings['fields']['print_date'])); ?>><span>نمایش تاریخ چاپ</span></label><label class="wsl-check"><input type="checkbox" name="<?php echo WSL_Settings::OPTION; ?>[show_border]" value="1" <?php checked($settings['show_border']); ?>><span>نمایش کادر لیبل</span></label></div></section>
        <?php
    }

    private static function logo_picker(string $id, string $name, int $value, string $url, string $title, string $description): void {
        ?>
        <section class="wsl-card"><div class="wsl-logo-picker" data-wsl-media-picker><div class="wsl-logo-preview<?php echo $url ? ' has-image' : ''; ?>" data-wsl-logo-preview><img src="<?php echo esc_url($url); ?>" alt="پیش‌نمایش لوگو"<?php echo $url ? '' : ' hidden'; ?>><span<?php echo $url ? ' hidden' : ''; ?>>هنوز لوگویی انتخاب نشده</span></div><div><strong><?php echo esc_html($title); ?></strong><p class="description"><?php echo esc_html($description); ?></p><input id="<?php echo esc_attr($id); ?>" type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" data-wsl-logo-id><button type="button" class="button button-secondary" data-wsl-logo-select><?php echo $url ? 'تغییر لوگو' : 'انتخاب لوگو'; ?></button><button type="button" class="button button-link-delete" data-wsl-logo-remove<?php echo $url ? '' : ' hidden'; ?>>حذف لوگو</button></div></div></section>
        <?php
    }
}
