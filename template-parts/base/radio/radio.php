<?php

declare(strict_types=1);

// shadcn/ui's RadioGroupItem wraps a headless UI primitive (historically Radix UI; shadcn now
// also ships Base UI/React Aria variants of many components): a <button role="radio"> plus a
// hidden native input, driven by JS group state. A radio button is exactly the case where native
// HTML already does the job without any of that -- <input type="radio"> has full native grouping
// (same `name` = mutually exclusive), checked/disabled/required/keyboard/form-submission behaviour
// built in (see CLAUDE.md #1). This component is a plain, data-attributed <input type="radio">,
// optionally paired with template-parts/base/label.php (same convenience pattern
// as checkbox.php's `label` config).
//
// Standalone atom for manual composition/custom layouts. For the common case of rendering a whole
// group of radios from a simple list, use template-parts/base/radio/radio-group.php instead, which
// nests this file per item and auto-wires the shared `name` + `checked` state from a single
// `value` config.
//
// Supported config:
//   checked        bool     default false. Native `checked` attribute.
//   disabled       bool     native `disabled` attribute, plus a mirrored `data-disabled="true"`
//                            CSS hook (matches checkbox.php/shadcn's own convention)
//   required       bool     native `required` attribute (on a radio group, any one item having
//                            `required` makes the whole group require a selection).
//   name           string   native form field name -- MUST be shared across all radios that
//                            should form one mutually exclusive group.
//   value          string   native `value` attribute (the value submitted when this radio is
//                            checked).
//   aria_invalid   bool     sets aria-invalid="true" plus a mirrored data-invalid="true" CSS
//                            hook, same error-state hooks as input.php/checkbox.php (previously
//                            missing here -- inconsistent with the rest of the form-control
//                            family). radio-group.php also has its own group-level `aria_invalid`
//                            for the whole group.
//   id             string   native `id`; auto-generated via wp_unique_id() when omitted (needed
//                            to pair with `label` via its `for` attribute)
//   label          string   optional visible label text; when given, nests
//                            template-parts/base/label.php right after the input, both wrapped in
//                            a plain <span data-slot="radio-field">. Omit for full manual control:
//                            render the bare input, then compose your own label.php call +
//                            wrapper markup at the call site (same escape hatch as checkbox.php)
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
$name = trim((string) ($config['name'] ?? ''));
$value = trim((string) ($config['value'] ?? ''));
$id = trim((string) ($config['id'] ?? ''));
$label_text = trim((string) ($config['label'] ?? ''));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($id === '') {
    $id = 'hengegroup-theme-radio-' . wp_unique_id();
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['type'] = 'radio';
$element_attributes['data-slot'] = 'radio';
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

$input_markup = '<input' . hengegroup_theme_render_attributes($element_attributes) . '>';

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
    '<span data-slot="radio-field">%1$s%2$s</span>',
    $input_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
