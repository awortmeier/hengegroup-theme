<?php

declare(strict_types=1);

// shadcn/ui's FieldGroup: a plain wrapper stacking several field.php calls, and (per shadcn's own
// docs) a CSS container-query root so a child field.php's `orientation: 'responsive'` can mean
// something. Content-agnostic wrapper, same nesting pattern as button-group.php/kbd-group.php:
// buffer several field.php (or field-set.php) calls and pass the combined HTML as `content`.
//
// Phase 2 (CLAUDE.md Regel 1): classes taken 1:1 from shadcn's own FieldGroup (registry/
// new-york-v4/ui/field.tsx, live-checked 2026-08-30) -- Tailwind v4 ships container queries
// natively (`@container`/`@md:`-style variants), no plugin/raw-CSS exception needed. The named
// container `@container/field-group` is exactly what field.php's own `responsive` orientation
// classes target via `@md/field-group:`-prefixed utilities (see that file's header) -- this file
// unblocks that, no separate project-CSS `@container` rule needed elsewhere. The
// `data-[slot=checkbox-group]` clause targets an element that self-carries that data-slot (not a
// descendant) -- shadcn's own CheckboxGroup reuses FieldGroup's markup with an overridden
// `data-slot`, same `data_slot`-override escape hatch as this project's own field-label.php/
// input.php; kept 1:1 even though no checkbox-group.php exists in this theme yet (see
// field-set.php's header for the same forward-compatible-selector reasoning).
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

$base_classes =
    'group/field-group @container/field-group flex w-full flex-col gap-7 ' .
    'data-[slot=checkbox-group]:gap-3 [&>[data-slot=field-group]]:gap-4';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

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
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
