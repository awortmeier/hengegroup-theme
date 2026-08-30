<?php

declare(strict_types=1);

// shadcn/ui's CarouselNext -- the mirror of carousel-previous.php (ArrowRight icon,
// `data-carousel-nav="next"`). See that file's header comment for the full rationale (button.php
// nesting, the honest "needs JS" gap, the `data-carousel-nav` matching convention); not repeated
// here to avoid the two files drifting out of sync with duplicated prose.
//
// Supported config:
//   aria_label   string   default: translated "Next slide" (esc_html__())
//   icon         array    icon.php config override (default: ['name' => 'arrow-right', 'set' =>
//                           'lucide'])
//   disabled     bool     default false -- see carousel-previous.php's header comment
//   class / attributes / data_attributes   passthrough onto the nested button.php call

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$aria_label = trim((string) ($config['aria_label'] ?? ''));
$icon_config = is_array($config['icon'] ?? null)
    ? $config['icon']
    : ['name' => 'arrow-right', 'set' => 'lucide'];
$disabled = !empty($config['disabled']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($aria_label === '') {
    $aria_label = esc_html__('Next slide', 'hengegroup-theme');
}

$data_attributes['carousel-nav'] = 'next';

get_template_part('template-parts/base/button', null, [
    'config' => [
        'variant' => 'outline',
        'size' => 'icon-base',
        'icon' => $icon_config,
        'aria_label' => $aria_label,
        'disabled' => $disabled,
        'class' => $class_name,
        'attributes' => $attributes,
        'data_attributes' => $data_attributes,
    ],
]);
