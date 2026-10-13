<?php

declare(strict_types=1);

// Block "Produktkategorie": Kopf aus der Kategorie, Produktboxen = alle Produkte der Kategorie inkl.
// Unterkategorien. `id` = Kategorie-Slug (Sprungziel, auch fuer die 301 alter Kategorie-URLs).
// Kopf als volle Rasterzeile (Auto-Placement), Ueberschrift ohne text-balance.
// Hintergrund: light = body, muted = neutral-200.

if (!is_array($attributes ?? null) || !function_exists('hengegroup_theme_render_product_cards')) {
    return;
}

$term = get_term((int) ($attributes['categoryId'] ?? 0), 'product_cat');

if (!($term instanceof WP_Term)) {
    return;
}

$category = hengegroup_theme_get_product_category_data($term);
$grid_markup = hengegroup_theme_render_product_cards(
    hengegroup_theme_get_category_product_ids($category['term_id']),
);

if ($grid_markup === '') {
    return;
}

$background = ($attributes['background'] ?? 'light') === 'muted' ? 'bg-neutral-200' : '';

printf(
    '<section id="%1$s" class="%2$s" data-slot="produktkategorie">',
    esc_attr($category['slug']),
    esc_attr(trim('scroll-mt-24 section-spacing ' . $background)),
);
echo '<div class="wrapper gap-y-6">';
get_template_part('template-parts/components/section-intro', null, [
    'config' => [
        'kicker' => $category['kicker'],
        'kicker_variant' => $category['variant'],
        'heading' => $category['heading'],
        'text' => $category['description'],
        'class' => 'col-span-12 mb-4',
    ],
]);
echo $grid_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</div>';
echo '</section>';
