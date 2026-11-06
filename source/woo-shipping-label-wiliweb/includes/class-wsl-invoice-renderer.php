<?php
defined('ABSPATH') || defined('WSL_TESTING') || exit;

final class WSL_Invoice_Renderer {
    public static function invoice(array $invoice, array $settings): string {
        $invoice = apply_filters('wsl_invoice_data', $invoice, $settings);
        if (!is_array($invoice)) {
            return '';
        }
        $template = ($settings['invoice']['template'] ?? 'modern') === 'classic' ? 'classic' : 'modern';
        $title = apply_filters('wsl_invoice_title', (string) ($settings['invoice']['title'] ?? 'فاکتور فروش'), $invoice, $settings);
        $logo_id = (int) ($invoice['seller']['logo_id'] ?? 0);
        $logo_url = $logo_id ? (string) (wp_get_attachment_image_url($logo_id, 'medium') ?: '') : '';
        $logo_url = apply_filters('wsl_invoice_logo_url', $logo_url, $invoice, $settings);
        $path = dirname(__DIR__) . '/templates/invoice-' . $template . '.php';
        ob_start();
        include $path;
        $html = (string) ob_get_clean();
        return (string) apply_filters('wsl_invoice_html', $html, $invoice, $settings);
    }

    public static function page(array $invoices, array $settings, int $skipped = 0): void {
        include dirname(__DIR__) . '/templates/invoice-print-page.php';
    }

    public static function enabled(array $settings, string $key): bool {
        return !empty($settings['invoice']['fields'][$key]);
    }

    public static function row(array $settings, string $key, string $label, $value, string $class = ''): string {
        $value = trim((string) $value);
        if (!self::enabled($settings, $key) || $value === '') {
            return '';
        }
        $classes = trim('wsl-invoice-row ' . $class);
        return '<div class="' . esc_attr($classes) . '"><span>' . esc_html($label) . '</span><strong>' . esc_html($value) . '</strong></div>';
    }

    public static function total_row(array $settings, string $key, string $label, array $money, bool $emphasis = false): string {
        if (!self::enabled($settings, $key) || !array_key_exists('formatted', $money)) {
            return '';
        }
        return '<div class="wsl-invoice-total' . ($emphasis ? ' is-grand' : '') . '"><span>' . esc_html($label) . '</span><strong>' . esc_html((string) $money['formatted']) . '</strong></div>';
    }

    public static function buyer_address(array $buyer): string {
        return implode('، ', array_filter([
            trim((string) ($buyer['province'] ?? '')),
            trim((string) ($buyer['city'] ?? '')),
            trim((string) ($buyer['address'] ?? '')),
        ]));
    }
}
