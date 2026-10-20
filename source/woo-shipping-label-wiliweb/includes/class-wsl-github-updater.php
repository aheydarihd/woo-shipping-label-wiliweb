<?php
defined('ABSPATH') || defined('WSL_TESTING') || exit;

final class WSL_GitHub_Updater {
    private const REPO = 'aheydarihd/woo-shipping-label-wiliweb';
    private const SLUG = 'woo-shipping-label-wiliweb';
    private const CACHE = 'wsl_github_release';

    public static function register(): void {
        add_filter('update_plugins_github.com', [self::class, 'update'], 10, 4);
        add_filter('plugins_api', [self::class, 'information'], 20, 3);
    }

    public static function validate_release(array $data): ?array {
        $version = ltrim((string) ($data['tag_name'] ?? ''), 'v');
        if (!preg_match('/^\d+\.\d+\.\d+$/D', $version) || !empty($data['draft']) || !empty($data['prerelease'])) {
            return null;
        }
        $tag = (string) ($data['tag_name'] ?? '');
        $name = self::SLUG . '-' . $version . '.zip';
        $expected = 'https://github.com/' . self::REPO . '/releases/download/' . $tag . '/' . $name;
        foreach (($data['assets'] ?? []) as $asset) {
            if (($asset['name'] ?? '') === $name && ($asset['browser_download_url'] ?? '') === $expected) {
                return [
                    'version' => $version,
                    'package' => $expected,
                    'url' => 'https://github.com/' . self::REPO,
                    'notes' => (string) ($data['body'] ?? ''),
                ];
            }
        }
        return null;
    }

    public static function release(): ?array {
        $cached = get_transient(self::CACHE);
        if (is_array($cached)) {
            return $cached ?: null;
        }
        $response = wp_remote_get('https://api.github.com/repos/' . self::REPO . '/releases/latest', [
            'timeout' => 8,
            'headers' => [
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ],
        ]);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            set_transient(self::CACHE, [], 5 * MINUTE_IN_SECONDS);
            return null;
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $release = is_array($data) ? self::validate_release($data) : null;
        set_transient(self::CACHE, $release ?: [], $release ? HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS);
        return $release;
    }

    public static function update($update, array $plugin_data, string $plugin_file, array $locales) {
        if ($plugin_file !== plugin_basename(WSL_FILE)) {
            return $update;
        }
        $release = self::release();
        if (!$release || !version_compare($release['version'], (string) ($plugin_data['Version'] ?? WSL_VERSION), '>')) {
            return $update;
        }
        return [
            'id' => $release['url'],
            'slug' => self::SLUG,
            'version' => $release['version'],
            'url' => $release['url'],
            'package' => $release['package'],
            'tested' => '7.1',
            'requires_php' => '8.1',
            'requires' => '6.6',
        ];
    }

    public static function information($result, string $action, $args) {
        if ($action !== 'plugin_information' || ($args->slug ?? '') !== self::SLUG) {
            return $result;
        }
        $release = self::release();
        if (!$release) {
            return $result;
        }
        return (object) [
            'name' => 'لیبل پستی ووکامرس WiliWeb',
            'slug' => self::SLUG,
            'version' => $release['version'],
            'author' => '<a href="https://wiliweb.com">WiliWeb</a>',
            'homepage' => $release['url'],
            'requires' => '6.6',
            'requires_php' => '8.1',
            'download_link' => $release['package'],
            'sections' => [
                'description' => 'چاپ تکی و گروهی لیبل پستی سفارش‌های ووکامرس با چیدمان قابل تنظیم A4.',
                'changelog' => nl2br(esc_html($release['notes'])),
            ],
        ];
    }
}
