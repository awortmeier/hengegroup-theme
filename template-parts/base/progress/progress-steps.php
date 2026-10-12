<?php

declare(strict_types=1);

// Segmented step indicator (N equal bars). Labels are composed by the caller.
//
// Config:
//   steps        int      total segments, default 1
//   current      int      completed segments, default 0
//   color        string   default | light (on dark backgrounds)
//   aria_label   string
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$steps = max(1, (int) ($config['steps'] ?? 1));
$current = max(0, min($steps, (int) ($config['current'] ?? 0)));
$color = trim((string) ($config['color'] ?? 'default'));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_colors = ['default', 'light'];

if (!in_array($color, $allowed_colors, true)) {
    $color = 'default';
}

$segment_classes = [
    'default' => [
        'complete' => 'bg-henge-green',
        'upcoming' => 'bg-border',
    ],
    'light' => [
        'complete' => 'bg-[color-mix(in_oklab,var(--color-henge-green)_65%,white)]',
        'upcoming' => 'bg-white/20',
    ],
];

$element_attributes = $attributes;
$element_attributes['class'] = trim('flex gap-2' . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['role'] = 'progressbar';
$element_attributes['data-slot'] = 'progress-steps';
$element_attributes['data-color'] = $color;
$element_attributes['aria-valuemin'] = '0';
$element_attributes['aria-valuemax'] = (string) $steps;
$element_attributes['aria-valuenow'] = (string) $current;

if ($aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

$segments_markup = '';

for ($i = 0; $i < $steps; $i++) {
    $segment_state = $i < $current ? 'complete' : 'upcoming';

    $segments_markup .= sprintf(
        '<div class="h-1.5 flex-1 rounded-full transition-colors duration-300 %1$s" data-slot="progress-steps-segment" data-state="%2$s"></div>',
        esc_attr($segment_classes[$color][$segment_state]),
        esc_attr($segment_state),
    );
}

printf(
    '<div%s>%s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $segments_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
