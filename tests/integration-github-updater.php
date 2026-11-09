<?php
declare(strict_types=1);

$root = 'E:/Codex/Wiliweb/woo-crm-v2/tmp/runtime/wordpress/';
require $root . 'wp-load.php';

delete_transient('wsl_github_release');
$release = WSL_GitHub_Updater::release();
if (($release['version'] ?? '') !== '1.2.0') {
    fwrite(STDERR, "UPDATER_API=FAIL\n");
    exit(1);
}
$update = WSL_GitHub_Updater::update(false, ['Version' => '1.0.6'], plugin_basename(WSL_FILE), []);
$expected = 'https://github.com/aheydarihd/woo-shipping-label-wiliweb/releases/download/v1.2.0/woo-shipping-label-wiliweb-1.2.0.zip';
if (($update['version'] ?? '') !== '1.2.0' || ($update['package'] ?? '') !== $expected) {
    fwrite(STDERR, "UPDATER_OFFER=FAIL\n");
    exit(1);
}
echo "UPDATER_API=PASS UPDATER_OFFER=PASS\n";
