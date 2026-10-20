<?php
declare(strict_types=1);
require 'E:/Codex/Wiliweb/woo-crm-v2/tmp/runtime/wordpress/wp-load.php';
$mode = $argv[1] ?? '';
if (!in_array($mode, ['yes', 'no'], true)) {
    fwrite(STDERR, "Use yes or no\n");
    exit(1);
}
global $wpdb;
$updated = $wpdb->update($wpdb->options, ['option_value' => $mode], ['option_name' => 'woocommerce_custom_orders_table_enabled'], ['%s'], ['%s']);
wp_cache_delete('woocommerce_custom_orders_table_enabled', 'options');
if ($updated === false) {
    fwrite(STDERR, "Storage option update failed\n");
    exit(1);
}
echo 'woocommerce_custom_orders_table_enabled=' . get_option('woocommerce_custom_orders_table_enabled') . PHP_EOL;
