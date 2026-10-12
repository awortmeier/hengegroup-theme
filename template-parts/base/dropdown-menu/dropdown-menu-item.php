<?php

declare(strict_types=1);

// Menu item (role="menuitem") as <button>, or <a> with `href`. Works without JS; dropdown-menu.js
// adds arrow-key navigation.
//
// Config:
//   text       string   required
//   href       string   renders <a>
//   icon       array    icon.php config, before the text
//   shortcut   string   pre-rendered kbd.php/kbd-group.php markup, right-aligned
//   variant    string   default | destructive
//   inset      bool     aligns text-only items with icon items
//   disabled   bool     <a>: drops href and sets aria-disabled
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ''));
$href = trim((string) ($config['href'] ?? ''));
$icon_config = is_array($config['icon'] ?? null) ? $config['icon'] : null;
$shortcut = (string) ($config['shortcut'] ?? '');
$variant = trim((string) ($config['variant'] ?? 'default'));
$inset = !empty($config['inset']);
$disabled = !empty($config['disabled']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($text === '') {
    return;
}

$allowed_variants = ['default', 'destructive'];

if (!in_array($variant, $allowed_variants, true)) {
    $variant = 'default';
}

$icon_markup = $icon_config !== null ? hengegroup_theme_render_icon($icon_config) : '';

$shortcut_markup =
    $shortcut !== ''
        ? sprintf(
            '<span class="ml-auto pl-4">%s</span>',
            $shortcut, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        )
        : '';

$inner_html = sprintf(
    '%1$s<span data-slot="dropdown-menu-item-text">%2$s</span>%3$s',
    $icon_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
    $shortcut_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

$base_classes =
    'relative flex w-full cursor-default items-center gap-2 rounded-lg px-2 py-1.5 text-left ' .
    'text-sm outline-hidden select-none hover:bg-accent hover:text-accent-foreground ' .
    'focus:bg-accent focus:text-accent-foreground disabled:pointer-events-none disabled:opacity-50 ' .
    'aria-disabled:pointer-events-none aria-disabled:opacity-50 data-[inset=true]:pl-8 ' .
    'data-[variant=destructive]:text-destructive ' .
    'data-[variant=destructive]:hover:bg-destructive/10 ' .
    'data-[variant=destructive]:hover:text-destructive ' .
    'data-[variant=destructive]:focus:bg-destructive/10 ' .
    'data-[variant=destructive]:focus:text-destructive ' .
    "[&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4";

$element_attributes = $attributes;

$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'dropdown-menu-item';
$element_attributes['data-variant'] = $variant;
$element_attributes['role'] = 'menuitem';

if ($inset) {
    $element_attributes['data-inset'] = 'true';
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

if ($href !== '') {
    if ($disabled) {
        $element_attributes['aria-disabled'] = 'true';
    } else {
        $element_attributes['href'] = $href;
    }

    printf(
        '<a%1$s>%2$s</a>',
        hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );

    return;
}

$element_attributes['type'] = 'button';

if ($disabled) {
    $element_attributes['disabled'] = true;
}

printf(
    '<button%1$s>%2$s</button>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
