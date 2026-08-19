<?php

declare(strict_types=1);

// shadcn/ui's FieldSet: a real native <fieldset>, semantically grouping several related fields
// (e.g. a "Profile" section of a longer form) -- native HTML already provides the right grouping
// element, no need to fake it with a <div role="group"> (see CLAUDE.md #1). Content-agnostic
// wrapper, same nesting pattern as field-group.php: buffer an optional
// field-legend.php call, an optional field-description.php call, and a field-group.php call, then
// pass the combined HTML as `content`.
//
// Supported config:
//   content   string   required. Pre-rendered HTML to wrap (typically field-legend.php +
//                       field-description.php + field-group.php, in that order)
//   class / attributes / data_attributes   passthrough onto the outer
//                       <fieldset data-slot="field-set"> wrapper

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

$element_attributes['data-slot'] = 'field-set';

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<fieldset%1$s>%2$s</fieldset>',
    base_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
