<?php

declare(strict_types=1);

// Horizontally scrolling row of attachment.php cards (scroll-area.php). The edge fade is CSS in
// app.css (no Tailwind utility for it).
//
// Config:
//   content   string   attachment.php markup (required)
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
    'flex min-w-0 snap-x snap-mandatory gap-3 overscroll-x-contain scroll-px-1 ' .
    'px-1 py-1 [&>[data-slot=attachment]]:shrink-0 [&>[data-slot=attachment]]:snap-start';

get_template_part('template-parts/base/scroll-area', null, [
    'config' => [
        'content' => $content,
        'orientation' => 'horizontal',
        'scrollbar' => 'none',
        'data_slot' => 'attachment-group',
        'class' => trim($base_classes . ($class_name !== '' ? ' ' . $class_name : '')),
        'attributes' => $attributes,
        'data_attributes' => $data_attributes,
    ],
]);
