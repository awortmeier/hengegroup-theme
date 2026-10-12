<?php

declare(strict_types=1);

// Native <progress>; without `value` it is indeterminate. data-state reflects render time only.
// Stripes are CSS in app.css (no Tailwind utility), colored via currentColor.
//
// Config:
//   value            int|string   omit for indeterminate
//   max              int|string   default 100
//   size             string       sm | base (default) | lg
//   variant          string       henge-green (default) | henge-blue | destructive
//   striped          bool         animated stripes
//   aria_label       string
//   aria_valuetext   string
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$has_value =
    array_key_exists('value', $config) && $config['value'] !== null && $config['value'] !== '';
$value = $has_value ? trim((string) $config['value']) : '';
$max = trim((string) ($config['max'] ?? '100'));
$size = trim((string) ($config['size'] ?? 'base'));
$variant = trim((string) ($config['variant'] ?? 'henge-green'));
$striped = !empty($config['striped']);
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$aria_valuetext = trim((string) ($config['aria_valuetext'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($max === '' || !is_numeric($max)) {
    $max = '100';
}

$state = 'indeterminate';

if ($has_value && is_numeric($value)) {
    $state = (float) $value >= (float) $max ? 'complete' : 'loading';
}

$allowed_sizes = ['sm', 'base', 'lg'];

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'base';
}

$allowed_variants = ['henge-green', 'henge-blue', 'destructive'];

if (!in_array($variant, $allowed_variants, true)) {
    $variant = 'henge-green';
}

$size_classes = ['sm' => 'h-1', 'base' => 'h-2', 'lg' => 'h-3.5'];

// Track = bg-{variant}/opacity on the root, fill = text-{variant} + bg-current (for the stripes).
$variant_classes = [
    'henge-green' => 'bg-henge-green/20 text-henge-green',
    'henge-blue' => 'bg-henge-blue/15 text-henge-blue',
    'destructive' => 'bg-destructive/15 text-destructive',
];

$base_classes =
    'block w-full appearance-none overflow-hidden rounded-full border-none ' .
    '[&::-webkit-progress-bar]:rounded-full [&::-webkit-progress-bar]:bg-transparent ' .
    '[&::-webkit-progress-value]:rounded-full [&::-webkit-progress-value]:bg-current ' .
    '[&::-webkit-progress-value]:transition-[width] [&::-webkit-progress-value]:duration-300 ' .
    '[&::-moz-progress-bar]:rounded-full [&::-moz-progress-bar]:bg-current';

$computed_class = "{$base_classes} {$size_classes[$size]} {$variant_classes[$variant]}";

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['data-slot'] = 'progress';
$element_attributes['data-state'] = $state;
$element_attributes['data-size'] = $size;
$element_attributes['data-variant'] = $variant;
$element_attributes['max'] = $max;

if ($striped) {
    $element_attributes['data-striped'] = 'true';
}

if ($has_value) {
    $element_attributes['value'] = $value;
}

if ($aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
}

if ($aria_valuetext !== '') {
    $element_attributes['aria-valuetext'] = $aria_valuetext;
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<progress%s></progress>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
