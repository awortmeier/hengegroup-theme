<?php

declare(strict_types=1);

// shadcn/ui's DropdownMenuRadioItem: `role="menuitemradio"`, same reasoning as
// dropdown-menu-checkbox-item.php (no native HTML control behind it, genuinely JS-only
// interactivity, see that file's header comment) -- just the mutually-exclusive-within-a-group
// variant. Standalone atom for manual composition; for the common case of rendering a whole group
// from a simple list, use template-parts/base/dropdown-menu/dropdown-menu-radio-group.php instead,
// which nests this file per item (same convenience pattern as
// radio-group.php/radio.php).
//
// The indicator (dot icon) is always rendered; project CSS shows/hides it off of
// `data-state="checked"|"unchecked"`, same reasoning as dropdown-menu-checkbox-item.php's own
// indicator.
//
// Supported config:
//   text        string   required. Visible label
//   checked     bool     default false. Initial state
//   disabled    bool     native `disabled` on the <button>
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ''));
$checked = !empty($config['checked']);
$disabled = !empty($config['disabled']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($text === '') {
    return;
}

$indicator_markup = hengegroup_theme_render_icon(['name' => 'circle', 'set' => 'lucide']);

$inner_html = sprintf(
    '<span data-slot="dropdown-menu-item-indicator">%1$s</span><span data-slot="dropdown-menu-item-text">%2$s</span>',
    $indicator_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['type'] = 'button';
$element_attributes['data-slot'] = 'dropdown-menu-radio-item';
$element_attributes['role'] = 'menuitemradio';
$element_attributes['aria-checked'] = $checked ? 'true' : 'false';
$element_attributes['data-state'] = $checked ? 'checked' : 'unchecked';

if ($disabled) {
    $element_attributes['disabled'] = true;
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<button%1$s>%2$s</button>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
