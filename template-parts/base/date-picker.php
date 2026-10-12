<?php

declare(strict_types=1);

// Date picker: <details>/<summary> with a nested calendar.php (works without JS).
// date-picker.js updates the trigger text, closes on selection, outside click and Escape, and
// blocks opening while disabled (without JS `disabled` is only announced).
//
// Config:
//   mode                 string         single (default) | multiple
//   selected             string|array   Y-m-d
//   placeholder          string         default "Pick a date"
//   date_format          string         date_i18n() format, default 'F j, Y'
//   month / year         int
//   disabled_dates / min_date / max_date / week_starts_on / show_outside_days   see calendar.php
//   name                 string         form name of the date(s)
//   icon                 array|false    default calendar icon
//   disabled             bool
//   aria_label           string
//   id                   string         default wp_unique_id()
//   class / attributes / data_attributes

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
    // "true" instead of "dialog": the panel is not a dialog (no role, no focus trap).
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
    '<div class="absolute top-full left-0 z-50 mt-2" data-slot="date-picker-content">%s</div>',
    $calendar_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

$wrapper_attributes = $attributes;
$wrapper_attributes['class'] = trim('relative' . ($class_name !== '' ? ' ' . $class_name : ''));
$wrapper_attributes['data-slot'] = 'date-picker';
$wrapper_attributes['data-mode'] = $mode;
$wrapper_attributes['data-placeholder'] = $placeholder;
$wrapper_attributes['data-count-template'] = esc_html__('%d dates selected', 'hengegroup-theme');
$wrapper_attributes['id'] = $id;

$wrapper_attributes = hengegroup_theme_merge_data_attributes($wrapper_attributes, $data_attributes);

printf(
    '<details%1$s>%2$s%3$s</details>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
