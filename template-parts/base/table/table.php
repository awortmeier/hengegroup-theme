<?php

declare(strict_types=1);

// <table> in a horizontally scrolling container (scroll-area.php). Content: optional caption first,
// then header, body, footer.
//
// Config:
//   content   string   pre-rendered HTML (required)
//   striped   bool     zebra rows
//   card      bool     default true; card look on the container (false = bare)
//   class / attributes / data_attributes   on the <table>, not the container

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$striped = !empty($config['striped']);
$card = !array_key_exists('card', $config) || !empty($config['card']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

$table_attributes = $attributes;

$table_classes = 'w-full caption-bottom text-sm';

if ($striped) {
    $table_classes .= ' [&_tbody>tr:nth-child(even)]:bg-muted/50';
}

$table_attributes['class'] = trim($table_classes . ($class_name !== '' ? ' ' . $class_name : ''));
$table_attributes['data-slot'] = 'table';

$table_attributes = hengegroup_theme_merge_data_attributes($table_attributes, $data_attributes);

$table_markup = sprintf(
    '<table%1$s>%2$s</table>',
    hengegroup_theme_render_attributes($table_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

$container_classes = 'relative w-full';

if ($card) {
    $container_classes .= ' rounded-xl border border-border bg-card shadow-xs';
}

get_template_part('template-parts/base/scroll-area', null, [
    'config' => [
        'content' => $table_markup,
        'orientation' => 'horizontal',
        'data_slot' => 'table-container',
        'class' => $container_classes,
    ],
]);
