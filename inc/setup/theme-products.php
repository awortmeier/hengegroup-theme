<?php

declare(strict_types=1);

// Produktbereich: Taxonomie "Anwendungen", URLs und Weiterleitungen. /produkte/ und /anwendungen/
// sind Seiten aus Bloecken; Produkte liegen unter /produkte/<slug>/. Alle Produkt-Archive leiten
// per 301 auf die Uebersicht.

/**
 * Hierarchisch (Gruppe > Anwendung), nicht oeffentlich.
 */
function hengegroup_theme_register_anwendung_taxonomy(): void
{
    register_taxonomy(HENGEGROUP_THEME_ANWENDUNG_TAXONOMY, 'product', [
        'labels' => [
            'name' => __('Anwendungen', 'hengegroup-theme'),
            'singular_name' => __('Anwendung', 'hengegroup-theme'),
            'menu_name' => __('Anwendungen', 'hengegroup-theme'),
            'all_items' => __('Alle Anwendungen', 'hengegroup-theme'),
            'add_new_item' => __('Neue Anwendung oder Gruppe hinzufügen', 'hengegroup-theme'),
            'edit_item' => __('Anwendung bearbeiten', 'hengegroup-theme'),
            'search_items' => __('Anwendungen durchsuchen', 'hengegroup-theme'),
            'not_found' => __('Keine Anwendungen gefunden', 'hengegroup-theme'),
            'parent_item' => __('Gruppe', 'hengegroup-theme'),
            'parent_item_colon' => __('Gruppe:', 'hengegroup-theme'),
            'back_to_items' => __('← Zurück zu den Anwendungen', 'hengegroup-theme'),
            'filter_by_item' => __('Nach Anwendung filtern', 'hengegroup-theme'),
        ],
        'hierarchical' => true,
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => false,
        'show_in_rest' => false,
        'show_tagcloud' => false,
        'show_admin_column' => true,
        'show_in_quick_edit' => true,
        'meta_box_cb' => 'hengegroup_theme_render_product_anwendungen_meta_box',
        'rewrite' => false,
        'query_var' => false,
    ]);
}
add_action('init', 'hengegroup_theme_register_anwendung_taxonomy');

function hengegroup_theme_filter_register_post_type_product(array $args): array
{
    $args['has_archive'] = false;
    $args['rewrite'] = [
        'slug' => 'produkte',
        'with_front' => false,
        'feeds' => false,
    ];

    return $args;
}
add_filter(
    'woocommerce_register_post_type_product',
    'hengegroup_theme_filter_register_post_type_product',
);

/**
 * Keine Shop-Seite, sonst kapert WooCommerce /produkte/ mit seinem Archiv.
 */
function hengegroup_theme_filter_shop_page_id(): int
{
    return -1;
}
add_filter('woocommerce_get_shop_page_id', 'hengegroup_theme_filter_shop_page_id');

/**
 * Rewrite-Regeln einmalig neu schreiben; bei URL-Aenderungen die Version erhoehen.
 */
function hengegroup_theme_action_init_flush_product_rewrites(): void
{
    $version = '2026-10-07-2';

    if (get_option('hengegroup_theme_product_rewrite_version') === $version) {
        return;
    }

    flush_rewrite_rules(false);
    update_option('hengegroup_theme_product_rewrite_version', $version, true);
}
add_action('init', 'hengegroup_theme_action_init_flush_product_rewrites', 99);

/**
 * Legt `pa_koernung` an, falls es fehlt (globales Attribut, damit es spaeter Varianten werden kann).
 */
function hengegroup_theme_action_init_ensure_grain_attribute(): void
{
    if (
        !function_exists('wc_create_attribute') ||
        !function_exists('wc_attribute_taxonomy_id_by_name') ||
        get_option('hengegroup_theme_grain_attribute_version') === '1'
    ) {
        return;
    }

    if (wc_attribute_taxonomy_id_by_name(HENGEGROUP_THEME_GRAIN_ATTRIBUTE) === 0) {
        $result = wc_create_attribute([
            'name' => __('Körnung', 'hengegroup-theme'),
            'slug' => HENGEGROUP_THEME_GRAIN_ATTRIBUTE,
            'type' => 'select',
            'order_by' => 'menu_order',
            'has_archives' => false,
        ]);

        if (is_wp_error($result)) {
            return;
        }
    }

    update_option('hengegroup_theme_grain_attribute_version', '1', true);
}
add_action('init', 'hengegroup_theme_action_init_ensure_grain_attribute', 20);

/**
 * Unterseiten von /produkte/ treffen sonst die Produkt-Regel.
 */
function hengegroup_theme_filter_request_product_child_pages(array $query_vars): array
{
    $name = $query_vars['product'] ?? ($query_vars['name'] ?? '');

    if (
        ($query_vars['post_type'] ?? '') !== 'product' ||
        !is_string($name) ||
        $name === '' ||
        get_page_by_path($name, OBJECT, 'product') instanceof WP_Post
    ) {
        return $query_vars;
    }

    $page = get_page_by_path('produkte/' . $name);

    return $page instanceof WP_Post ? ['pagename' => 'produkte/' . $name] : $query_vars;
}
add_filter('request', 'hengegroup_theme_filter_request_product_child_pages');

/**
 * 301 aller Produkt-Taxonomie-Archive (auch kuenftiger) auf die Uebersicht, Kategorien auf ihren Anker.
 */
function hengegroup_theme_action_template_redirect_product_archives(): void
{
    if (is_tax('product_cat')) {
        $term = get_queried_object();
        $anchor = $term instanceof WP_Term ? '#' . $term->slug : '';

        wp_safe_redirect(hengegroup_theme_get_products_page_url() . $anchor, 301);
        exit();
    }

    if (is_tax(get_object_taxonomies('product')) || is_post_type_archive('product')) {
        wp_safe_redirect(hengegroup_theme_get_products_page_url(), 301);
        exit();
    }
}
add_action('template_redirect', 'hengegroup_theme_action_template_redirect_product_archives');

function hengegroup_theme_filter_wp_sitemaps_taxonomies_products(array $taxonomies): array
{
    return array_diff_key($taxonomies, array_flip(get_object_taxonomies('product')));
}
add_filter('wp_sitemaps_taxonomies', 'hengegroup_theme_filter_wp_sitemaps_taxonomies_products');

function hengegroup_theme_filter_seo_post_types_products(array $post_types): array
{
    $post_types[] = 'product';

    return $post_types;
}
add_filter('hengegroup_theme_seo_post_types', 'hengegroup_theme_filter_seo_post_types_products');
