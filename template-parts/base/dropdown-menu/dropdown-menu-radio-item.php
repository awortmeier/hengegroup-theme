<?php

declare(strict_types=1);

// Menu item with role="menuitemradio" (needs dropdown-menu.js). Usually rendered via
// dropdown-menu-radio-group.php. The dot is always rendered and hidden via data-state.
//
// Config:
//   text       string   required
//   checked    bool
//   disabled   bool
//   class / attributes / data_attributes

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

$indicator_classes =
    'absolute left-2 flex size-3.5 items-center justify-center ' .
    'group-data-[state=unchecked]:hidden [&_svg]:size-2 [&_svg]:fill-current';

$inner_html = sprintf(
    '<span class="%1$s" data-slot="dropdown-menu-item-indicator">%2$s</span>' .
        '<span data-slot="dropdown-menu-item-text">%3$s</span>',
    esc_attr($indicator_classes),
    $indicator_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);

$base_classes =
    'group relative flex w-full cursor-default items-center gap-2 rounded-lg py-1.5 pr-2 pl-8 ' .
    'text-left text-sm outline-hidden select-none hover:bg-accent hover:text-accent-foreground ' .
    'focus:bg-accent focus:text-accent-foreground disabled:pointer-events-none disabled:opacity-50';

$element_attributes = $attributes;

$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['type'] = 'button';
$element_attributes['data-slot'] = 'dropdown-menu-radio-item';
$element_attributes['role'] = 'menuitemradio';
$element_attributes['aria-checked'] = $checked ? 'true' : 'false';
$element_attributes['data-state'] = $checked ? 'checked' : 'unchecked';

if ($disabled) {
    $element_attributes['disabled'] = true;
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<button%1$s>%2$s</button>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
