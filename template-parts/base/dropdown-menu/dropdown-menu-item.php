<?php

declare(strict_types=1);

// shadcn/ui's DropdownMenuItem: a single actionable row inside dropdown-menu.php's `content`. A
// plain, real <button>/<a> (asChild/Slot analog via `href`, exactly like button.php) with
// `role="menuitem"` -- fully Tab-reachable and activatable with zero JS (see that file's header
// comment on the zero-JS baseline); dropdown-menu.js only adds roving-tabindex arrow-key
// navigation on top once it initializes, it doesn't replace basic activation.
//
// Composition: `icon` nests template-parts/base/icon.php via
// base_theme_render_icon(), exactly like button.php's icon slot. `shortcut` is caller-provided,
// pre-rendered HTML -- pass a buffered template-parts/base/kbd/kbd.php or kbd-group.php call
// (shadcn's own DropdownMenuShortcut is just styled text, kbd.php already covers that, see
// dropdown-menu.php's header comment).
//
// Supported config:
//   text          string   required. Visible label
//   href          string   renders <a> instead of <button> (shadcn's asChild/Slot analog, same as
//                          button.php)
//   icon          array    icon.php config, e.g. ['name' => 'user', 'set' => 'lucide']. Rendered
//                          before the text, same leading-icon convention as button.php's default
//                          `icon_position`
//   shortcut      string   pre-rendered HTML for a trailing keyboard-shortcut hint (see above)
//   variant       string   default | destructive (default: default) -- shadcn's DropdownMenuItem
//                          variant vocabulary
//   inset         bool     adds a data-inset="true" hook so text-only items can align with
//                          icon-having siblings (project-CSS padding, see CLAUDE.md #1)
//   disabled      bool     native `disabled` on a <button>; on an <a> (no native disabled), drops
//                          `href` and sets `aria-disabled="true"` instead, same technique as
//                          button.php's own href+disabled handling
//   class / attributes / data_attributes   passthrough, as in the other base parts

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

$icon_markup = $icon_config !== null ? base_theme_render_icon($icon_config) : '';

$inner_html = sprintf(
    '%1$s<span data-slot="dropdown-menu-item-text">%2$s</span>%3$s',
    $icon_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
    $shortcut, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'dropdown-menu-item';
$element_attributes['data-variant'] = $variant;
$element_attributes['role'] = 'menuitem';

if ($inset) {
    $element_attributes['data-inset'] = 'true';
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

if ($href !== '') {
    if ($disabled) {
        $element_attributes['aria-disabled'] = 'true';
    } else {
        $element_attributes['href'] = $href;
    }

    printf(
        '<a%1$s>%2$s</a>',
        base_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
    base_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
