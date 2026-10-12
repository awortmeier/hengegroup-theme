<?php

declare(strict_types=1);

// Native <input type="radio">, colored via accent-color. Usually rendered via radio-group.php.
//
// Config:
//   checked        bool
//   disabled       bool     also data-disabled
//   required       bool
//   name           string   shared by all radios of a group
//   value          string
//   aria_invalid   bool     aria-invalid + data-invalid
//   id             string   default wp_unique_id()
//   label          string   adds label.php, both wrapped in <span data-slot="radio-field">
//   aria_label     string   when there is no visible label
//   class / attributes / data_attributes   on the <input>

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

$base_classes =
    'peer accent-henge-green aspect-square size-4 shrink-0 rounded-full border border-input ' .
    'shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring ' .
    'focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive ' .
    'aria-invalid:ring-destructive/20 disabled:cursor-not-allowed disabled:opacity-50';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

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
    '<span class="inline-flex items-center gap-2" data-slot="radio-field">%1$s%2$s</span>',
    $input_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
