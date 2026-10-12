<?php

declare(strict_types=1);

// Progress ring: conic-gradient masked to a ring (transparent hole, works on any background).
// --pc-value is set inline because Tailwind cannot generate a class for a runtime number.
//
// Config:
//   value            int|string   default 0
//   max              int|string   default 100
//   size             string       sm | base (default) | lg
//   variant          string       henge-green (default) | henge-blue | henge-grey | destructive
//   label            string       center text (default: percentage)
//   show_label       bool         default true
//   aria_label       string       falls back to the label
//   aria_valuetext   string
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$value = is_numeric($config['value'] ?? null) ? (float) $config['value'] : 0.0;
$max = is_numeric($config['max'] ?? null) ? (float) $config['max'] : 100.0;
$size = trim((string) ($config['size'] ?? 'base'));
$variant = trim((string) ($config['variant'] ?? 'henge-green'));
$label = array_key_exists('label', $config) ? trim((string) $config['label']) : null;
$show_label = !array_key_exists('show_label', $config) || !empty($config['show_label']);
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$aria_valuetext = trim((string) ($config['aria_valuetext'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($max <= 0.0) {
    $max = 100.0;
}

$percent = max(0.0, min(100.0, ($value / $max) * 100));
$percent_rounded = (int) round($percent);
$state = $percent_rounded >= 100 ? 'complete' : 'loading';

$computed_label = $label ?? $percent_rounded . ' %';

$allowed_sizes = ['sm', 'base', 'lg'];

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'base';
}

$allowed_variants = ['henge-green', 'henge-blue', 'henge-grey', 'destructive'];

if (!in_array($variant, $allowed_variants, true)) {
    $variant = 'henge-green';
}

$size_classes = [
    'sm' =>
        'size-10 text-xs [mask-image:radial-gradient(farthest-side,transparent_calc(100%_-_5px),black_calc(100%_-_5px))]',
    'base' =>
        'size-14 text-sm [mask-image:radial-gradient(farthest-side,transparent_calc(100%_-_7px),black_calc(100%_-_7px))]',
    'lg' =>
        'size-20 text-base [mask-image:radial-gradient(farthest-side,transparent_calc(100%_-_10px),black_calc(100%_-_10px))]',
];

$variant_classes = [
    'henge-green' => '[--pc-color:var(--color-henge-green)]',
    'henge-blue' => '[--pc-color:var(--color-henge-blue)]',
    'henge-grey' => '[--pc-color:var(--color-henge-grey)]',
    'destructive' => '[--pc-color:var(--color-destructive)]',
];

$base_classes =
    'relative inline-flex shrink-0 items-center justify-center rounded-full ' .
    '[--pc-track:var(--color-border)] ' .
    'bg-[conic-gradient(var(--pc-color)_var(--pc-value),var(--pc-track)_0)] ' .
    'font-medium tabular-nums';

$computed_class = "{$base_classes} {$size_classes[$size]} {$variant_classes[$variant]}";

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);
$element_attributes['style'] = '--pc-value: ' . $percent_rounded . '%;';

$element_attributes['role'] = 'progressbar';
$element_attributes['data-slot'] = 'progress-circle';
$element_attributes['data-state'] = $state;
$element_attributes['data-size'] = $size;
$element_attributes['data-variant'] = $variant;
$element_attributes['aria-valuemin'] = '0';
$element_attributes['aria-valuemax'] = (string) $max;
$element_attributes['aria-valuenow'] = (string) $value;

$accessible_name = $aria_label !== '' ? $aria_label : $computed_label;

if ($accessible_name !== '') {
    $element_attributes['aria-label'] = $accessible_name;
}

if ($aria_valuetext !== '') {
    $element_attributes['aria-valuetext'] = $aria_valuetext;
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<div%s>%s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $show_label
        ? '<span data-slot="progress-circle-label">' . esc_html($computed_label) . '</span>'
        : '',
);
