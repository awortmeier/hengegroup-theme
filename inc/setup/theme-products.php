<?php

declare(strict_types=1);

// Produktbereich: Taxonomie "Anwendungen" (`produkt_anwendung`), URL-Struktur und Weiterleitungen. Backend-Felder:
// inc/setup/theme-products-admin.php, Daten-/Render-Helfer: inc/template-parts/products.php.
// Begruendung: docs/entscheidungen.md "Produktbereich: Datenmodell" und "Produktbereich: URLs".
//
// URL-Struktur (explizite Vorgabe):
//   /produkte/                -> normale WordPress-Seite aus Bloecken (Buehne, "Produktkategorie",
//                                "Kontakt"), KEIN WooCommerce-Shop-Archiv -- gleiches Muster wie
//                                /karriere/. Deshalb liefert der Filter
//                                `woocommerce_get_shop_page_id` -1: sonst wuerde WooCommerce die
//                                Seite (falls sie als Shop-Seite eingetragen ist) mit
//                                seinem Archiv-Template kapern und die Bloecke ignorieren.
//   /produkte/<produkt>/      -> Produktdetailseite (woocommerce/single-product.php). Rewrite-Slug
//                                des Post-Types `product` per Filter statt ueber die
//                                WooCommerce-Permalink-Einstellung, damit die Struktur im Code
//                                steht und nicht auf jeder Instanz von Hand gesetzt werden muss.
//   /anwendungen/             -> normale Seite aus Bloecken (Buehne, "Anwendungsgruppe", "Kontakt");
//                                Anwendungen haben keine eigenen Seiten (explizite Vorgabe).
//
// Produkt-Archive gibt es nicht als eigene Seiten (deshalb auch kein woocommerce/archive-product.php):
// das Post-Type-Archiv und JEDE Produkt-Taxonomie (Kategorien, Schlagwoerter, Marken
// `product_brand`, oeffentliche Attribute) leiten per 301 auf die Produktuebersicht weiter
// (Kategorien direkt auf ihre Sektion, `#<kategorie-slug>`, siehe Block "Produktkategorie").

/**
 * Taxonomie "Anwendungen" am Produkt, hierarchisch: Ebene 1 = Gruppen (Sektionen der Seite
 * /anwendungen/), Ebene 2 = Anwendungen. Nicht oeffentlich (keine Archivseiten, keine Sitemap) --
 * angezeigt wird sie nur ueber den Block "Anwendungsgruppe", die Produktbox und die
 * Produktdetailseite. Zuordnung im Produkt-Editor ueber eine eigene, nach Gruppen sortierte Box
 * (theme-products-admin.php) sowie per Quick Edit/Massenbearbeitung in der Produktliste.
 * Begruendung: docs/entscheidungen.md "Anwendungen: Taxonomie statt Post-Type, nur
 * Uebersichtsseite".
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
    $version = '2026-10-07-2';

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
 * 301 von allen Produkt-Taxonomie-Archiven und dem Produkt-Archiv auf die Produktuebersicht --
 * Kategorien direkt auf ihre Sektion (`#<slug>`, Anker des Blocks "Produktkategorie"). Alle
 * Taxonomien am Post-Type `product` statt einer festen Liste, damit auch spaeter hinzukommende
 * (z. B. WooCommerce-Marken `product_brand`, oeffentliche Attribute) nicht auf WooCommerces
 * ungestyltem Standard-Archiv landen.
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

/**
 * Keine Produkt-Taxonomien in der XML-Sitemap -- es gibt keine eigenen Seiten dafuer (siehe
 * Weiterleitung oben).
 */
function hengegroup_theme_filter_wp_sitemaps_taxonomies_products(array $taxonomies): array
{
    return array_diff_key($taxonomies, array_flip(get_object_taxonomies('product')));
}
add_filter('wp_sitemaps_taxonomies', 'hengegroup_theme_filter_wp_sitemaps_taxonomies_products');

/**
 * Produkte bekommen die bestehende SEO-Metabox (siehe docs/how-to.md "Ein weiteres,
 * Seiten-spezifisches SEO-Feld nutzen").
 */
function hengegroup_theme_filter_seo_post_types_products(array $post_types): array
{
    $post_types[] = 'product';

    return $post_types;
}
add_filter('hengegroup_theme_seo_post_types', 'hengegroup_theme_filter_seo_post_types_products');
