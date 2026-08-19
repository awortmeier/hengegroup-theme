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
