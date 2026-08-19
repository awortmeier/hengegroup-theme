<?php

declare(strict_types=1);

// shadcn/ui's DropdownMenuRadioGroup: `role="group"` around several DropdownMenuRadioItem, JS
// enforces the mutual exclusivity (unlike radio-group.php's native <input type="radio">
// `name`-sharing, there is no native grouping mechanism here at all -- see
// dropdown-menu-radio-item.php's header comment on why these are JS-only controls). Nests several
// template-parts/base/dropdown-menu/dropdown-menu-radio-item.php calls and wires up `checked` from
// a single `value` (same convenience pattern as radio-group.php/radio.php).
//
// Supported config:
//   items    array    required, ordered list of:
//     value      string   required. This item's value
//     text       string   visible label (default: `value`)
//     disabled   bool     per-item disabled
//   value    string   the currently checked item's `value`
//   class / attributes / data_attributes   passthrough onto the outer
//                    <div role="group" data-slot="dropdown-menu-radio-group"> wrapper

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$items_config = is_array($config['items'] ?? null) ? $config['items'] : [];
$selected_value = array_key_exists('value', $config) ? (string) $config['value'] : null;
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

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
    get_template_part('template-parts/base/dropdown-menu/dropdown-menu-radio-item', null, [
        'config' => [
            'text' => trim((string) ($item_config['text'] ?? $value)),
            'checked' => $selected_value !== null && $selected_value === $value,
            'disabled' => !empty($item_config['disabled']),
            'data_attributes' => ['value' => $value],
        ],
    ]);
    $items_markup .= (string) ob_get_clean();
}

if ($items_markup === '') {
    return;
}

$wrapper_attributes = $attributes;

if ($class_name !== '') {
    $wrapper_attributes['class'] = $class_name;
}

$wrapper_attributes['role'] = 'group';
$wrapper_attributes['data-slot'] = 'dropdown-menu-radio-group';

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $wrapper_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<div%1$s>%2$s</div>',
    base_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $items_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
