<?php

declare(strict_types=1);

// shadcn/ui's Tabs wraps a headless UI primitive (historically Radix UI; shadcn now also ships
// Base UI/React Aria variants of many components): JS-driven active-tab state
// (value/defaultValue/onValueChange), a `role="tablist"` of `role="tab"` triggers with roving
// tabindex + arrow-key navigation, and `role="tabpanel"` content that is shown/hidden in sync.
//
// Same native-first reasoning as toggle.php/toggle-group.php's `type: 'radio'` mode: a group of
// same-`name` native `<input type="radio">` already gives mutual exclusivity, native focus/
// arrow-key movement between items and native `:checked` state for free -- zero JS needed for the
// active-tab state itself (this theme has no JS framework wired up, see CLAUDE.md #1, only
// vanilla modules under assets/js/). Each trigger is a real radio input, visually hidden but kept
// focusable (NOT display:none/visibility:hidden -- see the CSS contract below), paired with a
// <label> styled as the visible tab surface -- same technique as toggle.php, wrapped in a small
// `data-slot="tabs-trigger-item"` <span> (needed for the positional CSS matching below, see there).
//
// Deliberate semantic trade-off in the no-JS baseline, stated plainly (same category as
// toggle.php's checkbox/radio -> button gap): shadcn's Tabs announces "tab, N of M" inside a
// "tab list". A native `<input type="radio">`'s only valid ARIA role transformations are `radio`,
// `menuitemradio` and `switch` -- `tab` is not among them, so faking that announcement isn't
// attempted in pure HTML. This component instead announces "radio button, selected, N of M"
// inside a `role="radiogroup"` (exactly the wrapper role radio-group.php/toggle-group.php already
// use for the same reason) -- an established, WCAG-valid way to expose a mutually-exclusive
// choice, just not an identical announcement to shadcn's own. Content panels stay plain
// `data-slot="tabs-content"` <div>s without `role="tabpanel"` in this baseline -- that role is
// meant to pair with a `role="tab"` per the WAI-ARIA APG Tabs pattern, which plain HTML doesn't
// implement here; each panel instead gets `aria-labelledby` pointing at its trigger's id, keeping
// a real accessible-name association without claiming a pattern this markup doesn't fully provide
// (same "don't bolt on possibly-mismatched ARIA" call as accordion.php's plain, role-less
// `data-slot="accordion-content"` div).
//
// Unlike e.g. dropdown-menu.php's outside-click problem, this specific gap is fully closable with
// JS once JS is available -- so per the established ARIA-gap-closing rule,
// assets/js/template-parts/base/tabs.js does exactly that: it hides the radio inputs from the
// accessibility tree (`aria-hidden` + `tabindex="-1"`) and upgrades `[data-slot="tabs-list"]` to
// `role="tablist"`, each trigger `<label>` to a real focusable `role="tab"` with roving tabindex,
// and each panel to `role="tabpanel"`, plus orientation-aware arrow-key navigation. Panel
// visibility itself is left completely alone -- the CSS `:checked` contract below still does that
// job unchanged, JS only ever touches ARIA/focus, never display. `data-js="tabs"` (set on
// `[data-slot="tabs"]` once initialized, same convention as `data-js="select"`/`"dropdown-menu"`)
// is the project-CSS hook for the resulting focus-ring handoff, see the CSS contract below.
//
// Panel switching itself -- something toggle.php never had to solve, since a single checkbox/radio
// IS its own visual state -- has no single native HTML mechanism once multiple radios must each
// reveal a *different*, non-sibling panel. It's covered by a CSS `:has()` + positional
// `:nth-child()` contract instead of per-value rules, because CSS can't generically cross-reference
// two elements' attribute values (only their position) -- a per-value rule would have to be
// re-authored for every distinct tab value a project ever uses, while a positional rule is authored
// ONCE (e.g. a Sass @for loop up to a practical max tab count) and works for any items array:
//   [data-slot="tabs-content"] { display: none; }
//   [data-slot="tabs"]:has([data-slot="tabs-list"] > :nth-child(1) [data-slot="tabs-trigger-input"]:checked)
//     [data-slot="tabs-panels"] > :nth-child(1) { display: block; }
//   [data-slot="tabs"]:has([data-slot="tabs-list"] > :nth-child(2) [data-slot="tabs-trigger-input"]:checked)
//     [data-slot="tabs-panels"] > :nth-child(2) { display: block; }
//   /* ... repeat up to the project's practical max tab count */
// The hidden-input visibility technique itself follows toggle.php's own contract:
//   [data-slot="tabs-trigger-input"] { /* visually-hidden, e.g. position:absolute; width:1px;
//     height:1px; overflow:hidden; clip-path:inset(50%); -- must stay focusable, not display:none */ }
//   [data-slot="tabs-trigger-input"]:checked + [data-slot="tabs-trigger"] { /* active styles */ }
//   [data-slot="tabs-trigger-input"]:focus-visible + [data-slot="tabs-trigger"] { /* focus ring in
//     the no-JS baseline -- focus lives on the hidden input, not the visible label */ }
//   [data-js="tabs"] [data-slot="tabs-trigger"]:focus-visible { /* focus ring once tabs.js is
//     active -- see the JS section below, tabs.js moves real focus onto the label itself, so the
//     adjacent-sibling selector above no longer fires and this one takes over */ }
//   [data-slot="tabs-trigger-input"]:disabled + [data-slot="tabs-trigger"] { /* disabled styles */ }
//
// `badge` (per item) is a Rule-2 composition, not shadcn vocabulary: shadcn's own TabsTrigger has
// no badge/counter prop, but a trailing count/status badge on a tab ("Files changed 12", "Inbox 3")
// is a common real-world pattern -- same spirit as toggle.php's `type: 'radio'`, an implementation
// extension stated plainly as such rather than dressed up as an upstream prop. Nests
// template-parts/base/badge.php (already the base library's own Badge component --
// no reason to hand-roll a second badge markup here) instead of duplicating its markup, buffered via
// a local closure the same way accordion.php buffers typography.php for its optional heading_tag (no
// cross-cutting helpers.php entry yet since this is currently badge.php's only nesting consumer).
// Always trailing (data-badge="inline-end", same data-attribute convention as
// button.php/toggle.php's own data-icon) -- there's no established leading-badge use case to justify
// a `badge_position` config yet.
//
// `variant` (live-rechecked against shadcn's current Tabs docs): TabsList now ships
// a `variant="line"` example (underline-style triggers) alongside its unnamed default (pill/
// segmented-control look). Phase 1 only wires the `data-variant` hook onto
// `[data-slot="tabs-list"]` -- the actual line-vs-pill visuals are Phase 2 Tailwind work, same
// "hook now, style later" split as every other data-variant/data-size in this theme.
//
// Supported config:
//   items         array    required, ordered list of:
//     value          string   required. This item's native radio `value` (also the CSS `data-value`
//                               hook); items without a value are skipped
//     trigger        string   visible trigger label (plain text, escaped); omit only for an
//                               icon-only trigger (`icon` + `aria_label` required in that case,
//                               same rule as button.php/toggle.php)
//     icon           array    icon.php config for the trigger, e.g. ['name' => 'user', 'set' =>
//                               'lucide']. Rendered with a data-icon="inline-start" attribute
//                               (matches button.php/toggle.php's convention; always inline-start
//                               here since shadcn's own TabsTrigger has no `icon_position` concept)
//     badge          array    optional badge.php config for a trailing counter/status badge, e.g.
//                               ['text' => '3', 'variant' => 'secondary'] -- see note above
//     content        string   required. Pre-rendered HTML for the panel body (caller's
//                               responsibility to escape/build, e.g. via typography.php -- same
//                               convention as accordion.php's `content`); items without content
//                               are skipped
//     disabled       bool     per-item disabled (in addition to the group-wide `disabled`)
//     aria_label     string   accessible name for the trigger; required for icon-only triggers
//     class          string   passthrough onto this item's trigger <label>
//   value         string   the currently active item's `value` (shadcn's own singular `value`/
//                          `defaultValue` prop -- Tabs always has exactly one active tab, unlike
//                          accordion.php's per-item `default_open` booleans). Falls back to the
//                          first item when omitted or when it matches no item's `value` -- shadcn's
//                          Tabs likewise always renders with a tab active, never with none selected
//   orientation   string   horizontal (default) | vertical -- sets data-orientation/aria-orientation
//                          only, same caveat as toggle-group.php's own `orientation`: native
//                          radio-group Left/Right (or Up/Down) key handling follows DOM order, not
//                          visual layout, regardless of this value
//   variant       string   default | line -- sets data-variant on the tabs-list wrapper only
//                          (matches shadcn's TabsList `variant`, see note above); Phase 1 has no
//                          visual difference between the two, this is purely the Phase-2 CSS hook
//   name          string   shared radio `name` for every trigger (what makes them mutually
//                          exclusive at all); auto-generated via wp_unique_id() when omitted, same
//                          convention as radio-group.php/toggle-group.php
//   disabled      bool     disables every trigger; also sets `data-disabled="true"` on the
//                          tabs-list wrapper itself (no native `disabled` on a <div>), matching
//                          radio-group.php/toggle-group.php's convention
//   aria_label    string   accessible name for the tabs-list wrapper (`role="radiogroup"`)
//   class / attributes / data_attributes   passthrough onto the outer wrapper
//                          (<div data-slot="tabs">)

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$items_config = is_array($config['items'] ?? null) ? $config['items'] : [];
$active_value = trim((string) ($config['value'] ?? ''));
$orientation = trim((string) ($config['orientation'] ?? 'horizontal'));
$variant = trim((string) ($config['variant'] ?? 'default'));
$name = trim((string) ($config['name'] ?? ''));
$group_disabled = !empty($config['disabled']);
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_orientations = ['horizontal', 'vertical'];

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'horizontal';
}

$allowed_variants = ['default', 'line'];

if (!in_array($variant, $allowed_variants, true)) {
    $variant = 'default';
}

// Normalize + filter items; value and content are required, trigger is required unless the item
// is icon-only -- invalid items are silently skipped (fail-soft, consistent with the rest of the
// base components).
$items = [];

foreach ($items_config as $item_config) {
    if (!is_array($item_config)) {
        continue;
    }

    $value = trim((string) ($item_config['value'] ?? ''));
    $content = (string) ($item_config['content'] ?? '');
    $trigger_text = trim((string) ($item_config['trigger'] ?? ''));
    $icon_config = is_array($item_config['icon'] ?? null) ? $item_config['icon'] : null;
    $badge_config = is_array($item_config['badge'] ?? null) ? $item_config['badge'] : null;

    if ($value === '' || trim($content) === '') {
        continue;
    }

    if ($trigger_text === '' && $icon_config === null) {
        continue;
    }

    $items[] = [
        'value' => $value,
        'content' => $content,
        'trigger' => $trigger_text,
        'icon' => $icon_config,
        'badge' => $badge_config,
        'disabled' => !empty($item_config['disabled']),
        'aria_label' => trim((string) ($item_config['aria_label'] ?? '')),
        'class' => trim((string) ($item_config['class'] ?? '')),
    ];
}

if ($items === []) {
    return;
}

$item_values = array_column($items, 'value');

if (!in_array($active_value, $item_values, true)) {
    $active_value = $item_values[0];
}

if ($name === '') {
    $name = 'hengegroup-theme-tabs-' . wp_unique_id();
}

$render_badge = static function (array $badge_config): string {
    ob_start();
    get_template_part('template-parts/base/badge', null, ['config' => $badge_config]);

    return (string) ob_get_clean();
};

$triggers_markup = '';
$panels_markup = '';

foreach ($items as $item) {
    $unique = wp_unique_id();
    $trigger_id = 'hengegroup-theme-tabs-trigger-' . $unique;
    $content_id = 'hengegroup-theme-tabs-content-' . $unique;
    $is_checked = $item['value'] === $active_value;
    $is_disabled = $group_disabled || $item['disabled'];
    $is_icon_only = $item['trigger'] === '' && $item['icon'] !== null;

    if ($item['icon'] !== null) {
        $icon_config = $item['icon'];

        if (!$is_icon_only) {
            $icon_config['data_attributes'] = array_merge(
                is_array($icon_config['data_attributes'] ?? null)
                    ? $icon_config['data_attributes']
                    : [],
                ['icon' => 'inline-start'],
            );
        }

        $icon_markup = hengegroup_theme_render_icon($icon_config);
    } else {
        $icon_markup = '';
    }

    if ($item['badge'] !== null) {
        $badge_config = $item['badge'];
        $badge_config['data_attributes'] = array_merge(
            is_array($badge_config['data_attributes'] ?? null)
                ? $badge_config['data_attributes']
                : [],
            ['badge' => 'inline-end'],
        );
        $badge_markup = $render_badge($badge_config);
    } else {
        $badge_markup = '';
    }

    $inner_html = $is_icon_only ? $icon_markup : $icon_markup . esc_html($item['trigger']);
    $inner_html .= $badge_markup;

    $input_attributes = [
        'type' => 'radio',
        'data-slot' => 'tabs-trigger-input',
        'id' => $trigger_id,
        'name' => $name,
        'value' => $item['value'],
    ];

    if ($is_checked) {
        $input_attributes['checked'] = true;
    }

    if ($is_disabled) {
        $input_attributes['disabled'] = true;
        $input_attributes['data-disabled'] = 'true';
    }

    $label_attributes = [];

    if ($item['class'] !== '') {
        $label_attributes['class'] = $item['class'];
    }

    $label_attributes['data-slot'] = 'tabs-trigger';
    $label_attributes['data-value'] = $item['value'];
    $label_attributes['data-state'] = $is_checked ? 'active' : 'inactive';
    $label_attributes['for'] = $trigger_id;
    $label_attributes['aria-controls'] = $content_id;

    if ($is_disabled) {
        $label_attributes['data-disabled'] = 'true';
    }

    if ($item['aria_label'] !== '') {
        $label_attributes['aria-label'] = $item['aria_label'];
    }

    $triggers_markup .= sprintf(
        '<span data-slot="tabs-trigger-item"><input%1$s><label%2$s>%3$s</label></span>',
        hengegroup_theme_render_attributes($input_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        hengegroup_theme_render_attributes($label_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );

    $panel_attributes = [
        'data-slot' => 'tabs-content',
        'id' => $content_id,
        'data-value' => $item['value'],
        'aria-labelledby' => $trigger_id,
    ];

    $panels_markup .= sprintf(
        '<div%1$s>%2$s</div>',
        hengegroup_theme_render_attributes($panel_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $item['content'], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$list_attributes = [
    'role' => 'radiogroup',
    'data-slot' => 'tabs-list',
    'data-orientation' => $orientation,
    'aria-orientation' => $orientation,
    'data-variant' => $variant,
];

if ($group_disabled) {
    $list_attributes['data-disabled'] = 'true';
}

if ($aria_label !== '') {
    $list_attributes['aria-label'] = $aria_label;
}

$wrapper_attributes = $attributes;

if ($class_name !== '') {
    $wrapper_attributes['class'] = $class_name;
}

$wrapper_attributes['data-slot'] = 'tabs';
$wrapper_attributes['data-orientation'] = $orientation;

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $wrapper_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<div%1$s><div%2$s>%3$s</div><div data-slot="tabs-panels">%4$s</div></div>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    hengegroup_theme_render_attributes($list_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $triggers_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $panels_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
