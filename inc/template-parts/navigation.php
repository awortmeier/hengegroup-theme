<?php

declare(strict_types=1);

// Site-specific navigation composition (not a template-parts/base/* component) -- maps this
// theme's own registered "primary" wp_nav_menu() location onto navigation-menu.php's `items`
// config (see that file's own header comment for the config shape and its panel-content recipe).
// Lives here rather than inc/template-parts/helpers.php, which is scoped to shared render helpers
// FOR base components (see that file's own header comment) -- this is page/template-level
// composition of a base component, not a helper a base component itself calls.
//
// Why wp_nav_menu()-driven instead of a hardcoded items array: see docs/entscheidungen.md "Header:
// Navigationsinhalt aus wp_nav_menu statt hartkodiert" -- the header's nav content is real,
// editorially maintained business structure (product/career/company sections), not fixed chrome,
// so it belongs in WordPress's own menu editor like any other site nav, not compiled into a
// template.
//
// Only one level of children becomes a dropdown panel -- a flat, single-column list of plain
// links, the same panel-internal "list item" recipe page-component-showcase-navigation-menu.php
// already documents, minus that showcase's own description line (a WP menu item's own optional
// "Description" field -- set via the block editor's menu Screen Options -- has no guaranteed
// content for real menu items, unlike the showcase's own fixture data). Matches
// navigation-menu.php's own scope (nested DropdownMenuSub is explicitly out of scope there, see
// that file's header comment): a menu item nested deeper than one level (a grandchild) is silently
// skipped, same "can't represent it, don't half-render it" convention as navigation-menu.php's own
// invalid-entry handling.

/**
 * Builds navigation-menu.php's `items` config array from a registered wp_nav_menu() theme
 * location. Top-level items with no children become plain links; items with children become a
 * trigger + single-column dropdown panel (see the file header above for the recipe/scope).
 *
 * "Current page" state comes from `_wp_menu_item_classes_by_context()` -- the same core filter
 * wp_nav_menu() itself runs on `wp_nav_menu_objects` before walking the tree -- rather than a
 * reimplementation of WordPress's own current-item detection here.
 *
 * Returns [] when no menu is assigned to $location, so the caller can skip rendering the nav
 * entirely (matches the theme's old wp_nav_menu(['fallback_cb' => false, ...]) behaviour: no menu,
 * no fallback).
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

    // Caller-supplied `class` for navigation-menu-link.php's panel-internal "list item" look (see
    // the file header above) -- same values page-component-showcase-navigation-menu.php's own
    // $panel_link_class already uses, kept identical for one visual language across every dropdown
    // panel in the project rather than a fresh one-off here.
    $panel_link_class = 'flex flex-col gap-1 rounded-lg p-2.5 hover:bg-henge-green/10';

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

        if ($children === []) {
            $items[] = ['text' => $item_text, 'href' => $item_href, 'active' => $item_active];
            continue;
        }

        ob_start();

        foreach ($children as $child_item) {
            $child_classes = is_array($child_item->classes ?? null) ? $child_item->classes : [];

            get_template_part('template-parts/base/navigation-menu/navigation-menu-link', null, [
                'config' => [
                    'href' => (string) $child_item->url,
                    'class' => $panel_link_class,
                    'active' => in_array('current-menu-item', $child_classes, true),
                    'content' =>
                        '<span class="text-sm font-semibold">' .
                        esc_html((string) $child_item->title) .
                        '</span>',
                ],
            ]);
        }

        $panel_links_markup = (string) ob_get_clean();

        $items[] = [
            'text' => $item_text,
            'content' =>
                '<div class="grid grid-cols-[minmax(14rem,1fr)] gap-0.5">' .
                $panel_links_markup .
                '</div>',
        ];
    }

    return $items;
}
