<?php

declare(strict_types=1);

// Native <input type="checkbox" role="switch">; the thumb is an ::after pseudo-element.
// data-state reflects render time only, style off :checked.
//
// Config:
//   checked        bool
//   disabled       bool     also data-disabled
//   required       bool
//   aria_invalid   bool     aria-invalid + data-invalid
//   size           string   default | sm
//   name / value   string
//   id             string   default wp_unique_id()
//   label          string   adds label.php, both wrapped in <span data-slot="switch-field">
//   aria_label     string
//   class / attributes / data_attributes   on the <input>

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
    $id = 'hengegroup-theme-switch-' . wp_unique_id();
}

$base_classes =
    'peer relative inline-flex shrink-0 cursor-pointer appearance-none items-center ' .
    'rounded-full border border-transparent bg-input shadow-xs transition-colors outline-none ' .
    'after:absolute after:top-1/2 after:left-0 after:-translate-y-1/2 after:rounded-full ' .
    "after:bg-background after:shadow-sm after:transition-transform after:content-[''] " .
    'checked:bg-henge-green checked:after:translate-x-3.5 focus-visible:border-ring ' .
    'focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive ' .
    'aria-invalid:ring-destructive/20 disabled:cursor-not-allowed disabled:opacity-50';

// Fixed 14px shift instead of calc(100% - 2px): track and thumb differ by 16px in both sizes.
$size_classes = [
    'default' => 'h-[1.15rem] w-8 after:size-4',
    'sm' => 'h-4 w-7 after:size-3',
];

$computed_class = "{$base_classes} {$size_classes[$size]}";

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

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

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

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
    '<span class="inline-flex items-center gap-2" data-slot="switch-field">%1$s%2$s</span>',
    $input_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
