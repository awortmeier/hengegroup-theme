<?php

declare(strict_types=1);

// Non-button text inside button-group.php, e.g. "https://".
//
// Config:
//   text   string   required
//   tag    string   div (default) | span | label
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ''));
$tag = trim((string) ($config['tag'] ?? 'div'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($text === '') {
    return;
}

$allowed_tags = ['div', 'span', 'label'];

if (!in_array($tag, $allowed_tags, true)) {
    $tag = 'div';
}

$base_classes =
    'flex items-center gap-2 rounded-lg border border-border bg-muted px-4 text-sm font-medium ' .
    "shadow-xs [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4";

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'button-group-text';

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<%1$s%2$s>%3$s</%1$s>',
    esc_html($tag), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);
