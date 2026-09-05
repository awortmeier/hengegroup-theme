<?php

declare(strict_types=1);

// shadcn/ui's DropdownMenuCheckboxItem: `role="menuitemcheckbox"` -- unlike checkbox.php/
// switch.php, there is no native HTML control behind this at all (no valid ARIA role
// transformation gets a `<button>`/plain element to "menuitemcheckbox" for free the way
// `role="switch"` legitimately works on `<input type="checkbox">`, see switch.php's header
// comment). Toggling only has meaning as live, client-side menu interaction to begin with -- there
// is nothing to submit/persist server-side the way an actual form checkbox has -- so this is a
// genuinely JS-only interactive control, not a progressive enhancement over a working native
// fallback. Stated plainly: without assets/js/template-parts/base/dropdown-menu.js, this item
// still renders its correct initial `checked` state (real content, really announced by a
// screen reader on focus) but clicking it does nothing -- there is no honest native or
// server-side substitute for "toggle client state on click" (see CLAUDE.md #1).
//
// The indicator (check icon) is always rendered, for both checked and unchecked states; project
// CSS shows/hides it off of `data-state="checked"|"unchecked"` (same pattern as other data-state
// driven components in this theme) instead of this file conditionally omitting the markup --
// avoids dropdown-menu.js needing to clone/build icon markup itself, see
// hengegroup_theme_render_icon().
//
// Supported config:
//   text        string   required. Visible label
//   checked     bool     default false. Initial state, see above
//   disabled    bool     native `disabled` on the <button>
//   class / attributes / data_attributes   passthrough, as in the other base parts
//
// Phase 2 (CLAUDE.md Regel 1): styled via shadcn's own real stock DropdownMenuCheckboxItem class
// recipe (live-checked against current docs) adapted onto this project's own tokens -- see
// dropdown-menu.php's own header comment for why this file's open-panel look isn't traced to the
// Claude-Design reference like every prior Phase 2 entry (the panel never rendered during this
// session). `group` on the `<button>` lets the indicator span below react to this same element's
// own `data-state` via `group-data-[state=...]:` -- the indicator is always rendered (see above),
// visibility is the only thing toggling.

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

$indicator_markup = hengegroup_theme_render_icon(['name' => 'check', 'set' => 'lucide']);

// `absolute left-2` positions the indicator inside the item's own `pl-8` reserved gutter (below);
// `group-data-[state=unchecked]:hidden` is the only thing toggling it, see the Phase 2 file header.
$indicator_classes =
    'absolute left-2 flex size-3.5 items-center justify-center ' .
    'group-data-[state=unchecked]:hidden [&_svg]:size-4';

$inner_html = sprintf(
    '<span class="%1$s" data-slot="dropdown-menu-item-indicator">%2$s</span>' .
        '<span data-slot="dropdown-menu-item-text">%3$s</span>',
    esc_attr($indicator_classes),
    $indicator_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);

// `group` lets the indicator span above react to this element's own `data-state`, see the Phase 2
// file header. `pl-8` reserves the indicator's gutter (shadcn's own real spacing), `pr-2` matches
// dropdown-menu-item.php's own trailing edge.
$base_classes =
    'group relative flex w-full cursor-default items-center gap-2 rounded-lg py-1.5 pr-2 pl-8 ' .
    'text-left text-sm outline-hidden select-none hover:bg-accent hover:text-accent-foreground ' .
    'focus:bg-accent focus:text-accent-foreground disabled:pointer-events-none disabled:opacity-50';

$element_attributes = $attributes;

$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['type'] = 'button';
$element_attributes['data-slot'] = 'dropdown-menu-checkbox-item';
$element_attributes['role'] = 'menuitemcheckbox';
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
