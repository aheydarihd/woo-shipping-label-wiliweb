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
        add_meta_box('wsl-order-print', __('لیبل پستی', 'woo-shipping-label-wiliweb'), [self::class, 'metabox'], ['shop_order', 'woocommerce_page_wc-orders'], 'side', 'high');
    }

    public static function metabox($object): void {
        $order = $object instanceof WC_Order ? $object : wc_get_order($object->ID ?? 0);
        if (!$order || !current_user_can('edit_shop_order', $order->get_id())) {
            return;
        }
        ?>
        <p><?php esc_html_e('پیش‌نمایش لیبل این سفارش را در صفحه‌ای جدا باز کنید.', 'woo-shipping-label-wiliweb'); ?></p>
        <a class="button button-primary" href="<?php echo esc_url(WSL_Print_Controller::create_url([$order->get_id()])); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('پیش‌نمایش و چاپ لیبل', 'woo-shipping-label-wiliweb'); ?></a>
        <?php
    }

    public static function bulk_action(array $actions): array {
        $actions['wsl_print_labels'] = __('چاپ لیبل پستی', 'woo-shipping-label-wiliweb');
        return $actions;
    }

    public static function handle_bulk(string $redirect, string $action, array $ids): string {
        if ($action !== 'wsl_print_labels') {
            return $redirect;
        }
        if (!current_user_can('edit_shop_orders') && !current_user_can('manage_woocommerce')) {
            return add_query_arg('wsl_error', 'permission', $redirect);
        }
        $url = WSL_Print_Controller::create_url($ids);
        return $url !== '' ? $url : add_query_arg('wsl_error', 'empty', $redirect);
    }
}
