<?php

declare(strict_types=1);

// Toggle button on a visually hidden checkbox (`peer sr-only`) + <label> as the visible surface:
// works without JS. toggle.js hides the input from assistive tech and turns the label into
// role="button" aria-pressed (except radios inside a role="radiogroup", see toggle-group.php).
// Style off the input's live state via peer-*, not off the render-time data-state.
//
// Config:
//   pressed         bool     initial state (checked)
//   type            string   checkbox (default) | radio (only for toggle-group.php single mode)
//   text / label    string   omit for icon-only
//   icon            array    icon.php config
//   icon_position   string   start | end
//   variant         string   default | outline | accent
//   size            string   default | sm | lg
//   disabled        bool     also data-disabled
//   required        bool
//   aria_invalid    bool     aria-invalid + data-invalid
//   name / value    string   form submission
//   id              string   default wp_unique_id()
//   aria_label      string   required for icon-only (WP_DEBUG warning otherwise)
//   class / attributes / data_attributes   on the <label>

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

// One border-color class per variant: border-transparent plus a variant color would tie on
// specificity and Tailwind's output order would decide.
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

$label_attributes = hengegroup_theme_merge_data_attributes($label_attributes, $data_attributes);

printf(
    '%1$s<label%2$s>%3$s</label>',
    $checkbox_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    hengegroup_theme_render_attributes($label_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
