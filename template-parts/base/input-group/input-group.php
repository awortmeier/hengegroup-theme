<?php

declare(strict_types=1);

// Input with addons: the wrapper owns border/background/focus ring, the nested control renders bare.
// The control needs `data_slot: 'input-group-control'`; disabled/invalid styling reads the control
// via :has(). Put labels outside the group.
//
// Config:
//   content   string   input-group-addon + control markup (required)
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

$base_classes =
    'border-input bg-background relative flex w-full min-w-0 items-center rounded-lg border ' .
    'shadow-xs transition-[color,box-shadow] outline-none has-[>textarea]:items-start ' .
    'has-[>[data-align=inline-start]]:[&>input]:pl-2 has-[>[data-align=inline-end]]:[&>input]:pr-2 ' .
    'has-[>[data-align=block-start]]:flex-col has-[>[data-align=block-start]]:[&>input]:pb-3 ' .
    'has-[>[data-align=block-end]]:flex-col has-[>[data-align=block-end]]:[&>input]:pt-3 ' .
    'has-[[data-slot=input-group-control]:focus-visible]:border-ring ' .
    'has-[[data-slot=input-group-control]:focus-visible]:ring-ring/50 ' .
    'has-[[data-slot=input-group-control]:focus-visible]:ring-[3px] ' .
    'has-[[data-slot=input-group-control]:disabled]:pointer-events-none ' .
    'has-[[data-slot=input-group-control]:disabled]:opacity-50 ' .
    'has-[[data-slot=input-group-control][aria-invalid=true]]:ring-destructive/20 ' .
    'has-[[data-slot=input-group-control][aria-invalid=true]]:border-destructive';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'input-group';
$element_attributes['role'] = 'group';

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
