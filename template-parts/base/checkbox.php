<?php

declare(strict_types=1);

// shadcn/ui's Checkbox wraps a headless UI primitive (historically Radix UI; shadcn now also
// ships Base UI/React Aria variants of many components): a <button role="checkbox"> plus a
// hidden native input, driven entirely by JS state (checked/onCheckedChange). A checkbox is
// exactly the case where native HTML already does the job without any of that --
// <input type="checkbox"> has full native checked/disabled/required/keyboard/touch/form-submission
// behaviour built in (see CLAUDE.md #1: natives HTML-Verhalten hat Vorrang). This component is a
// plain, data-attributed <input type="checkbox">, optionally paired with
// template-parts/base/label.php (composition instead of duplicating a
// <label> tag here).
//
// Supported config:
//   checked        bool     default false. Native `checked` attribute.
//   disabled       bool     native `disabled` attribute, plus a mirrored `data-disabled="true"`
//                            CSS hook (shadcn sets both alongside each other on their own
//                            Checkbox, not just the native attribute)
//   required       bool     native `required` attribute.
//   indeterminate  bool     sets data-indeterminate="true" as a JS hook only -- there is no HTML
//                            attribute for indeterminate, it's a DOM property, JS-only in every
//                            browser. Deferred extension point: applying the actual visual state
//                            needs a one-line script (`el.indeterminate = true`) that isn't part
//                            of this theme yet -- not added unprompted (see CLAUDE.md #1).
//   aria_invalid   bool     sets aria-invalid="true" plus a mirrored data-invalid="true" CSS
//                            hook, same error-state hooks as input.php/textarea.php/
//                            native-select.php (shadcn's current Checkbox docs show both as real
//                            props, previously missing here -- inconsistent with the rest of the
//                            form-control family)
//   name / value   string   native form attributes, only rendered when given
//   id             string   native `id`; auto-generated via wp_unique_id() when omitted (needed
//                            to pair with `label` via its `for` attribute)
//   label          string   optional visible label text; when given, nests
//                            template-parts/base/label.php right after the input, both wrapped in
//                            a plain <span data-slot="checkbox-field">. Omit for full manual
//                            control: render the bare input, then compose your own label.php call
//                            + wrapper markup at the call site (shadcn's own usage pattern --
//                            Checkbox and Label are independent, composed by the caller)
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
$indeterminate = !empty($config['indeterminate']);
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
    $id = 'base-theme-checkbox-' . wp_unique_id();
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['type'] = 'checkbox';
$element_attributes['data-slot'] = 'checkbox';
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

if ($indeterminate) {
    $element_attributes['data-indeterminate'] = 'true';
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
    '<span data-slot="checkbox-field">%1$s%2$s</span>',
    $input_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
