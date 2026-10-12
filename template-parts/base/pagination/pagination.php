<?php

declare(strict_types=1);

// Pagination from an `items` array; every interactive item is a button.php (as <a> with href).
//
// Config:
//   items        array    required, each with `type`:
//     page (default)   text (required), href, is_active (aria-current, henge-green), disabled, size
//     previous         href, text, aria_label, icon, disabled, size
//     next             like previous
//     ellipsis         icon
//     class            on the item's <li>
//   aria_label   string   default "pagination"
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$items_config = is_array($config['items'] ?? null) ? $config['items'] : [];
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($items_config === []) {
    return;
}

if ($aria_label === '') {
    $aria_label = __('pagination', 'hengegroup-theme');
}

$allowed_types = ['page', 'previous', 'next', 'ellipsis'];

$render_button = static function (array $button_config): string {
    ob_start();
    get_template_part('template-parts/base/button', null, ['config' => $button_config]);

    return (string) ob_get_clean();
};

$list_items_markup = '';

foreach ($items_config as $item_config) {
    if (!is_array($item_config)) {
        continue;
    }

    $type = trim((string) ($item_config['type'] ?? 'page'));

    if (!in_array($type, $allowed_types, true)) {
        $type = 'page';
    }

    $item_class = trim((string) ($item_config['class'] ?? ''));
    $item_attributes = ['data-slot' => 'pagination-item'];

    if ($item_class !== '') {
        $item_attributes['class'] = $item_class;
    }

    if ($type === 'ellipsis') {
        $icon_config = is_array($item_config['icon'] ?? null)
            ? $item_config['icon']
            : ['name' => 'ellipsis', 'set' => 'lucide'];

        $icon_class = trim((string) ($icon_config['class'] ?? ''));

        if (!str_contains($icon_class, 'size-')) {
            $icon_config['class'] = trim($icon_class . ' size-4');
        }

        $inner_markup = sprintf(
            '<span data-slot="pagination-ellipsis" class="flex size-8 items-center justify-center text-muted-foreground" aria-hidden="true">%1$s<span class="sr-only">%2$s</span></span>',
            hengegroup_theme_render_icon($icon_config), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            esc_html__('More pages', 'hengegroup-theme'),
        );
    } elseif ($type === 'previous' || $type === 'next') {
        $text = trim((string) ($item_config['text'] ?? ''));
        $icon_config = is_array($item_config['icon'] ?? null) ? $item_config['icon'] : null;
        $link_aria_label = trim((string) ($item_config['aria_label'] ?? ''));
        $size = trim((string) ($item_config['size'] ?? ''));

        if ($type === 'previous') {
            $icon_position = 'start';
            $default_text = __('Previous', 'hengegroup-theme');
            $default_icon = ['name' => 'chevron-left', 'set' => 'lucide'];
            $default_aria_label = __('Go to previous page', 'hengegroup-theme');
        } else {
            $icon_position = 'end';
            $default_text = __('Next', 'hengegroup-theme');
            $default_icon = ['name' => 'chevron-right', 'set' => 'lucide'];
            $default_aria_label = __('Go to next page', 'hengegroup-theme');
        }

        $inner_markup = $render_button([
            'href' => trim((string) ($item_config['href'] ?? '')),
            'variant' => 'ghost',
            'size' => $size !== '' ? $size : 'base',
            'text' => $text !== '' ? $text : $default_text,
            'icon' => $icon_config ?? $default_icon,
            'icon_position' => $icon_position,
            'disabled' => !empty($item_config['disabled']),
            'attributes' => [
                'aria-label' => $link_aria_label !== '' ? $link_aria_label : $default_aria_label,
            ],
        ]);
    } else {
        $text = trim((string) ($item_config['text'] ?? ''));

        if ($text === '') {
            continue;
        }

        $is_active = !empty($item_config['is_active']);
        $size = trim((string) ($item_config['size'] ?? ''));
        $link_attributes = [];

        if ($is_active) {
            $link_attributes['aria-current'] = 'page';
            $link_attributes['data-active'] = 'true';
        }

        $inner_markup = $render_button([
            'href' => trim((string) ($item_config['href'] ?? '')),
            'variant' => $is_active ? 'henge-green' : 'ghost',
            'size' => $size !== '' ? $size : 'icon-base',
            'text' => $text,
            'disabled' => !empty($item_config['disabled']),
            'attributes' => $link_attributes,
        ]);
    }

    $list_items_markup .= sprintf(
        '<li%1$s>%2$s</li>',
        hengegroup_theme_render_attributes($item_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $inner_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

if ($list_items_markup === '') {
    return;
}

$wrapper_attributes = $attributes;
$wrapper_attributes['class'] = trim(
    'mx-auto flex w-full justify-center' . ($class_name !== '' ? ' ' . $class_name : ''),
);

$wrapper_attributes['data-slot'] = 'pagination';
$wrapper_attributes['aria-label'] = $aria_label;

$wrapper_attributes = hengegroup_theme_merge_data_attributes($wrapper_attributes, $data_attributes);

printf(
    '<nav%1$s><ul data-slot="pagination-content" class="flex flex-row items-center gap-1.5">%2$s</ul></nav>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $list_items_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
