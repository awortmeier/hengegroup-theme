<?php

declare(strict_types=1);

// shadcn/ui's FieldGroup: a plain wrapper stacking several field.php calls, and (per shadcn's own
// docs) a CSS container-query root so a child field.php's `orientation: 'responsive'` can mean
// something -- `container-type: inline-size` on `[data-slot="field-group"]` plus a
// `@container (min-width: ...) { [data-slot="field"][data-orientation="responsive"] { ... } }`
// rule is the project-CSS side of that (CLAUDE.md #1), not rendered here. Content-agnostic wrapper,
// same nesting pattern as button-group.php/kbd-group.php: buffer several field.php
// (or field-set.php) calls and pass the combined HTML as `content`.
//
// Supported config:
//   content   string   required. Pre-rendered HTML to wrap (buffered field.php/field-set.php calls)
//   class / attributes / data_attributes   passthrough onto the outer
//                       <div data-slot="field-group"> wrapper

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

$element_attributes['data-slot'] = 'field-group';

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
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
