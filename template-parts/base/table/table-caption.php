<?php

declare(strict_types=1);

// <caption>; must be the first child of <table>.
//
// Config:
//   text   string   required
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($text === '') {
    return;
}

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    'mt-4 text-sm text-muted-foreground' . ($class_name !== '' ? ' ' . $class_name : ''),
);
$element_attributes['data-slot'] = 'table-caption';

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<caption%1$s>%2$s</caption>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);
