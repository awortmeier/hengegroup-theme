<?php

declare(strict_types=1);

// Button, <a> with `href`.
//
// Config:
//   text / label    string   omit for icon-only
//   href            string   renders <a>
//   variant         string   henge-green (default) | henge-blue | henge-grey | grey-dark |
//                            grey-light | destructive | outline | ghost | link
//   size            string   sm | base (default) | lg | icon-sm | icon-base | icon-lg
//   type            string   button | submit | reset
//   full_width      bool     w-full
//   disabled        bool
//   loading         bool     implies disabled, shows a spinner, sets aria-busy
//   icon            array    icon.php config
//   icon_position   string   start | end
//   spinner         array    spinner.php overrides for the loading spinner
//   aria_label      string   required for icon-only (WP_DEBUG warning otherwise)
//   class / attributes / data_attributes   `class` is appended, not merged

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ($config['label'] ?? '')));
$href = trim((string) ($config['href'] ?? ''));
$variant = trim((string) ($config['variant'] ?? 'henge-green'));
$size = trim((string) ($config['size'] ?? 'base'));
$type = trim((string) ($config['type'] ?? 'button'));
$full_width = !empty($config['full_width']);
$disabled = !empty($config['disabled']);
$loading = !empty($config['loading']);
$icon_config = is_array($config['icon'] ?? null) ? $config['icon'] : null;
$icon_position = trim((string) ($config['icon_position'] ?? 'start'));
$spinner_config_override = is_array($config['spinner'] ?? null) ? $config['spinner'] : [];
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$has_icon = $icon_config !== null;

if ($text === '' && !$has_icon && !$loading) {
    return;
}

if ($loading) {
    $disabled = true;
}

$allowed_variants = [
    'henge-green',
    'henge-blue',
    'henge-grey',
    'grey-dark',
    'grey-light',
    'destructive',
    'outline',
    'ghost',
    'link',
];
$allowed_sizes = ['sm', 'base', 'lg', 'icon-sm', 'icon-base', 'icon-lg'];

if (!in_array($variant, $allowed_variants, true)) {
    $variant = 'henge-green';
}

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'base';
}

$base_classes =
    'inline-flex shrink-0 items-center justify-center gap-2 rounded-full font-medium ' .
    'whitespace-nowrap transition-all outline-none focus-visible:border-ring ' .
    'focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none ' .
    'disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 ' .
    "[&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4";

// `!` on colors: WordPress' default .wp-element-button colors otherwise win in the editor iframe,
// where global styles load after app.css.
$variant_classes = [
    'henge-green' => '!bg-henge-green !text-henge-green-foreground hover:!bg-henge-green/90',
    'henge-blue' => '!bg-henge-blue !text-henge-blue-foreground hover:!bg-henge-blue/90',
    'henge-grey' => '!bg-henge-grey !text-henge-grey-foreground hover:!bg-henge-grey/90',
    'grey-dark' => '!bg-grey-dark !text-grey-dark-foreground hover:!bg-grey-dark/90',
    'grey-light' => '!bg-grey-light !text-grey-light-foreground hover:!bg-grey-light/80',
    'destructive' =>
        '!bg-destructive !text-destructive-foreground hover:!bg-destructive/90 focus-visible:ring-destructive/20',
    'outline' =>
        'border border-grey-dark !bg-background shadow-xs hover:!bg-grey-light hover:!text-grey-light-foreground',
    'ghost' => 'hover:!bg-grey-light hover:!text-grey-light-foreground',
    'link' => '!text-grey-dark underline-offset-4 hover:underline',
];

$size_classes = [
    'sm' =>
        "h-6 gap-1 rounded-full px-2.5 text-sm has-[>svg]:px-2 [&_svg:not([class*='size-'])]:size-3",
    'base' => 'h-8 gap-1.5 rounded-full px-4 text-base has-[>svg]:px-3',
    'lg' => 'h-10 rounded-full px-7 text-lg has-[>svg]:px-5',
    'icon-sm' => "size-6 rounded-full text-sm [&_svg:not([class*='size-'])]:size-3",
    'icon-base' => 'size-8 text-base',
    'icon-lg' => 'size-10 text-lg',
];

$computed_class = "{$base_classes} {$variant_classes[$variant]} {$size_classes[$size]}";

if ($full_width) {
    $computed_class .= ' w-full';
}

if ($icon_position !== 'end') {
    $icon_position = 'start';
}

$is_icon_only = $text === '' && ($has_icon || $loading);

if ($loading) {
    // icon-* sizes reuse the spinner size of their text counterpart.
    $spinner_size_map = [
        'sm' => 'sm',
        'base' => 'base',
        'lg' => 'lg',
        'icon-sm' => 'sm',
        'icon-base' => 'base',
        'icon-lg' => 'lg',
    ];

    $spinner_config = array_merge(
        [
            'size' => $spinner_size_map[$size] ?? 'base',
            'color' => 'inherit',
            'decorative' => true,
        ],
        $spinner_config_override,
    );

    if (!$is_icon_only) {
        $spinner_config['data_attributes'] = array_merge(
            is_array($spinner_config['data_attributes'] ?? null)
                ? $spinner_config['data_attributes']
                : [],
            ['icon' => $icon_position === 'end' ? 'inline-end' : 'inline-start'],
        );
    }

    ob_start();
    get_template_part('template-parts/base/spinner', null, ['config' => $spinner_config]);
    $icon_markup = (string) ob_get_clean();
} elseif ($has_icon) {
    $active_icon_config = $icon_config;
    $active_icon_config['data_attributes'] = array_merge(
        is_array($active_icon_config['data_attributes'] ?? null)
            ? $active_icon_config['data_attributes']
            : [],
        $is_icon_only ? [] : ['icon' => $icon_position === 'end' ? 'inline-end' : 'inline-start'],
    );

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

$element_attributes = $attributes;
// wp-element-button exempts the button from theme.json's global link color
// (a:where(:not(.wp-element-button))), like core's own buttons.
$element_attributes['class'] = trim(
    $computed_class . ' wp-element-button' . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['data-slot'] = 'button';
$element_attributes['data-variant'] = $variant;
$element_attributes['data-size'] = $size;

if ($loading) {
    $element_attributes['aria-busy'] = 'true';
}

if ($is_icon_only && $aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
}

hengegroup_theme_warn_missing_aria_label('button.php', $is_icon_only, $aria_label);

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

if ($href !== '') {
    if ($disabled) {
        // <a> has no disabled attribute: drop href, announce via aria-disabled.
        $element_attributes['aria-disabled'] = 'true';
    } else {
        $element_attributes['href'] = $href;
    }

    printf(
        '<a%1$s>%2$s</a>',
        hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );

    return;
}

$allowed_types = ['button', 'submit', 'reset'];

if (!in_array($type, $allowed_types, true)) {
    $type = 'button';
}

$element_attributes['type'] = $type;

if ($disabled) {
    $element_attributes['disabled'] = true;
}

printf(
    '<button%1$s>%2$s</button>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
