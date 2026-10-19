<?php

declare(strict_types=1);

// Text with a fixed size scale (typography-classes.json, shared with the block editor). `variant`
// (look) and `tag` (semantics) are independent: pass `tag` whenever the heading level differs from
// the visual size.
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

$classes = hengegroup_theme_get_typography_classes();

if (!isset($classes['variants'][$variant])) {
    $variant = 'body-base';
}

$allowed_tags = ['p', 'span', 'div', 'strong', 'em', 'small', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

if (!in_array($tag, $allowed_tags, true)) {
    $tag = (string) $classes['tags'][$variant];
}

if (!isset($classes['colors'][$color])) {
    $color = 'default';
}

$computed_class = trim($classes['variants'][$variant] . ' ' . $classes['colors'][$color]);

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
