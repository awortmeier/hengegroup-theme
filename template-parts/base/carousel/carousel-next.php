<?php

declare(strict_types=1);

// "Next" button, mirror of carousel-previous.php.
//
// Config:
//   aria_label   string   default "Next slide"
//   icon         array    icon.php config (default arrow-right)
//   disabled     bool
//   class / attributes / data_attributes   on the nested button.php

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
