<?php
declare(strict_types=1);

$root = 'E:/Codex/Wiliweb/woo-crm-v2/tmp/runtime/wordpress/';
require $root . 'wp-load.php';

$mode = strtoupper((string) ($argv[1] ?? ''));
if (!in_array($mode, ['HPOS', 'CPT'], true)) {
    fwrite(STDERR, "Usage: php integration-set-storage.php HPOS|CPT\n");
    exit(2);
}

$value = $mode === 'HPOS' ? 'yes' : 'no';
$synchronizer = wc_get_container()->get(Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer::class);
for ($iteration = 0; $iteration < 50; $iteration++) {
    $batch = $synchronizer->get_next_batch_to_process(100);
    if (!$batch) {
        break;
    }
    $synchronizer->process_batch($batch);
}
if ($synchronizer->get_total_pending_count() > 0) {
    fwrite(STDERR, "STORAGE_SYNC=FAIL pending=" . $synchronizer->get_total_pending_count() . "\n");
    exit(1);
}
update_option('woocommerce_custom_orders_table_enabled', $value);
delete_transient('woocommerce_cache_excluded_uris');
if (get_option('woocommerce_custom_orders_table_enabled') !== $value) {
    fwrite(STDERR, "STORAGE_SET=FAIL mode={$mode}\n");
    exit(1);
}

echo "STORAGE_SET=PASS mode={$mode}\n";
