<?php

declare(strict_types=1);

// Produkt-Unternehmen: genau ein Term aus `stellen_unternehmen`, Metabox im Produkt-Editor.

function hengegroup_theme_get_badge_variants(): array
{
    return ['henge-blue', 'henge-green', 'henge-grey', 'grey-dark'];
}

function hengegroup_theme_action_add_meta_boxes_product_company(): void
{
    add_meta_box(
        'hengegroup-theme-product-company',
        __('Unternehmen', 'hengegroup-theme'),
        'hengegroup_theme_render_product_company_meta_box',
        'product',
        'side',
        'default',
    );
}
add_action('add_meta_boxes', 'hengegroup_theme_action_add_meta_boxes_product_company');

function hengegroup_theme_render_product_company_meta_box(WP_Post $post): void
{
    wp_nonce_field(
        'hengegroup_theme_save_product_company',
        'hengegroup_theme_product_company_nonce',
    );

    $company = hengegroup_theme_get_product_company($post->ID);
    $selected = $company !== null ? $company['term_id'] : 0;
    $terms = get_terms([
        'taxonomy' => HENGEGROUP_THEME_JOB_COMPANY_TAXONOMY,
        'hide_empty' => false,
    ]);

    echo '<p><select id="hengegroup-theme-product-company" name="hengegroup_theme_product_company" ' .
        'class="widefat" aria-label="' .
        esc_attr__('Unternehmen', 'hengegroup-theme') .
        '">';
    printf('<option value="0">%s</option>', esc_html__('— kein Unternehmen —', 'hengegroup-theme'));

    foreach (is_array($terms) ? $terms : [] as $term) {
        printf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr((string) $term->term_id),
            selected($selected, (int) $term->term_id, false),
            esc_html($term->name),
        );
    }

    echo '</select></p><p class="description">' .
        esc_html__(
            'Name und Farbe des Badges, der Anwendungsbereiche und des Anfrage-Buttons kommen vom Unternehmen (Menüpunkt „Unternehmen“).',
            'hengegroup-theme',
        ) .
        '</p>';
}

function hengegroup_theme_action_save_post_product_company(int $post_id): void
{
    if (
        !isset($_POST['hengegroup_theme_product_company_nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['hengegroup_theme_product_company_nonce'])),
            'hengegroup_theme_save_product_company',
        )
    ) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $company_id = isset($_POST['hengegroup_theme_product_company'])
        ? absint($_POST['hengegroup_theme_product_company'])
        : 0;
    $term = $company_id > 0 ? get_term($company_id, HENGEGROUP_THEME_JOB_COMPANY_TAXONOMY) : null;

    wp_set_object_terms(
        $post_id,
        $term instanceof WP_Term ? [(int) $term->term_id] : [],
        HENGEGROUP_THEME_JOB_COMPANY_TAXONOMY,
    );
}
add_action('save_post_product', 'hengegroup_theme_action_save_post_product_company');

/**
 * Einmalig: frueheres Freitext-Badge (`_badge_text`) -> Unternehmen; fehlende Unternehmen anlegen.
 */
function hengegroup_theme_action_init_migrate_product_badges(): void
{
    $version = '2026-10-18';

    if (get_option('hengegroup_theme_product_company_migration') === $version) {
        return;
    }

    $taxonomy = HENGEGROUP_THEME_JOB_COMPANY_TAXONOMY;
    $term_ids = [];

    foreach (hengegroup_theme_get_migrated_companies() as $name => $variant) {
        $term = get_term_by('name', $name, $taxonomy);

        if ($term instanceof WP_Term) {
            $term_ids[$name] = (int) $term->term_id;
            continue;
        }

        $created = wp_insert_term($name, $taxonomy);

        if (!is_wp_error($created)) {
            $term_ids[$name] = (int) $created['term_id'];
            update_term_meta($term_ids[$name], '_hengegroup_theme_company_variant', $variant);
        }
    }

    $product_ids = get_posts([
        'post_type' => 'product',
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'ids',
        'meta_key' => '_badge_text', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
    ]);

    foreach ($product_ids as $product_id) {
        $name = hengegroup_theme_get_legacy_badge_company(
            (string) get_post_meta((int) $product_id, '_badge_text', true),
        );

        if (
            isset($term_ids[$name]) &&
            hengegroup_theme_get_product_company((int) $product_id) === null
        ) {
            wp_set_object_terms((int) $product_id, [$term_ids[$name]], $taxonomy);
        }
    }

    update_option('hengegroup_theme_product_company_migration', $version, false);
}
add_action('init', 'hengegroup_theme_action_init_migrate_product_badges', 99);
