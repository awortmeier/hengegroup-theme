<?php

declare(strict_types=1);

// Produkt-Badge: Meta `_badge_text` + `_badge_variant` mit Metabox im Produkt-Editor.

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
