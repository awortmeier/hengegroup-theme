<?php

declare(strict_types=1);

// "Previous" button (button.php, outline icon). Needs carousel.js to work; swiping, wheel and arrow
// keys work without JS.
//
// Config:
//   aria_label   string   default "Previous slide"
//   icon         array    icon.php config (default arrow-left)
//   disabled     bool     initial state; carousel.js corrects it after mount
//   class / attributes / data_attributes   on the nested button.php

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$aria_label = trim((string) ($config['aria_label'] ?? ''));
$icon_config = is_array($config['icon'] ?? null)
    ? $config['icon']
    : ['name' => 'arrow-left', 'set' => 'lucide'];
$disabled = !empty($config['disabled']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($aria_label === '') {
    $aria_label = esc_html__('Previous slide', 'hengegroup-theme');
}

$data_attributes['carousel-nav'] = 'previous';

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
