<?php

declare(strict_types=1);

// Non-interactive section heading inside the menu.
//
// Config:
//   text    string   required
//   inset   bool     aligns with icon items
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ''));
$inset = !empty($config['inset']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($text === '') {
    return;
}

$base_classes = 'px-2 py-1.5 text-sm font-medium text-foreground data-[inset=true]:pl-8';

$element_attributes = $attributes;

$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'dropdown-menu-label';

if ($inset) {
    $element_attributes['data-inset'] = 'true';
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);
