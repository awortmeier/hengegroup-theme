<?php

declare(strict_types=1);

// Block "Produktkategorie": Kopf aus dem Block (leer = aus der Kategorie), Produktboxen = alle
// Produkte der Kategorie inkl. Unterkategorien. `id` = Kategorie-Slug (Sprungziel, auch fuer die 301
// alter Kategorie-URLs).
// Die Produktboxen liegen in einem Subgrid: eigener Zeilenabstand, Spalten wie .wrapper.

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

$background = hengegroup_theme_get_section_background((string) ($attributes['background'] ?? ''));
$spacing_class = hengegroup_theme_get_section_spacing_class(
    (string) ($attributes['spacing'] ?? ''),
);

printf(
    '<section id="%1$s" class="%2$s" data-slot="produktkategorie" data-background="%3$s">',
    esc_attr($category['slug']),
    esc_attr(trim('scroll-mt-24 ' . $spacing_class . ' ' . $background['class'])),
    esc_attr($background['name']),
);
echo '<div class="wrapper gap-y-10">';
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo hengegroup_theme_render_section_header(
    hengegroup_theme_get_term_section_header_config($category, $attributes) + [
        'color' => $background['color'],
        'class' => 'col-span-12',
    ],
);
echo '<div class="col-span-12 grid grid-cols-subgrid gap-y-6">';
echo $grid_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</div>';
echo '</div>';
echo '</section>';
