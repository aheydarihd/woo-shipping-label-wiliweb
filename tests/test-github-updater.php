<?php
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-github-updater.php';

$valid = WSL_GitHub_Updater::validate_release([
    'tag_name' => 'v1.0.6',
    'draft' => false,
    'prerelease' => false,
    'body' => "رفع خطا\nبهبود چاپ",
    'assets' => [[
        'name' => 'woo-shipping-label-wiliweb-1.0.6.zip',
        'browser_download_url' => 'https://github.com/aheydarihd/woo-shipping-label-wiliweb/releases/download/v1.0.6/woo-shipping-label-wiliweb-1.0.6.zip',
    ]],
]);
wsl_assert_same('1.0.6', $valid['version'] ?? '', 'stable release accepted');
wsl_assert(str_ends_with($valid['package'] ?? '', 'woo-shipping-label-wiliweb-1.0.6.zip'), 'exact package selected');
wsl_assert_same(null, WSL_GitHub_Updater::validate_release(['tag_name' => 'v1.0.6-beta', 'assets' => []]), 'prerelease version rejected');
wsl_assert_same(null, WSL_GitHub_Updater::validate_release(['tag_name' => 'v1.0.6', 'draft' => true, 'assets' => []]), 'draft rejected');
wsl_assert_same(null, WSL_GitHub_Updater::validate_release([
    'tag_name' => 'v1.0.6',
    'assets' => [['name' => 'wrong.zip', 'browser_download_url' => 'https://example.test/wrong.zip']],
]), 'unexpected package rejected');
