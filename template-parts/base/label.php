<?php

declare(strict_types=1);

// <label>. With `data_slot: 'field-label'` it uses the field-label class set instead (replaced, not
// appended, because the classes conflict and PHP has no tailwind-merge).
//
// Config:
//   text / label   string   required
//   for            string   control id (omit when the label wraps the control)
//   data_slot      string   default 'label'
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ($config['label'] ?? '')));
$for = trim((string) ($config['for'] ?? ''));
$data_slot = trim((string) ($config['data_slot'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($text === '') {
    return;
}

if ($data_slot === '') {
    $data_slot = 'label';
}

$is_field_label = $data_slot === 'field-label';

$base_classes = $is_field_label
    ? 'group/field-label peer/field-label flex w-fit gap-2 leading-snug ' .
        'group-data-[disabled=true]/field:opacity-50 has-[>[data-slot=field]]:w-full ' .
        'has-[>[data-slot=field]]:flex-col has-[>[data-slot=field]]:rounded-lg ' .
        'has-[>[data-slot=field]]:border has-[>[data-slot=field]]:border-border ' .
        '[&>*]:data-[slot=field]:p-4 has-data-[state=checked]:border-henge-green ' .
        'has-data-[state=checked]:bg-henge-green/5'
    : 'flex items-center gap-2 text-sm leading-none font-medium select-none ' .
        'group-has-[[data-disabled=true]]:pointer-events-none ' .
        'group-has-[[data-disabled=true]]:opacity-50 peer-disabled:cursor-not-allowed ' .
        'peer-disabled:opacity-50';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = $data_slot;

if ($for !== '') {
    $element_attributes['for'] = $for;
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<label%1$s>%2$s</label>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);
