<?php

declare(strict_types=1);

// Maps the "primary" wp_nav_menu() location onto navigation-menu.php items. One level of children
// becomes a dropdown panel; deeper levels are skipped. A parent with a real URL stays a link.
// CSS class "font-accent" (menu item field) sets a child, or all children of a parent, in Crillee.

/**
 * [] when no menu is assigned. Current page via _wp_menu_item_classes_by_context(), like core.
 */
function hengegroup_theme_primary_navigation_items(string $location = 'primary'): array
{
    $menu_locations = get_nav_menu_locations();
    $menu_id = (int) ($menu_locations[$location] ?? 0);

    if ($menu_id === 0) {
        return [];
    }

    $menu_items = wp_get_nav_menu_items($menu_id);

    if (!is_array($menu_items) || $menu_items === []) {
        return [];
    }

    $menu_items = apply_filters(
        'wp_nav_menu_objects',
        $menu_items,
        (object) ['theme_location' => $location],
    );

    $children_by_parent = [];

    foreach ($menu_items as $menu_item) {
        $parent_id = (int) $menu_item->menu_item_parent;
        $children_by_parent[$parent_id][] = $menu_item;
    }

    $panel_link_class = 'flex flex-col gap-1 rounded-lg p-2.5';

    $items = [];

    foreach ($children_by_parent[0] ?? [] as $menu_item) {
        $item_text = (string) $menu_item->title;
        $item_href = (string) $menu_item->url;
        $item_classes = is_array($menu_item->classes ?? null) ? $menu_item->classes : [];
        $item_active =
            in_array('current-menu-item', $item_classes, true) ||
            in_array('current-menu-parent', $item_classes, true) ||
            in_array('current_page_parent', $item_classes, true);

        $children = $children_by_parent[(int) $menu_item->ID] ?? [];
        $children_accent = in_array('font-accent', $item_classes, true);

        if ($children === []) {
            $items[] = ['text' => $item_text, 'href' => $item_href, 'active' => $item_active];
            continue;
        }

        ob_start();

        foreach ($children as $child_item) {
            $child_classes = is_array($child_item->classes ?? null) ? $child_item->classes : [];
            $child_accent = $children_accent || in_array('font-accent', $child_classes, true);

            get_template_part('template-parts/base/navigation-menu/navigation-menu-link', null, [
                'config' => [
                    'href' => (string) $child_item->url,
                    'class' => $panel_link_class,
                    'active' => in_array('current-menu-item', $child_classes, true),
                    'content' =>
                        '<span class="text-base font-semibold' .
                        ($child_accent ? ' font-accent' : '') .
                        '">' .
                        esc_html((string) $child_item->title) .
                        '</span>',
                ],
            ]);
        }

        $panel_links_markup = (string) ob_get_clean();

        $items[] = [
            'text' => $item_text,
            // "#" or empty: pure dropdown without a target page.
            'href' => ltrim($item_href, '#') !== '' ? $item_href : '',
            'active' => $item_active,
            'content' =>
                '<div class="grid grid-cols-[minmax(14rem,1fr)] gap-0.5">' .
                $panel_links_markup .
                '</div>',
        ];
    }

    return $items;
}
