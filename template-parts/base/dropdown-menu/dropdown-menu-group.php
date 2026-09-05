<?php

declare(strict_types=1);

// shadcn/ui's DropdownMenuGroup: `role="group"` around a set of related dropdown-menu-item.php
// calls (e.g. paired with a preceding dropdown-menu-label.php). Content-agnostic wrapper, same
// nesting pattern as button-group.php/kbd-group.php: buffer the inner item calls
// and pass the combined HTML string as `content`.
//
// Supported config:
//   content   string   required. Pre-rendered HTML to wrap (dropdown-menu-item.php/
//                       -checkbox-item.php calls)
//   class / attributes / data_attributes   passthrough, as in the other base parts
//
// Phase 2 (CLAUDE.md Regel 1): no classes added -- shadcn's own real stock DropdownMenuGroup
// (live-checked against current docs) carries none either, it's a pure semantic wrapper; the
// visible styling all lives on the nested item calls this wraps.

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

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['role'] = 'group';
$element_attributes['data-slot'] = 'dropdown-menu-group';

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
