<?php

declare(strict_types=1);

// shadcn/ui's Textarea has no headless-library primitive either (Radix UI/Base UI/React Aria
// alike) -- it's a styled native <textarea>, no JS
// state at all (see CLAUDE.md #1). Same as input.php: a plain, data-attributed <textarea>,
// optionally paired with template-parts/base/label.php (same convenience pattern
// as input.php's `label` config, label before the field).
//
// Unlike <input>, a <textarea>'s value is its text content, not a `value` attribute -- handled
// accordingly below (escaped via esc_textarea() and placed between the opening/closing tags).
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind, classes taken 1:1 from shadcn's own Textarea
// component (registry/new-york-v4/ui/textarea.tsx), same deviations as input.php (see that file's
// header for the full rationale): `dark:`-prefixed classes dropped, `rounded-md` -> `rounded-lg`,
// padding mapped to the Bewerbungsformular reference's `12px 14px` (`py-3`/`px-3.5`), and a
// `data_slot === 'input-group-control'` branch for input-group.php composition.
//
// Supported config:
//   name / value / placeholder / autocomplete / minlength / maxlength   string   native
//                    attributes, only rendered when given (`value` becomes the element's text
//                    content)
//   rows / cols      int|string   native attributes, only rendered when given
//   wrap             string   soft | hard (native <textarea wrap>, only rendered when valid)
//   disabled / required / readonly   bool   native attributes; `disabled` also mirrors as
//                    data-disabled="true"
//   aria_invalid     bool    sets aria-invalid="true" plus a mirrored data-invalid="true" --
//                    same error-state hooks as input.php
//   id               string  native `id`; auto-generated via wp_unique_id() when omitted (needed
//                            to pair with `label` via its `for` attribute)
//   data_slot        string  overrides the root `data-slot` value (default: 'textarea') -- same
//                            composing-parent escape hatch as input.php's `data_slot`, used by
//                            input-group.php ('input-group-control')
//   label            string  optional visible label text; when given, nests
//                            template-parts/base/label.php before the textarea, both wrapped in a
//                            plain <div data-slot="textarea-field">. Omit for full manual control.
//   aria_label       string  accessible name when no visible `label` is given
//   class / attributes / data_attributes   passthrough onto the <textarea> itself (not onto the
//                            optional wrapper div, which stays a plain, unstyled hook)

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$value = (string) ($config['value'] ?? '');
$name = trim((string) ($config['name'] ?? ''));
$placeholder = trim((string) ($config['placeholder'] ?? ''));
$autocomplete = trim((string) ($config['autocomplete'] ?? ''));
$minlength = trim((string) ($config['minlength'] ?? ''));
$maxlength = trim((string) ($config['maxlength'] ?? ''));
$rows = trim((string) ($config['rows'] ?? ''));
$cols = trim((string) ($config['cols'] ?? ''));
$wrap = trim((string) ($config['wrap'] ?? ''));
$disabled = !empty($config['disabled']);
$required = !empty($config['required']);
$readonly = !empty($config['readonly']);
$aria_invalid = !empty($config['aria_invalid']);
$id = trim((string) ($config['id'] ?? ''));
$data_slot = trim((string) ($config['data_slot'] ?? ''));
$label_text = trim((string) ($config['label'] ?? ''));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($data_slot === '') {
    $data_slot = 'textarea';
}

$allowed_wraps = ['soft', 'hard'];

if (!in_array($wrap, $allowed_wraps, true)) {
    $wrap = '';
}

if ($id === '') {
    $id = 'hengegroup-theme-textarea-' . wp_unique_id();
}

$is_group_control = $data_slot === 'input-group-control';

$computed_class = $is_group_control
    ? 'flex-1 min-w-0 field-sizing-content rounded-none border-0 bg-transparent px-3.5 py-3 ' .
        'text-base shadow-none outline-none focus-visible:ring-0 ' .
        'placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 ' .
        'md:text-sm'
    : 'border-input placeholder:text-muted-foreground flex field-sizing-content min-h-16 ' .
        'w-full rounded-lg border bg-background px-3.5 py-3 text-base shadow-xs ' .
        'transition-[color,box-shadow] outline-none disabled:cursor-not-allowed ' .
        'disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 ' .
        'focus-visible:ring-[3px] aria-invalid:ring-destructive/20 aria-invalid:border-destructive';

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['data-slot'] = $data_slot;
$element_attributes['id'] = $id;

if ($name !== '') {
    $element_attributes['name'] = $name;
}

if ($placeholder !== '') {
    $element_attributes['placeholder'] = $placeholder;
}

if ($autocomplete !== '') {
    $element_attributes['autocomplete'] = $autocomplete;
}

if ($minlength !== '') {
    $element_attributes['minlength'] = $minlength;
}

if ($maxlength !== '') {
    $element_attributes['maxlength'] = $maxlength;
}

if ($rows !== '') {
    $element_attributes['rows'] = $rows;
}

if ($cols !== '') {
    $element_attributes['cols'] = $cols;
}

if ($wrap !== '') {
    $element_attributes['wrap'] = $wrap;
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

$textarea_markup = sprintf(
    '<textarea%1$s>%2$s</textarea>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_textarea($value),
);

if ($label_text === '') {
    echo $textarea_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    return;
}

ob_start();
get_template_part('template-parts/base/label', null, [
    'config' => ['text' => $label_text, 'for' => $id],
]);
$label_markup = (string) ob_get_clean();

printf(
    '<div class="flex flex-col gap-2" data-slot="textarea-field">%1$s%2$s</div>',
    $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $textarea_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
