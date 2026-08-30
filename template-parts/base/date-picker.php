<?php

declare(strict_types=1);

// shadcn/ui's own docs are explicit: "The Date Picker is built using a composition of the
// <Popover /> and the <Calendar /> components" -- it is a documented RECIPE, not a standalone
// component with its own headless primitive. When this file was first written, this theme had no
// standalone popover.php to compose with (this project's JS-enhanced floating-panel components --
// select.php/combobox.php/dropdown-menu.php -- had each so far implemented their own
// open/close/outside-click logic locally rather than sharing one "Popover" abstraction), so rather
// than inventing a popover.php that nothing else needed yet, this file mirrored dropdown-menu.php's
// own recipe instead: native <details>/<summary> for the zero-JS trigger/panel shell (CLAUDE.md
// #1), with template-parts/base/calendar.php nested directly inside the panel
// instead of a Popover wrapper -- structurally "DetailsSummary + Calendar", the same substitution
// dropdown-menu.php already made for "DetailsSummary + Menu items".
//
// template-parts/base/popover.php now exists (built to match shadcn's/Base UI's actual Popover
// behaviour -- role="dialog", focus moves into the panel on open, see that file's header comment).
// This file deliberately keeps its own recipe rather than being refactored to nest popover.php:
// date-picker.js already owns the open/close/outside-click/Escape logic and the trigger-text
// reformatting on selection, none of which a nested popover.php would simplify, and popover.php's
// role="dialog" panel would be a misleading label for content that is really a form control's
// picker surface, not a dialog. Revisit this pairing if/when a second consumer of popover.php
// shows the same recipe would genuinely reduce duplication here.
//
// Zero-JS baseline: click (or Enter/Space on) the trigger to show/hide the panel; the nested
// calendar.php is, per its own header comment, fully functional without JS (native radio/checkbox
// day selection, real prev/next month links). Two honest, documented gaps in that baseline, not
// silently dropped: the trigger's displayed date text does NOT update to reflect a pick (there is
// no native way to reflect one element's state into another's text content), and the panel does not
// auto-close after picking a date (same class of gap as dropdown-menu.php's own missing
// outside-click/Escape-close in its zero-JS path). assets/js/template-parts/base/date-picker.js
// closes both gaps: outside-click/Escape to close (mirrors dropdown-menu.js), and a listener for
// the nested calendar's native `change` event (fires on its radio/checkbox inputs automatically, no
// special wiring needed) that reformats the trigger text and closes the panel. Month navigation
// inside the panel already works without a page reload for free once JS is active -- that's
// calendar.js's own job, and it initializes independently off of `[data-slot="calendar"]`
// regardless of whether that element happens to be nested inside a date-picker.
//
// Deliberately out of scope for v1: the "Range Picker" variant shadcn's own docs also show. That
// needs calendar.php's `mode: 'range'`, which is itself an explicitly deferred, not-yet-built
// extension point there (see that file's header comment) -- building a range date-picker on top of
// a calendar that can't do ranges isn't possible yet, not a separate decision to make here.
// `disabled` similarly has a known gap: <details>/<summary> has no native disabled concept (same
// limitation accordion.php already documents), so this file only sets aria-disabled/data-disabled
// hooks; date-picker.js intercepts the trigger's click while `data-disabled` is set and prevents
// the native <details> toggle, so the panel actually stays closed once JS is active -- without JS,
// the trigger remains clickable/keyboard-operable despite the hooks, an honest, documented gap
// like the others in this file.
//
// CSS contract, same shape as dropdown-menu.php's: date-picker.js sets `data-js` on the outer
// <details data-slot="date-picker"> once initialized; nothing needs hiding/showing by it (the
// native `open` attribute already does that), but project CSS can use it to gate e.g. a fade
// transition that would look broken without JS driving it.
//
// Supported config:
//   mode              string   single | multiple (default: single) -- forwarded to the nested
//                              calendar.php as-is, see that file's own `mode` doc
//   selected          string|array   forwarded to calendar.php's own `selected`
//   placeholder       string   trigger text shown when nothing is selected (default: "Pick a
//                              date")
//   date_format       string   date_i18n()-compatible format for the trigger's selected-date text
//                              (default: 'F j, Y', e.g. "August 6, 2026")
//   month / year      int      forwarded to calendar.php's own `month`/`year` (initial displayed
//                              month)
//   disabled_dates / min_date / max_date / week_starts_on / show_outside_days
//                     forwarded as-is to calendar.php, see that file's own docs
//   name              string   forwarded to calendar.php's own `name` (real form field for the
//                              picked date(s))
//   icon              array    icon.php config for the trigger's leading icon (default:
//                              ['name' => 'calendar', 'set' => 'lucide'], shadcn's own choice);
//                              pass false to omit
//   disabled          bool     sets aria-disabled="true"/data-disabled="true" on the trigger (see
//                              the scope note above on its zero-JS limits)
//   aria_label        string   accessible name for the trigger when its visible text
//                              (placeholder/selected date) isn't descriptive enough on its own
//   id                string   native `id` on the outer <details>; auto-generated via
//                              wp_unique_id() when omitted
//   class / attributes / data_attributes   passthrough onto the outer
//                              <details data-slot="date-picker"> wrapper
//
// Not itself config, but worth documenting: the wrapper also carries `data-placeholder` and
// `data-count-template` (the already-translated "%d dates selected" pattern) -- date-picker.js's
// own bootstrap data, so it can revert to the placeholder or rebuild the summary text after a live
// pick without hardcoding a second, possibly-untranslated copy of either string (DOM stays the
// single source of truth, not duplicated JS-side config).

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$mode = trim((string) ($config['mode'] ?? 'single'));
$placeholder = trim((string) ($config['placeholder'] ?? ''));
$date_format = trim((string) ($config['date_format'] ?? 'F j, Y'));
$disabled = !empty($config['disabled']);
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$id = trim((string) ($config['id'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (array_key_exists('icon', $config) && $config['icon'] === false) {
    $icon_config = null;
} elseif (is_array($config['icon'] ?? null)) {
    $icon_config = $config['icon'];
} else {
    $icon_config = ['name' => 'calendar', 'set' => 'lucide'];
}

$allowed_modes = ['single', 'multiple'];

if (!in_array($mode, $allowed_modes, true)) {
    $mode = 'single';
}

if ($placeholder === '') {
    $placeholder = esc_html__('Pick a date', 'hengegroup-theme');
}

if ($id === '') {
    $id = 'hengegroup-theme-date-picker-' . wp_unique_id();
}

$selected_dates = [];

if (array_key_exists('selected', $config)) {
    $raw_selected = $config['selected'];
    $selected_dates = array_map(
        'strval',
        is_array($raw_selected) ? $raw_selected : [(string) $raw_selected],
    );
}

$value_text = $placeholder;

if ($selected_dates !== []) {
    $formatted = array_map(
        static fn(string $iso): string => date_i18n($date_format, strtotime($iso)),
        $selected_dates,
    );

    $value_text =
        count($formatted) > 2
            ? sprintf(
                /* translators: %d: number of selected dates */
                esc_html__('%d dates selected', 'hengegroup-theme'),
                count($formatted),
            )
            : implode(' - ', $formatted);
}

$icon_markup = $icon_config !== null ? hengegroup_theme_render_icon($icon_config) : '';

$calendar_config = ['mode' => $mode, 'id' => $id . '-calendar'];

foreach (
    [
        'selected',
        'month',
        'year',
        'disabled_dates',
        'min_date',
        'max_date',
        'week_starts_on',
        'show_outside_days',
        'name',
    ]
    as $forwarded_key
) {
    if (array_key_exists($forwarded_key, $config)) {
        $calendar_config[$forwarded_key] = $config[$forwarded_key];
    }
}

ob_start();
get_template_part('template-parts/base/calendar', null, ['config' => $calendar_config]);
$calendar_markup = (string) ob_get_clean();

if ($calendar_markup === '') {
    return;
}

// Phase 2 (CLAUDE.md Regel 1): the trigger gets the same field-surface Tailwind classes as
// input.php (see that file's header for the Bewerbungsformular design-reference mapping) plus
// button.php's own `outline` hover treatment -- matching its own `data-variant="outline"` above --
// since a <summary> has no native pointer/hover affordance of its own. The panel gets the same
// minimal floating-panel treatment as select.php's/combobox.php's own content panels.
$trigger_attributes = [
    'class' =>
        'inline-flex cursor-pointer list-none items-center gap-2 rounded-lg border ' .
        'border-input bg-background px-3.5 py-3 text-base shadow-xs outline-none ' .
        'transition-[color,box-shadow] hover:bg-grey-light hover:text-grey-light-foreground ' .
        'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 ' .
        'aria-disabled:pointer-events-none aria-disabled:opacity-50 md:text-sm ' .
        '[&::-webkit-details-marker]:hidden [&_svg]:pointer-events-none [&_svg]:shrink-0 ' .
        "[&_svg:not([class*='size-'])]:size-4",
    'data-slot' => 'date-picker-trigger',
    'data-variant' => 'outline',
    // Not "dialog": that value promises a role="dialog" panel (see dropdown-menu.php's own
    // aria-haspopup="menu" + role="menu" pairing on the same pattern), but the panel below never
    // gets a role -- there's no focus trap/aria-modal either, it's a plain floating <div> holding
    // calendar.php's grid, not a real dialog. "true" is the honest, generic "this trigger opens
    // some kind of popup" value instead of announcing dialog semantics the panel doesn't have.
    'aria-haspopup' => 'true',
];

if ($disabled) {
    $trigger_attributes['aria-disabled'] = 'true';
    $trigger_attributes['data-disabled'] = 'true';
}

if ($aria_label !== '') {
    $trigger_attributes['aria-label'] = $aria_label;
}

$trigger_markup = sprintf(
    '<summary%1$s>%2$s<span data-slot="date-picker-value">%3$s</span></summary>',
    hengegroup_theme_render_attributes($trigger_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $icon_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($value_text),
);

$content_markup = sprintf(
    '<div class="mt-2 rounded-lg border border-input bg-background p-3 shadow-md" ' .
        'data-slot="date-picker-content">%s</div>',
    $calendar_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

$wrapper_attributes = $attributes;

if ($class_name !== '') {
    $wrapper_attributes['class'] = $class_name;
}

$wrapper_attributes['data-slot'] = 'date-picker';
$wrapper_attributes['data-mode'] = $mode;
$wrapper_attributes['data-placeholder'] = $placeholder;
$wrapper_attributes['data-count-template'] = esc_html__('%d dates selected', 'hengegroup-theme');
$wrapper_attributes['id'] = $id;

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $wrapper_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<details%1$s>%2$s%3$s</details>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
