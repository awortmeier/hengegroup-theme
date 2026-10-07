<?php

declare(strict_types=1);

// Rendert template-parts/blocks/produktkategorie/block.json (Design "Produktuebersicht", je
// Kategorie eine Sektion) -- siehe template-parts/blocks/buehne/render.php's Kopfkommentar fuer
// die allgemeine Phase-3-Block-Konvention.
//
// Redakteure waehlen nur die Kategorie und den Hintergrund; Kicker, Farbe, Ueberschrift und
// Beschreibung kommen aus der Produktkategorie (Produkte > Kategorien), die Produktboxen sind
// automatisch alle veroeffentlichten Produkte der Kategorie inkl. Unterkategorien (Menue-
// Reihenfolge, dann Name) -- neue Produkte erscheinen ohne Pflege an der Seite. Die Boxen sind
// woocommerce/content-product.php ueber hengegroup_theme_render_produkte_grid(), dieselbe Box wie
// ueberall sonst.
//
// `id` = Slug der Kategorie: Sprungziel fuer Menue-Links (z. B. /produkte/#schleifmittel) und fuer
// die 301-Weiterleitung alter Kategorie-Adressen (inc/setup/theme-products.php).
//
// Kopf (Kicker/Ueberschrift/Text) als volle Rasterzeile statt Teil-`col-span` -- sonst rutscht die
// erste Produktbox per Grid-Auto-Placement in die freien Spalten daneben (gleiche Falle wie in
// produkte/render.php beschrieben; Bugfix 2026-10-07). Nur der Beschreibungstext ist schmaler
// (900 px wie im Design); die Ueberschrift laeuft ueber die volle Breite und ohne `text-balance`,
// damit lange Ueberschriften wie "Schleifmittel, Strahlmittel, Granatsand, Feuerfest-Produkte"
// nicht kuenstlich auf zwei gleich lange Zeilen verteilt werden (explizite Nachfrage 2026-10-07).
//
// Hintergrund: `light` = Seitenhintergrund (kommt vom body, siehe docs/entscheidungen.md
// "Seitenhintergrund grey-light kommt vom body"), `muted` = der etwas dunklere Grauton der
// Referenz (#e5e3df -> neutral-200, gleiche Zuordnung wie die Bewerbungs-Sektion) -- im Design
// wechseln sich die Sektionen ab.

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
