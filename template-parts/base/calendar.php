<?php

declare(strict_types=1);

// shadcn/ui's Calendar is not built on a headless UI primitive like most other components here --
// it wraps the third-party `react-day-picker` library (Base UI/React Aria/Radix UI variants exist
// in shadcn's docs too, but the underlying date-grid logic is still react-day-picker's own, not one
// of those interaction-pattern primitives). There is no native HTML element that renders an
// always-visible, custom-styled month grid (`<input type="date">` is the closest native date
// control, but it only ever shows an OS-controlled POPUP picker, never an inline grid -- a
// genuinely different UI, not a substitute here, see CLAUDE.md #1).
//
// What IS achievable natively, and is what this component does: PHP already has full calendar math
// built in (DateTimeImmutable), so the month grid itself -- weekday headers, day-of-week alignment,
// leading/trailing days from adjacent months -- is computed and rendered server-side, zero JS. Each
// day cell reuses template-parts/base/toggle/toggle.php with `type: 'radio'`
// (`mode: 'single'`) or `type: 'checkbox'` (`mode: 'multiple'`) -- the exact same native-grouping
// technique toggle-group.php already uses, just laid out into a real `<table>` grid instead of a
// flat sequence, so it needs its own per-cell loop rather than nesting toggle-group.php directly.
// Month navigation is real `<a href="...">` links built via WordPress's own `add_query_arg()`
// (preserves whatever other query args the current URL already has) -- a real page
// reload/navigation, genuinely functional with zero JS (see CLAUDE.md #1), and still the entire
// story for a caller that never loads assets/js/template-parts/base/calendar.js. This component
// does NOT read `$_GET` itself to figure out which month to show -- like every other base
// component, it's a pure config-driven renderer; a caller relying on the zero-JS reload path owns
// reading the nav query var back out and passing `month`/`year` in on the next request (same
// division of responsibility as WordPress's own `paged` query var and page templates).
//
// calendar.js progressively enhances the above into reload-free navigation: it intercepts clicks
// on the prev/next links and rebuilds the month grid in place instead. Unlike select.js/combobox.js
// (which enhance by reading an already-rendered DOM of ALL possible options), there is no
// "everything, just hidden" DOM to read from here -- switching months means generating dates that
// were never rendered by PHP at all, so the grid math (day-of-week alignment, leading/trailing
// days) is necessarily reimplemented in JS using the native `Date` object, mirroring the
// DateTimeImmutable arithmetic below formula-for-formula (closer to tooltip.js's own placement
// math -- a real client-side calculation, not a case of duplicating something already in the DOM).
// Everything ELSE it needs (mode, week_starts_on, show_outside_days, name, nav_name, min/max/
// disabled/selected dates) is read from `data-*` attributes on the wrapper (below) rather than
// hardcoded twice -- config still flows from PHP once, JS just also receives it.
//
// Deliberately out of scope for v1, each a genuinely different/more complex feature, not a variant
// of this one (same reasoning as native-select.php's `multiple`, combobox.php's chips mode,
// dropdown-menu.php's submenus):
//   - `mode: 'range'` -- highlighting an arbitrary span between two clicks has no native
//     single-request equivalent at all (unlike single/multiple, which map cleanly onto
//     radio/checkbox groups); a real client-side feature, not built speculatively
//   - `numberOfMonths` (multiple months side by side) -- render this component multiple times with
//     adjacent `month`/`year` values instead
//   - `captionLayout: 'dropdown'` (month/year as native-select.php pickers instead of plain text)
//   - disabling prev/next navigation itself when the whole adjacent month falls outside
//     `min_date`/`max_date` -- `min_date`/`max_date` only disable individual day cells in v1, not
//     navigation availability
//   - "Date Picker" (Calendar + Popover/tooltip.php's positioning technique) -- shadcn's own docs
//     treat this as a separate, composed component, not part of Calendar itself (meanwhile built
//     separately as date-picker.php, which nests this file directly, see its own header comment)
//   - `timeZone` (Intl.DateTimeFormat-based day-boundary shifting) -- this component only ever
//     works with date-only values (`Y-m-d`), no time-of-day/timezone concept exists to shift
//   - Hijri/Persian calendar mode -- a different calendar system entirely, not a day-cell variant;
//     would need its own month-grid math (DateTimeImmutable + JS `Date` both assume Gregorian)
//   - preset shortcuts ("Today" / "Tomorrow" / "In 3 days" / "booked dates") -- caller-specific
//     convenience buttons around the grid, not part of the grid itself; a consuming
//     component/page can already build these today by pre-setting `selected`/`month`/`year`
//
// A small, well-scoped change to toggle.php was needed for this: its `aria_label` config now
// overrides the label-derived accessible name whenever given, not just for icon-only toggles (see
// that file's header comment) -- each day cell passes a full "Monday, January 5, 2026" via
// `aria_label` while its visible label stays just the day number, matching shadcn's own Calendar
// day-button accessible names.
//
// Nesting toggle.php means each day cell also inherits its documented "checkbox"/"radio button"
// vs. shadcn's own "button" announcement gap (see toggle.php's header comment) -- and, per
// the established ARIA-gap-closing rule, toggle.js's enhanceToggle() closes it here too: no
// `role="radiogroup"` wraps this component's day cells (a calendar grid isn't a radiogroup), so
// both `mode: 'single'` and `mode: 'multiple'` days get upgraded to `role="button"
// aria-pressed`, matching shadcn's real Calendar day buttons. calendar.js imports and calls
// enhanceToggle() directly on every day cell it creates client-side during month navigation, since
// those cells never go through toggle.js's own DOMContentLoaded sweep (see that file's header
// comment) -- a component that creates toggle.php-shaped markup after page load is responsible for
// re-applying this enhancement itself, the same way it's responsible for everything else
// toggle.php would otherwise have rendered for it.
//
// Also the first base component to use WordPress's own i18n functions (`date_i18n()` for the
// month/weekday labels, `esc_attr__()` with this theme's `hengegroup-theme` text domain for the "Previous
// month"/"Next month" nav labels) -- every other component so far only ever renders CALLER-supplied
// text (already the caller's responsibility to translate before passing in), but weekday/month
// names and the nav labels are static UI chrome this component invents itself, so they need real
// locale/translation support rather than hardcoded English literals. calendar.js mirrors this via
// the browser's own `Intl.DateTimeFormat`, locale taken from `document.documentElement.lang`
// (whatever WordPress's `language_attributes()` already put on `<html>`) -- not a second,
// hardcoded set of month/weekday names.
//
// CSS contract, same shape as select.php/combobox.php: calendar.js sets `data-js` on the outer
// <div data-slot="calendar"> once initialized; nothing needs to be hidden/shown by it, but project
// CSS can use the attribute to opt into e.g. a fade transition between months that would look
// broken without JS driving it.
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind against a "Termine" date-picker reference design
// (single-month, single-selection card -- shadcn's own Calendar has no shipped visual spec of its
// own to take classes 1:1 from, unlike button.php/input.php). Mapping decisions:
//   - the whole wrapper IS the card (bg-card/border-border/rounded-2xl/p-6/shadow-sm) rather than a
//     bare grid that expects an external card to wrap it -- the reference renders one self-contained
//     white rounded surface, not a grid needing outside chrome. This meant reducing
//     date-picker.php's own popover-content div to a plain `absolute`-positioned wrapper (mt-2, no
//     more of its own competing border/bg/padding/shadow -- see that file's own comment) instead of
//     double-carding when this component sits inside that popover panel; that positioning div was
//     also missing `absolute` entirely before this change (a real, pre-existing bug -- its panel
//     pushed following content down instead of floating, unlike select.php's/combobox.php's already-
//     fixed panels), fixed alongside this since both live in the same file.
//   - the reference's accent color (a solid-fill selected day + a "Gewählt: <date>" summary line
//     below the grid) maps to henge-green -- tokens.css documents it as this project's shadcn-
//     "primary" equivalent, and button.php already uses it as the default/primary action color.
//     The summary line itself is NOT reproduced here: it's caller-owned demo chrome reflecting a
//     picked value back as text, not part of the grid this component renders -- the same boundary
//     already drawn for the reference's date-range/booking-flow screens (out of scope, see above);
//     date-picker.php's own trigger `<span data-slot="date-picker-value">` already fills that role
//     for this component's real, documented composition.
//   - day-cell sizing (h-10/rounded-xl/w-full-per-column via `table-fixed`) and nav-button sizing
//     (size-9/rounded-xl) are taken directly from the reference's own pixel values (40px/12px radius,
//     36px), all of which happen to land exactly on real Tailwind steps, no arbitrary values needed.
//   - a day's SELECTED look must react live to a click with zero JS (native radio/checkbox has no
//     reload in between) -- per toggle.php's own documented CSS contract, that means styling off the
//     checkbox's live `:checked`/`:disabled` state via Tailwind's `peer`/`peer-checked:`/
//     `peer-disabled:`, not a render-time boolean class. This is why toggle.php's checkbox itself now
//     renders `class="peer sr-only"` unconditionally (see that file's header comment) -- a small,
//     colour-free prerequisite pulled forward from toggle.php's own still-unstyled Phase 2, not
//     something this file could route around via its own `class` config (toggle.php's `class`/
//     `attributes`/`data_attributes` passthrough only ever reaches the visible label, by design, see
//     that file's header -- never the checkbox). "Today" (a render-time-only fact, it can't change
//     without a full page view) stays a plain conditional `ring-1 ring-border` class instead, no
//     `peer-*` needed.
//   - calendar.js's own buildDayCell()/renderMonth() (client-side month navigation, no PHP re-render)
//     mirror every one of these classes verbatim on the elements they create, the same
//     "reimplemented formula-for-formula" duplication that file's header comment already documents
//     for the grid-math side of the same problem -- a navigated-to month would otherwise render
//     completely unstyled after the first client-side rebuild.
//
// Supported config:
//   mode              string   single | multiple (default: single) -- selection mode, mirrors
//                              toggle-group.php's own `type` naming for the same distinction
//   month             int      1-12 (default: current month per current_time('n'))
//   year              int      default: current year per current_time('Y')
//   selected          string|array   ISO date string(s) (YYYY-MM-DD) currently selected -- a
//                              string for `single`, an array of strings for `multiple`, same
//                              shape as toggle-group.php's own `value`
//   disabled_dates    array    ISO date strings to individually disable
//   min_date / max_date   string   ISO date strings (inclusive); days outside this range are
//                              disabled
//   week_starts_on    int      0 (Sunday, default) - 6 (Saturday)
//   show_outside_days bool     default true. Shows adjacent months' day numbers in the leading/
//                              trailing blank cells (non-interactive, `data-outside="true"`) --
//                              shadcn's own default. `false` renders those cells fully empty.
//   navigation        bool     default true. Renders the prev/next month links + caption; `false`
//                              omits all three, for a caller that pins the month externally
//   nav_name          string   query var name for the month-navigation links; auto-generated as
//                              `{id}_month` when omitted (keeps multiple calendars on one page from
//                              colliding)
//   name              string   shared form field name for the day radios/checkboxes; auto-generated
//                              via wp_unique_id() when omitted, exactly like toggle-group.php
//   aria_label        string   accessible name for the outer <table>
//   id                string   native `id` on the outer wrapper; auto-generated via
//                              wp_unique_id() when omitted
//   class / attributes / data_attributes   passthrough onto the outer
//                              <div data-slot="calendar"> wrapper
//
// Not itself config, but worth documenting: the wrapper also carries `data-mode`/
// `data-week-starts-on`/`data-show-outside-days`/`data-name`/`data-nav-name`/`data-min-date`/
// `data-max-date`/`data-disabled-dates` (JSON array)/`data-selected` (JSON array)/
// `data-aria-label-custom` (only when `aria_label` was given -- tells calendar.js NOT to
// overwrite the table's aria-label with an auto-generated caption after month navigation, since
// both the custom and auto-generated cases otherwise set aria-label on the same <table> element)
// and the `<table>` carries `data-month`/`data-year` -- calendar.js's own bootstrap data,
// reflecting this same config back out as attributes rather than JS needing a second config
// source.

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$mode = trim((string) ($config['mode'] ?? 'single'));
$month = (int) ($config['month'] ?? current_time('n'));
$year = (int) ($config['year'] ?? current_time('Y'));
$disabled_dates = is_array($config['disabled_dates'] ?? null) ? $config['disabled_dates'] : [];
$min_date = trim((string) ($config['min_date'] ?? ''));
$max_date = trim((string) ($config['max_date'] ?? ''));
$week_starts_on = (int) ($config['week_starts_on'] ?? 0);
$show_outside_days =
    !array_key_exists('show_outside_days', $config) || !empty($config['show_outside_days']);
$navigation = !array_key_exists('navigation', $config) || !empty($config['navigation']);
$nav_name = trim((string) ($config['nav_name'] ?? ''));
$name = trim((string) ($config['name'] ?? ''));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$id = trim((string) ($config['id'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_modes = ['single', 'multiple'];

if (!in_array($mode, $allowed_modes, true)) {
    $mode = 'single';
}

if ($month < 1 || $month > 12) {
    $month = (int) current_time('n');
}

if ($week_starts_on < 0 || $week_starts_on > 6) {
    $week_starts_on = 0;
}

$selected_dates = [];

if (array_key_exists('selected', $config)) {
    $raw_selected = $config['selected'];
    $selected_dates = array_map(
        'strval',
        is_array($raw_selected) ? $raw_selected : [(string) $raw_selected],
    );
}

if ($id === '') {
    $id = 'hengegroup-theme-calendar-' . wp_unique_id();
}

if ($nav_name === '') {
    $nav_name = $id . '_month';
}

if ($name === '') {
    $name = 'hengegroup-theme-calendar-day-' . wp_unique_id();
}

$today = current_time('Y-m-d');

$first_of_month = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
$days_in_month = (int) $first_of_month->format('t');
$first_weekday = (int) $first_of_month->format('w');
$leading = ($first_weekday - $week_starts_on + 7) % 7;
$trailing = (7 - (($leading + $days_in_month) % 7)) % 7;

$prev_month_last_day = $first_of_month->modify('-1 day');
$next_month_first_day = $first_of_month->modify('+1 month');

$reference_sunday = new DateTimeImmutable('2023-01-01');
$weekday_headers = '';

for ($offset = 0; $offset < 7; $offset++) {
    $weekday_date = $reference_sunday->modify(sprintf('+%d days', ($week_starts_on + $offset) % 7));
    $weekday_headers .= sprintf(
        '<th scope="col" data-slot="calendar-head-cell" ' .
            'class="py-1 text-center text-xs font-semibold tracking-wide text-muted-foreground" ' .
            'abbr="%1$s">%2$s</th>',
        esc_attr(date_i18n('l', $weekday_date->getTimestamp())),
        esc_html(date_i18n('D', $weekday_date->getTimestamp())),
    );
}

$cells = [];

for ($i = 0; $i < $leading; $i++) {
    $day_number = (int) $prev_month_last_day->format('j') - $leading + $i + 1;
    $cells[] = ['outside' => true, 'day' => $day_number];
}

for ($day = 1; $day <= $days_in_month; $day++) {
    $cells[] = ['outside' => false, 'day' => $day];
}

for ($i = 0; $i < $trailing; $i++) {
    $cells[] = ['outside' => true, 'day' => $i + 1];
}

$body_markup = '';
$week_markup = '';
$column = 0;

foreach ($cells as $cell) {
    if ($cell['outside']) {
        $week_markup .= $show_outside_days
            ? sprintf(
                '<td data-slot="calendar-cell" data-outside="true" ' .
                    'class="h-10 p-0 text-center align-middle text-base text-muted-foreground">%d</td>',
                (int) $cell['day'],
            )
            : '<td data-slot="calendar-cell" data-outside="true" class="h-10 p-0"></td>';

        $column++;
    } else {
        $day = (int) $cell['day'];
        $iso_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $is_selected = in_array($iso_date, $selected_dates, true);
        $is_disabled =
            in_array($iso_date, $disabled_dates, true) ||
            ($min_date !== '' && $iso_date < $min_date) ||
            ($max_date !== '' && $iso_date > $max_date);
        $is_today = $iso_date === $today;

        $day_timestamp = mktime(0, 0, 0, $month, $day, $year);

        // Selected/disabled MUST react live to the native checkbox/radio's own `:checked`/`:disabled`
        // state (`peer-*`, see header comment) -- a render-time boolean class here would go stale the
        // instant a user picks a different day without a page reload. "Today" has no such live
        // concern (it can't change mid-view), so it's the one plain conditional class below.
        $day_classes =
            'peer-focus-visible:border-ring peer-focus-visible:ring-[3px] ' .
            'peer-focus-visible:ring-ring/50 focus-visible:border-ring focus-visible:outline-none ' .
            'focus-visible:ring-[3px] focus-visible:ring-ring/50 flex h-10 w-full cursor-pointer ' .
            'items-center justify-center rounded-xl border border-transparent text-base font-normal ' .
            'text-foreground transition-colors hover:bg-muted peer-checked:border-henge-green ' .
            'peer-checked:bg-henge-green peer-checked:font-semibold ' .
            'peer-checked:text-henge-green-foreground peer-checked:hover:bg-henge-green/90 ' .
            'peer-disabled:pointer-events-none peer-disabled:cursor-not-allowed ' .
            'peer-disabled:opacity-50';

        if ($is_today) {
            $day_classes .= ' ring-1 ring-border';
        }

        ob_start();
        get_template_part('template-parts/base/toggle/toggle', null, [
            'config' => [
                'type' => $mode === 'single' ? 'radio' : 'checkbox',
                'name' => $mode === 'single' ? $name : $name . '[]',
                'value' => $iso_date,
                'pressed' => $is_selected,
                'disabled' => $is_disabled,
                'text' => (string) $day,
                'aria_label' => date_i18n('l, F j, Y', $day_timestamp),
                'class' => $day_classes,
                'data_attributes' => $is_today ? ['today' => 'true'] : [],
            ],
        ]);
        $day_markup = (string) ob_get_clean();

        $week_markup .= sprintf(
            '<td data-slot="calendar-cell" class="p-0 text-center align-middle">%s</td>',
            $day_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
        $column++;
    }

    if ($column === 7) {
        $body_markup .= '<tr data-slot="calendar-row">' . $week_markup . '</tr>';
        $week_markup = '';
        $column = 0;
    }
}

$nav_markup = '';

if ($navigation) {
    $prev_year = (int) $prev_month_last_day->format('Y');
    $prev_month = (int) $prev_month_last_day->format('n');
    $next_year = (int) $next_month_first_day->format('Y');
    $next_month = (int) $next_month_first_day->format('n');

    $prev_url = esc_url(add_query_arg($nav_name, sprintf('%04d-%02d', $prev_year, $prev_month)));
    $next_url = esc_url(add_query_arg($nav_name, sprintf('%04d-%02d', $next_year, $next_month)));

    $prev_icon = hengegroup_theme_render_icon([
        'name' => 'chevron-left',
        'set' => 'lucide',
        'class' => 'size-4',
    ]);
    $next_icon = hengegroup_theme_render_icon([
        'name' => 'chevron-right',
        'set' => 'lucide',
        'class' => 'size-4',
    ]);

    $nav_button_classes =
        'inline-flex size-9 items-center justify-center rounded-xl border border-border ' .
        'bg-background text-foreground transition-colors hover:bg-muted focus-visible:border-ring ' .
        'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50';

    $nav_markup = sprintf(
        '<div data-slot="calendar-nav" class="mb-3.5 flex items-center justify-between">' .
            '<a href="%1$s" data-slot="calendar-nav-button" data-nav="prev" class="%2$s" ' .
            'aria-label="%3$s">%4$s</a>' .
            '<span data-slot="calendar-caption" class="text-base font-semibold text-foreground">%5$s</span>' .
            '<a href="%6$s" data-slot="calendar-nav-button" data-nav="next" class="%2$s" ' .
            'aria-label="%7$s">%8$s</a>' .
            '</div>',
        $prev_url,
        esc_attr($nav_button_classes),
        esc_attr__('Previous month', 'hengegroup-theme'),
        $prev_icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        esc_html(date_i18n('F Y', $first_of_month->getTimestamp())),
        $next_url,
        esc_attr__('Next month', 'hengegroup-theme'),
        $next_icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$table_attributes = [
    'data-slot' => 'calendar-grid',
    'class' => 'w-full table-fixed border-separate border-spacing-1',
    'data-month' => (string) $month,
    'data-year' => (string) $year,
];

if ($aria_label !== '') {
    $table_attributes['aria-label'] = $aria_label;
} else {
    $table_attributes['aria-label'] = date_i18n('F Y', $first_of_month->getTimestamp());
}

$table_markup = sprintf(
    '<table%1$s><thead><tr data-slot="calendar-row">%2$s</tr></thead><tbody>%3$s</tbody></table>',
    hengegroup_theme_render_attributes($table_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $weekday_headers, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $body_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

// Phase 2 (CLAUDE.md Regel 1): the whole wrapper IS the card -- see header comment for why this
// component owns the full bg/border/radius/shadow/padding treatment instead of expecting a caller
// to supply it (unlike e.g. select.php's/combobox.php's bare floating panels).
$card_classes = 'rounded-2xl border border-border bg-card p-6 text-card-foreground shadow-sm';

$wrapper_attributes = $attributes;
$wrapper_attributes['class'] = trim($card_classes . ($class_name !== '' ? ' ' . $class_name : ''));
$wrapper_attributes['data-slot'] = 'calendar';
$wrapper_attributes['id'] = $id;

// Marks a caller-supplied aria_label as custom, distinct from the auto-generated "F Y" caption
// (see $table_attributes above) -- both cases set aria-label on the SAME element (the <table>),
// so calendar.js can't tell them apart just by checking whether aria-label is present (it always
// is, either way). This wrapper-level marker is what calendar.js's renderMonth() checks before
// overwriting the table's aria-label with a freshly formatted caption after month navigation.
if ($aria_label !== '') {
    $wrapper_attributes['data-aria-label-custom'] = 'true';
}

$wrapper_attributes['data-mode'] = $mode;
$wrapper_attributes['data-week-starts-on'] = (string) $week_starts_on;
$wrapper_attributes['data-show-outside-days'] = $show_outside_days ? 'true' : 'false';
$wrapper_attributes['data-name'] = $name;

if ($navigation) {
    $wrapper_attributes['data-nav-name'] = $nav_name;
}

if ($min_date !== '') {
    $wrapper_attributes['data-min-date'] = $min_date;
}

if ($max_date !== '') {
    $wrapper_attributes['data-max-date'] = $max_date;
}

if ($disabled_dates !== []) {
    $wrapper_attributes['data-disabled-dates'] = wp_json_encode(array_values($disabled_dates));
}

if ($selected_dates !== []) {
    $wrapper_attributes['data-selected'] = wp_json_encode(array_values($selected_dates));
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $wrapper_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<div%1$s>%2$s%3$s</div>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $nav_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $table_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
