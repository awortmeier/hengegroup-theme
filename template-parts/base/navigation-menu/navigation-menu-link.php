<?php

declare(strict_types=1);

// Navigation link (top-level item or inside a mega-menu panel). Only a minimal base look; the
// caller supplies the context-specific look via `class`.
//
// Config:
//   text / label   string   link text
//   content        string   richer pre-rendered body (wins over text)
//   href           string   required
//   active         bool     aria-current="page", henge-blue
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ($config['label'] ?? '')));
$content = (string) ($config['content'] ?? '');
$href = trim((string) ($config['href'] ?? ''));
$active = !empty($config['active']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($href === '' || ($text === '' && trim($content) === '')) {
    return;
}

$inner_html = trim($content) !== '' ? $content : esc_html($text);

$base_class =
    'outline-none rounded-lg transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50';

if ($active) {
    $base_class .= ' text-henge-blue font-semibold';
}

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_class . ($class_name !== '' ? ' ' . $class_name : ''));
$element_attributes['data-slot'] = 'navigation-menu-link';
$element_attributes['href'] = $href;

if ($active) {
    $element_attributes['data-active'] = 'true';
    $element_attributes['aria-current'] = 'page';
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<a%1$s>%2$s</a>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
