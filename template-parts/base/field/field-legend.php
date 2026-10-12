<?php

declare(strict_types=1);

// <legend> for field-set.php.
//
// Config:
//   text      string   required
//   variant   string   legend (default) | label (smaller, like a field label)
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ''));
$variant = trim((string) ($config['variant'] ?? 'legend'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($text === '') {
    return;
}

$allowed_variants = ['legend', 'label'];

if (!in_array($variant, $allowed_variants, true)) {
    $variant = 'legend';
}

$base_classes = 'mb-3 font-medium data-[variant=legend]:text-base data-[variant=label]:text-sm';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'field-legend';
$element_attributes['data-variant'] = $variant;

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<legend%1$s>%2$s</legend>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);
