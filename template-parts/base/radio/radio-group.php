<?php

declare(strict_types=1);

// Radio group (role="radiogroup") of radio.php items; exclusivity via the shared native `name`.
// Arrow keys follow DOM order regardless of `orientation`.
//
// Config:
//   items          array    required, each { value (required), label, disabled, id }
//   value          string   checked item
//   name           string   shared name, default wp_unique_id()
//   disabled       bool     all items; also data-disabled on the wrapper
//   required       bool     set on every item
//   aria_invalid   bool     aria-invalid + data-invalid on the wrapper
//   orientation    string   vertical (default) | horizontal
//   aria_label     string
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$items_config = is_array($config['items'] ?? null) ? $config['items'] : [];
$selected_value = array_key_exists('value', $config) ? (string) $config['value'] : null;
$name = trim((string) ($config['name'] ?? ''));
$group_disabled = !empty($config['disabled']);
$required = !empty($config['required']);
$aria_invalid = !empty($config['aria_invalid']);
$orientation = trim((string) ($config['orientation'] ?? 'vertical'));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_orientations = ['horizontal', 'vertical'];

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'vertical';
}

if ($name === '') {
    $name = 'hengegroup-theme-radio-group-' . wp_unique_id();
}

$items_markup = '';

foreach ($items_config as $item_config) {
    if (!is_array($item_config)) {
        continue;
    }

    $value = trim((string) ($item_config['value'] ?? ''));

    if ($value === '') {
        continue;
    }

    ob_start();
    get_template_part('template-parts/base/radio/radio', null, [
        'config' => [
            'name' => $name,
            'value' => $value,
            'checked' => $selected_value !== null && $selected_value === $value,
            'disabled' => $group_disabled || !empty($item_config['disabled']),
            'required' => $required,
            'id' => trim((string) ($item_config['id'] ?? '')),
            'label' => trim((string) ($item_config['label'] ?? '')),
        ],
    ]);
    $items_markup .= (string) ob_get_clean();
}

if ($items_markup === '') {
    return;
}

$base_classes =
    'grid gap-3 data-[orientation=horizontal]:grid-flow-col data-[orientation=horizontal]:auto-cols-max';

$wrapper_attributes = $attributes;
$wrapper_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$wrapper_attributes['role'] = 'radiogroup';
$wrapper_attributes['data-slot'] = 'radio-group';
$wrapper_attributes['data-orientation'] = $orientation;
$wrapper_attributes['aria-orientation'] = $orientation;

if ($group_disabled) {
    $wrapper_attributes['data-disabled'] = 'true';
}

if ($aria_invalid) {
    $wrapper_attributes['aria-invalid'] = 'true';
    $wrapper_attributes['data-invalid'] = 'true';
}

if ($aria_label !== '') {
    $wrapper_attributes['aria-label'] = $aria_label;
}

$wrapper_attributes = hengegroup_theme_merge_data_attributes($wrapper_attributes, $data_attributes);

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $items_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
