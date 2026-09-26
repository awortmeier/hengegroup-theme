<?php

declare(strict_types=1);

// Custom Post Type "stellenangebote" (Karriere/Jobs, explicit request 2026-09-23) -- rough first
// cut, content model deliberately minimal (title/editor/thumbnail/excerpt only). The actual job
// text is authored entirely via the standard Gutenberg editor (explicit request), no structured
// meta fields (Standort/Unternehmen/Benefits/Anforderungen/Bewerbungsformular etc.) yet -- those
// are a separate, larger follow-up once the exact field list is specified, see docs/to-do.md.
//
// `has_archive`/`rewrite` both use the SAME slug 'karriere' on purpose -- overview page at
// /karriere/, single job at /karriere/<slug>/ (explicit request, matches the live site's intended
// URL structure), the same "archive slug == single rewrite slug" recipe core WordPress itself uses
// for /blog/ + /blog/post-name/. See docs/entscheidungen.md "Stellenangebote: Custom-Post-Type
// angelegt" for the full rationale, incl. why the post type KEY ('stellenangebote') is German while
// still being independent from the URL slug.

function hengegroup_theme_register_stellenangebote_post_type(): void
{
    register_post_type('stellenangebote', [
        'labels' => [
            'name' => __('Stellenangebote', 'hengegroup-theme'),
            'singular_name' => __('Stellenangebot', 'hengegroup-theme'),
            'add_new' => __('Neu hinzufügen', 'hengegroup-theme'),
            'add_new_item' => __('Neues Stellenangebot hinzufügen', 'hengegroup-theme'),
            'edit_item' => __('Stellenangebot bearbeiten', 'hengegroup-theme'),
            'new_item' => __('Neues Stellenangebot', 'hengegroup-theme'),
            'view_item' => __('Stellenangebot ansehen', 'hengegroup-theme'),
            'view_items' => __('Stellenangebote ansehen', 'hengegroup-theme'),
            'search_items' => __('Stellenangebote durchsuchen', 'hengegroup-theme'),
            'not_found' => __('Keine Stellenangebote gefunden', 'hengegroup-theme'),
            'not_found_in_trash' => __(
                'Keine Stellenangebote im Papierkorb gefunden',
                'hengegroup-theme',
            ),
            'all_items' => __('Alle Stellenangebote', 'hengegroup-theme'),
            'archives' => __('Stellenangebot-Archiv', 'hengegroup-theme'),
            'menu_name' => __('Karriere', 'hengegroup-theme'),
        ],
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-businessman',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'has_archive' => 'karriere',
        'rewrite' => [
            'slug' => 'karriere',
            'with_front' => false,
        ],
        'show_in_nav_menus' => true,
    ]);
}
add_action('init', 'hengegroup_theme_register_stellenangebote_post_type');

// Reuses the existing SEO metabox (Titel/Beschreibung/Social-Bild/Robots, siehe
// inc/setup/theme-seo-admin.php) für Stellenangebote statt einer eigenen SEO-Loesung -- siehe
// docs/how-to.md "Ein weiteres, Seiten-spezifisches SEO-Feld nutzen" fuer den Filter-Vertrag.
add_filter('hengegroup_theme_seo_post_types', function (array $post_types): array {
    $post_types[] = 'stellenangebote';

    return $post_types;
});
