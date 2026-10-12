<?php

declare(strict_types=1);

// Divider line. A <div> instead of <hr> to support vertical and decorative (role="none").
//
// Config:
//   orientation   string   horizontal (default) | vertical (needs a flex/grid parent)
//   decorative    bool     default true; false = role="separator"
//   weight        string   thin | default | thick | section
//   style         string   solid (default) | dashed (always hairline) | accent | gradient
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$orientation = trim((string) ($config['orientation'] ?? 'horizontal'));
$decorative = !array_key_exists('decorative', $config) || !empty($config['decorative']);
$weight = trim((string) ($config['weight'] ?? 'default'));
$style = trim((string) ($config['style'] ?? 'solid'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_orientations = ['horizontal', 'vertical'];

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'horizontal';
}

// Full literal class strings: Tailwind only finds classes that appear literally in the source.
$weight_map = [
    'thin' => ['fill' => 'bg-foreground/8', 'thickness' => 'px', 'rounded' => false],
    'default' => ['fill' => 'bg-foreground/12', 'thickness' => 'px', 'rounded' => false],
    'thick' => ['fill' => 'bg-foreground/24', 'thickness' => 'px', 'rounded' => false],
    'section' => ['fill' => 'bg-foreground/16', 'thickness' => '0.75', 'rounded' => true],
];

if (!array_key_exists($weight, $weight_map)) {
    $weight = 'default';
}

$allowed_styles = ['solid', 'dashed', 'accent', 'gradient'];

if (!in_array($style, $allowed_styles, true)) {
    $style = 'solid';
}

// Vertical uses self-stretch + h-auto: h-full resolves to 0 in a content-sized flex row.
if ($style === 'dashed') {
    $variant_classes =
        'data-[orientation=horizontal]:h-0 data-[orientation=horizontal]:w-full ' .
        'data-[orientation=horizontal]:border-t data-[orientation=vertical]:h-auto ' .
        'data-[orientation=vertical]:self-stretch data-[orientation=vertical]:w-0 ' .
        'data-[orientation=vertical]:border-l border-dashed border-foreground/28';
} else {
    $weight_config = $weight_map[$weight];
    $thickness_classes =
        $weight_config['thickness'] === 'px'
            ? 'data-[orientation=horizontal]:h-px data-[orientation=vertical]:w-px'
            : 'data-[orientation=horizontal]:h-0.75 data-[orientation=vertical]:w-0.75';

    if ($style === 'accent') {
        $fill_class = 'bg-henge-green';
    } elseif ($style === 'gradient') {
        $fill_class =
            'data-[orientation=horizontal]:bg-gradient-to-r ' .
            'data-[orientation=vertical]:bg-gradient-to-b ' .
            'from-henge-blue via-henge-green to-henge-grey';
    } else {
        $fill_class = $weight_config['fill'];
    }

    $variant_classes = trim(
        $thickness_classes .
            ' data-[orientation=horizontal]:w-full data-[orientation=vertical]:h-auto ' .
            'data-[orientation=vertical]:self-stretch ' .
            $fill_class .
            ($weight_config['rounded'] ? ' rounded-full' : ''),
    );
}

$base_classes = "shrink-0 {$variant_classes}";

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'separator';
$element_attributes['data-orientation'] = $orientation;
$element_attributes['data-weight'] = $weight;
$element_attributes['data-style'] = $style;

if ($decorative) {
    $element_attributes['role'] = 'none';
} else {
    $element_attributes['role'] = 'separator';

    if ($orientation === 'vertical') {
        $element_attributes['aria-orientation'] = 'vertical';
    }
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<div%s></div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
