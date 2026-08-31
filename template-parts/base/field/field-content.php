<?php

declare(strict_types=1);

// shadcn/ui's FieldContent: a plain flex-column wrapper grouping a control with its
// description/error, used when the label sits BESIDE the control instead of above it (`field.php`
// with `orientation: 'horizontal'`, e.g. a checkbox/switch field). Content-agnostic, same nesting
// pattern as field-group.php/button-group.php: buffer the control +
// field-description.php/field-error.php output(s) and pass the combined HTML as `content`.
//
// Phase 2 (CLAUDE.md Regel 1): classes taken 1:1 from shadcn's own FieldContent (registry/
// new-york-v4/ui/field.tsx, live-checked 2026-08-30). The `group/field-content` marker is what
// field.php's own horizontal-orientation classes key off of
// (`has-[>[data-slot=field-content]]:items-start`) to keep a checkbox/radio control top-aligned
// with the first line of a multi-line label instead of vertically centering it.
//
// Supported config:
//   content   string   required. Pre-rendered HTML to wrap
//   class / attributes / data_attributes   passthrough onto the outer
//                       <div data-slot="field-content"> wrapper

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

$base_classes = 'group/field-content flex flex-1 flex-col gap-1.5 leading-snug';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'field-content';

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
