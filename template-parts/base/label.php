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
// cascades to all of them, instead of repeating the same classes in every caller.
//
// field-label.php's thin wrapper is the one exception: shadcn's own FieldLabel renders their Label
// component but layers a SUBSTANTIALLY different class list on top (live-checked 2026-08-30) --
// not just Label's classes plus a few additions, several outright conflict (`leading-none` vs.
// `leading-snug`, `items-center` vs. `w-fit`). Concatenating both would leave contradicting
// utilities in the same class attribute with no tailwind-merge to resolve them (same caveat as
// button.php's own `class` config), so this file swaps to FieldLabel's own class set entirely --
// same full-replacement-based-on-a-flag idiom as input.php's `data_slot === 'input-group-control'`
// branch -- instead of appending. Deviations from shadcn's FieldLabel: `rounded-md` -> `rounded-lg`
// (this project's field-surface radius, see input.php/input-group.php's own header comments),
// `border` gained an explicit `border-border` (same bare-`border`-needs-a-color-utility reasoning
// as button-group-text.php's header), `border-primary`/`bg-primary` renamed to this project's
// henge-green brand token (same substitution button.php's variant vocabulary already makes), and
// the `dark:has-data-[state=checked]:bg-primary/10` clause dropped (no dark-mode strategy yet, same
// reasoning as button.php/badge.php).
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

$is_field_label = $data_slot === 'field-label';

$base_classes = $is_field_label
    ? 'group/field-label peer/field-label flex w-fit gap-2 leading-snug ' .
        'group-data-[disabled=true]/field:opacity-50 has-[>[data-slot=field]]:w-full ' .
        'has-[>[data-slot=field]]:flex-col has-[>[data-slot=field]]:rounded-lg ' .
        'has-[>[data-slot=field]]:border has-[>[data-slot=field]]:border-border ' .
        '[&>*]:data-[slot=field]:p-4 has-data-[state=checked]:border-henge-green ' .
        'has-data-[state=checked]:bg-henge-green/5'
    : 'flex items-center gap-2 text-sm leading-none font-medium select-none ' .
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
