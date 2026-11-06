<?php
declare(strict_types=1);

$group = 'all';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--group=')) {
        $group = substr($arg, 8);
    }
}
$groups = [
    'domain' => ['test-layout.php', 'test-settings.php'],
    'order-data' => ['test-order-data.php', 'test-invoice-order-data.php'],
    'invoice-data' => ['test-invoice-order-data.php'],
    'controller' => ['test-print-controller.php'],
    'renderer' => ['test-renderer.php'],
    'admin' => ['test-admin-hooks.php', 'test-github-updater.php'],
];
$files = $group === 'all' ? array_values(array_unique(array_merge(...array_values($groups)))) : ($groups[$group] ?? []);
$passed = 0;
foreach ($files as $file) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $file;
    if (!is_file($path)) {
        continue;
    }
    try {
        require $path;
        echo "PASS {$file}\n";
        $passed++;
    } catch (Throwable $error) {
        fwrite(STDERR, "FAIL {$file}: {$error->getMessage()}\n");
        exit(1);
    }
}
echo "PASS {$passed} test files\n";
