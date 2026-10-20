<?php
defined('ABSPATH') || defined('WSL_TESTING') || exit;

final class WSL_Renderer {
    public static function label(array $label, array $settings): string {
        $template = ($settings['template'] ?? 'table') === 'simple' ? 'simple' : 'table';
        $path = dirname(__DIR__) . '/templates/label-' . $template . '.php';
        ob_start();
        include $path;
        return (string) ob_get_clean();
    }

    public static function sheets(array $labels, array $settings): array {
        $per_page = (int) ($settings['layout']['per_page'] ?? 4);
        return WSL_Layout::paginate($labels, $per_page);
    }

    public static function page(array $labels, array $settings, int $skipped = 0): void {
        $sheets = self::sheets($labels, $settings);
        $layout = WSL_Layout::validate($settings['layout'] ?? []);
        if ($layout instanceof WP_Error) {
            $layout = WSL_Layout::preset(4);
        }
        include dirname(__DIR__) . '/templates/print-page.php';
    }

    public static function enabled(array $settings, string $key): bool {
        return !empty($settings['fields'][$key]);
    }

    public static function row(array $settings, string $key, string $label, $value): string {
        $value = is_array($value) ? implode('، ', array_filter($value)) : trim((string) $value);
        if (!self::enabled($settings, $key) || $value === '') {
            return '';
        }
        return '<div class="wsl-row wsl-field-' . esc_attr($key) . '"><strong>' . esc_html($label) . ':</strong> <span>' . esc_html($value) . '</span></div>';
    }

    public static function barcode(string $value): string {
        $bits = '11010010000';
        foreach (str_split(hash('sha256', $value)) as $hex) {
            $bits .= str_pad(base_convert($hex, 16, 2), 4, '0', STR_PAD_LEFT);
        }
        $bits .= '1100011101011';
        $bars = '';
        $x = 0;
        foreach (str_split($bits) as $bit) {
            if ($bit === '1') {
                $bars .= '<rect x="' . $x . '" y="0" width="1" height="28"/>';
            }
            $x++;
        }
        return '<svg class="wsl-barcode" viewBox="0 0 ' . $x . ' 34" role="img" aria-label="' . esc_attr('بارکد سفارش ' . $value) . '"><g>' . $bars . '</g><text x="' . ($x / 2) . '" y="33" text-anchor="middle">' . esc_html($value) . '</text></svg>';
    }
}
