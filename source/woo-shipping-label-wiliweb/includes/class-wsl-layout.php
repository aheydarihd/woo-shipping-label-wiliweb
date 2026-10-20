<?php
defined('ABSPATH') || defined('WSL_TESTING') || exit;

final class WSL_Layout {
    private const PORTRAIT_PRESETS = [
        1 => [1, 1],
        2 => [2, 1],
        4 => [2, 2],
        6 => [3, 2],
        8 => [4, 2],
    ];

    private const LANDSCAPE_PRESETS = [
        1 => [1, 1],
        2 => [1, 2],
        4 => [1, 4],
        6 => [2, 3],
        8 => [2, 4],
    ];

    public static function preset(int $count, string $orientation = 'portrait'): array {
        $orientation = $orientation === 'landscape' ? 'landscape' : 'portrait';
        $presets = $orientation === 'landscape' ? self::LANDSCAPE_PRESETS : self::PORTRAIT_PRESETS;
        $count = isset($presets[$count]) ? $count : 4;
        [$columns, $rows] = $presets[$count];
        $margin = 6.0;
        $gap = 4.0;
        return [
            'paper' => 'A4',
            'per_page' => $count,
            'orientation' => $orientation,
            'columns' => $columns,
            'rows' => $rows,
            'margin_top' => $margin,
            'margin_right' => $margin,
            'margin_bottom' => $margin,
            'margin_left' => $margin,
            'gap_horizontal' => $columns > 1 ? $gap : 0.0,
            'gap_vertical' => $rows > 1 ? $gap : 0.0,
            'label_width' => round((210 - (2 * $margin) - (($columns - 1) * $gap)) / $columns, 2),
            'label_height' => round((294 - (2 * $margin) - (($rows - 1) * $gap)) / $rows, 2),
        ];
    }

    public static function validate(array $layout) {
        $count = absint($layout['per_page'] ?? 0);
        if (!isset(self::PORTRAIT_PRESETS[$count])) {
            return new WP_Error('wsl_invalid_count', 'تعداد لیبل انتخاب‌شده معتبر نیست.');
        }
        $orientation = ($layout['orientation'] ?? '') === 'landscape' ? 'landscape' : 'portrait';
        $base = self::preset($count, $orientation);
        foreach (['margin_top', 'margin_right', 'margin_bottom', 'margin_left', 'gap_horizontal', 'gap_vertical', 'label_width', 'label_height'] as $key) {
            if (isset($layout[$key]) && is_numeric($layout[$key])) {
                $base[$key] = max(0.0, min(297.0, (float) $layout[$key]));
            }
        }
        $used_width = $base['margin_left'] + $base['margin_right'] + ($base['columns'] * $base['label_width']) + (($base['columns'] - 1) * $base['gap_horizontal']);
        $used_height = $base['margin_top'] + $base['margin_bottom'] + ($base['rows'] * $base['label_height']) + (($base['rows'] - 1) * $base['gap_vertical']);
        if ($used_width > 210.01 || $used_height > 294.01 || $base['label_width'] < 35 || $base['label_height'] < 25) {
            return new WP_Error('wsl_layout_overflow', 'ابعاد لیبل‌ها در برگه A4 جا نمی‌شود.');
        }
        return $base;
    }

    public static function paginate(array $ids, int $per_page): array {
        $per_page = isset(self::PORTRAIT_PRESETS[$per_page]) ? $per_page : 4;
        return array_chunk(array_values($ids), $per_page);
    }
}
