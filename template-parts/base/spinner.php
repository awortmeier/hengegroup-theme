<?php

declare(strict_types=1);

// Loading spinner: static track ring plus a rotating quarter arc (dasharray 15.71 = quarter of
// 2π·10). Same viewBox for all sizes, so the stroke grows with the size.
//
// Config:
//   size         string   sm | base (default) | lg | xl
//   color        string   default (henge-green) | muted | inherit (e.g. inside button.php)
//   aria_label   string   default "Loading"
//   decorative   bool     default false (role="status"); true = aria-hidden
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$size = trim((string) ($config['size'] ?? 'base'));
$color = trim((string) ($config['color'] ?? 'default'));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$decorative = !empty($config['decorative']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_sizes = ['sm', 'base', 'lg', 'xl'];

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'base';
}

$allowed_colors = ['default', 'muted', 'inherit'];

if (!in_array($color, $allowed_colors, true)) {
    $color = 'default';
}

if ($aria_label === '') {
    $aria_label = __('Loading', 'hengegroup-theme');
}

$size_classes = [
    'sm' => 'size-3.5',
    'base' => 'size-5',
    'lg' => 'size-7',
    'xl' => 'size-10',
];

$color_classes = [
    'default' => 'text-henge-green',
    'muted' => 'text-muted-foreground',
    'inherit' => '',
];

$base_classes = 'inline-block shrink-0 animate-spin';

$computed_class = trim("{$base_classes} {$size_classes[$size]} {$color_classes[$color]}");

$svg_attributes = $attributes;
$svg_attributes['class'] = trim($computed_class . ($class_name !== '' ? ' ' . $class_name : ''));
$svg_attributes['viewBox'] = '0 0 24 24';
$svg_attributes['fill'] = 'none';
$svg_attributes['stroke'] = 'currentColor';
$svg_attributes['stroke-width'] = '2';
$svg_attributes['data-slot'] = 'spinner';
$svg_attributes['data-size'] = $size;
$svg_attributes['data-color'] = $color;

if ($decorative) {
    $svg_attributes['aria-hidden'] = 'true';
} else {
    $svg_attributes['role'] = 'status';
    $svg_attributes['aria-label'] = $aria_label;
}

$svg_attributes = hengegroup_theme_merge_data_attributes($svg_attributes, $data_attributes);

printf(
    '<svg%1$s><circle cx="12" cy="12" r="10" class="opacity-20"></circle>' .
        '<circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-dasharray="15.71 62.83"></circle></svg>',
    hengegroup_theme_render_attributes($svg_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
