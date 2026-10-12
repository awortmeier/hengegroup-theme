<?php

declare(strict_types=1);

// <th>. Empty content still renders the cell so columns stay aligned. colspan/rowspan via `attributes`.
//
// Config:
//   content   string   pre-rendered HTML
//   scope     string   col (default) | row | colgroup | rowgroup
//   align     string   start (default) | center | end
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$scope = trim((string) ($config['scope'] ?? 'col'));
$align = trim((string) ($config['align'] ?? 'start'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_scopes = ['col', 'row', 'colgroup', 'rowgroup'];

if (!in_array($scope, $allowed_scopes, true)) {
    $scope = 'col';
}

$allowed_aligns = ['start', 'center', 'end'];

if (!in_array($align, $allowed_aligns, true)) {
    $align = 'start';
}

$align_classes = [
    'start' => 'text-left',
    'center' => 'text-center',
    'end' => 'text-right',
];

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    "h-10 px-4 align-middle text-xs font-semibold tracking-widest text-muted-foreground uppercase whitespace-nowrap [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-[2px] {$align_classes[$align]}" .
        ($class_name !== '' ? ' ' . $class_name : ''),
);
$element_attributes['data-slot'] = 'table-head';
$element_attributes['scope'] = $scope;

if ($align !== 'start') {
    $element_attributes['data-align'] = $align;
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<th%1$s>%2$s</th>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
