<?php

declare(strict_types=1);

// One slide (role="group", aria-roledescription="slide").
//
// Config:
//   content   string   slide HTML (required)
//   id        string   default wp_unique_id(); carousel.js tracks the current slide by it
//   basis     string   CSS flex-basis, e.g. '50%' for two slides per view (validated, inline style)
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$id = trim((string) ($config['id'] ?? ''));
$basis = trim((string) ($config['basis'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

if ($id === '') {
    $id = 'hengegroup-theme-carousel-item-' . wp_unique_id();
}

if (!preg_match('/^\d+(\.\d+)?(%|px|rem|em|vw|vh)$/', $basis)) {
    $basis = '';
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'carousel-item';
$element_attributes['id'] = $id;
$element_attributes['role'] = 'group';
$element_attributes['aria-roledescription'] = 'slide';

if ($basis !== '') {
    $element_attributes['style'] = 'flex-basis: ' . $basis . ';';
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
