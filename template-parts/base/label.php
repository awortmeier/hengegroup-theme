<?php

declare(strict_types=1);

// shadcn/ui's Label wraps a headless UI primitive (historically Radix UI; shadcn now also ships
// Base UI/React Aria variants of many components), whose only real job -- clicking the label
// focuses/activates its associated control -- is already native <label> behaviour, no JS needed
// (see CLAUDE.md #1: natives HTML-Verhalten hat Vorrang). This component is therefore just a
// data-attributed native <label>: no Tailwind/utility classes baked in, only data-slot="label"
// for the project's own CSS. Built as its own base component (instead of inlined into
// checkbox.php) because every future form control (radio, switch, input, textarea, select, ...)
// needs the same pairing -- compose it, don't duplicate a <label> tag per component.
//
// Phase 2 (CLAUDE.md Regel 1): classes taken 1:1 from shadcn's own Label component (registry/
// new-york-v4/ui/label.tsx) -- since every sibling form component (input.php/textarea.php/
// checkbox.php/...) nests THIS file for its own `label` convenience config, styling it here once
// cascades to all of them plus field-label.php's thin wrapper, instead of repeating the same
// classes in every caller.
//
// Supported config:
//   text / label   string   required. Visible label content (plain text, escaped)
//   for            string   the associated control's id (native `for` attribute); omit when the
//                            label will wrap its control instead of being its sibling
//   data_slot      string   overrides the root `data-slot` value (default: 'label') -- same
//                            composing-parent escape hatch as input.php's/textarea.php's
//                            `data_slot`; field-label.php requests 'field-label' here instead of
//                            duplicating this file's attribute-building logic.
//                            Leave unset for standalone use.
//   class / attributes / data_attributes   passthrough, as in the other base parts (`class` is
//                            appended AFTER the computed base classes, same caveat as button.php's
//                            own `class`)

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ($config['label'] ?? '')));
$for = trim((string) ($config['for'] ?? ''));
$data_slot = trim((string) ($config['data_slot'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($text === '') {
    return;
}

if ($data_slot === '') {
    $data_slot = 'label';
}

$base_classes =
    'flex items-center gap-2 text-sm leading-none font-medium select-none ' .
    'group-has-[[data-disabled=true]]:pointer-events-none ' .
    'group-has-[[data-disabled=true]]:opacity-50 peer-disabled:cursor-not-allowed ' .
    'peer-disabled:opacity-50';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = $data_slot;

if ($for !== '') {
    $element_attributes['for'] = $for;
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<label%1$s>%2$s</label>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);
