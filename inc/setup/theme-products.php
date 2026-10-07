<?php

declare(strict_types=1);

// Produktbereich: Post-Type "anwendung", URL-Struktur und Weiterleitungen. Backend-Felder:
// inc/setup/theme-products-admin.php, Daten-/Render-Helfer: inc/template-parts/products.php.
// Begruendung: docs/entscheidungen.md "Produktbereich: Datenmodell" und "Produktbereich: URLs".
//
// URL-Struktur (explizite Vorgabe):
//   /produkte/                -> normale WordPress-Seite aus Bloecken (Buehne, "Produktkategorie",
//                                "Kontakt"), KEIN WooCommerce-Shop-Archiv -- gleiches Muster wie
//                                /karriere/. Deshalb liefert der Filter
//                                `woocommerce_get_shop_page_id` -1: sonst wuerde WooCommerce die
//                                Seite (falls sie als Shop-Seite eingetragen ist) per
//                                archive-product.php kapern und die Bloecke ignorieren.
//   /produkte/<produkt>/      -> Produktdetailseite (woocommerce/single-product.php). Rewrite-Slug
//                                des Post-Types `product` per Filter statt ueber die
//                                WooCommerce-Permalink-Einstellung, damit die Struktur im Code
//                                steht und nicht auf jeder Instanz von Hand gesetzt werden muss.
//   /anwendungen/<anwendung>/ -> Anwendungsseite (single-anwendung.php).
//
// Produktkategorie-/Schlagwort-Archive und das Post-Type-Archiv gibt es nicht als eigene Seiten:
// sie leiten per 301 auf die Produktuebersicht weiter (Kategorien direkt auf ihre Sektion,
// `#<kategorie-slug>`, siehe Block "Produktkategorie").

/**
 * Post-Type "Anwendungen": eigene Seite je Anwendung (Gutenberg-Inhalt, Kurztext fuer die Karten
 * auf der Produktdetailseite, Beitragsbild, Icon). Die Zuordnung zu Produkten wird NUR am Produkt
 * gepflegt (theme-products-admin.php).
 */
function hengegroup_theme_register_anwendung_post_type(): void
{
    register_post_type(HENGEGROUP_THEME_ANWENDUNG_POST_TYPE, [
        'labels' => [
            'name' => __('Anwendungen', 'hengegroup-theme'),
            'singular_name' => __('Anwendung', 'hengegroup-theme'),
            'add_new' => __('Neu hinzufügen', 'hengegroup-theme'),
            'add_new_item' => __('Neue Anwendung hinzufügen', 'hengegroup-theme'),
            'edit_item' => __('Anwendung bearbeiten', 'hengegroup-theme'),
            'new_item' => __('Neue Anwendung', 'hengegroup-theme'),
            'view_item' => __('Anwendung ansehen', 'hengegroup-theme'),
            'view_items' => __('Anwendungen ansehen', 'hengegroup-theme'),
            'search_items' => __('Anwendungen durchsuchen', 'hengegroup-theme'),
            'not_found' => __('Keine Anwendungen gefunden', 'hengegroup-theme'),
            'not_found_in_trash' => __(
                'Keine Anwendungen im Papierkorb gefunden',
                'hengegroup-theme',
            ),
            'all_items' => __('Anwendungen', 'hengegroup-theme'),
            'menu_name' => __('Anwendungen', 'hengegroup-theme'),
        ],
        'public' => true,
        'show_in_rest' => true,
        // Unter "Produkte" statt als eigener Hauptmenuepunkt -- Anwendungen gehoeren inhaltlich
        // zum Produktbereich.
        'show_in_menu' => 'edit.php?post_type=product',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes'],
        'has_archive' => false,
        'rewrite' => [
            'slug' => 'anwendungen',
            'with_front' => false,
        ],
    ]);
}
add_action('init', 'hengegroup_theme_register_anwendung_post_type');

/**
 * Produkte unter /produkte/<slug>/ und ohne eigenes Post-Type-Archiv (die Uebersicht ist eine
 * Seite, siehe Dateikopf).
 */
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
 * Keine WooCommerce-Shop-Seite, solange nichts bestellbar ist -- siehe Dateikopf. Sobald der Shop
 * kommt, braucht es fuer Warenkorb/Kasse keine Shop-Seite; nur Links wie "Zurueck zum Shop"
 * zeigen dann auf die Produktuebersicht (siehe docs/to-do.md).
 */
function hengegroup_theme_filter_shop_page_id(): int
{
    return -1;
}
add_filter('woocommerce_get_shop_page_id', 'hengegroup_theme_filter_shop_page_id');

/**
 * Schreibt die Rewrite-Regeln einmalig neu, sobald sich die URL-Struktur aendert (Version unten
 * hochzaehlen) -- gleiches Muster wie hengegroup_theme_action_init_flush_career_rewrites().
 */
function hengegroup_theme_action_init_flush_product_rewrites(): void
{
    $version = '2026-10-07';

    if (get_option('hengegroup_theme_product_rewrite_version') === $version) {
        return;
    }

    flush_rewrite_rules(false);
    update_option('hengegroup_theme_product_rewrite_version', $version, true);
}
add_action('init', 'hengegroup_theme_action_init_flush_product_rewrites', 99);

/**
 * Legt das globale Attribut "Koernung" (`pa_koernung`) einmalig an, falls es fehlt -- die
 * Produktdetailseite liest die lieferbaren Koernungen daraus (inc/template-parts/products.php).
 * Ein globales Attribut statt freier Zeilen, damit die Koernungen spaeter ohne Datenumbau zur
 * Variantenauswahl werden koennen ("Fuer Variationen verwenden"), sobald Produkte bestellbar sind.
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
 * /produkte/<name>/ trifft zuerst die Einzel-Regel der Produkte. Gibt es kein Produkt mit diesem
 * Slug, aber eine Unterseite der Uebersichtsseite, wird die Anfrage auf diese Seite umgebogen --
 * gleiches Muster wie hengegroup_theme_filter_request_career_child_pages().
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
 * 301 von Produktkategorie-/Schlagwort-Archiven und dem Produkt-Archiv auf die Produktuebersicht --
 * Kategorien direkt auf ihre Sektion (`#<slug>`, Anker des Blocks "Produktkategorie").
 */
function hengegroup_theme_action_template_redirect_product_archives(): void
{
    if (is_tax('product_cat')) {
        $term = get_queried_object();
        $anchor = $term instanceof WP_Term ? '#' . $term->slug : '';

        wp_safe_redirect(hengegroup_theme_get_products_page_url() . $anchor, 301);
        exit();
    }

    if (is_tax('product_tag') || is_post_type_archive('product')) {
        wp_safe_redirect(hengegroup_theme_get_products_page_url(), 301);
        exit();
    }
}
add_action('template_redirect', 'hengegroup_theme_action_template_redirect_product_archives');

/**
 * Produktkategorien/-schlagwoerter nicht in der XML-Sitemap -- es gibt keine eigenen Seiten dafuer.
 */
function hengegroup_theme_filter_wp_sitemaps_taxonomies_products(array $taxonomies): array
{
    unset($taxonomies['product_cat'], $taxonomies['product_tag']);

    return $taxonomies;
}
add_filter('wp_sitemaps_taxonomies', 'hengegroup_theme_filter_wp_sitemaps_taxonomies_products');

/**
 * Produkte und Anwendungen bekommen die bestehende SEO-Metabox (siehe docs/how-to.md "Ein weiteres,
 * Seiten-spezifisches SEO-Feld nutzen").
 */
function hengegroup_theme_filter_seo_post_types_products(array $post_types): array
{
    $post_types[] = 'product';
    $post_types[] = HENGEGROUP_THEME_ANWENDUNG_POST_TYPE;

    return $post_types;
}
add_filter('hengegroup_theme_seo_post_types', 'hengegroup_theme_filter_seo_post_types_products');
