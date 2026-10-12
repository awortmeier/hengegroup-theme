<?php

declare(strict_types=1);

// Slot inside input-group.php for an icon, button (button.php, ghost, sm) or text
// (input-group-text.php) next to the control.
//
// Config:
//   content   string   pre-rendered HTML (required)
//   align     string   inline-start (default) | inline-end | block-start | block-end
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$align = trim((string) ($config['align'] ?? 'inline-start'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

$allowed_aligns = ['inline-start', 'inline-end', 'block-start', 'block-end'];

if (!in_array($align, $allowed_aligns, true)) {
    $align = 'inline-start';
}

$base_classes =
    'text-muted-foreground flex items-center justify-center gap-2 py-3 text-base font-medium ' .
    "select-none md:text-sm [&_svg:not([class*='size-'])]:size-4 [&_svg]:pointer-events-none " .
    'data-[align=inline-start]:order-first data-[align=inline-start]:pl-3.5 ' .
    'data-[align=inline-start]:has-[>button]:-ml-2 ' .
    'data-[align=inline-end]:order-last data-[align=inline-end]:pr-3.5 ' .
    'data-[align=inline-end]:has-[>button]:-mr-2 ' .
    'data-[align=block-start]:order-first data-[align=block-start]:w-full ' .
    'data-[align=block-start]:justify-start data-[align=block-start]:px-3.5 ' .
    'data-[align=block-start]:pt-3 data-[align=block-end]:order-last ' .
    'data-[align=block-end]:w-full data-[align=block-end]:justify-start ' .
    'data-[align=block-end]:px-3.5 data-[align=block-end]:pb-3';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'input-group-addon';
$element_attributes['data-align'] = $align;
$element_attributes['role'] = 'group';

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
