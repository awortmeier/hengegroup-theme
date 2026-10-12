<?php

declare(strict_types=1);

// Accordion on native <details>/<summary>; `single` shares a `name` so the browser keeps one item
// open. Style off [open] (group-open:), there is no JS state. Height animation is CSS in app.css
// (::details-content).
//
// Config:
//   type          string        single (default) | multiple
//   items         array         required, each { trigger (required), content (HTML, required),
//                               value (id), default_open, class }
//   heading_tag   string        '' (default) | h2-h6, wraps the trigger label
//   icon          array|false   chevron icon.php config; false shows the native marker
//   color         string        default | light (on dark backgrounds)
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$type = trim((string) ($config['type'] ?? 'single'));
$heading_tag = strtolower(trim((string) ($config['heading_tag'] ?? '')));
$color = trim((string) ($config['color'] ?? 'default'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];
$items_config = is_array($config['items'] ?? null) ? $config['items'] : [];

$allowed_types = ['single', 'multiple'];

if (!in_array($type, $allowed_types, true)) {
    $type = 'single';
}

$allowed_heading_tags = ['', 'h2', 'h3', 'h4', 'h5', 'h6'];

if (!in_array($heading_tag, $allowed_heading_tags, true)) {
    $heading_tag = '';
}

$allowed_colors = ['default', 'light'];

if (!in_array($color, $allowed_colors, true)) {
    $color = 'default';
}

if (array_key_exists('icon', $config) && $config['icon'] === false) {
    $icon_config = null;
} elseif (is_array($config['icon'] ?? null)) {
    $icon_config = $config['icon'];
} else {
    $icon_config = ['name' => 'chevron-down', 'set' => 'lucide'];
}

$items = [];

foreach ($items_config as $item_config) {
    if (!is_array($item_config)) {
        continue;
    }

    $trigger_text = trim((string) ($item_config['trigger'] ?? ''));
    $content = (string) ($item_config['content'] ?? '');

    if ($trigger_text === '' || trim($content) === '') {
        continue;
    }

    $items[] = [
        'trigger' => $trigger_text,
        'content' => $content,
        'value' => trim((string) ($item_config['value'] ?? '')),
        'default_open' => !empty($item_config['default_open']),
        'class' => trim((string) ($item_config['class'] ?? '')),
    ];
}

if ($items === []) {
    return;
}

$group_name = 'hengegroup-theme-accordion-' . wp_unique_id();

$color_classes = [
    'default' => [
        'divider' => 'border-border',
        'trigger_text' => 'text-foreground',
        'content_text' => 'text-muted-foreground',
        'icon_text' => 'text-muted-foreground',
    ],
    'light' => [
        'divider' => 'border-grey-light/20',
        'trigger_text' => 'text-grey-light',
        'content_text' => 'text-grey-light/80',
        'icon_text' => 'text-grey-light/60',
    ],
];

$divider_class = $color_classes[$color]['divider'];

$render_heading = static function (string $tag, string $text): string {
    ob_start();
    get_template_part('template-parts/base/typography', null, [
        // Same look as the plain trigger (text-lg font-semibold).
        'config' => [
            'variant' => 'body-base',
            'tag' => $tag,
            'text' => $text,
            'class' => 'font-semibold',
        ],
    ]);

    return (string) ob_get_clean();
};

if ($icon_config !== null) {
    $icon_class = trim(
        'pointer-events-none size-4 shrink-0 transition-transform duration-200 group-open:rotate-180 ' .
            $color_classes[$color]['icon_text'] .
            (isset($icon_config['class']) ? ' ' . trim((string) $icon_config['class']) : ''),
    );
    $icon_config['class'] = $icon_class;
    $icon_markup = hengegroup_theme_render_icon($icon_config);
} else {
    $icon_markup = '';
}

// Hide the native marker only when an icon replaces it.
$trigger_base_class =
    'flex flex-1 cursor-pointer items-center justify-between gap-5 rounded-md py-5 text-left ' .
    'text-lg font-semibold outline-none transition-colors hover:text-henge-green ' .
    'focus-visible:ring-[3px] focus-visible:ring-ring/50 ' .
    $color_classes[$color]['trigger_text'];

if ($icon_config !== null) {
    $trigger_base_class .= ' list-none [&::-webkit-details-marker]:hidden';
}

$content_class = trim(
    'overflow-hidden pb-5 text-base leading-relaxed ' .
        $color_classes[$color]['content_text'] .
        ($icon_config !== null ? ' pr-11' : ''),
);

$items_markup = '';

foreach ($items as $item) {
    $item_value =
        $item['value'] !== ''
            ? $item['value']
            : 'hengegroup-theme-accordion-item-' . wp_unique_id();

    $trigger_markup =
        $heading_tag !== ''
            ? $render_heading($heading_tag, $item['trigger'])
            : esc_html($item['trigger']);

    $details_attributes = [];

    if ($type === 'single') {
        $details_attributes['name'] = $group_name;
    }

    $details_attributes['data-slot'] = 'accordion-item';
    $details_attributes['id'] = $item_value;
    $details_attributes['class'] = trim(
        'group border-b ' . $divider_class . ($item['class'] !== '' ? ' ' . $item['class'] : ''),
    );

    if ($item['default_open']) {
        $details_attributes['open'] = true;
    }

    $items_markup .= sprintf(
        '<details%1$s><summary%2$s>%3$s%4$s</summary><div%5$s>%6$s</div></details>',
        hengegroup_theme_render_attributes($details_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        hengegroup_theme_render_attributes([
            'data-slot' => 'accordion-trigger',
            'class' => $trigger_base_class,
        ]), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $icon_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        hengegroup_theme_render_attributes([
            'data-slot' => 'accordion-content',
            'class' => $content_class,
        ]), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $item['content'], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$wrapper_attributes = $attributes;
$wrapper_attributes['class'] = trim(
    'border-t ' . $divider_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$wrapper_attributes['data-slot'] = 'accordion';
$wrapper_attributes['data-type'] = $type;
$wrapper_attributes['data-color'] = $color;

$wrapper_attributes = hengegroup_theme_merge_data_attributes($wrapper_attributes, $data_attributes);

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $items_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
