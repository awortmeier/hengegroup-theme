<?php

declare(strict_types=1);

// shadcn/ui's Select wraps a headless UI primitive (historically Radix UI; shadcn now also ships
// Base UI/React Aria variants of many components): a custom, JS-driven popover-based dropdown
// (SelectTrigger/SelectContent/SelectItem), fully re-implementing native <select> behaviour in
// JS/CSS for custom styling. This component deliberately stays with the native <select>/<option>
// instead (explicit request + CLAUDE.md #1: natives HTML-Verhalten hat Vorrang) -- full
// keyboard/touch/OS-native picker support, zero JS. There is no "NativeSelect" in shadcn's
// registry to mirror vocabulary from, so the config below follows plain HTML
// <select>/<option>/<optgroup> semantics instead. Same optional label pairing as input.php/
// checkbox.php (nests template-parts/base/label.php).
//
// Named native-select.php (data-slot="native-select") rather than select.php/data-slot="select"
// on purpose: a separate, JS-driven Select-equivalent component is planned for later and
// will own the select.php filename + shadcn's own data-slot="select" naming -- this file and that
// future one are deliberately distinct components, not variants of each other (see the trade-off
// note below), so they need non-colliding names/data-slots from the start.
//
// Trade-off vs. shadcn's Select: no custom-styled dropdown list, no rich option content (icons,
// descriptions) -- native <option> is text-only. If that's needed, use the future JS-driven
// select.php instead, not this one.
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind with the same field-surface treatment as
// input.php (border/background/radius/padding/typography, see that file's header for the full
// mapping from the Bewerbungsformular design reference) -- there is no shadcn "NativeSelect" to
// take classes from 1:1 (see above), so this borrows input.php's own Input classes instead of
// inventing a third variant, since a native <select> needs the exact same field-surface look as a
// native <input>. Deliberately NOT adding `appearance-none` + a custom chevron overlay: that would
// be new markup (an icon element), not a Tailwind class, out of scope for a styling-only pass (see
// CLAUDE.md #1) -- select.php already covers the custom-chevron/custom-listbox case for callers
// that need it. The browser's own native dropdown arrow renders inside the padded box as-is.
//
// Supported config:
//   options        array   required, ordered list of:
//     value / text    string   a plain <option value="..."> entry
//     selected        bool     preselects this option (ignored when top-level `value` is given)
//     disabled        bool     per-option disabled
//     OR, for a group (mutually exclusive with value/text/selected/disabled above):
//     label           string   <optgroup label="...">
//     options         array    nested list of the same plain-option shape (no further nesting)
//   value          string|array   preselects the option(s) whose `value` matches; a string for a
//                        single select, an array of strings when `multiple` is true. Overrides any
//                        per-option `selected` flags when given.
//   placeholder    string   prepends a disabled, initially-selected placeholder option with an
//                        empty value (the standard native-<select> placeholder trick) -- only
//                        shown as selected when no `value`/`selected` option is given
//   multiple       bool     native `multiple` attribute
//   name           string   native `name`
//   disabled / required   bool   native attributes; `disabled` also mirrors as
//                  data-disabled="true"
//   aria_invalid   bool     sets aria-invalid="true" plus a mirrored data-invalid="true", same
//                  error-state hooks as input.php
//   id             string   native `id`; auto-generated via wp_unique_id() when omitted (needed
//                        to pair with `label` via its `for` attribute)
//   label          string   optional visible label text; when given, nests
//                        template-parts/base/label.php before the select, both wrapped in a plain
//                        <div data-slot="native-select-field">. Omit for full manual control.
//   aria_label     string   accessible name when no visible `label` is given
//   class / attributes / data_attributes   passthrough onto the <select> itself (not onto the
//                        optional wrapper div, which stays a plain, unstyled hook)

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$options_config = is_array($config['options'] ?? null) ? $config['options'] : [];
$placeholder = trim((string) ($config['placeholder'] ?? ''));
$multiple = !empty($config['multiple']);
$name = trim((string) ($config['name'] ?? ''));
$disabled = !empty($config['disabled']);
$required = !empty($config['required']);
$aria_invalid = !empty($config['aria_invalid']);
$id = trim((string) ($config['id'] ?? ''));
$label_text = trim((string) ($config['label'] ?? ''));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$selected_values = null;

if (array_key_exists('value', $config)) {
    $raw_value = $config['value'];

    if ($multiple && is_array($raw_value)) {
        $selected_values = array_map(static fn($value): string => (string) $value, $raw_value);
    } else {
        $selected_values = [(string) $raw_value];
    }
}

$render_option = static function (array $option_config) use ($selected_values): string {
    $text = trim((string) ($option_config['text'] ?? ''));

    if ($text === '') {
        return '';
    }

    $value = (string) ($option_config['value'] ?? '');
    $option_attributes = ['value' => $value];

    $is_selected =
        $selected_values !== null
            ? in_array($value, $selected_values, true)
            : !empty($option_config['selected']);

    if ($is_selected) {
        $option_attributes['selected'] = true;
    }

    if (!empty($option_config['disabled'])) {
        $option_attributes['disabled'] = true;
    }

    return sprintf(
        '<option%1$s>%2$s</option>',
        hengegroup_theme_render_attributes($option_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        esc_html($text),
    );
};

$options_markup = '';

if ($placeholder !== '') {
    $placeholder_attributes = ['value' => '', 'disabled' => true, 'hidden' => true];

    if ($selected_values === null) {
        $placeholder_attributes['selected'] = true;
    }

    $options_markup .= sprintf(
        '<option%1$s>%2$s</option>',
        hengegroup_theme_render_attributes($placeholder_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        esc_html($placeholder),
    );
}

foreach ($options_config as $option_config) {
    if (!is_array($option_config)) {
        continue;
    }

    $group_options = is_array($option_config['options'] ?? null) ? $option_config['options'] : null;

    if ($group_options === null) {
        $options_markup .= $render_option($option_config); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        continue;
    }

    $group_markup = '';

    foreach ($group_options as $nested_option_config) {
        if (!is_array($nested_option_config)) {
            continue;
        }

        $group_markup .= $render_option($nested_option_config);
    }

    if ($group_markup === '') {
        continue;
    }

    $options_markup .= sprintf(
        '<optgroup label="%1$s">%2$s</optgroup>',
        esc_attr(trim((string) ($option_config['label'] ?? ''))),
        $group_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

if ($options_markup === '') {
    return;
}

if ($id === '') {
    $id = 'hengegroup-theme-native-select-' . wp_unique_id();
}

$base_classes =
    'placeholder:text-muted-foreground selection:bg-henge-green ' .
    'selection:text-henge-green-foreground border-input flex w-full min-w-0 rounded-lg border ' .
    'bg-background px-3.5 py-3 text-base shadow-xs transition-[color,box-shadow] outline-none ' .
    'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm ' .
    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] ' .
    'aria-invalid:ring-destructive/20 aria-invalid:border-destructive';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'native-select';
$element_attributes['id'] = $id;

if ($name !== '') {
    $element_attributes['name'] = $name;
}

if ($multiple) {
    $element_attributes['multiple'] = true;
}

if ($disabled) {
    $element_attributes['disabled'] = true;
    $element_attributes['data-disabled'] = 'true';
}

if ($required) {
    $element_attributes['required'] = true;
}

if ($aria_invalid) {
    $element_attributes['aria-invalid'] = 'true';
    $element_attributes['data-invalid'] = 'true';
}

if ($label_text === '' && $aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

$select_markup = sprintf(
    '<select%1$s>%2$s</select>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $options_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

if ($label_text === '') {
    echo $select_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    return;
}

ob_start();
get_template_part('template-parts/base/label', null, [
    'config' => ['text' => $label_text, 'for' => $id],
]);
$label_markup = (string) ob_get_clean();

printf(
    '<div class="flex flex-col gap-2" data-slot="native-select-field">%1$s%2$s</div>',
    $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $select_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
