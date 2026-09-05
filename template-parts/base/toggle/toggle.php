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
// The checkbox's own `peer sr-only` classes below are the Tailwind-only implementation of the first
// line of that contract (Regel 1 forbids a hand-written `[data-slot="toggle-input"]` CSS rule for
// this): `sr-only` is Tailwind's built-in visually-hidden-but-focusable utility, and `peer` is what
// lets a CONSUMER's own classes on the paired label react to this input's live `:checked`/`:disabled`
// state via `peer-checked:`/`peer-disabled:` (Tailwind's own name for the adjacent-sibling-selector
// trick the contract above describes). This is a deliberately narrow, colour/variant-free pull-
// forward of that one structural piece of toggle.php's own future Phase-2 styling -- pulled forward
// specifically because template-parts/base/calendar.php's day cells (nesting this file) need the
// checkbox invisible and the state reactive to look and behave right (see that file's header
// comment); the rest of toggle.php (variant/size colors on the label) is still unstyled Phase 1,
// unaffected by and independent of this addition.
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind on the strength of the Claude-Design reference
// "Hengegroup" (https://claude.ai/code/artifact/120c0655-89f0-4c42-b99b-bb5227b96ccc)'s "Basis"/
// "Varianten"/"Größen" sections (same `.dc.html`-reference workflow as tabs.php's/kbd.php's own
// entries, see docs/entscheidungen.md for this component's entry). No file-per-variant split, no
// further folder move (CLAUDE.md Regel 4 reserves a folder/split for structurally different
// sub-components, not styling variants of one element -- toggle/ already IS such a folder, split
// from toggle-group.php since Phase 1, see docs/entscheidungen.md's `kbd.php` entry for the general
// rule): `variant`/`size` are pure Tailwind class-lookup swaps on the exact markup this file already
// renders, the same `$variant_classes`/`$size_classes` one-file pattern as button.php/badge.php/
// tabs.php.
//
// The hidden checkbox/radio (`peer sr-only`, unconditional since Phase 1, see above) drives the
// label's live look via Tailwind's `peer-*` variants -- the CSS contract already spec'd above,
// Tailwind-only: `peer-checked:` for the pressed look, `peer-focus-visible:`/`focus-visible:` for
// the no-JS/JS-enhanced focus ring (same split as tabs.php's own trigger), `peer-disabled:` for
// disabled, `peer-aria-invalid:` for the error state. `peer-checked:hover:` pairs re-assert the
// pressed colour under hover (same fix tabs.php's/calendar.php's own peer-checked elements already
// needed -- a plain `hover:` utility carries the same specificity as `peer-checked:` alone and could
// otherwise win on a pressed-and-hovered toggle).
//
// `variant` gained a third value, `accent` -- the reference's "Akzent" row ("primäre Auswahl",
// a solid henge-green pressed fill) alongside its existing "Ohne Kante" (`default`, never bordered)
// and "Mit Kante" (`outline`, bordered even unpressed) rows. Named `accent` rather than a brand-
// color name (unlike button.php's full brand-color variant vocabulary) because this project already
// uses "accent" as the generic name for "this element's one emphasis state maps to the brand green"
// (see separator.php's `style: 'accent'`, badge.php's `font: 'accent'`) -- `default`/`outline`
// stay shadcn's own Toggle vocabulary (kept for parity, see the config table below), `accent` is the
// one deliberate addition, justified by the reference itself, not invented without cause.
// `default`'s pressed fill is this project's `grey-dark` brand-grey (not shadcn's own neutral
// `bg-accent`) -- same brand-token swap as tabs.php's segmented-variant `peer-checked:bg-grey-dark`,
// which this component's reference visually matches almost exactly (a solid anthracite pill).
// `outline`'s border uses this project's `grey-dark` brand-grey (design request 2026-09-05, after
// the initial `border-input` pick rendered invisible against the page background in the real
// build -- see the `$variant_classes` comment below for why that happened) -- same brand-grey
// border override as button.php's/badge.php's own `outline`, not shadcn's stock `border-input` role.
//
// Sizes (`sm`/`default`/`lg`) target the reference's own labelled heights (30px/38px/46px) via
// Tailwind's fractional spacing scale (`h-7.5`/`h-9.5`/`h-11.5` = 1.875rem/2.375rem/2.875rem --
// real scale steps, same half-step convention as badge.php's own `py-0.75`, not an arbitrary bracket
// value). Font-size scales per size the same way button.php's own `sm`/`base`/`lg` does
// (text-sm/text-base/text-lg) -- the reference visibly grows the label text from `sm` to `lg`, same
// category of deviation from shadcn's single-size Toggle text as button.php's own per-size
// font-size entry documents. `min-w-*` (matching each size's own height) is shadcn's own icon-only-
// squaring trick, carried over unverified against a reference (`icon`-only toggles aren't in this
// reference) -- consistent with the rest of this file's icon/text-agnostic sizing, not a new
// invention.
//
// Cross-file impact: calendar.php's day-cell nesting of this file (`variant`/`size` left at their
// defaults, everything else driven by its own full `class` override, see that file's header comment)
// pre-dates this component ever computing its own classes -- its `class` was written to be the ONLY
// classes the rendered label carries. Now that `default`/`size: 'default'` compute their own shape/
// color classes too, several of calendar.php's own utilities collide on the same CSS property
// (`rounded-xl` vs this file's `rounded-full`, `h-10` vs `h-9.5`, `font-normal` vs `font-medium`,
// `text-foreground` vs `text-muted-foreground`, `peer-checked:bg-henge-green` vs
// `peer-checked:bg-grey-dark`, ...). Since PHP has no `tailwind-merge`/`cn()` to resolve that (see
// docs/entscheidungen.md), calendar.php's own `$day_classes` now marks every one of its intentionally-
// overriding utilities `!important` (Tailwind's own `!`-prefix modifier, e.g. `!rounded-xl`,
// `peer-checked:!bg-henge-green`) so its calendar-grid look keeps winning regardless of which file
// Tailwind's class scanner happens to encounter first -- see that file's own comment at its
// `$day_classes` definition for the full, itemized list.
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
//   variant          string   default | outline | accent (default: default) -- see Phase 2 note above
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
$allowed_variants = ['default', 'outline', 'accent'];
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

// Phase 2 (CLAUDE.md Regel 1) Tailwind classes for the visible <label> -- see this file's own
// header comment for the reference/reasoning behind `$variant_classes`/`$size_classes` and the
// `peer-*` state wiring below.
$base_classes =
    'peer-focus-visible:border-ring peer-focus-visible:ring-[3px] ' .
    'peer-focus-visible:ring-ring/50 focus-visible:border-ring focus-visible:ring-[3px] ' .
    'focus-visible:ring-ring/50 peer-aria-invalid:border-destructive ' .
    'peer-aria-invalid:ring-destructive/20 peer-disabled:pointer-events-none ' .
    'peer-disabled:cursor-not-allowed peer-disabled:opacity-50 inline-flex shrink-0 ' .
    'cursor-pointer items-center justify-center rounded-full border ' .
    'font-medium whitespace-nowrap text-muted-foreground outline-none transition-colors ' .
    'select-none hover:bg-muted hover:text-foreground peer-checked:font-semibold ' .
    "[&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4";

// `border-{color}` (never bare `border-transparent`/`border-grey-dark` shared across variants in
// $base_classes above) so each rendered element only ever carries ONE border-color utility --
// `border-transparent` and a variant's own border color are the same CSS property at equal
// specificity, and Tailwind's generated stylesheet order (not the order classes appear in the
// `class` attribute) decides same-specificity ties; keeping both on one element left the visible
// border a coin flip depending on which utility the project-wide scanner happened to register
// first. One border-color class per variant sidesteps that entirely.
$variant_classes = [
    'default' =>
        'border-transparent peer-checked:bg-grey-dark peer-checked:text-grey-dark-foreground ' .
        'peer-checked:shadow-xs peer-checked:hover:bg-grey-dark ' .
        'peer-checked:hover:text-grey-dark-foreground',
    'outline' =>
        'border-grey-dark bg-background shadow-xs peer-checked:bg-grey-dark ' .
        'peer-checked:text-grey-dark-foreground peer-checked:hover:bg-grey-dark ' .
        'peer-checked:hover:text-grey-dark-foreground',
    'accent' =>
        'border-transparent peer-checked:bg-henge-green peer-checked:text-henge-green-foreground ' .
        'peer-checked:shadow-xs peer-checked:hover:bg-henge-green/90 ' .
        'peer-checked:hover:text-henge-green-foreground',
];

$size_classes = [
    'sm' => "h-7.5 min-w-7.5 gap-1 px-2 text-sm [&_svg:not([class*='size-'])]:size-3",
    'default' => 'h-9.5 min-w-9.5 gap-1.5 px-2.5 text-base',
    'lg' => 'h-11.5 min-w-11.5 gap-2 px-3.5 text-lg',
];

$computed_class = "{$base_classes} {$variant_classes[$variant]} {$size_classes[$size]}";

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
    'class' => 'peer sr-only',
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
$label_attributes['class'] = trim($computed_class . ($class_name !== '' ? ' ' . $class_name : ''));

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
