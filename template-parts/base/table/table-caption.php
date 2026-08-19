<?php

declare(strict_types=1);

// shadcn/ui's TableCaption: a real native <caption>, already the correct semantic element for a
// table's accessible name/description ("provides context for a table" per the HTML spec, exposed
// to assistive tech automatically, no aria-label/aria-labelledby needed) -- no ARIA/JS needed at
// all (CLAUDE.md #1). Plain text like kbd.php/field-title.php, not a content-agnostic wrapper: a
// caption is near-universally a short line of text, and per the HTML spec it must be the FIRST
// child of <table> -- see table.php's header comment for the composition order this implies.
//
// Supported config:
//   text   string   required. Visible caption text, escaped
//   class / attributes / data_attributes   passthrough, as in the other base parts

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

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'table-caption';

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<caption%1$s>%2$s</caption>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);
