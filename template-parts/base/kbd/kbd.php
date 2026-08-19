<?php

declare(strict_types=1);

// Direct translation of shadcn/ui's Kbd: <kbd> is already a real semantic HTML element ("Defines
// text representing user input, typically keyboard input" per the HTML spec) with correct
// built-in meaning -- no ARIA/JS needed at all, just a data-slot for the project's own CSS (see
// CLAUDE.md #1). For a multi-key combo (e.g. "Ctrl" + "K"), compose several kbd.php calls inside
// template-parts/base/kbd/kbd-group.php instead of adding a "keys" array here.
//
// Supported config:
//   text   string   required. The key label (e.g. "Ctrl", "K", "Enter", "⌘"), escaped
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

$element_attributes['data-slot'] = 'kbd';

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<kbd%1$s>%2$s</kbd>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);
