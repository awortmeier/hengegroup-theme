<?php

declare(strict_types=1);

// Block "Produktkategorie": Kopf aus der Kategorie, Produktboxen = alle Produkte der Kategorie inkl.
// Unterkategorien. `id` = Kategorie-Slug (Sprungziel, auch fuer die 301 alter Kategorie-URLs).
// Kopf als volle Rasterzeile (Auto-Placement), Ueberschrift ohne text-balance.
// Hintergrund: light = body, muted = neutral-200.

if (!is_array($attributes ?? null) || !function_exists('hengegroup_theme_render_produkte_grid')) {
    return;
}

$term = get_term((int) ($attributes['categoryId'] ?? 0), 'product_cat');

if (!($term instanceof WP_Term)) {
    return;
}

$category = hengegroup_theme_get_product_category_data($term);
$grid_markup = hengegroup_theme_render_produkte_grid(
    hengegroup_theme_get_category_product_ids($category['term_id']),
);

if ($grid_markup === '') {
    return;
}

$background = ($attributes['background'] ?? 'light') === 'muted' ? 'bg-neutral-200' : '';

printf(
    '<section id="%1$s" class="%2$s" data-slot="produktkategorie">',
    esc_attr($category['slug']),
    esc_attr(trim('scroll-mt-24 py-16 md:py-25 ' . $background)),
);
echo '<div class="wrapper gap-y-6">';
echo '<div class="col-span-12 mb-4">';

if ($category['kicker'] !== '') {
    echo '<div class="mb-4.5">';
    get_template_part('template-parts/base/badge', null, [
        'config' => [
            'text' => $category['kicker'],
            'variant' => $category['variant'],
            'font' => 'accent',
            'class' => 'px-3.5 tracking-[1.5px] uppercase',
        ],
    ]);
    echo '</div>';
}

get_template_part('template-parts/base/typography', null, [
    'config' => [
        'variant' => 'headline-sm',
        'tag' => 'h2',
        'text' => $category['heading'],
        'class' => 'mb-4.5 text-pretty lg:text-[34px]',
    ],
]);

if ($category['description'] !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => 'body-base',
            'text' => $category['description'],
            'class' => 'max-w-[900px]',
        ],
    ]);
}

echo '</div>';
echo $grid_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</div>';
echo '</section>';
