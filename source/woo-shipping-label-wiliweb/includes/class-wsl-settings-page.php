<?php
defined('ABSPATH') || exit;

final class WSL_Settings_Page {
    public static function register(): void {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_init', [self::class, 'settings']);
        add_action('admin_enqueue_scripts', [self::class, 'assets']);
    }

    public static function menu(): void {
        add_submenu_page('woocommerce', __('لیبل پستی', 'woo-shipping-label-wiliweb'), __('لیبل پستی', 'woo-shipping-label-wiliweb'), 'manage_woocommerce', 'wsl-settings', [self::class, 'page']);
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

    public static function page(): void {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        $s = WSL_Settings::get();
        $logo_url = !empty($s['sender']['logo_id']) ? wp_get_attachment_image_url((int) $s['sender']['logo_id'], 'medium') : '';
        $labels = [
            'recipient_name'=>'نام گیرنده','recipient_phone'=>'موبایل گیرنده','recipient_province'=>'استان گیرنده','recipient_city'=>'شهر گیرنده','recipient_address'=>'نشانی گیرنده','recipient_postcode'=>'کدپستی گیرنده',
            'sender_name'=>'نام فرستنده','sender_phone'=>'تلفن فرستنده','sender_province'=>'استان فرستنده','sender_city'=>'شهر فرستنده','sender_address'=>'نشانی فرستنده','sender_postcode'=>'کدپستی فرستنده',
            'order_number'=>'شماره سفارش','order_date'=>'تاریخ سفارش','print_date'=>'تاریخ چاپ','order_status'=>'وضعیت سفارش','shipping_method'=>'روش ارسال','payment_method'=>'روش پرداخت','items'=>'اقلام سفارش','order_total'=>'مبلغ سفارش','customer_note'=>'یادداشت مشتری','recipient_email'=>'ایمیل گیرنده','website'=>'وب‌سایت','logo'=>'لوگو','barcode'=>'بارکد',
        ];
        ?>
        <div class="wrap wsl-admin" dir="rtl">
            <div class="wsl-hero"><div><h1>لیبل پستی WiliWeb</h1><p>چیدمان چاپ سفارش‌ها را دقیق و مستقل از قالب فروشگاه تنظیم کنید.</p></div><span>نسخه <?php echo esc_html(WSL_VERSION); ?></span></div>
            <form action="options.php" method="post">
                <?php settings_fields('wsl_settings_group'); ?>
                <section class="wsl-card"><h2>قالب و چیدمان</h2>
                    <div class="wsl-template-grid">
                        <label class="wsl-template"><input type="radio" name="<?php echo WSL_Settings::OPTION; ?>[template]" value="table" <?php checked($s['template'], 'table'); ?>><span><b>جدولی</b><small>تفکیک واضح فرستنده و گیرنده</small></span></label>
                        <label class="wsl-template"><input type="radio" name="<?php echo WSL_Settings::OPTION; ?>[template]" value="simple" <?php checked($s['template'], 'simple'); ?>><span><b>ساده</b><small>فشرده و کم‌مصرف</small></span></label>
                    </div>
                    <div class="wsl-layout-options">
                        <label>تعداد لیبل در هر A4<select name="<?php echo WSL_Settings::OPTION; ?>[layout][per_page]" data-wsl-layout><?php foreach ([1,2,4,6,8] as $count) : ?><option value="<?php echo $count; ?>" <?php selected($s['layout']['per_page'], $count); ?>><?php echo esc_html($count); ?> لیبل</option><?php endforeach; ?></select></label>
                        <fieldset class="wsl-orientation"><legend>جهت خود لیبل</legend>
                            <label><input type="radio" name="<?php echo WSL_Settings::OPTION; ?>[layout][orientation]" value="portrait" data-wsl-orientation <?php checked($s['layout']['orientation'], 'portrait'); ?>><span>عمودی</span></label>
                            <label><input type="radio" name="<?php echo WSL_Settings::OPTION; ?>[layout][orientation]" value="landscape" data-wsl-orientation <?php checked($s['layout']['orientation'], 'landscape'); ?>><span>افقی</span></label>
                        </fieldset>
                    </div>
                    <details class="wsl-advanced"><summary>تنظیم دقیق اندازه‌ها (میلی‌متر)</summary><div class="wsl-fields">
                        <?php foreach (['label_width'=>'عرض لیبل','label_height'=>'ارتفاع لیبل','margin_top'=>'حاشیه بالا','margin_right'=>'حاشیه راست','margin_bottom'=>'حاشیه پایین','margin_left'=>'حاشیه چپ','gap_horizontal'=>'فاصله افقی','gap_vertical'=>'فاصله عمودی'] as $key=>$title) : ?>
                            <label><span><?php echo esc_html($title); ?></span><input type="number" min="0" max="297" step="0.1" data-wsl-size="<?php echo esc_attr($key); ?>" name="<?php echo WSL_Settings::OPTION; ?>[layout][<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($s['layout'][$key]); ?>"></label>
                        <?php endforeach; ?>
                    </div><p class="description">با تغییر تعداد، اندازه استاندارد همان چیدمان جایگزین می‌شود؛ بعد می‌توانید اعداد را دقیق‌تر تنظیم کنید.</p></details>
                    <div class="wsl-preview" data-wsl-preview data-count="<?php echo (int) $s['layout']['per_page']; ?>"></div>
                </section>
                <section class="wsl-card"><h2>اطلاعات فرستنده</h2><div class="wsl-fields">
                    <?php foreach (['name'=>'نام فروشگاه یا فرستنده','phone'=>'شماره تماس','province'=>'استان','city'=>'شهر','address'=>'نشانی کامل','postcode'=>'کدپستی','website'=>'وب‌سایت'] as $key=>$title) : ?>
                        <label><span><?php echo esc_html($title); ?></span><input type="text" name="<?php echo WSL_Settings::OPTION; ?>[sender][<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($s['sender'][$key]); ?>"></label>
                    <?php endforeach; ?>
                </div>
                    <div class="wsl-logo-picker">
                        <div class="wsl-logo-preview<?php echo $logo_url ? ' has-image' : ''; ?>" data-wsl-logo-preview>
                            <img src="<?php echo esc_url($logo_url ?: ''); ?>" alt="پیش‌نمایش لوگو"<?php echo $logo_url ? '' : ' hidden'; ?>>
                            <span<?php echo $logo_url ? ' hidden' : ''; ?>>هنوز لوگویی انتخاب نشده</span>
                        </div>
                        <div><strong>لوگوی روی لیبل</strong><p class="description">تصویر را مستقیم از کتابخانه رسانه انتخاب یا همان‌جا بارگذاری کنید.</p>
                            <input type="hidden" name="<?php echo WSL_Settings::OPTION; ?>[sender][logo_id]" value="<?php echo (int) $s['sender']['logo_id']; ?>" data-wsl-logo-id>
                            <button type="button" class="button button-secondary" data-wsl-logo-select><?php echo $logo_url ? 'تغییر لوگو' : 'انتخاب لوگو'; ?></button>
                            <button type="button" class="button button-link-delete" data-wsl-logo-remove<?php echo $logo_url ? '' : ' hidden'; ?>>حذف لوگو</button>
                        </div>
                    </div>
                </section>
                <section class="wsl-card"><h2>موارد قابل نمایش</h2><p>اطلاعات اصلی گیرنده و فرستنده برجسته شده‌اند؛ هر مورد را مستقل روشن یا خاموش کنید.</p><div class="wsl-switches">
                    <?php foreach ($labels as $key=>$title) : ?><label class="<?php echo strpos($key, 'recipient_') === 0 || strpos($key, 'sender_') === 0 ? 'is-core' : ''; ?>"><input type="checkbox" name="<?php echo WSL_Settings::OPTION; ?>[fields][<?php echo esc_attr($key); ?>]" value="1" <?php checked(!empty($s['fields'][$key])); ?>><span><?php echo esc_html($title); ?></span></label><?php endforeach; ?>
                </div></section>
                <section class="wsl-card"><h2>ظاهر چاپ</h2><div class="wsl-fields"><label><span>رنگ اصلی</span><input type="color" name="<?php echo WSL_Settings::OPTION; ?>[accent]" value="<?php echo esc_attr($s['accent']); ?>"></label><label><span>اندازه متن</span><input type="number" min="8" max="16" name="<?php echo WSL_Settings::OPTION; ?>[font_size]" value="<?php echo (int) $s['font_size']; ?>"></label><label class="wsl-check"><input type="checkbox" name="<?php echo WSL_Settings::OPTION; ?>[show_border]" value="1" <?php checked($s['show_border']); ?>><span>نمایش کادر لیبل</span></label></div></section>
                <?php submit_button('ذخیره تنظیمات'); ?>
            </form>
        </div>
        <?php
    }
}
