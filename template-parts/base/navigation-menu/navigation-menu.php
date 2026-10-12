<?php

declare(strict_types=1);

// Top navigation with mega-menu panels. Each trigger is a <details> sharing one `name`, so only
// one panel is open at a time without JS. navigation-menu.js adds hover intent, outside
// click/Escape and Left/Right/Home/End navigation. Panels sit directly under their trigger.
//
// Config:
//   items         array    required, each { text/label (required), href (link item) or content
//                          (panel HTML, wins over href), active, id }
//   orientation   string   horizontal (default) | vertical
//   color         string   default | light (on dark backgrounds)
//   delay         int      hover-open delay in ms (default 50)
//   close_delay   int      close delay in ms (default 50)
//   aria_label    string   recommended when there are several <nav>s
//   id            string   default wp_unique_id(); also seeds the shared <details> name
//   class / attributes / data_attributes
//
// Panel links: navigation-menu-link.php with e.g.
// `class: 'flex flex-col gap-1 rounded-lg p-2.5 hover:bg-henge-green/10'`. Multi-column panels
// need their own minmax() track floor.

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$items_config = is_array($config['items'] ?? null) ? $config['items'] : [];
$orientation = trim((string) ($config['orientation'] ?? 'horizontal'));
$color = trim((string) ($config['color'] ?? 'default'));
$delay = trim((string) ($config['delay'] ?? '50'));
$close_delay = trim((string) ($config['close_delay'] ?? '50'));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$id = trim((string) ($config['id'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_orientations = ['horizontal', 'vertical'];
$allowed_colors = ['default', 'light'];

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'horizontal';
}

if (!in_array($color, $allowed_colors, true)) {
    $color = 'default';
}

if (!is_numeric($delay)) {
    $delay = '50';
}

if (!is_numeric($close_delay)) {
    $close_delay = '50';
}

if ($id === '') {
    $id = 'hengegroup-theme-navigation-menu-' . wp_unique_id();
}

$group_name = $id . '-group';

$color_classes = [
    'default' => [
        'idle' => 'text-foreground/85',
        'hover' =>
            'hover:bg-henge-green/10 hover:text-henge-green group-open:bg-henge-green/10 group-open:text-henge-green',
        'panel_border' => 'border-border',
        'panel_bg' => 'bg-popover',
        'panel_text' => 'text-popover-foreground',
        'panel_shadow' => 'shadow-[0_12px_32px_rgba(0,0,0,0.14)]',
    ],
    'light' => [
        'idle' => 'text-grey-light/85',
        'hover' =>
            'hover:bg-grey-light/10 hover:text-henge-green group-open:bg-grey-light/10 group-open:text-henge-green',
        'panel_border' => 'border-grey-light/15',
        'panel_bg' => 'bg-neutral-800',
        'panel_text' => 'text-grey-light',
        'panel_shadow' => 'shadow-[0_20px_48px_rgba(0,0,0,0.45)]',
    ],
];

$trigger_button_base =
    'inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2.5 text-sm font-medium outline-none ' .
    'transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50 ' .
    $color_classes[$color]['hover'];

$icon_class =
    'pointer-events-none size-3.5 shrink-0 transition-transform duration-200 group-open:-scale-y-100';

$content_classes = trim(
    'absolute left-0 top-full z-50 mt-2.5 min-w-72 overflow-hidden rounded-2xl border p-3.5 ' .
        $color_classes[$color]['panel_border'] .
        ' ' .
        $color_classes[$color]['panel_bg'] .
        ' ' .
        $color_classes[$color]['panel_text'] .
        ' ' .
        $color_classes[$color]['panel_shadow'] .
        ' animate-[hg-popover-in_140ms_ease-out]',
);

$items_markup = '';

foreach ($items_config as $item) {
    if (!is_array($item)) {
        continue;
    }

    $item_text = trim((string) ($item['text'] ?? ($item['label'] ?? '')));
    $item_href = trim((string) ($item['href'] ?? ''));
    $item_content = (string) ($item['content'] ?? '');
    $item_active = !empty($item['active']);
    $item_id = trim((string) ($item['id'] ?? ''));

    if ($item_text === '') {
        continue;
    }

    if (trim($item_content) !== '') {
        if ($item_id === '') {
            $item_id = $id . '-item-' . wp_unique_id();
        }

        $trigger_class =
            trim($color_classes[$color]['idle'] . ' ' . $trigger_button_base) .
            ' list-none cursor-pointer [&::-webkit-details-marker]:hidden';

        $icon_markup = hengegroup_theme_render_icon([
            'name' => 'chevron-down',
            'set' => 'lucide',
            'class' => $icon_class,
        ]);

        $trigger_markup = sprintf(
            '<summary class="%1$s" data-slot="navigation-menu-trigger" aria-haspopup="true">%2$s%3$s</summary>',
            esc_attr($trigger_class),
            esc_html($item_text),
            $icon_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );

        $content_markup = sprintf(
            '<div class="%1$s" data-slot="navigation-menu-content">%2$s</div>',
            esc_attr($content_classes),
            $item_content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );

        $items_markup .= sprintf(
            '<li data-slot="navigation-menu-item">' .
                '<details class="group relative" data-slot="navigation-menu-trigger-item" name="%1$s" id="%2$s">%3$s%4$s</details>' .
                '</li>',
            esc_attr($group_name),
            esc_attr($item_id),
            $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );

        continue;
    }

    if ($item_href === '') {
        continue;
    }

    // Active links get their color from navigation-menu-link.php; avoid two text colors.
    $link_idle_class = $item_active ? '' : $color_classes[$color]['idle'];
    $link_class = trim($link_idle_class . ' ' . $trigger_button_base);

    ob_start();
    get_template_part('template-parts/base/navigation-menu/navigation-menu-link', null, [
        'config' => [
            'text' => $item_text,
            'href' => $item_href,
            'active' => $item_active,
            'class' => $link_class,
        ],
    ]);
    $link_markup = (string) ob_get_clean();

    if (trim($link_markup) === '') {
        continue;
    }

    $items_markup .= sprintf(
        '<li data-slot="navigation-menu-item">%s</li>',
        $link_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

if (trim($items_markup) === '') {
    return;
}

$list_markup = sprintf(
    '<ul class="flex flex-wrap items-center gap-0.5" data-slot="navigation-menu-list">%s</ul>',
    $items_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'navigation-menu';
$element_attributes['data-orientation'] = $orientation;
$element_attributes['data-color'] = $color;
$element_attributes['data-delay'] = $delay;
$element_attributes['data-close-delay'] = $close_delay;
$element_attributes['id'] = $id;

if ($aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<nav%1$s>%2$s</nav>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $list_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
