<?php

declare(strict_types=1);

// Render-Helfer fuer die Produktbox und das Produktraster der Bloecke "Produkte"/"Produkte-Raster".

/**
 * '' ohne Badge-Text.
 */
function hengegroup_theme_render_product_badge(int $product_id): string
{
    $text = trim((string) get_post_meta($product_id, '_badge_text', true));

    if ($text === '') {
        return '';
    }

    $variant = (string) get_post_meta($product_id, '_badge_variant', true);
    $variants = hengegroup_theme_get_badge_variants();

    if (!in_array($variant, $variants, true)) {
        $variant = $variants[0];
    }

    ob_start();
    get_template_part('template-parts/base/badge', null, [
        'config' => [
            'text' => $text,
            'variant' => $variant,
            'font' => 'accent',
            'class' => 'uppercase tracking-wide',
        ],
    ]);

    return (string) ob_get_clean();
}

/**
 * "Anwendungen"-Label + Badges ohne Link; '' ohne Anwendungen.
 */
function hengegroup_theme_render_product_anwendung_badges(int $product_id): string
{
    $anwendungen = hengegroup_theme_get_product_anwendungen($product_id);

    if ($anwendungen === []) {
        return '';
    }

    $badges_markup = '';

    foreach ($anwendungen as $anwendung) {
        ob_start();
        get_template_part('template-parts/base/badge', null, [
            'config' => [
                'text' => $anwendung->name,
                'variant' => 'outline',
            ],
        ]);
        $badges_markup .= (string) ob_get_clean();
    }

    ob_start();
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => 'body-xs',
            'tag' => 'span',
            'text' => __('Anwendungen', 'hengegroup-theme'),
            'class' => 'mb-1 block font-medium tracking-wider uppercase',
        ],
    ]);
    $label_markup = (string) ob_get_clean();

    return sprintf(
        '<div data-slot="product-anwendungen">%s<div class="flex flex-wrap gap-1.5">%s</div></div>',
        $label_markup,
        $badges_markup,
    );
}

/**
 * Produktboxen als `<ul class="contents">` in der gegebenen Reihenfolge; '' ohne Treffer.
 *
 * @param int[] $product_ids
 */
function hengegroup_theme_render_produkte_grid(array $product_ids): string
{
    if (!function_exists('wc_get_template_part') || $product_ids === []) {
        return '';
    }

    $products_query = new WP_Query([
        'post_type' => 'product',
        'post_status' => 'publish',
        'post__in' => $product_ids,
        'orderby' => 'post__in',
        'posts_per_page' => count($product_ids),
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
    ]);

    if (!$products_query->have_posts()) {
        return '';
    }

    ob_start();
    echo '<ul class="contents">';

    while ($products_query->have_posts()) {
        $products_query->the_post();
        wc_get_template_part('content', 'product');
    }

    echo '</ul>';

    wp_reset_postdata();

    return (string) ob_get_clean();
}
