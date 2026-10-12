<?php

declare(strict_types=1);

// Loading placeholder with a shimmer sweep (hg-skeleton-shimmer in app.css), off for reduced
// motion. Decorative by default since skeleton screens contain many shapes.
//
// Config:
//   shape        string   line (default) | block | circle | pill (size/radius, overridable via class)
//   color        string   default | light (on dark backgrounds)
//   decorative   bool     default true; false = role="status" + aria-label
//   aria_label   string   default "Loading"
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$shape = trim((string) ($config['shape'] ?? 'line'));
$color = trim((string) ($config['color'] ?? 'default'));
$decorative = !array_key_exists('decorative', $config) || !empty($config['decorative']);
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_shapes = ['line', 'block', 'circle', 'pill'];

if (!in_array($shape, $allowed_shapes, true)) {
    $shape = 'line';
}

$allowed_colors = ['default', 'light'];

if (!in_array($color, $allowed_colors, true)) {
    $color = 'default';
}

if ($aria_label === '') {
    $aria_label = __('Loading', 'hengegroup-theme');
}

$shape_classes = [
    'line' => 'h-3 w-full rounded-full',
    'block' => 'h-32 w-full rounded-md',
    'circle' => 'size-10 rounded-full',
    'pill' => 'h-8 w-20 rounded-full',
];

$color_classes = [
    'default' => 'bg-accent',
    'light' => 'bg-grey-light/15',
];

$computed_class = trim(
    "{$shape_classes[$shape]} {$color_classes[$color]} " .
        'animate-[hg-skeleton-shimmer_1.8s_ease-in-out_infinite] motion-reduce:animate-none',
);

$element_attributes = $attributes;

$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['data-slot'] = 'skeleton';
$element_attributes['data-shape'] = $shape;
$element_attributes['data-color'] = $color;

if ($decorative) {
    $element_attributes['aria-hidden'] = 'true';
} else {
    $element_attributes['role'] = 'status';
    $element_attributes['aria-label'] = $aria_label;
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<div%s></div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
