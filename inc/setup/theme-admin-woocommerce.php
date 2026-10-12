<?php

declare(strict_types=1);

function hengegroup_theme_get_woocommerce_submenu_pages_to_remove(): array
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
        // Rezensionen/Marken bleiben aktiv, nur die Verwaltungsseiten verschwinden.
        [
            'parent' => 'edit.php?post_type=product',
            'slug' => 'product-reviews',
        ],
        // `&amp;`: Core legt Taxonomie-Untermenues HTML-kodiert an, remove_submenu_page() vergleicht exakt.
        [
            'parent' => 'edit.php?post_type=product',
            'slug' => 'edit-tags.php?taxonomy=product_brand&amp;post_type=product',
        ],
    ];
}

/**
 * Ungenutzte WC-Hauptmenues (Seiten bleiben per URL erreichbar). Slugs muessen exakt passen.
 */
function hengegroup_theme_get_woocommerce_menu_pages_to_remove(): array
{
    return [
        'woocommerce-marketing',
        'woocommerce',
        'admin.php?page=wc-settings&tab=checkout&from=PAYMENTS_MENU_ITEM',
    ];
}

function hengegroup_theme_filter_product_type_selector_remove_unused_types(array $types): array
{
    unset($types['grouped'], $types['external']);

    return $types;
}

/**
 * Virtuell/Herunterladbar ausblenden: WC bietet keinen Filter, daher CSS im Backend.
 */
function hengegroup_theme_action_admin_head_hide_woocommerce_virtual_downloadable(): void
{
    $screen = get_current_screen();

    if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'product') {
        return;
    }

    echo '<style>label[for="_virtual"],label[for="_downloadable"]{display:none !important;}</style>';
}

function hengegroup_theme_is_admin_product_edit_screen(): bool
{
    $screen = get_current_screen();

    return $screen instanceof WP_Screen &&
        $screen->base === 'post' &&
        $screen->post_type === 'product';
}

/**
 * Produktbeschreibungen nur im Visual-Editor, ohne "Medien hinzufuegen".
 */
function hengegroup_theme_filter_wp_editor_settings_product_description_visual_only(
    array $settings,
    string $editor_id,
): array {
    if (
        !in_array($editor_id, ['content', 'excerpt'], true) ||
        !hengegroup_theme_is_admin_product_edit_screen()
    ) {
        return $settings;
    }

    $settings['quicktags'] = false;
    $settings['media_buttons'] = false;

    return $settings;
}

function hengegroup_theme_filter_mce_buttons_product_description_remove_buttons(
    array $buttons,
    string $editor_id,
): array {
    if (
        !in_array($editor_id, ['content', 'excerpt'], true) ||
        !hengegroup_theme_is_admin_product_edit_screen()
    ) {
        return $buttons;
    }

    return array_values(array_diff($buttons, ['formatselect', 'wp_more', 'blockquote', 'wp_adv']));
}

/**
 * Die Kurzbeschreibung nutzt den teeny-Editor mit eigenem Filter.
 */
function hengegroup_theme_filter_teeny_mce_buttons_product_short_description_remove_buttons(
    array $buttons,
    string $editor_id,
): array {
    if ($editor_id !== 'excerpt' || !hengegroup_theme_is_admin_product_edit_screen()) {
        return $buttons;
    }

    return array_values(array_diff($buttons, ['blockquote', 'fullscreen']));
}

/**
 * Absicherung: WooCommerce ueberschreibt die teeny-Toolbar per eigenem tinymce-Array; hier wirkt
 * der Filter garantiert zuletzt.
 */
function hengegroup_theme_filter_tiny_mce_before_init_product_short_description_remove_buttons(
    array $mce_init,
    string $editor_id,
): array {
    if ($editor_id !== 'excerpt' || !hengegroup_theme_is_admin_product_edit_screen()) {
        return $mce_init;
    }

    foreach (['toolbar1', 'toolbar2', 'toolbar3', 'toolbar4'] as $toolbar_key) {
        if (empty($mce_init[$toolbar_key]) || !is_string($mce_init[$toolbar_key])) {
            continue;
        }

        $buttons = array_diff(explode(',', $mce_init[$toolbar_key]), ['blockquote', 'fullscreen']);
        $mce_init[$toolbar_key] = implode(',', $buttons);
    }

    return $mce_init;
}

function hengegroup_theme_action_admin_menu_cleanup_woocommerce(): void
{
    foreach (hengegroup_theme_get_woocommerce_submenu_pages_to_remove() as $submenu_page) {
        $parent = $submenu_page['parent'] ?? '';
        $slug = $submenu_page['slug'] ?? '';

        if (!is_string($parent) || !is_string($slug) || $parent === '' || $slug === '') {
            continue;
        }

        remove_submenu_page($parent, $slug);
    }

    foreach (hengegroup_theme_get_woocommerce_menu_pages_to_remove() as $menu_slug) {
        remove_menu_page($menu_slug);
    }
}

function hengegroup_theme_action_admin_bar_menu_cleanup_woocommerce(): void
{
    global $wp_admin_bar;
    $wp_admin_bar->remove_node('woocommerce-site-visibility-badge');
}

/**
 * Die Marken-Box im Produkt-Editor entsteht unabhaengig vom Menue (show_ui der Taxonomie).
 */
function hengegroup_theme_action_add_meta_boxes_remove_product_brand_metabox(): void
{
    remove_meta_box('product_branddiv', 'product', 'side');
}

function hengegroup_theme_register_admin_woocommerce_hooks(): void
{
    add_filter(
        'wp_editor_settings',
        'hengegroup_theme_filter_wp_editor_settings_product_description_visual_only',
        10,
        2,
    );
    add_filter(
        'mce_buttons',
        'hengegroup_theme_filter_mce_buttons_product_description_remove_buttons',
        10,
        2,
    );
    add_filter(
        'teeny_mce_buttons',
        'hengegroup_theme_filter_teeny_mce_buttons_product_short_description_remove_buttons',
        10,
        2,
    );
    add_filter(
        'tiny_mce_before_init',
        'hengegroup_theme_filter_tiny_mce_before_init_product_short_description_remove_buttons',
        10,
        2,
    );
    add_action('admin_menu', 'hengegroup_theme_action_admin_menu_cleanup_woocommerce', 999);
    add_action('admin_bar_menu', 'hengegroup_theme_action_admin_bar_menu_cleanup_woocommerce', 999);
    add_filter(
        'product_type_selector',
        'hengegroup_theme_filter_product_type_selector_remove_unused_types',
    );
    add_action(
        'admin_head',
        'hengegroup_theme_action_admin_head_hide_woocommerce_virtual_downloadable',
    );
    add_action(
        'add_meta_boxes_product',
        'hengegroup_theme_action_add_meta_boxes_remove_product_brand_metabox',
        999,
    );
}

hengegroup_theme_register_admin_woocommerce_hooks();
