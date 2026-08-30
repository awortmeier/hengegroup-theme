<?php

declare(strict_types=1);

// shadcn/ui's Combobox is a composition of ComboboxInput/ComboboxContent/ComboboxList/
// ComboboxItem/ComboboxEmpty (Base UI/React Aria/Radix UI backends, same multi-backend split as
// select.php) around a headless combobox primitive: filter-as-you-type against a list, a
// custom-styled popover of matches, keyboard nav via the WAI-ARIA "combobox with listbox popup"
// pattern (role="combobox" + aria-expanded/aria-controls/aria-activedescendant, this time ON the
// text input itself, not a separate trigger button like select.php -- the input IS where typing
// happens here). Those four states describe a popup that only exists once combobox.js has built
// it, so -- same "PHP renders the honest zero-JS baseline, JS applies the ARIA correction"
// precedent as toggle.php/tabs.php -- this file does NOT render them statically;
// combobox.js's setupCombobox() sets role/aria-autocomplete/aria-controls once at init and keeps
// aria-expanded/aria-activedescendant in sync via open()/close()/setActive(). Without JS, the
// input stays a plain, unannounced `role="textbox"` paired with the native `<input list>`
// suggestion popup described below -- never a `combobox` role pointing at a popup that can't open.
//
// Unlike select.php (where the fully custom dropdown needs JS for ANY styling at all), a combobox
// gets a real, if unstyleable, zero-JS baseline for free: native `<input type="text" list="...">` +
// `<datalist>` already gives search-as-you-type filtering and picking with no JS whatsoever (see
// CLAUDE.md #1). This file is that baseline, enhanced by
// assets/js/template-parts/base/combobox.js into a custom-styled listbox mirroring select.js's own
// architecture (DOM as single source of truth, `<template>`-cloned selected-indicator, `data-js`
// CSS gate).
//
// Deliberate v1 scope decision, stated plainly: `value` and `text` are treated as the SAME
// submitted/displayed string. The native `<datalist>` `<option>`'s `value` attribute is set to
// `text` (not the distinct `value` config), because cross-browser support for showing one string
// as the suggestion label while submitting a DIFFERENT one is inconsistent enough (Safari in
// particular) to not be a reliable zero-JS baseline -- the actual `value` is still carried as a
// `data-value` attribute (a JS-only hint, ignored by native datalist rendering) so the JS-enhanced
// custom listbox (which renders its own items, not relying on native datalist rendering at all) CAN
// still resolve/submit the real `value` distinctly via its own hidden field once selected. A
// zero-JS user therefore always ends up submitting the display text, not a distinct canonical value
// -- documented, not silently dropped, same category as select.php's own "no rich option content"
// trade-off. Also out of scope for v1, a genuinely different/more complex component, not a variant
// of this one (same reasoning as native-select.php's `multiple`): shadcn's multi-select
// ComboboxChips mode.
//
// Grouping uses a flat per-option `group` string (not nested option arrays like
// native-select.php/select.php) since `<datalist>` has no native optgroup support at all --
// `data-group` is, like `data-value`, a JS-only rendering hint on the native `<option>`.
//
// Same optional label pairing as input.php (nests template-parts/base/label.php);
// same indicator-template composition as select.php (hengegroup_theme_render_icon() buffers the check
// icon once into a <template>, cloned per matching item by combobox.js instead of a second,
// JS-duplicated icon implementation).
//
// Phase 2 (CLAUDE.md Regel 1): the input gets the exact same field-surface Tailwind classes as
// input.php (see that file's header for the Bewerbungsformular design-reference mapping); the
// floating listbox (`combobox-content`) gets the same `absolute`-panel treatment as select.php's
// `select-content` (its own wrapper `<div data-slot="combobox">` is the `relative` anchor) for
// parity between this project's two custom-listbox components -- it must float OVER whatever comes
// after it in the DOM once opened, not push it down. The listbox ITEMS (`combobox-item`/
// `combobox-group`/`combobox-label`/`combobox-item-indicator`/`combobox-empty`) are built entirely
// in JS (assets/js/template-parts/base/combobox.js, from the native `<datalist>`'s own `<option>`s)
// and styled there, not here -- this file only renders the empty `<div role="listbox">` shell.
//
// CSS contract, same shape as select.php's: combobox.js sets `data-js` on the outer
// <div data-slot="combobox"> once initialized; project CSS gates the custom listbox's visibility
// on that attribute (`[data-slot="combobox"]:not([data-js]) [data-slot="combobox-content"] {
// display: none; }`). Before/without JS, `content` stays empty and `hidden` -- the native
// `<input list>` browser suggestion popup is the only UI, which is the deliberate zero-JS
// baseline described above, not a bug. Once JS initializes, it also removes the input's `list`
// attribute (keeping the <datalist> itself in the DOM as combobox.js's own data source) so the
// browser's native suggestion popup doesn't show a second, redundant UI next to the custom one.
//
// Supported config:
//   options        array   required, ordered flat list of:
//     value          string   required. This option's canonical value (JS-enhanced-only
//                              submission, see the scope note above)
//     text           string   visible/matched text (default: `value`)
//     group          string   optional group label, JS-only rendering hint (see above)
//     disabled       bool     JS-enhanced-only (native datalist has no reliable disabled-option
//                              support); still reaches the zero-JS suggestion list
//   value          string   preselects the option whose `value` matches -- its `text` becomes the
//                          input's initial value
//   placeholder    string   native `placeholder` on the input
//   empty_text     string   shown by combobox.js when filtering yields no matches (default:
//                          translated "No results found.", via esc_html__() -- same
//                          component-generated-UI-text precedent as calendar.php/date-picker.php,
//                          not a caller-supplied string, so it goes through WP's i18n like theirs)
//   name           string   native `name` on the input (see the v1 scope note -- always submits
//                          the display text, zero-JS or not)
//   disabled / required / readonly   bool   native attributes; `disabled` also mirrors as
//                  data-disabled="true"
//   aria_invalid   bool     sets aria-invalid="true" plus a mirrored data-invalid="true", same
//                  error-state hooks as input.php
//   id             string   native `id`; auto-generated via wp_unique_id() when omitted (needed to
//                        pair with `label` via its `for` attribute)
//   label          string   optional visible label text; when given, nests
//                        template-parts/base/label.php before the field, both wrapped in a plain
//                        <div data-slot="combobox-field">. Omit for full manual control.
//   aria_label     string   accessible name when no visible `label` is given
//   class / attributes / data_attributes   passthrough onto the <input> itself (not onto the
//                        outer <div data-slot="combobox"> wrapper)

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$options_config = is_array($config['options'] ?? null) ? $config['options'] : [];
$selected_value = array_key_exists('value', $config) ? (string) $config['value'] : null;
$placeholder = trim((string) ($config['placeholder'] ?? ''));
$empty_text = trim((string) ($config['empty_text'] ?? ''));
$name = trim((string) ($config['name'] ?? ''));
$disabled = !empty($config['disabled']);
$required = !empty($config['required']);
$readonly = !empty($config['readonly']);
$aria_invalid = !empty($config['aria_invalid']);
$id = trim((string) ($config['id'] ?? ''));
$label_text = trim((string) ($config['label'] ?? ''));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($empty_text === '') {
    $empty_text = esc_html__('No results found.', 'hengegroup-theme');
}

$options_markup = '';
$initial_text = '';

foreach ($options_config as $option_config) {
    if (!is_array($option_config)) {
        continue;
    }

    $value = trim((string) ($option_config['value'] ?? ''));

    if ($value === '') {
        continue;
    }

    $text = trim((string) ($option_config['text'] ?? ''));

    if ($text === '') {
        $text = $value;
    }

    $group = trim((string) ($option_config['group'] ?? ''));
    $option_disabled = !empty($option_config['disabled']);

    if ($selected_value !== null && $selected_value === $value) {
        $initial_text = $text;
    }

    $option_attributes = ['value' => $text, 'data-value' => $value];

    if ($group !== '') {
        $option_attributes['data-group'] = $group;
    }

    if ($option_disabled) {
        $option_attributes['data-disabled'] = 'true';
    }

    $options_markup .= sprintf(
        '<option%1$s>%2$s</option>',
        hengegroup_theme_render_attributes($option_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        esc_html($text),
    );
}

if ($options_markup === '') {
    return;
}

if ($id === '') {
    $id = 'hengegroup-theme-combobox-' . wp_unique_id();
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

$element_attributes['type'] = 'text';
$element_attributes['data-slot'] = 'combobox-input';
$element_attributes['id'] = $id;
$element_attributes['list'] = $id . '-datalist';
$element_attributes['autocomplete'] = 'off';

if ($name !== '') {
    $element_attributes['name'] = $name;
}

if ($placeholder !== '') {
    $element_attributes['placeholder'] = $placeholder;
}

if ($initial_text !== '') {
    $element_attributes['value'] = $initial_text;
}

if ($disabled) {
    $element_attributes['disabled'] = true;
    $element_attributes['data-disabled'] = 'true';
}

if ($required) {
    $element_attributes['required'] = true;
}

if ($readonly) {
    $element_attributes['readonly'] = true;
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

$input_markup = '<input' . hengegroup_theme_render_attributes($element_attributes) . '>';

$datalist_markup = sprintf(
    '<datalist id="%1$s">%2$s</datalist>',
    esc_attr($id . '-datalist'),
    $options_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

// `absolute` (not a plain in-flow block) + `z-50`: the panel must float OVER whatever comes after
// it in the document once opened, not push it down -- the wrapper below gets `relative` so this is
// positioned against it, same fix as select.php's own `select-content` (see that file for the full
// rationale). `max-h-64 overflow-y-auto` caps very long/unfiltered option lists.
$content_markup = sprintf(
    '<div class="absolute top-full left-0 z-50 mt-2 max-h-64 w-full overflow-y-auto ' .
        'rounded-lg border border-input bg-background p-1 shadow-md" ' .
        'data-slot="combobox-content" id="%1$s" role="listbox" hidden></div>',
    esc_attr($id . '-listbox'),
);

$indicator_markup = hengegroup_theme_render_icon(['name' => 'check', 'set' => 'lucide']);
$indicator_template_markup = sprintf(
    '<template data-slot="combobox-item-indicator-template">%s</template>',
    $indicator_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
$empty_template_markup = sprintf(
    '<template data-slot="combobox-empty-template">%s</template>',
    esc_html($empty_text),
);

$combobox_markup = sprintf(
    '<div class="relative" data-slot="combobox">%1$s%2$s%3$s%4$s%5$s</div>',
    $input_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $datalist_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $indicator_template_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $empty_template_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

if ($label_text === '') {
    echo $combobox_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    return;
}

ob_start();
get_template_part('template-parts/base/label', null, [
    'config' => ['text' => $label_text, 'for' => $id],
]);
$label_markup = (string) ob_get_clean();

printf(
    '<div class="flex flex-col gap-2" data-slot="combobox-field">%1$s%2$s</div>',
    $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $combobox_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
