<?php
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-github-updater.php';

$valid = WSL_GitHub_Updater::validate_release([
    'tag_name' => 'v1.1.0',
    'draft' => false,
    'prerelease' => false,
    'body' => "رفع خطا\nبهبود چاپ",
    'assets' => [[
        'name' => 'woo-shipping-label-wiliweb-1.1.0.zip',
        'browser_download_url' => 'https://github.com/aheydarihd/woo-shipping-label-wiliweb/releases/download/v1.1.0/woo-shipping-label-wiliweb-1.1.0.zip',
    ]],
]);
wsl_assert_same('1.1.0', $valid['version'] ?? '', 'stable release accepted');
wsl_assert(str_ends_with($valid['package'] ?? '', 'woo-shipping-label-wiliweb-1.1.0.zip'), 'exact package selected');
wsl_assert_same(null, WSL_GitHub_Updater::validate_release(['tag_name' => 'v1.0.6-beta', 'assets' => []]), 'prerelease version rejected');
wsl_assert_same(null, WSL_GitHub_Updater::validate_release(['tag_name' => 'v1.0.6', 'draft' => true, 'assets' => []]), 'draft rejected');
wsl_assert_same(null, WSL_GitHub_Updater::validate_release([
    'tag_name' => 'v1.0.6',
    'assets' => [['name' => 'wrong.zip', 'browser_download_url' => 'https://example.test/wrong.zip']],
]), 'unexpected package rejected');

$info = WSL_GitHub_Updater::build_information($valid);
wsl_assert_same('فاکتور ساز ویلی وب', $info->name ?? '', 'updater display name');
wsl_assert_same('woo-shipping-label-wiliweb', $info->slug ?? '', 'technical slug unchanged');
wsl_assert(str_contains($info->sections['description'] ?? '', 'فاکتور فروش'), 'updater description includes invoices');
wsl_assert(str_contains($info->sections['description'] ?? '', 'لیبل پستی'), 'updater description includes labels');
wsl_assert_same('https://github.com/aheydarihd/woo-shipping-label-wiliweb', $info->homepage ?? '', 'repository unchanged');
