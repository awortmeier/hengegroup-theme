<?php

declare(strict_types=1);

// Joins buttons into one segmented bar (role="group"). Divider: separator.php vertical with
// `class: 'bg-input m-0!'`.
//
// Config:
//   content       string   pre-rendered HTML (required)
//   orientation   string   horizontal (default) | vertical
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$orientation = trim((string) ($config['orientation'] ?? 'horizontal'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

$allowed_orientations = ['horizontal', 'vertical'];

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'horizontal';
}

$base_classes =
    'flex w-fit items-stretch has-[>[data-slot=button-group]]:gap-2 ' .
    '[&>*]:focus-visible:relative [&>*]:focus-visible:z-10 ' .
    'has-[select[aria-hidden=true]:last-child]:[&>[data-slot=select-trigger]:last-of-type]:rounded-r-lg ' .
    "[&>[data-slot=select-trigger]:not([class*='w-'])]:w-fit [&>input]:flex-1";

$orientation_classes = [
    'horizontal' =>
        '[&>*:not(:first-child)]:rounded-l-none [&>*:not(:first-child)]:border-l-0 ' .
        '[&>*:not(:last-child)]:rounded-r-none',
    'vertical' =>
        'flex-col [&>*:not(:first-child)]:rounded-t-none [&>*:not(:first-child)]:border-t-0 ' .
        '[&>*:not(:last-child)]:rounded-b-none',
];

$computed_class = "{$base_classes} {$orientation_classes[$orientation]}";

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['role'] = 'group';
$element_attributes['data-slot'] = 'button-group';
$element_attributes['data-orientation'] = $orientation;

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
