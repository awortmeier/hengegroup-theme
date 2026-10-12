<?php

declare(strict_types=1);

// Helper text for a form field.
//
// Config:
//   text   string   required
//   for    string   control id; derives the id via hengegroup_theme_field_description_id()
//   id     string   explicit id (wins over `for`), else wp_unique_id()
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ''));
$id = trim((string) ($config['id'] ?? ''));
$for = trim((string) ($config['for'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($text === '') {
    return;
}

if ($id === '') {
    $id =
        $for !== ''
            ? hengegroup_theme_field_description_id($for)
            : 'hengegroup-theme-field-description-' . wp_unique_id();
}

$base_classes =
    'text-muted-foreground text-sm leading-normal font-normal last:mt-0 nth-last-2:-mt-1 ' .
    'group-has-[[data-orientation=horizontal]]/field:text-balance ' .
    '[[data-variant=legend]+&]:-mt-1.5 [&>a]:underline [&>a]:underline-offset-4 ' .
    '[&>a:hover]:text-henge-green';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'field-description';
$element_attributes['id'] = $id;

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<p%1$s>%2$s</p>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);
