<?php

declare(strict_types=1);

// shadcn/ui's RadioGroup wraps a headless UI primitive (historically Radix UI; shadcn now also
// ships Base UI/React Aria variants of many components): context provider + JS-driven arrow-key
// navigation between items. Native HTML already provides grouping (a shared `name` attribute
// makes radios mutually exclusive) and basic arrow-key navigation between same-named radios --
// this component is a plain wrapper (`role="radiogroup"`) around several nested
// template-parts/base/radio/radio.php calls, no JS needed (CLAUDE.md #1).
//
// `orientation` is exposed for CSS/ARIA parity with shadcn's vocabulary (data-orientation,
// aria-orientation), but note native browsers don't adapt arrow-key direction to visual layout
// the way that headless JS implementation does -- Up/Down/Left/Right all move between radios in
// DOM order regardless of `orientation`; this only affects layout/ARIA hints, not actual key direction --
// not a functional gap worth solving with JS for the common case.
//
// Supported config:
//   items         array    required, ordered list of:
//     value          string   required. This item's native `value`
//     label           string   visible label text; when given, nests
//                              template-parts/base/label.php next to this item's radio (same
//                              convenience as radio.php's own `label`)
//     disabled        bool     per-item disabled (in addition to the group-wide `disabled`)
//     id              string   this item's id; auto-generated via wp_unique_id() when omitted
//   value         string   the currently checked item's `value`; the matching item gets `checked`
//   name          string   native form field name, shared across all items; auto-generated via
//                          wp_unique_id() when omitted (a shared `name` is what makes the items a
//                          mutually exclusive group at all -- items don't get their own `name`)
//   disabled      bool     disables every item in the group; also sets `data-disabled="true"` on
//                          the group wrapper itself as a whole-group CSS hook (there's no native
//                          `disabled` attribute for a <div>)
//   required      bool     applied to every item -- native HTML requires it on each radio sharing
//                          the group's `name` for the browser to enforce "at least one selected"
//   aria_invalid  bool     sets aria-invalid="true" plus a mirrored data-invalid="true" on the
//                          group wrapper, same error-state hooks as input.php/native-select.php
//   orientation   string   horizontal | vertical (default: vertical, matches shadcn's own default
//                          stacked layout) -- sets data-orientation/aria-orientation only, see
//                          note above
//   aria_label    string   accessible name for the group wrapper itself (e.g. when there's no
//                          visible group heading) -- matches toggle-group.php's own `aria_label`
//   class / attributes / data_attributes   passthrough onto the outer
//                          <div role="radiogroup" data-slot="radio-group"> wrapper

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$items_config = is_array($config['items'] ?? null) ? $config['items'] : [];
$selected_value = array_key_exists('value', $config) ? (string) $config['value'] : null;
$name = trim((string) ($config['name'] ?? ''));
$group_disabled = !empty($config['disabled']);
$required = !empty($config['required']);
$aria_invalid = !empty($config['aria_invalid']);
$orientation = trim((string) ($config['orientation'] ?? 'vertical'));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_orientations = ['horizontal', 'vertical'];

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'vertical';
}

if ($name === '') {
    $name = 'base-theme-radio-group-' . wp_unique_id();
}

$items_markup = '';

foreach ($items_config as $item_config) {
    if (!is_array($item_config)) {
        continue;
    }

    $value = trim((string) ($item_config['value'] ?? ''));

    if ($value === '') {
        continue;
    }

    ob_start();
    get_template_part('template-parts/base/radio/radio', null, [
        'config' => [
            'name' => $name,
            'value' => $value,
            'checked' => $selected_value !== null && $selected_value === $value,
            'disabled' => $group_disabled || !empty($item_config['disabled']),
            'required' => $required,
            'id' => trim((string) ($item_config['id'] ?? '')),
            'label' => trim((string) ($item_config['label'] ?? '')),
        ],
    ]);
    $items_markup .= (string) ob_get_clean();
}

if ($items_markup === '') {
    return;
}

$wrapper_attributes = $attributes;

if ($class_name !== '') {
    $wrapper_attributes['class'] = $class_name;
}

$wrapper_attributes['role'] = 'radiogroup';
$wrapper_attributes['data-slot'] = 'radio-group';
$wrapper_attributes['data-orientation'] = $orientation;
$wrapper_attributes['aria-orientation'] = $orientation;

if ($group_disabled) {
    $wrapper_attributes['data-disabled'] = 'true';
}

if ($aria_invalid) {
    $wrapper_attributes['aria-invalid'] = 'true';
    $wrapper_attributes['data-invalid'] = 'true';
}

if ($aria_label !== '') {
    $wrapper_attributes['aria-label'] = $aria_label;
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $wrapper_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<div%1$s>%2$s</div>',
    base_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $items_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
