<?php

declare(strict_types=1);

// Divider between field sections, optionally with centered text (e.g. "Or continue with").
//
// Config:
//   text   string   optional centered text
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$base_classes = 'relative -my-2 h-5 text-sm group-data-[variant=outline]/field-group:-mb-2';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'field-separator';
$element_attributes['data-content'] = $text !== '' ? 'true' : 'false';

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

ob_start();
get_template_part('template-parts/base/separator/separator', null, [
    'config' => ['class' => 'absolute inset-0 top-1/2'],
]);
$separator_markup = (string) ob_get_clean();

$content_markup =
    $text !== ''
        ? sprintf(
            '<span class="relative mx-auto block w-fit bg-background px-2 text-muted-foreground" data-slot="field-separator-content">%s</span>',
            esc_html($text),
        )
        : '';

printf(
    '<div%1$s>%2$s%3$s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $separator_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
