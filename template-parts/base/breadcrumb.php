<?php

declare(strict_types=1);

// shadcn/ui's Breadcrumb has no headless-library primitive behind it at all (Radix UI/Base
// UI/React Aria alike) -- it's plain semantic HTML
// (<nav aria-label="breadcrumb"><ol><li>...) with no interactive/JS state, ever. The most
// straightforward "native HTML wins" case in this theme (see CLAUDE.md #1): a direct translation
// of shadcn's DOM structure (nav > ol > li[+separator li] > a|span), same data-slot names.
//
// Nests template-parts/base/icon.php for the separator and any per-item icons.
//
// Supported config:
//   items       array   required, ordered list of item configs. The LAST item is always rendered
//                        as the non-interactive "current page" (shadcn's BreadcrumbPage: <span
//                        role="link" aria-disabled="true" aria-current="page">), regardless of
//                        whether `href` is given for it -- matches the WAI-ARIA breadcrumb
//                        pattern (the current page is never a link to itself). All other items:
//     text          string   visible label (ignored when `ellipsis` is true)
//     href          string   optional; renders <a data-slot="breadcrumb-link">. Without href,
//                             renders as plain (non-link) text -- still not the "current page"
//                             unless it's the last item
//     icon          array    optional icon.php config, rendered before the text
//     ellipsis      bool     when true, renders shadcn's BreadcrumbEllipsis ("..." placeholder for
//                             collapsed items) instead of a normal link/page; other keys ignored.
//                             `icon` overrides the default Lucide `ellipsis` glyph.
//     class         string   passthrough onto this item's <li data-slot="breadcrumb-item">
//   separator   array|false  icon.php config for the separator between items (default: Lucide
//                        chevron-right); false renders an empty separator <li> (e.g. for a
//                        CSS-driven text separator instead)
//   aria_label  string   accessible name for the outer <nav> (default: localized 'breadcrumb',
//                        matching shadcn's own hardcoded default) -- configurable/localizable like
//                        every other landmark-ish component in this group (carousel.php's own
//                        `aria_label`, progress.php's, spinner.php's), unlike the previous version
//                        of this file which had it hardcoded with no override
//   class / attributes / data_attributes   passthrough onto the outer
//                        <nav data-slot="breadcrumb">

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$items_config = is_array($config['items'] ?? null) ? $config['items'] : [];
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (array_key_exists('separator', $config) && $config['separator'] === false) {
    $separator_icon_config = null;
} elseif (is_array($config['separator'] ?? null)) {
    $separator_icon_config = $config['separator'];
} else {
    $separator_icon_config = ['name' => 'chevron-right', 'set' => 'lucide'];
}

// Normalize + filter items; a non-ellipsis item needs a non-empty `text`, invalid items are
// silently skipped (fail-soft, consistent with the rest of the base components).
$items = [];

foreach ($items_config as $item_config) {
    if (!is_array($item_config)) {
        continue;
    }

    $is_ellipsis = !empty($item_config['ellipsis']);
    $text = trim((string) ($item_config['text'] ?? ''));

    if (!$is_ellipsis && $text === '') {
        continue;
    }

    $items[] = [
        'ellipsis' => $is_ellipsis,
        'text' => $text,
        'href' => trim((string) ($item_config['href'] ?? '')),
        'icon' => is_array($item_config['icon'] ?? null) ? $item_config['icon'] : null,
        'class' => trim((string) ($item_config['class'] ?? '')),
    ];
}

if ($items === []) {
    return;
}

if ($aria_label === '') {
    $aria_label = __('breadcrumb', 'hengegroup-theme');
}

$last_index = count($items) - 1;
$separator_markup =
    $separator_icon_config !== null ? hengegroup_theme_render_icon($separator_icon_config) : '';

$list_items_markup = '';

foreach ($items as $index => $item) {
    if ($index > 0) {
        $list_items_markup .= sprintf(
            '<li data-slot="breadcrumb-separator" role="presentation" aria-hidden="true">%s</li>',
            $separator_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    }

    $item_attributes = ['data-slot' => 'breadcrumb-item'];

    if ($item['class'] !== '') {
        $item_attributes['class'] = $item['class'];
    }

    if ($item['ellipsis']) {
        $ellipsis_icon_config = $item['icon'] ?? ['name' => 'ellipsis', 'set' => 'lucide'];

        $inner_markup = sprintf(
            '<span data-slot="breadcrumb-ellipsis" role="presentation" aria-hidden="true">%s</span>',
            hengegroup_theme_render_icon($ellipsis_icon_config), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    } else {
        $icon_markup = $item['icon'] !== null ? hengegroup_theme_render_icon($item['icon']) : '';
        $label_markup = $icon_markup . esc_html($item['text']);

        if ($index === $last_index) {
            $inner_markup = sprintf(
                '<span data-slot="breadcrumb-page" role="link" aria-disabled="true" aria-current="page">%s</span>',
                $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            );
        } elseif ($item['href'] !== '') {
            $link_attributes = ['data-slot' => 'breadcrumb-link', 'href' => $item['href']];

            $inner_markup = sprintf(
                '<a%1$s>%2$s</a>',
                hengegroup_theme_render_attributes($link_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            );
        } else {
            $inner_markup = $label_markup;
        }
    }

    $list_items_markup .= sprintf(
        '<li%1$s>%2$s</li>',
        hengegroup_theme_render_attributes($item_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $inner_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$wrapper_attributes = $attributes;

if ($class_name !== '') {
    $wrapper_attributes['class'] = $class_name;
}

$wrapper_attributes['data-slot'] = 'breadcrumb';
$wrapper_attributes['aria-label'] = $aria_label;

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $wrapper_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<nav%1$s><ol data-slot="breadcrumb-list">%2$s</ol></nav>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $list_items_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
