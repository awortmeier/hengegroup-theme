<?php

declare(strict_types=1);

// <tbody>. `data-last-visible` (set by data-table.js) drops the border of the last visible row,
// because :last-child also counts rows hidden by paging.
//
// Config:
//   content   string   table-row.php markup (required)
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    '[&_tr:last-child]:border-0 [&_tr[data-last-visible]]:border-0' .
        ($class_name !== '' ? ' ' . $class_name : ''),
);
$element_attributes['data-slot'] = 'table-body';

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<tbody%1$s>%2$s</tbody>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
