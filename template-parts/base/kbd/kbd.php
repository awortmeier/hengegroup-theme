<?php

declare(strict_types=1);

// Keycap (<kbd>). Combos: several kbd.php inside kbd-group.php.
//
// Config:
//   text      string   required
//   size      string   sm | default | lg
//   pressed   bool     sunken look, sets data-state="on"
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ''));
$size = trim((string) ($config['size'] ?? 'default'));
$pressed = !empty($config['pressed']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($text === '') {
    return;
}

$allowed_sizes = ['sm', 'default', 'lg'];

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'default';
}

$base_classes =
    'pointer-events-none inline-flex w-fit shrink-0 items-center justify-center ' .
    'whitespace-nowrap font-mono leading-none text-foreground select-none ' .
    'bg-background border border-foreground/15 border-b-2 border-b-foreground/25 ' .
    'transition-[transform,background-color,color,border-color] duration-100 ease-out ' .
    'data-[state=on]:translate-y-px data-[state=on]:border-b data-[state=on]:border-henge-green ' .
    'data-[state=on]:border-b-henge-green data-[state=on]:bg-henge-green ' .
    'data-[state=on]:text-henge-green-foreground';

$size_classes = [
    'sm' => 'h-6 min-w-6 rounded-sm px-2 text-xs',
    'default' => 'h-8 min-w-8 rounded-md px-2.5 text-sm',
    'lg' => 'h-10 min-w-10 rounded-lg px-3 text-base',
];

$computed_class = "{$base_classes} {$size_classes[$size]}";

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['data-slot'] = 'kbd';
$element_attributes['data-size'] = $size;
$element_attributes['data-state'] = $pressed ? 'on' : 'off';

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<kbd%1$s>%2$s</kbd>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);
