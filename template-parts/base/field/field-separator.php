<?php

declare(strict_types=1);

// shadcn/ui's FieldSeparator: a divider between sections inside field-group.php, optionally with
// inline content in the middle (e.g. "Or continue with"). Unlike separator.php (a bare line, no
// content slot at all), this needs to hold text -- not a case of reusing separator.php as-is, the
// "line - text - line" look is this file's own, single-element job (a project-CSS concern via e.g.
// ::before/::after border lines around the text, or flex + border-top on the empty siblings, not
// baked in here, see CLAUDE.md #1).
//
// Supported config:
//   text   string   optional inline content in the middle of the divider; omit for a plain,
//                    content-less line
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'field-separator';
$element_attributes['role'] = 'separator';

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

$content_markup =
    $text !== ''
        ? sprintf('<span data-slot="field-separator-content">%s</span>', esc_html($text))
        : '';

printf(
    '<div%1$s>%2$s</div>',
    base_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
