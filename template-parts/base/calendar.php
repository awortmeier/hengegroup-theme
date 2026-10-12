<?php

declare(strict_types=1);

// Month grid rendered server-side; each day is a toggle.php (radio for single, checkbox for
// multiple), so selection works without JS. Month navigation is an add_query_arg() link (the
// caller reads the query var and passes month/year). calendar.js navigates without reload and
// recomputes the grid with the same formula and the same classes; it reads its settings from the
// data-* attributes rendered here.
//
// Config:
//   mode                string         single (default) | multiple
//   month               int            1-12, default current month
//   year                int            default current year
//   selected            string|array   Y-m-d date(s)
//   disabled_dates      array          Y-m-d dates
//   min_date / max_date string         Y-m-d, inclusive
//   week_starts_on      int            0 (Sunday, default) - 6
//   show_outside_days   bool           default true
//   navigation          bool           default true; prev/next + caption
//   nav_name            string         query var for navigation, default {id}_month
//   name                string         form name of the days, default wp_unique_id()
//   aria_label          string         name of the <table>
//   id                  string         default wp_unique_id()
//   class / attributes / data_attributes

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

        // Selected/disabled via peer-* so they follow the live input state. `!` because toggle.php's
        // own default classes target the same properties (no tailwind-merge in PHP).
        $day_classes =
            'peer-focus-visible:border-ring peer-focus-visible:ring-[3px] ' .
            'peer-focus-visible:ring-ring/50 focus-visible:border-ring focus-visible:outline-none ' .
            'focus-visible:ring-[3px] focus-visible:ring-ring/50 flex !h-10 w-full cursor-pointer ' .
            'items-center justify-center !rounded-xl border border-transparent text-base ' .
            '!font-normal !text-foreground transition-colors hover:bg-muted ' .
            'peer-checked:border-henge-green peer-checked:!bg-henge-green peer-checked:font-semibold ' .
            'peer-checked:!text-henge-green-foreground peer-checked:!shadow-none ' .
            'peer-checked:hover:!bg-henge-green/90 peer-checked:hover:!text-henge-green-foreground ' .
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

$card_classes = 'rounded-2xl border border-border bg-card p-6 text-card-foreground shadow-sm';

$wrapper_attributes = $attributes;
$wrapper_attributes['class'] = trim($card_classes . ($class_name !== '' ? ' ' . $class_name : ''));
$wrapper_attributes['data-slot'] = 'calendar';
$wrapper_attributes['id'] = $id;

// Tells calendar.js not to overwrite a custom aria-label after navigation.
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

$wrapper_attributes = hengegroup_theme_merge_data_attributes($wrapper_attributes, $data_attributes);

printf(
    '<div%1$s>%2$s%3$s</div>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $nav_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $table_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
