<?php

declare(strict_types=1);

function base_theme_get_woocommerce_submenu_pages_to_remove(): array
{
    return [
        [
            'parent' => 'woocommerce',
            'slug' => 'coupons-moved',
        ],
        [
            'parent' => 'woocommerce',
            'slug' => 'wc-reports',
        ],
        [
            'parent' => 'wc-admin&path=/analytics/overview',
            'slug' => 'wc-admin&path=/analytics/downloads',
        ],
        [
            'parent' => 'woocommerce-marketing',
            'slug' => 'admin.php?page=wc-admin&path=/marketing',
        ],
    ];
}

function base_theme_action_admin_menu_cleanup_woocommerce(): void
{
    foreach (base_theme_get_woocommerce_submenu_pages_to_remove() as $submenu_page) {
        $parent = $submenu_page['parent'] ?? '';
        $slug = $submenu_page['slug'] ?? '';

        if (!is_string($parent) || !is_string($slug) || $parent === '' || $slug === '') {
            continue;
        }

        remove_submenu_page($parent, $slug);
    }
}

function base_theme_action_admin_bar_menu_cleanup_woocommerce(): void
{
    global $wp_admin_bar;
    $wp_admin_bar->remove_node('woocommerce-site-visibility-badge');
}

function base_theme_register_admin_woocommerce_hooks(): void
{
    add_action('admin_menu', 'base_theme_action_admin_menu_cleanup_woocommerce', 999);
    add_action('admin_bar_menu', 'base_theme_action_admin_bar_menu_cleanup_woocommerce', 999);
}

base_theme_register_admin_woocommerce_hooks();
