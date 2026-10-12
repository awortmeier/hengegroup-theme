<?php

declare(strict_types=1);

// Group of toggle.php items. `single` uses radio inputs sharing a name (native exclusivity,
// role="radiogroup"); toggle-group.js turns it into role="group" with aria-pressed buttons.
// `multiple` uses checkboxes (role="group"), upgraded by toggle.js.
//
// Config:
//   type           string         single (default) | multiple
//   items          array          required, each { value (required), text/label, icon,
//                                 icon_position, aria_label (icon-only), disabled, variant, size, id }
//   value          string|array   selected value(s)
//   variant        string         default | outline | accent
//   size           string         default | sm | lg
//   disabled       bool           all items; also data-disabled on the wrapper
//   required       bool
//   aria_invalid   bool           aria-invalid + data-invalid on the wrapper
//   name           string         single: shared radio name (default wp_unique_id());
//                                 multiple: optional, items get name[]
//   orientation    string         horizontal (default) | vertical
//   aria_label     string
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$type = trim((string) ($config['type'] ?? 'single'));
$items_config = is_array($config['items'] ?? null) ? $config['items'] : [];
$group_variant = trim((string) ($config['variant'] ?? 'default'));
$group_size = trim((string) ($config['size'] ?? 'default'));
$group_disabled = !empty($config['disabled']);
$required = !empty($config['required']);
$aria_invalid = !empty($config['aria_invalid']);
$name = trim((string) ($config['name'] ?? ''));
$orientation = trim((string) ($config['orientation'] ?? 'horizontal'));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_types = ['single', 'multiple'];
$allowed_variants = ['default', 'outline', 'accent'];
$allowed_sizes = ['default', 'sm', 'lg'];
$allowed_orientations = ['horizontal', 'vertical'];

if (!in_array($type, $allowed_types, true)) {
    $type = 'single';
}

if (!in_array($group_variant, $allowed_variants, true)) {
    $group_variant = 'default';
}

if (!in_array($group_size, $allowed_sizes, true)) {
    $group_size = 'default';
}

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'horizontal';
}

$is_single = $type === 'single';

if ($is_single) {
    $selected_values = array_key_exists('value', $config) ? [(string) $config['value']] : [];

    if ($name === '') {
        $name = 'hengegroup-theme-toggle-group-' . wp_unique_id();
    }
} else {
    $raw_values = $config['value'] ?? [];
    $selected_values = array_map(
        'strval',
        is_array($raw_values) ? $raw_values : [(string) $raw_values],
    );
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

    $item_variant = trim((string) ($item_config['variant'] ?? $group_variant));

    if (!in_array($item_variant, $allowed_variants, true)) {
        $item_variant = $group_variant;
    }

    $item_size = trim((string) ($item_config['size'] ?? $group_size));

    if (!in_array($item_size, $allowed_sizes, true)) {
        $item_size = $group_size;
    }

    ob_start();
    get_template_part('template-parts/base/toggle/toggle', null, [
        'config' => [
            'type' => $is_single ? 'radio' : 'checkbox',
            'name' => $is_single ? $name : ($name !== '' ? $name . '[]' : ''),
            'value' => $value,
            'pressed' => in_array($value, $selected_values, true),
            'disabled' => $group_disabled || !empty($item_config['disabled']),
            'required' => $required,
            'variant' => $item_variant,
            'size' => $item_size,
            'text' => trim((string) ($item_config['text'] ?? ($item_config['label'] ?? ''))),
            'icon' => is_array($item_config['icon'] ?? null) ? $item_config['icon'] : null,
            'icon_position' => trim((string) ($item_config['icon_position'] ?? 'start')),
            'aria_label' => trim((string) ($item_config['aria_label'] ?? '')),
            'id' => trim((string) ($item_config['id'] ?? '')),
        ],
    ]);
    $item_markup = (string) ob_get_clean();

    // Wrapper per item: peer-checked: is a general sibling selector and would hit later items too.
    $items_markup .= '<span data-slot="toggle-group-item">' . $item_markup . '</span>';
}

if ($items_markup === '') {
    return;
}

$root_classes =
    'inline-flex flex-wrap items-center gap-1 data-[orientation=vertical]:flex-col ' .
    'data-[orientation=vertical]:items-start';

$wrapper_attributes = $attributes;
$wrapper_attributes['class'] = trim($root_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$wrapper_attributes['role'] = $is_single ? 'radiogroup' : 'group';
$wrapper_attributes['data-slot'] = 'toggle-group';
$wrapper_attributes['data-type'] = $type;
$wrapper_attributes['data-variant'] = $group_variant;
$wrapper_attributes['data-size'] = $group_size;
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
