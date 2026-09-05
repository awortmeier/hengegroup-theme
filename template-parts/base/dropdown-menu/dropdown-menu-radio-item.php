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
//
// Phase 2 (CLAUDE.md Regel 1): styled via shadcn's own real stock DropdownMenuRadioItem class
// recipe (live-checked against current docs) adapted onto this project's own tokens -- same
// derivation as dropdown-menu-checkbox-item.php's own Phase 2 note (see that file's header for why
// this isn't traced to the Claude-Design reference like every prior Phase 2 entry, and for the
// `group`/indicator-visibility technique reused verbatim here).

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

// `fill-current` makes the dot icon a solid disc (shadcn's own real look -- an outline circle would
// read as an unchecked radio), same reasoning as dropdown-menu-checkbox-item.php's own indicator
// classes.
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

// Same base classes/`group` technique as dropdown-menu-checkbox-item.php, see that file's header.
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
