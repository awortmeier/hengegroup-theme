<?php

declare(strict_types=1);

// API modeled on shadcn/ui's Button (variant/size vocabulary, icon slot, loading state,
// polymorphic root element) but intentionally unstyled: no Tailwind/utility classes are
// applied here. variant/size are exposed only as data-attributes so a project's own CSS
// can target e.g. [data-slot="button"][data-variant="destructive"][data-size="lg"].
//
// Supported config:
//   text / label   string   visible label (omit for an icon-only button)
//   href           string   renders <a> instead of <button> (shadcn's asChild/Slot analog)
//   variant        string   default | secondary | destructive | outline | ghost | link
//   size           string   default | xs | sm | lg | icon | icon-xs | icon-sm | icon-lg (matches
//                           shadcn's current Button size scale as of its Base UI/React
//                           Aria/Radix UI multi-backend rewrite -- `xs` and the combined
//                           `icon-*` sizes used to not exist in shadcn's stock vocabulary, they
//                           do now, so nothing here is a project addition anymore)
//   type           string   button | submit | reset (ignored when href is set)
//   disabled       bool
//   loading        bool     implies disabled; swaps the icon slot for a spinner and sets aria-busy
//   icon           array    icon.php config, e.g. ['name' => 'arrow-right', 'set' => 'lucide'].
//                           Rendered with a data-icon="inline-start"|"inline-end" attribute
//                           (matching shadcn's own icon-spacing convention) reflecting
//                           `icon_position`, unless the button is icon-only (nothing to be
//                           "inline" relative to there)
//   icon_position  string   start | end (ignored for icon-only buttons)
//   spinner_icon   array    icon.php config override for the loading spinner
//                           (default: ['name' => 'loader-circle', 'set' => 'lucide'])
//   aria_label     string   required for icon-only buttons (no visible text to name them) -- a
//                           missing value doesn't hard-fail the render, but triggers
//                           a WP_DEBUG-only _doing_it_wrong() hint, see
//                           hengegroup_theme_warn_missing_aria_label()
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ($config['label'] ?? '')));
$href = trim((string) ($config['href'] ?? ''));
$variant = trim((string) ($config['variant'] ?? 'default'));
$size = trim((string) ($config['size'] ?? 'default'));
$type = trim((string) ($config['type'] ?? 'button'));
$disabled = !empty($config['disabled']);
$loading = !empty($config['loading']);
$icon_config = is_array($config['icon'] ?? null) ? $config['icon'] : null;
$icon_position = trim((string) ($config['icon_position'] ?? 'start'));
$spinner_icon_config = is_array($config['spinner_icon'] ?? null)
    ? $config['spinner_icon']
    : ['name' => 'loader-circle', 'set' => 'lucide'];
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

$allowed_variants = ['default', 'secondary', 'destructive', 'outline', 'ghost', 'link'];
$allowed_sizes = ['default', 'xs', 'sm', 'lg', 'icon', 'icon-xs', 'icon-sm', 'icon-lg'];

if (!in_array($variant, $allowed_variants, true)) {
    $variant = 'default';
}

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'default';
}

if ($icon_position !== 'end') {
    $icon_position = 'start';
}

$is_icon_only = $text === '' && ($has_icon || $loading);

if ($loading) {
    $active_icon_config = $spinner_icon_config;
} elseif ($has_icon) {
    $active_icon_config = $icon_config;
} else {
    $active_icon_config = null;
}

if ($active_icon_config !== null) {
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

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

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

foreach ($data_attributes as $name => $value) {
    $data_name = trim((string) $name);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $value;
}

if ($href !== '') {
    if ($disabled) {
        // <a> has no native disabled attribute; dropping href removes it from the tab order
        // and from navigation, aria-disabled announces the state to assistive tech.
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
