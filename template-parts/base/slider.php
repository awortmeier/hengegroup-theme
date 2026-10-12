<?php

declare(strict_types=1);

// Native <input type="range">, single value, colored via accent-color.
//
// Config:
//   min / max / step   string|int
//   value              string|int
//   name               string
//   disabled           bool     also data-disabled
//   aria_invalid       bool     aria-invalid + data-invalid
//   orientation        string   horizontal (default) | vertical (only data-orientation)
//   id                 string   default wp_unique_id()
//   label              string   adds label.php
//   aria_label         string
//   class / attributes / data_attributes   on the <input>

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$min = trim((string) ($config['min'] ?? ''));
$max = trim((string) ($config['max'] ?? ''));
$step = trim((string) ($config['step'] ?? ''));
$value = trim((string) ($config['value'] ?? ''));
$name = trim((string) ($config['name'] ?? ''));
$disabled = !empty($config['disabled']);
$aria_invalid = !empty($config['aria_invalid']);
$orientation = trim((string) ($config['orientation'] ?? 'horizontal'));
$id = trim((string) ($config['id'] ?? ''));
$label_text = trim((string) ($config['label'] ?? ''));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_orientations = ['horizontal', 'vertical'];

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'horizontal';
}

if ($id === '') {
    $id = 'hengegroup-theme-slider-' . wp_unique_id();
}

$base_classes =
    'accent-henge-green h-1.5 w-full cursor-pointer rounded-full outline-none ' .
    'focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 ' .
    'disabled:cursor-not-allowed disabled:opacity-50';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['type'] = 'range';
$element_attributes['data-slot'] = 'slider';
$element_attributes['data-orientation'] = $orientation;
$element_attributes['id'] = $id;

if ($name !== '') {
    $element_attributes['name'] = $name;
}

if ($min !== '') {
    $element_attributes['min'] = $min;
}

if ($max !== '') {
    $element_attributes['max'] = $max;
}

if ($step !== '') {
    $element_attributes['step'] = $step;
}

if ($value !== '') {
    $element_attributes['value'] = $value;
}

if ($disabled) {
    $element_attributes['disabled'] = true;
    $element_attributes['data-disabled'] = 'true';
}

if ($aria_invalid) {
    $element_attributes['aria-invalid'] = 'true';
    $element_attributes['data-invalid'] = 'true';
}

if ($label_text === '' && $aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

$slider_markup = '<input' . hengegroup_theme_render_attributes($element_attributes) . '>';

if ($label_text === '') {
    echo $slider_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    return;
}

ob_start();
get_template_part('template-parts/base/label', null, [
    'config' => ['text' => $label_text, 'for' => $id],
]);
$label_markup = (string) ob_get_clean();

printf(
    '<div class="flex flex-col gap-2" data-slot="slider-field">%1$s%2$s</div>',
    $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $slider_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
