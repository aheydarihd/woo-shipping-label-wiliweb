<?php
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/source/woo-shipping-label-wiliweb/includes/class-wsl-layout.php';

foreach ([1, 2, 4, 6, 8] as $count) {
    $preset = WSL_Layout::preset($count);
    wsl_assert_same($count, $preset['per_page'], "preset {$count}");
}
wsl_assert_same(2, WSL_Layout::preset(4)['columns'], 'four columns');
wsl_assert_same(2, WSL_Layout::preset(4)['rows'], 'four rows');
wsl_assert_same('portrait', WSL_Layout::preset(4)['orientation'], 'default label orientation');
$landscape_four = WSL_Layout::preset(4, 'landscape');
wsl_assert_same(1, $landscape_four['columns'], 'four landscape labels use one column');
wsl_assert_same(4, $landscape_four['rows'], 'four landscape labels use four rows');
wsl_assert($landscape_four['label_width'] > $landscape_four['label_height'], 'landscape labels are wider than tall');
wsl_assert_same('landscape', WSL_Layout::validate(['per_page' => 4, 'orientation' => 'landscape'])['orientation'], 'landscape orientation validates');
wsl_assert_same([[11, 12, 13, 14], [15]], WSL_Layout::paginate([11, 12, 13, 14, 15], 4), 'pagination order');
wsl_assert(WSL_Layout::validate(WSL_Layout::preset(4))['per_page'] === 4, 'valid preset');
$invalid = WSL_Layout::validate(['per_page' => 3]);
wsl_assert($invalid instanceof WP_Error, 'unsupported count rejected');
