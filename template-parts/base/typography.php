<?php

declare(strict_types=1);

// Text with a fixed size scale. `variant` (look) and `tag` (semantics) are independent: pass `tag`
// whenever the heading level differs from the visual size.
//
// Config:
//   text / content   string   required
//   variant          string   headline-lg | headline-base | headline-sm | headline-xs | body-lg |
//                             body-base (default) | body-sm | body-xs
//   tag              string   default depends on variant
//   color            string   default | light (on dark backgrounds) | neutral (muted)
//   accent_words     array    words in the accent font
//   accent_markup    bool     text is RichText HTML with the "Akzent" format (false)
//   data_slot        string   default 'typography'
//   class / attributes / data_attributes   `class` is appended, not merged

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ($config['content'] ?? '')));
$variant = trim((string) ($config['variant'] ?? 'body-base'));
$tag = strtolower(trim((string) ($config['tag'] ?? '')));
$color = trim((string) ($config['color'] ?? 'default'));
$data_slot = trim((string) ($config['data_slot'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$accent_words = is_array($config['accent_words'] ?? null) ? $config['accent_words'] : [];
$accent_markup = ($config['accent_markup'] ?? false) === true;
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($text === '') {
    return;
}

if ($data_slot === '') {
    $data_slot = 'typography';
}

$variant_default_tags = [
    'headline-lg' => 'h1',
    'headline-base' => 'h2',
    'headline-sm' => 'h3',
    'headline-xs' => 'h4',
    'body-lg' => 'p',
    'body-base' => 'p',
    'body-sm' => 'p',
    'body-xs' => 'small',
];

if (!array_key_exists($variant, $variant_default_tags)) {
    $variant = 'body-base';
}

$allowed_tags = ['p', 'span', 'div', 'strong', 'em', 'small', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

if (!in_array($tag, $allowed_tags, true)) {
    $tag = $variant_default_tags[$variant];
}

$allowed_colors = ['default', 'light', 'neutral'];

if (!in_array($color, $allowed_colors, true)) {
    $color = 'default';
}

$variant_classes = [
    'headline-lg' => 'text-6xl font-semibold leading-tight',
    'headline-base' => 'text-5xl font-semibold leading-tight',
    'headline-sm' => 'text-4xl font-semibold leading-tight',
    'headline-xs' => 'text-3xl font-semibold leading-tight',
    'body-lg' => 'text-2xl leading-normal',
    'body-base' => 'text-lg leading-normal',
    'body-sm' => 'text-base leading-normal',
    'body-xs' => 'text-sm leading-normal',
];

$color_classes = [
    'default' => '',
    'light' => 'text-grey-light',
    'neutral' => 'text-muted-foreground',
];

$computed_class = $variant_classes[$variant];

if ($color_classes[$color] !== '') {
    $computed_class .= ' ' . $color_classes[$color];
}

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['data-slot'] = $data_slot;
$element_attributes['data-variant'] = $variant;
$element_attributes['data-color'] = $color;

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

$content = $accent_markup
    ? hengegroup_theme_render_accent_markup($text)
    : hengegroup_theme_render_accent_text($text, $accent_words);

printf(
    '<%1$s%2$s>%3$s</%1$s>',
    esc_html($tag),
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
