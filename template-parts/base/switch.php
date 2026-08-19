<?php

declare(strict_types=1);

// shadcn/ui's Switch wraps a headless UI primitive (historically Radix UI; shadcn now also ships
// Base UI/React Aria variants of many components): a <button role="switch" aria-checked> plus a
// hidden native input, driven by JS state (checked/onCheckedChange) -- the same architecture as
// Checkbox/Toggle. Unlike Toggle's checkbox-vs-button situation (see toggle.php's header comment),
// Switch has a genuinely trade-off-free native equivalent: `role="switch"` is an explicitly
// PERMITTED ARIA role transformation on `<input type="checkbox">` (unlike role="button", which
// isn't in checkbox's allowed role list -- that's exactly why toggle.php documents a semantic gap
// and this file doesn't need to). A plain `<input type="checkbox" role="switch">` is announced by
// screen readers as "switch, on/off", matching shadcn's own Switch 1:1 -- full native
// checked/disabled/required/keyboard/touch/form-submission behaviour, zero JS (see CLAUDE.md #1).
// This component is that input, data-attributed, optionally paired with
// template-parts/base/label.php (same convenience pattern as checkbox.php's
// `label` config).
//
// `data-state="checked"|"unchecked"` mirrors shadcn's own Switch vocabulary (distinct from
// toggle.php's "on"/"off") but, like data-state elsewhere in this theme (progress.php/avatar.php),
// only reflects the value at render time -- project CSS should style off of the native
// `:checked` pseudo-class for anything that needs to stay live after a user interaction, not this
// attribute; it's provided purely so `[data-slot="switch"][data-state="..."]` selectors are
// available for parity with shadcn's own styling convention.
//
// Supported config:
//   checked        bool     default false. Native `checked` attribute.
//   disabled       bool     native `disabled` attribute, plus a mirrored `data-disabled="true"`
//                            CSS hook (matches checkbox.php/shadcn's own convention)
//   required       bool     native `required` attribute.
//   aria_invalid   bool     sets aria-invalid="true" plus a mirrored data-invalid="true" CSS hook,
//                            same error-state hooks as checkbox.php/input.php
//   size           string   default | sm (default: default) -- shadcn's Switch size axis (as of
//                            its Base UI multi-backend rewrite); sets data-size only, actual
//                            dimensions are project-CSS (see CLAUDE.md #1)
//   name / value   string   native form attributes, only rendered when given
//   id             string   native `id`; auto-generated via wp_unique_id() when omitted (needed to
//                            pair with `label` via its `for` attribute)
//   label          string   optional visible label text; when given, nests
//                            template-parts/base/label.php right after the input, both wrapped in a
//                            plain <span data-slot="switch-field">. Omit for full manual control,
//                            same escape hatch as checkbox.php
//   aria_label     string   accessible name when no visible `label` is given
//   class / attributes / data_attributes   passthrough onto the <input> itself (not onto the
//                            optional wrapper span, which stays a plain, unstyled hook)

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$checked = !empty($config['checked']);
$disabled = !empty($config['disabled']);
$required = !empty($config['required']);
$aria_invalid = !empty($config['aria_invalid']);
$size = trim((string) ($config['size'] ?? 'default'));
$name = trim((string) ($config['name'] ?? ''));
$value = trim((string) ($config['value'] ?? ''));
$id = trim((string) ($config['id'] ?? ''));
$label_text = trim((string) ($config['label'] ?? ''));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_sizes = ['default', 'sm'];

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'default';
}

if ($id === '') {
    $id = 'base-theme-switch-' . wp_unique_id();
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['type'] = 'checkbox';
$element_attributes['role'] = 'switch';
$element_attributes['data-slot'] = 'switch';
$element_attributes['data-size'] = $size;
$element_attributes['data-state'] = $checked ? 'checked' : 'unchecked';
$element_attributes['id'] = $id;

if ($name !== '') {
    $element_attributes['name'] = $name;
}

if ($value !== '') {
    $element_attributes['value'] = $value;
}

if ($checked) {
    $element_attributes['checked'] = true;
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

$input_markup = '<input' . base_theme_render_attributes($element_attributes) . '>';

if ($label_text === '') {
    echo $input_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    return;
}

ob_start();
get_template_part('template-parts/base/label', null, [
    'config' => ['text' => $label_text, 'for' => $id],
]);
$label_markup = (string) ob_get_clean();

printf(
    '<span data-slot="switch-field">%1$s%2$s</span>',
    $input_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
