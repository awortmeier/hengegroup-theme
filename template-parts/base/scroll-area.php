<?php

declare(strict_types=1);

// Scroll container with a thin native scrollbar (CSS Scrollbars + ::-webkit-scrollbar).
//
// Config:
//   content       string   pre-rendered (required)
//   orientation   string   vertical (default) | horizontal | both
//   scrollbar     string   thin (default) | none
//   data_slot     string   default 'scroll-area'
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$orientation = trim((string) ($config['orientation'] ?? 'vertical'));
$scrollbar = trim((string) ($config['scrollbar'] ?? 'thin'));
$data_slot = trim((string) ($config['data_slot'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

if ($data_slot === '') {
    $data_slot = 'scroll-area';
}

$allowed_orientations = ['vertical', 'horizontal', 'both'];

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'vertical';
}

$allowed_scrollbars = ['thin', 'none'];

if (!in_array($scrollbar, $allowed_scrollbars, true)) {
    $scrollbar = 'thin';
}

$overflow_classes = [
    'vertical' => 'overflow-y-auto overflow-x-hidden',
    'horizontal' => 'overflow-x-auto overflow-y-hidden',
    'both' => 'overflow-auto',
];

$scrollbar_classes = [
    'thin' =>
        '[scrollbar-width:thin] [scrollbar-color:var(--color-border)_transparent] ' .
        '[&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar]:h-2 ' .
        '[&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:rounded-full ' .
        '[&::-webkit-scrollbar-thumb]:bg-border',
    'none' => '[scrollbar-width:none] [&::-webkit-scrollbar]:hidden',
];

$base_classes = trim($overflow_classes[$orientation] . ' ' . $scrollbar_classes[$scrollbar]);

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = $data_slot;
$element_attributes['data-orientation'] = $orientation;
// Focusable so keyboard users can scroll it (WCAG 2.1.1).
$element_attributes['tabindex'] = '0';

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
