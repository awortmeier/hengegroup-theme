<?php

declare(strict_types=1);

// Data model for this project's product catalog "Badge" (2026-09-22, see docs/entscheidungen.md
// "Produktbox: Badge-/Anwendungs-Datenmodell" for the full rationale): plain product meta
// (`_badge_text` string, `_badge_variant` one of henge-blue/henge-green/henge-grey/grey-dark),
// edited via a `add_meta_box()` text field + select right below. Replaces an earlier `firma`
// taxonomy + fixed Kominex/Imexco-are-blue business rule that auto-grouped multiple company
// terms into one badge (explicit request 2026-09-23, see docs/entscheidungen.md "Produktbox:
// `firma`-Taxonomie durch einzelnes Badge-Feld ersetzt") -- there's only ever one badge now, and
// its color is a direct editorial choice instead of derived from a company-name allow-list.
// `grey-dark` added to the color choice afterwards (explicit request 2026-09-23).
//
// The "Anwendungen" custom post type + `_anwendungen` product-meta relationship that used to live
// here has been removed again (explicit request 2026-09-23, see docs/entscheidungen.md
// "Anwendungen: Produktkategorie statt eigenem Post-Type") -- Anwendungen are now this project's
// regular WooCommerce `product_cat` terms, no theme-specific data model needed for them anymore.
//
// Front-end rendering (badge markup, woocommerce/content-product.php) lives in
// inc/template-parts/woocommerce-product-card.php.

/**
 * Allowed `_badge_variant` values for the product-editor "Badge" meta box below -- four of the
 * solid/neutral brand-color variants badge.php's own vocabulary offers
 * (henge-blue/henge-green/henge-grey/grey-dark), deliberately narrower than badge.php's full
 * variant list (grey-light/outline make no sense as an editorial "pick a brand color" choice
 * here).
 */
function hengegroup_theme_get_badge_variants(): array
{
    return ['henge-blue', 'henge-green', 'henge-grey', 'grey-dark'];
}

function hengegroup_theme_action_add_meta_boxes_product_badge(): void
{
    add_meta_box(
        'hengegroup-theme-product-badge',
        __('Badge', 'hengegroup-theme'),
        'hengegroup_theme_render_product_badge_meta_box',
        'product',
        'side',
        'default',
    );
}
add_action('add_meta_boxes', 'hengegroup_theme_action_add_meta_boxes_product_badge');

/**
 * Renders the product-editor "Badge" meta box: a free-text label (`_badge_text`) plus a color
 * select (`_badge_variant`, see hengegroup_theme_get_badge_variants()) -- the label/color pair
 * hengegroup_theme_render_product_badge() (woocommerce-product-card.php) reads back on the front
 * end. Empty text means no badge renders at all, same "buffer and check for emptiness" convention
 * as the rest of the product card.
 */
function hengegroup_theme_render_product_badge_meta_box(WP_Post $post): void
{
    wp_nonce_field('hengegroup_theme_save_product_badge', 'hengegroup_theme_product_badge_nonce');

    $text = (string) get_post_meta($post->ID, '_badge_text', true);
    $variant = (string) get_post_meta($post->ID, '_badge_variant', true);
    $variants = hengegroup_theme_get_badge_variants();

    if (!in_array($variant, $variants, true)) {
        $variant = $variants[0];
    }

    printf(
        '<p><label for="hengegroup-theme-badge-text">%s</label><input type="text" ' .
            'id="hengegroup-theme-badge-text" name="hengegroup_theme_badge_text" value="%s" ' .
            'class="widefat"></p>',
        esc_html__('Text', 'hengegroup-theme'),
        esc_attr($text),
    );

    echo '<p><label for="hengegroup-theme-badge-variant">' .
        esc_html__('Farbe', 'hengegroup-theme') .
        '</label><select id="hengegroup-theme-badge-variant" ' .
        'name="hengegroup_theme_badge_variant" class="widefat">';

    foreach ($variants as $variant_option) {
        printf(
            '<option value="%1$s"%2$s>%1$s</option>',
            esc_attr($variant_option),
            selected($variant, $variant_option, false),
        );
    }

    echo '</select></p>';
}

function hengegroup_theme_action_save_post_product_badge(int $post_id): void
{
    if (
        !isset($_POST['hengegroup_theme_product_badge_nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['hengegroup_theme_product_badge_nonce'])),
            'hengegroup_theme_save_product_badge',
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

    $text = isset($_POST['hengegroup_theme_badge_text'])
        ? sanitize_text_field(wp_unslash($_POST['hengegroup_theme_badge_text']))
        : '';
    $variant = isset($_POST['hengegroup_theme_badge_variant'])
        ? sanitize_text_field(wp_unslash($_POST['hengegroup_theme_badge_variant']))
        : '';
    $variants = hengegroup_theme_get_badge_variants();

    if (!in_array($variant, $variants, true)) {
        $variant = $variants[0];
    }

    update_post_meta($post_id, '_badge_text', $text);
    update_post_meta($post_id, '_badge_variant', $variant);
}
add_action('save_post_product', 'hengegroup_theme_action_save_post_product_badge');
