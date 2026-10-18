<?php

declare(strict_types=1);

// Post-Type "stellenangebote" + Taxonomien, Routing und Ablauf. Stellen unter /karriere/<slug>/,
// /karriere/ selbst ist eine Seite aus Bloecken. Abgelaufene Stellen: 301 auf die Karriereseite,
// raus aus Listen und Sitemap (Redakteure sehen sie mit Hinweis). Alte URLs /karriere/?job=<id>
// leiten ueber "Alte Stellen-ID" weiter.

function hengegroup_theme_register_stellenangebote_post_type(): void
{
    register_post_type(HENGEGROUP_THEME_JOB_POST_TYPE, [
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
            'menu_name' => __('Stellenangebote', 'hengegroup-theme'),
        ],
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-businessman',
        'supports' => ['title', 'editor', 'excerpt', 'revisions'],
        // Vorlage (nicht gesperrt): Einleitung + drei Stellen-Listen.
        'template' => [
            [
                'core/paragraph',
                [
                    'placeholder' => __(
                        'Einleitung: Wer seid ihr, wen sucht ihr, wo und ab wann?',
                        'hengegroup-theme',
                    ),
                ],
            ],
            ['hengegroup-theme/stellen-liste', ['type' => 'benefits']],
            ['hengegroup-theme/stellen-liste', ['type' => 'profile']],
            ['hengegroup-theme/stellen-liste', ['type' => 'tasks']],
        ],
        'has_archive' => false,
        'rewrite' => [
            'slug' => 'karriere',
            'with_front' => false,
        ],
        'show_in_nav_menus' => false,
    ]);
}
add_action('init', 'hengegroup_theme_register_stellenangebote_post_type');

/**
 * Nicht oeffentlich (keine duennen Archivseiten); Zuordnung in der Stellendetails-Box.
 */
function hengegroup_theme_register_stellenangebote_taxonomies(): void
{
    $shared = [
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => false,
        'show_admin_column' => true,
        'show_tagcloud' => false,
        'hierarchical' => false,
        'meta_box_cb' => false,
        'rewrite' => false,
    ];

    register_taxonomy(
        HENGEGROUP_THEME_JOB_COMPANY_TAXONOMY,
        [HENGEGROUP_THEME_JOB_POST_TYPE, 'product'],
        array_merge($shared, [
            'show_in_menu' => false,
            'show_in_quick_edit' => false,
            'labels' => [
                'name' => __('Unternehmen', 'hengegroup-theme'),
                'singular_name' => __('Unternehmen', 'hengegroup-theme'),
                'add_new_item' => __('Neues Unternehmen hinzufügen', 'hengegroup-theme'),
                'edit_item' => __('Unternehmen bearbeiten', 'hengegroup-theme'),
                'search_items' => __('Unternehmen durchsuchen', 'hengegroup-theme'),
                'not_found' => __('Keine Unternehmen gefunden', 'hengegroup-theme'),
                'back_to_items' => __('← Zurück zu den Unternehmen', 'hengegroup-theme'),
            ],
        ]),
    );

    register_taxonomy(
        HENGEGROUP_THEME_JOB_LOCATION_TAXONOMY,
        HENGEGROUP_THEME_JOB_POST_TYPE,
        array_merge($shared, [
            'labels' => [
                'name' => __('Standorte', 'hengegroup-theme'),
                'singular_name' => __('Standort', 'hengegroup-theme'),
                'add_new_item' => __('Neuen Standort hinzufügen', 'hengegroup-theme'),
                'edit_item' => __('Standort bearbeiten', 'hengegroup-theme'),
                'search_items' => __('Standorte durchsuchen', 'hengegroup-theme'),
                'not_found' => __('Keine Standorte gefunden', 'hengegroup-theme'),
                'back_to_items' => __('← Zurück zu den Standorten', 'hengegroup-theme'),
            ],
        ]),
    );

    register_taxonomy(
        HENGEGROUP_THEME_JOB_CATEGORY_TAXONOMY,
        HENGEGROUP_THEME_JOB_POST_TYPE,
        array_merge($shared, [
            'labels' => [
                'name' => __('Tätigkeitsbereiche', 'hengegroup-theme'),
                'singular_name' => __('Tätigkeitsbereich', 'hengegroup-theme'),
                'add_new_item' => __('Neuen Tätigkeitsbereich hinzufügen', 'hengegroup-theme'),
                'edit_item' => __('Tätigkeitsbereich bearbeiten', 'hengegroup-theme'),
                'search_items' => __('Tätigkeitsbereiche durchsuchen', 'hengegroup-theme'),
                'not_found' => __('Keine Tätigkeitsbereiche gefunden', 'hengegroup-theme'),
                'back_to_items' => __('← Zurück zu den Tätigkeitsbereichen', 'hengegroup-theme'),
            ],
        ]),
    );
}
add_action('init', 'hengegroup_theme_register_stellenangebote_taxonomies');

/**
 * Rewrite-Regeln einmalig neu schreiben; bei URL-Aenderungen die Version erhoehen.
 */
function hengegroup_theme_action_init_flush_career_rewrites(): void
{
    $version = '2026-10-07';

    if (get_option('hengegroup_theme_career_rewrite_version') === $version) {
        return;
    }

    flush_rewrite_rules(false);
    update_option('hengegroup_theme_career_rewrite_version', $version, true);
}
add_action('init', 'hengegroup_theme_action_init_flush_career_rewrites', 99);

/**
 * Unterseiten der Karriereseite treffen sonst die Stellen-Regel und liefern 404.
 */
function hengegroup_theme_filter_request_career_child_pages(array $query_vars): array
{
    if (
        ($query_vars['post_type'] ?? '') !== HENGEGROUP_THEME_JOB_POST_TYPE ||
        empty($query_vars['name']) ||
        !is_string($query_vars['name'])
    ) {
        return $query_vars;
    }

    $name = $query_vars['name'];
    $job = get_page_by_path($name, OBJECT, HENGEGROUP_THEME_JOB_POST_TYPE);

    if ($job instanceof WP_Post) {
        return $query_vars;
    }

    $page = get_page_by_path('karriere/' . $name);

    if (!($page instanceof WP_Post)) {
        return $query_vars;
    }

    return ['pagename' => 'karriere/' . $name];
}
add_filter('request', 'hengegroup_theme_filter_request_career_child_pages');

function hengegroup_theme_action_template_redirect_jobs(): void
{
    $legacy_id = isset($_GET['job']) ? sanitize_text_field(wp_unslash($_GET['job'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    if ($legacy_id !== '' && !is_singular(HENGEGROUP_THEME_JOB_POST_TYPE)) {
        $request_path = trim((string) wp_parse_url(add_query_arg([]), PHP_URL_PATH), '/');
        $career_path = trim(
            (string) wp_parse_url(hengegroup_theme_get_career_page_url(), PHP_URL_PATH),
            '/',
        );

        if (
            $request_path === 'karriere' ||
            ($career_path !== '' && $request_path === $career_path)
        ) {
            $keys = hengegroup_theme_get_job_meta_keys();
            $matches = get_posts([
                'post_type' => HENGEGROUP_THEME_JOB_POST_TYPE,
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'fields' => 'ids',
                'meta_key' => $keys['legacy_id'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                'meta_value' => $legacy_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            ]);

            $target =
                $matches !== [] && !hengegroup_theme_is_job_expired((int) $matches[0])
                    ? (string) get_permalink((int) $matches[0])
                    : hengegroup_theme_get_career_page_url();

            wp_safe_redirect($target, 301);
            exit();
        }
    }

    if (!is_singular(HENGEGROUP_THEME_JOB_POST_TYPE) || is_preview()) {
        return;
    }

    $post_id = (int) get_queried_object_id();

    if (!hengegroup_theme_is_job_expired($post_id) || current_user_can('edit_post', $post_id)) {
        return;
    }

    wp_safe_redirect(hengegroup_theme_get_career_page_url(), 301);
    exit();
}
add_action('template_redirect', 'hengegroup_theme_action_template_redirect_jobs');

function hengegroup_theme_filter_wp_sitemaps_posts_query_args_jobs(
    array $args,
    string $post_type,
): array {
    if ($post_type !== HENGEGROUP_THEME_JOB_POST_TYPE) {
        return $args;
    }

    $args['meta_query'] = hengegroup_theme_get_active_job_meta_query(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query

    return $args;
}
add_filter(
    'wp_sitemaps_posts_query_args',
    'hengegroup_theme_filter_wp_sitemaps_posts_query_args_jobs',
    10,
    2,
);

/**
 * lastmod fuer Stellen (WordPress liefert fuer CPTs keinen).
 */
function hengegroup_theme_filter_wp_sitemaps_posts_entry_jobs(
    array $entry,
    WP_Post $post,
    string $post_type,
): array {
    if ($post_type === HENGEGROUP_THEME_JOB_POST_TYPE) {
        $entry['lastmod'] = (string) get_post_modified_time('c', true, $post);
    }

    return $entry;
}
add_filter(
    'wp_sitemaps_posts_entry',
    'hengegroup_theme_filter_wp_sitemaps_posts_entry_jobs',
    10,
    3,
);

add_filter('hengegroup_theme_seo_post_types', function (array $post_types): array {
    $post_types[] = HENGEGROUP_THEME_JOB_POST_TYPE;

    return $post_types;
});
