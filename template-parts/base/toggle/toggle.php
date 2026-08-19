<?php

declare(strict_types=1);

// shadcn/ui's Toggle wraps a headless UI primitive (historically Radix UI; shadcn now also ships
// Base UI/React Aria variants of many components): a <button type="button" aria-pressed="true|false"
// data-state="on|off"> whose pressed state lives in JS (pressed/defaultPressed/onPressedChange) --
// a click handler flipping one attribute. That is a real, if small, JS requirement: an aria-pressed
// button does nothing on click without it, so this isn't a case where JS is merely an enhancement
// layer on top of a working baseline -- the whole feature is the click.
//
// A checkbox already IS a native two-state control with built-in toggling, keyboard support and
// form participation, and is used here instead: a real <input type="checkbox">, visually hidden but
// kept focusable (NOT display:none/visibility:hidden -- see the CSS contract below), paired with a
// <label> styled as the visible button surface (an <input> is a void element and can't hold icon/
// text children itself, so the label is where icon.php/text actually render). Clicking the label or
// space-toggling the focused checkbox both work with zero JS (see CLAUDE.md #1).
//
// Deliberate semantic trade-off in the no-JS baseline, stated plainly: the announced role/state
// differs from shadcn's own Toggle. shadcn's <button aria-pressed> is announced by screen readers
// as "button, pressed/not pressed"; this component's real interactive element is a checkbox (or,
// with `type: 'radio'`, a radio button -- see below), announced as "checkbox, checked/not checked"
// (or "radio button, selected, N of M"). Both are established, WCAG-valid ways to expose a
// boolean/choice state, but neither is an identical announcement to shadcn's -- there is no valid
// ARIA role transformation from a native checkbox/radio to role="button" (neither's allowed role
// list includes "button"), so faking the button announcement isn't attempted in pure HTML.
//
// This gap is fully closable with JS once JS is available, so per the established ARIA-gap-closing
// rule, assets/js/template-parts/base/toggle.js does exactly that: it hides the input from the
// accessibility tree (`aria-hidden` + `tabindex="-1"`) and upgrades the `<label>` to a real
// focusable `role="button" aria-pressed="true|false"` element, kept in sync with the input's own
// `checked` state. The exception is `type: 'radio'` toggles that sit inside a `role="radiogroup"`
// (toggle-group.php's `single` mode, detected via `input.closest('[role="radiogroup"]')`, not via
// `type` alone) -- there, `role="radio"` is already the correct fit (see toggle-group.php's own
// header comment), upgrading individual items to `role="button"` would break that composition, not
// improve it. Every other checkbox/radio-typed toggle -- standalone (`type: 'checkbox'`),
// toggle-group.php's own `multiple` mode (plain `role="group"`, no radiogroup constraint), and
// calendar.php's per-day toggle.php nesting in either mode (no radiogroup wrapper around a calendar
// grid) -- gets upgraded, matching shadcn's real Calendar/ToggleGroup item announcements too, not
// just standalone Toggle. A project that needs the exact aria-pressed button
// semantics without JS available at all (e.g. inside a toolbar using the WAI-ARIA "Toolbar" pattern,
// server-rendered only) should still treat the no-JS baseline above as a documented gap, not a
// silent one.
//
// `type: 'radio'` exists purely so template-parts/base/toggle/toggle-group.php can nest this file
// for its `type: 'single'` (mutually exclusive) mode -- a shared `name` across several
// radio-typed toggles gives native, zero-JS exclusive selection, the same trick radio-group.php
// already uses for radio.php. This isn't shadcn vocabulary (shadcn's Toggle has no such prop, it's
// a pure native-HTML implementation detail, see CLAUDE.md #1); standalone callers should leave it
// at its default.
//
// CSS contract: the checkbox holds the live state; style off of it, not off of the render-time
// data-state below (which only reflects the value at render time, like data-state elsewhere in this
// theme, e.g. progress.php/avatar.php).
//   [data-slot="toggle-input"] { /* visually-hidden technique, e.g. position:absolute; width:1px;
//     height:1px; overflow:hidden; clip-path:inset(50%); -- must stay focusable, not display:none */ }
//   [data-slot="toggle-input"]:checked + [data-slot="toggle"] { /* pressed styles */ }
//   [data-slot="toggle-input"]:focus-visible + [data-slot="toggle"] { /* focus ring in the no-JS
//     baseline -- focus lives on the hidden input, not the visible label */ }
//   [data-js="toggle"]:focus-visible { /* focus ring once toggle.js is active (`type: 'checkbox'`
//     only) -- toggle.js moves real focus onto the label itself, which also carries `data-js` */ }
//   [data-slot="toggle-input"]:disabled + [data-slot="toggle"] { /* disabled styles */ }
//
// Supported config:
//   pressed          bool     default false. Native `checked` on the underlying input (shadcn's own
//                              prop name for this is `pressed`/`defaultPressed`, kept here for
//                              vocabulary parity even though it maps onto `checked` under the hood)
//   type             string   checkbox | radio (default: checkbox) -- internal rendering detail, not
//                              shadcn vocabulary (see above); only toggle-group.php's `single` mode
//                              has a reason to pass `radio`
//   text / label     string   visible label (omit for an icon-only toggle)
//   icon             array    icon.php config, e.g. ['name' => 'bold', 'set' => 'lucide']. Rendered
//                              with a data-icon="inline-start"|"inline-end" attribute (matching
//                              button.php's convention) reflecting `icon_position`, unless the toggle
//                              is icon-only
//   icon_position    string   start | end (ignored for icon-only toggles)
//   variant          string   default | outline (default: default)
//   size             string   default | sm | lg (default: default)
//   disabled         bool     native `disabled` on the checkbox, plus a mirrored
//                              data-disabled="true" CSS hook on both the checkbox and the label
//   required         bool     native `required` on the checkbox -- matches radio.php/checkbox.php's
//                              own `required`, kept consistent across the whole form-control family
//                              even though shadcn's own Toggle has no such prop
//   aria_invalid     bool     sets aria-invalid="true" on the checkbox plus a mirrored
//                              data-invalid="true" CSS hook on both the checkbox and the label --
//                              same error-state hooks as radio.php/checkbox.php, for the same
//                              cross-family-consistency reason as `required` above
//   name / value     string   native form attributes on the checkbox, only rendered when given --
//                              a natural side-effect of the checkbox technique (real form submission
//                              of the pressed state), not something shadcn's own JS-state-only
//                              Toggle offers
//   id               string   native `id` on the checkbox; auto-generated via wp_unique_id() when
//                              omitted (needed to pair the checkbox with the visible label via
//                              `for`/`id`)
//   aria_label       string   accessible name for the checkbox; overrides whatever accessible name
//                              the label content would otherwise give it -- required for icon-only
//                              toggles (a label made only of an aria-hidden icon has no text for the
//                              checkbox's accessible-name-from-label-content computation), optional
//                              for text/icon+text toggles that want a richer name than their short
//                              visible label (e.g. calendar.php gives each day toggle a full
//                              "Monday, January 5, 2026" while the visible label stays just "5").
//                              A missing value on an icon-only toggle doesn't hard-fail the render,
//                              but triggers a WP_DEBUG-only _doing_it_wrong() hint,
//                              see hengegroup_theme_warn_missing_aria_label()
//   class / attributes / data_attributes   passthrough onto the visible <label> (the styling
//                              target), not onto the hidden checkbox

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$pressed = !empty($config['pressed']);
$type = trim((string) ($config['type'] ?? 'checkbox'));
$text = trim((string) ($config['text'] ?? ($config['label'] ?? '')));
$icon_config = is_array($config['icon'] ?? null) ? $config['icon'] : null;
$icon_position = trim((string) ($config['icon_position'] ?? 'start'));
$variant = trim((string) ($config['variant'] ?? 'default'));
$size = trim((string) ($config['size'] ?? 'default'));
$disabled = !empty($config['disabled']);
$required = !empty($config['required']);
$aria_invalid = !empty($config['aria_invalid']);
$name = trim((string) ($config['name'] ?? ''));
$value = trim((string) ($config['value'] ?? ''));
$id = trim((string) ($config['id'] ?? ''));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$has_icon = $icon_config !== null;

if ($text === '' && !$has_icon) {
    return;
}

$allowed_types = ['checkbox', 'radio'];
$allowed_variants = ['default', 'outline'];
$allowed_sizes = ['default', 'sm', 'lg'];

if (!in_array($type, $allowed_types, true)) {
    $type = 'checkbox';
}

if (!in_array($variant, $allowed_variants, true)) {
    $variant = 'default';
}

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'default';
}

if ($icon_position !== 'end') {
    $icon_position = 'start';
}

$is_icon_only = $text === '' && $has_icon;

if ($has_icon) {
    $active_icon_config = $icon_config;

    if (!$is_icon_only) {
        $active_icon_config['data_attributes'] = array_merge(
            is_array($active_icon_config['data_attributes'] ?? null)
                ? $active_icon_config['data_attributes']
                : [],
            ['icon' => $icon_position === 'end' ? 'inline-end' : 'inline-start'],
        );
    }

    $icon_markup = hengegroup_theme_render_icon($active_icon_config);
} else {
    $icon_markup = '';
}

if ($is_icon_only) {
    $inner_html = $icon_markup;
} elseif ($icon_markup !== '') {
    $inner_html =
        $icon_position === 'end' ? esc_html($text) . $icon_markup : $icon_markup . esc_html($text);
} else {
    $inner_html = esc_html($text);
}

if ($id === '') {
    $id = 'hengegroup-theme-toggle-' . wp_unique_id();
}

$checkbox_attributes = [
    'type' => $type,
    'data-slot' => 'toggle-input',
    'id' => $id,
];

if ($name !== '') {
    $checkbox_attributes['name'] = $name;
}

if ($value !== '') {
    $checkbox_attributes['value'] = $value;
}

if ($pressed) {
    $checkbox_attributes['checked'] = true;
}

if ($disabled) {
    $checkbox_attributes['disabled'] = true;
    $checkbox_attributes['data-disabled'] = 'true';
}

if ($required) {
    $checkbox_attributes['required'] = true;
}

if ($aria_invalid) {
    $checkbox_attributes['aria-invalid'] = 'true';
    $checkbox_attributes['data-invalid'] = 'true';
}

if ($aria_label !== '') {
    $checkbox_attributes['aria-label'] = $aria_label;
}

hengegroup_theme_warn_missing_aria_label('toggle.php', $is_icon_only, $aria_label);

$checkbox_markup = '<input' . hengegroup_theme_render_attributes($checkbox_attributes) . '>';

$label_attributes = $attributes;

if ($class_name !== '') {
    $label_attributes['class'] = $class_name;
}

$label_attributes['data-slot'] = 'toggle';
$label_attributes['data-variant'] = $variant;
$label_attributes['data-size'] = $size;
$label_attributes['data-state'] = $pressed ? 'on' : 'off';
$label_attributes['for'] = $id;

if ($disabled) {
    $label_attributes['data-disabled'] = 'true';
}

if ($aria_invalid) {
    $label_attributes['data-invalid'] = 'true';
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $label_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '%1$s<label%2$s>%3$s</label>',
    $checkbox_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    hengegroup_theme_render_attributes($label_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
