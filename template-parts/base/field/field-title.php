<?php

declare(strict_types=1);

// shadcn/ui's FieldTitle: a small text atom with label-like styling, used inside
// field-content.php (e.g. naming the control when the real field-label.php sits beside rather than
// above it, or when there's no single control to `for`-pair with at all). Plain text, no ARIA role
// beyond what it already provides -- unlike field-label.php it is never `for`-paired to a control
// (that's field-label.php's job), so it stays a plain <div>, not a <label>.
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

$element_attributes['data-slot'] = 'field-title';

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<div%1$s>%2$s</div>',
    base_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);
