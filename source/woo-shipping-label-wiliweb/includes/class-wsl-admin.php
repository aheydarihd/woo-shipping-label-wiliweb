<?php
defined('ABSPATH') || defined('WSL_TESTING') || exit;

final class WSL_Admin {
    public static function register(): void {
        add_action('add_meta_boxes', [self::class, 'metaboxes']);
        add_filter('bulk_actions-edit-shop_order', [self::class, 'bulk_action']);
        add_filter('bulk_actions-woocommerce_page_wc-orders', [self::class, 'bulk_action']);
        add_filter('handle_bulk_actions-edit-shop_order', [self::class, 'handle_bulk'], 10, 3);
        add_filter('handle_bulk_actions-woocommerce_page_wc-orders', [self::class, 'handle_bulk'], 10, 3);
    }

    public static function metaboxes(): void {
        add_meta_box('wsl-order-print', __('اسناد سفارش', 'woo-shipping-label-wiliweb'), [self::class, 'metabox'], ['shop_order', 'woocommerce_page_wc-orders'], 'side', 'high');
    }

    public static function metabox($object): void {
        $order = $object instanceof WC_Order ? $object : wc_get_order($object->ID ?? 0);
        if (!$order || !current_user_can('edit_shop_order', $order->get_id())) {
            return;
        }
        $settings = WSL_Settings::get();
        $actions = [];
        if (WSL_Settings::module_enabled($settings, 'labels')) {
            $actions[] = ['label', __('چاپ لیبل', 'woo-shipping-label-wiliweb')];
        }
        if (WSL_Settings::module_enabled($settings, 'invoice')) {
            $actions[] = ['invoice', __('چاپ فاکتور', 'woo-shipping-label-wiliweb')];
        }
        if (!$actions) {
            echo '<p>' . esc_html__('هر دو بخش چاپ در تنظیمات افزونه غیرفعال هستند.', 'woo-shipping-label-wiliweb') . '</p>';
            return;
        }
        echo '<p>' . esc_html__('سند موردنظر را در صفحه‌ای جدا پیش‌نمایش و چاپ کنید.', 'woo-shipping-label-wiliweb') . '</p><p class="wsl-order-documents">';
        foreach ($actions as [$document, $label]) {
            $url = WSL_Print_Controller::create_url([$order->get_id()], $document);
            echo '<a class="button' . ($document === 'invoice' ? ' button-primary' : '') . '" href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($label) . '</a> ';
        }
        echo '</p>';
    }

    public static function bulk_action(array $actions): array {
        $settings = WSL_Settings::get();
        if (WSL_Settings::module_enabled($settings, 'labels')) {
            $actions['wsl_print_labels'] = __('چاپ لیبل پستی', 'woo-shipping-label-wiliweb');
        }
        if (WSL_Settings::module_enabled($settings, 'invoice')) {
            $actions['wsl_print_invoices'] = __('چاپ فاکتور فروش', 'woo-shipping-label-wiliweb');
        }
        return $actions;
    }

    public static function handle_bulk(string $redirect, string $action, array $ids): string {
        $documents = [
            'wsl_print_labels' => ['document' => 'label', 'module' => 'labels'],
            'wsl_print_invoices' => ['document' => 'invoice', 'module' => 'invoice'],
        ];
        if (!isset($documents[$action])) {
            return $redirect;
        }
        if (!current_user_can('edit_shop_orders') && !current_user_can('manage_woocommerce')) {
            return add_query_arg('wsl_error', 'permission', $redirect);
        }
        $document = $documents[$action];
        if (!WSL_Settings::module_enabled(WSL_Settings::get(), $document['module'])) {
            return add_query_arg('wsl_error', 'disabled', $redirect);
        }
        $url = WSL_Print_Controller::create_bulk_url($ids, $document['document']);
        return $url !== '' ? $url : add_query_arg('wsl_error', 'empty', $redirect);
    }
}
