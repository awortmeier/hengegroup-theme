<?php

declare(strict_types=1);

// shadcn/ui's FieldLegend: a real native <legend>, paired with field-set.php's <fieldset> the same
// way label.php pairs with a control (native HTML already provides the right element, no
// <div role="legend"> fakery, see CLAUDE.md #1). `variant: 'label'` exists because shadcn also lets
// a FieldLegend visually match FieldLabel's smaller sizing (e.g. a fieldset used inside a compact
// field-group.php) while staying a real <legend> underneath -- a styling axis, not a tag change.
//
// Supported config:
//   text      string   required. Visible content (plain text, escaped)
//   variant   string   legend (default) | label -- sets data-variant only, sizing is project-CSS
//                       (CLAUDE.md #1)
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ''));
$variant = trim((string) ($config['variant'] ?? 'legend'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($text === '') {
    return;
}

$allowed_variants = ['legend', 'label'];

if (!in_array($variant, $allowed_variants, true)) {
    $variant = 'legend';
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'field-legend';
$element_attributes['data-variant'] = $variant;

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<legend%1$s>%2$s</legend>',
    base_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);
