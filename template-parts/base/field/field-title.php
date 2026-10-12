<?php

declare(strict_types=1);

// Label-styled text inside field-content.php that is not paired with a control.
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

$base_classes =
    'flex w-fit items-center gap-2 text-sm leading-snug font-medium ' .
    'group-data-[disabled=true]/field:opacity-50';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'field-title';

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);
