<?php

declare(strict_types=1);

// shadcn/ui's InputOTP wraps the third-party `input-otp` library: a single hidden real <input>
// synced via JS to N visually separate "slot" boxes (with a fake per-slot blinking caret) for a
// segmented look. For the actual OTP use case, a single plain native <input> is not just
// sufficient but the platform-recommended approach: `autocomplete="one-time-code"` on ONE input is
// what enables reliable SMS/clipboard OTP autofill in browsers -- splitting it into several inputs
// (or a JS-synced fake one) is known to make that autofill less reliable, not more (see
// CLAUDE.md #1). This component is that single native input.
//
// Segmented-boxes look without JS: achievable on this single input via CSS alone (a
// `letter-spacing` sized to the desired box width plus a repeating background gradient used as
// divider lines) -- a project-CSS concern, not baked into this component (see CLAUDE.md #1).
//
// Supported config:
//   length           int      OTP code length (default: 6). Sets both `maxlength` and `minlength`
//                             unless `minlength` is given separately (e.g. for a variable-length
//                             code).
//   minlength        int      overrides the length-derived `minlength` when given
//   numeric          bool     default true. true -> inputmode="numeric" + pattern="[0-9]*"
//                             (numeric mobile keypad, digit-only client-side hint); false ->
//                             neither is set, for alphanumeric codes
//   pattern          string   overrides the `numeric`-derived pattern when given
//   value            string   native `value`
//   name             string   native `name`
//   disabled / required / readonly   bool   native attributes; `disabled` also mirrors as
//                             data-disabled="true"
//   aria_invalid     bool     sets aria-invalid="true" plus a mirrored data-invalid="true", same
//                             error-state hooks as input.php
//   id               string   native `id`; auto-generated via wp_unique_id() when omitted (needed
//                             to pair with `label` via its `for` attribute)
//   label            string   optional visible label text; when given, nests
//                             template-parts/base/label.php before the field, both wrapped in a
//                             plain <div data-slot="input-otp-field">. Omit for full manual
//                             control.
//   aria_label       string   accessible name when no visible `label` is given
//   class / attributes / data_attributes   passthrough onto the <input> itself (not onto the
//                             optional wrapper div, which stays a plain, unstyled hook)

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$length = (int) ($config['length'] ?? 6);

if ($length < 1) {
    $length = 6;
}

$minlength = trim((string) ($config['minlength'] ?? ''));
$numeric = !array_key_exists('numeric', $config) || !empty($config['numeric']);
$pattern = trim((string) ($config['pattern'] ?? ''));
$value = trim((string) ($config['value'] ?? ''));
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

if ($pattern === '' && $numeric) {
    $pattern = '[0-9]*';
}

if ($minlength === '') {
    $minlength = (string) $length;
}

if ($id === '') {
    $id = 'base-theme-input-otp-' . wp_unique_id();
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['type'] = 'text';
$element_attributes['data-slot'] = 'input-otp';
$element_attributes['id'] = $id;
$element_attributes['autocomplete'] = 'one-time-code';
$element_attributes['maxlength'] = (string) $length;
$element_attributes['minlength'] = $minlength;

if ($numeric) {
    $element_attributes['inputmode'] = 'numeric';
}

if ($pattern !== '') {
    $element_attributes['pattern'] = $pattern;
}

if ($name !== '') {
    $element_attributes['name'] = $name;
}

if ($value !== '') {
    $element_attributes['value'] = $value;
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
    '<div data-slot="input-otp-field">%1$s%2$s</div>',
    $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $input_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
