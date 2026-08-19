<?php

declare(strict_types=1);

// shadcn/ui's InputGroupText: plain, non-interactive text inside an input-group-addon.php (e.g. a
// currency symbol prefix like "$", a domain suffix like "@company.com"). Same idea as
// button-group-text.php, kept as its own file rather than reused across both component families
// (CLAUDE.md #1 -- shadcn ships these as two distinct components with their own data-slot/default
// styling, not one shared atom) -- an inline <span> here, since this content always sits inline
// next to a text-field control, unlike button-group-text.php's own <div> choice.
//
// Supported config:
//   text   string   required. Visible content (plain text, escaped)
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

$element_attributes['data-slot'] = 'input-group-text';

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<span%1$s>%2$s</span>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);
