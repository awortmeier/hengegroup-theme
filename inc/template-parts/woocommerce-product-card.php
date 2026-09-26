<?php

declare(strict_types=1);

// Rendering helpers for the product box (woocommerce/content-product.php) -- "Badge" meta data
// model lives in inc/setup/theme-woocommerce-products.php, see that file's own header comment.
// Anwendungen (hengegroup_theme_render_product_anwendung_badges() below) are this project's
// regular WooCommerce `product_cat` terms, not a theme-specific data model anymore (explicit
// request 2026-09-23, see docs/entscheidungen.md "Anwendungen: Produktkategorie statt eigenem
// Post-Type") -- nothing to look up outside WordPress/WooCommerce's own `get_the_terms()`.
//
// hengegroup_theme_render_product_badge()/hengegroup_theme_render_product_anwendung_badges() call
// get_post_meta()/get_the_terms()/get_template_part() against real template-parts/base files and
// are intentionally left untested, same as hengegroup_theme_render_image()/
// hengegroup_theme_render_icon() (see tests/Unit/HelpersTest.php's own header comment) -- a future
// WP-backed integration suite's job, not Brain Monkey's (docs/to-do.md Abschnitt 1).
//
// hengegroup_theme_render_produkte_grid() (unten) ist der gemeinsame Nenner zwischen
// template-parts/blocks/produkte/render.php (Frontend) und
// template-parts/blocks/produkte-raster/render.php (Editor-Vorschau, siehe dessen Kopfkommentar
// fuer die Begruendung, warum das ein eigener, im Inserter versteckter Block ist) -- die
// WP_Query/`wc_get_template_part()`-Loop-Logik lebt dadurch nur an EINER Stelle, nicht in beiden
// render.php-Dateien dupliziert.

/**
 * Renders the product's single "Badge" (see the product-editor meta box in
 * inc/setup/theme-woocommerce-products.php: `_badge_text`/`_badge_variant` product meta) as
 * buffered badge.php markup. Returns '' when no badge text is set, so callers can skip the
 * media-badge wrapper entirely (same "buffer and check for emptiness" convention as
 * hengegroup_theme_render_image()).
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
            // badge.php's own `font` axis (explicit request): Crillee instead of the
            // site-wide Outfit body font for this brand-tag pill, same `font-accent`
            // utility hengegroup_theme_render_accent_text() already uses elsewhere for
            // highlighted brand words (see typography.php's own header comment).
            'font' => 'accent',
            // Additive-only classes (no property already set by badge.php's own base
            // classes, see that file's own header comment on why this project has no
            // tailwind-merge to safely override px-*/text-*/font-* instead) -- matches the
            // reference design's compact, all-caps company pill.
            'class' => 'uppercase tracking-wide',
        ],
    ]);

    return (string) ob_get_clean();
}

/**
 * Renders the product's assigned categories (`product_cat`, standard WooCommerce taxonomy -- this
 * project's stand-in for "Anwendungen", see docs/entscheidungen.md "Anwendungen: Produktkategorie
 * statt eigenem Post-Type") as an "Anwendungen" eyebrow label (matches the reference design's
 * small-caps section heading above the badge row) followed by buffered badge.php markup (neutral
 * `outline` variant -- unlike the `firma` badges above, Anwendungen carry no brand color), each a
 * plain <span> (no `href` given), matching the explicit request that Anwendungs-Badges never link
 * anywhere, only display. Returns '' when the product has no categories assigned, so callers can
 * skip the wrapper (and the label with it) entirely.
 */
function hengegroup_theme_render_product_anwendung_badges(int $product_id): string
{
    $terms = get_the_terms($product_id, 'product_cat');

    if (!is_array($terms) || $terms === []) {
        return '';
    }

    // WooCommerce auto-assigns every product WITHOUT an explicit category its own "Unkategorisiert"
    // default term (`get_option('default_product_cat', 0)`, WC 3.3+) -- filtered out here so an
    // uncategorized product shows no Anwendungen section at all instead of a spurious single badge.
    $default_term_id = (int) get_option('default_product_cat', 0);
    $terms = array_values(
        array_filter($terms, static fn(WP_Term $term): bool => $term->term_id !== $default_term_id),
    );

    if ($terms === []) {
        return '';
    }

    usort($terms, static fn(WP_Term $a, WP_Term $b): int => strnatcasecmp($a->name, $b->name));

    $badges_markup = '';

    foreach ($terms as $term) {
        ob_start();
        get_template_part('template-parts/base/badge', null, [
            'config' => [
                'text' => $term->name,
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
            // `color` intentionally left at its 'default' (unset here), NOT 'neutral' -- the
            // reference design's eyebrow label is the same near-black `rgb(30,29,28)` as the
            // title/description, not a muted grey.
            // Additive-only classes on top of body-xs's own `text-sm leading-normal` (no
            // font-weight/tracking/transform of its own to conflict with, same "no
            // tailwind-merge" caveat as the firma badges above). font-medium/tracking-wider match
            // the reference's `font-weight:500`/`letter-spacing:1px` (at 14px, 1px is closer to
            // Tailwind's `tracking-wider` (0.05em = 0.7px) than `tracking-widest` (0.1em = 1.4px)).
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
 * Renders the "Produkte"-block's product grid as `<ul class="contents">...</ul>` (same
 * `display: contents` technique as woocommerce/archive-product.php, see
 * template-parts/blocks/produkte/render.php's own header comment) -- each `<li>` is
 * woocommerce/content-product.php UNVERAENDERT, rendered via a dedicated `WP_Query` +
 * `wc_get_template_part('content', 'product')` loop over the given `$product_ids`, in the exact
 * order given (`orderby => post__in`, editors pick/reorder products manually, see
 * assets/js/blocks/produkte/edit.jsx's Kopfkommentar -- no implicit category/date ordering).
 * Returns '' when `$product_ids` is empty or none of the given IDs resolve to a published product
 * (so callers can skip the section/wrapper entirely, same "buffer and check for emptiness"
 * convention as the rest of this file).
 *
 * `wp_reset_postdata()` before returning -- this is always a secondary query (a real page's main
 * query loop runs separately, if any), never the main query, so callers never need to call it
 * themselves.
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
