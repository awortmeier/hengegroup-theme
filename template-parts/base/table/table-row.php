<?php

declare(strict_types=1);

// <tr>.
//
// Config:
//   content    string   table-head/-cell markup (required)
//   selected   bool     sets data-state="selected" (highlight)
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$selected = !empty($config['selected']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    'border-b transition-colors hover:bg-muted/50 has-aria-expanded:bg-muted/50 data-[state=selected]:bg-henge-green/5' .
        ($class_name !== '' ? ' ' . $class_name : ''),
);
$element_attributes['data-slot'] = 'table-row';

if ($selected) {
    $element_attributes['data-state'] = 'selected';
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<tr%1$s>%2$s</tr>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
