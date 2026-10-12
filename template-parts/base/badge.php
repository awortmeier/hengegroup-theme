<?php

declare(strict_types=1);

// Static label, <a> with `href`.
//
// Config:
//   text / label    string   omit for icon-only
//   href            string   renders <a>
//   variant         string   henge-green (default) | henge-blue | henge-grey | grey-dark |
//                            grey-light | outline
//   icon            array    icon.php config
//   icon_position   string   start | end
//   font            string   primary (default) | accent (Crillee)
//   aria_label      string   required for icon-only
//   class / attributes / data_attributes   `class` is appended, not merged

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ($config['label'] ?? '')));
$href = trim((string) ($config['href'] ?? ''));
$variant = trim((string) ($config['variant'] ?? 'henge-green'));
$icon_config = is_array($config['icon'] ?? null) ? $config['icon'] : null;
$icon_position = trim((string) ($config['icon_position'] ?? 'start'));
$font = trim((string) ($config['font'] ?? 'primary'));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$has_icon = $icon_config !== null;

if ($text === '' && !$has_icon) {
    return;
}

$allowed_variants = [
    'grey-dark',
    'grey-light',
    'henge-blue',
    'henge-green',
    'henge-grey',
    'outline',
];

if (!in_array($variant, $allowed_variants, true)) {
    $variant = 'henge-green';
}

$allowed_fonts = ['primary', 'accent'];

if (!in_array($font, $allowed_fonts, true)) {
    $font = 'primary';
}

$base_classes =
    'inline-flex w-fit shrink-0 items-center justify-center gap-1 overflow-hidden rounded-full ' .
    'border border-transparent px-2 py-0.75 text-sm font-medium whitespace-nowrap ' .
    'transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-[3px] ' .
    'focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 ' .
    '[&>svg]:pointer-events-none [&>svg]:size-3';

$variant_classes = [
    'henge-green' => 'bg-henge-green text-henge-green-foreground [a&]:hover:bg-henge-green/90',
    'henge-blue' => 'bg-henge-blue text-henge-blue-foreground [a&]:hover:bg-henge-blue/90',
    'henge-grey' => 'bg-henge-grey text-henge-grey-foreground [a&]:hover:bg-henge-grey/90',
    'grey-dark' => 'bg-grey-dark text-grey-dark-foreground [a&]:hover:bg-grey-dark/90',
    'grey-light' => 'bg-grey-light text-grey-light-foreground [a&]:hover:bg-grey-light/80',
    // `!` because border-transparent from the base classes would otherwise win (Tailwind order).
    'outline' =>
        '!border-neutral-400 text-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground',
];

$computed_class = "{$base_classes} {$variant_classes[$variant]}";

if ($font === 'accent') {
    $computed_class .= ' font-accent';
}

if ($icon_position !== 'end') {
    $icon_position = 'start';
}

$is_icon_only = $text === '';

if ($has_icon && !$is_icon_only) {
    $icon_config['data_attributes'] = array_merge(
        is_array($icon_config['data_attributes'] ?? null) ? $icon_config['data_attributes'] : [],
        ['icon' => $icon_position === 'end' ? 'inline-end' : 'inline-start'],
    );
}

$icon_markup = $has_icon ? hengegroup_theme_render_icon($icon_config) : '';

if ($is_icon_only) {
    $inner_html = $icon_markup;
} elseif ($icon_markup !== '') {
    $inner_html =
        $icon_position === 'end' ? esc_html($text) . $icon_markup : $icon_markup . esc_html($text);
} else {
    $inner_html = esc_html($text);
}

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['data-slot'] = 'badge';
$element_attributes['data-variant'] = $variant;
$element_attributes['data-font'] = $font;

if ($is_icon_only && $aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

$tag = 'span';

if ($href !== '') {
    $tag = 'a';
    $element_attributes['href'] = $href;
}

printf(
    '<%1$s%2$s>%3$s</%1$s>',
    esc_html($tag),
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
