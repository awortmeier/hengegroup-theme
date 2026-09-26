<?php

declare(strict_types=1);

// Rendert template-parts/blocks/produkte/block.json ueber WordPress' natives `render`-Feld -- siehe
// template-parts/blocks/buehne/render.php's Kopfkommentar fuer die allgemeine Phase-3-Block-
// Konvention.
//
// Produktkarten sind woocommerce/content-product.php UNVERAENDERT -- wiederverwendet ueber
// hengegroup_theme_render_produkte_grid() (inc/template-parts/woocommerce-product-card.php), die
// eigentliche WP_Query + `wc_get_template_part('content', 'product')`-Loop-Logik, geteilt mit
// template-parts/blocks/produkte-raster/render.php (siehe dessen Kopfkommentar: der
// Editor-Vorschau-Zwillingsblock fuer die Live-Produktraster-Vorschau im Canvas, waehrend
// Ueberschrift/Text hier als natives, direkt im Content-Bereich editierbares `RichText` laufen,
// siehe assets/js/blocks/produkte/edit.jsx's Kopfkommentar fuer die volle Begruendung dieser
// Aufteilung).
//
// Layout: `.wrapper`-12-Spalten-Grid (siehe docs/entscheidungen.md "12-Spalten-Grid ueber
// .wrapper"). Kopfzeile (Ueberschrift/Text/Button) bekommt trotzdem `col-span-12` (volle Zeile,
// gleiche Konvention wie jeder andere Block-Kopf) und wird per `max-w-2xl` optisch schmaler
// gehalten -- ein Teil-`col-span` liesse die erste Produktkarte per CSS-Grid-Auto-Placement in die
// auf dieser Zeile noch freien Spalten rutschen, statt in einer eigenen Zeile darunter zu beginnen
// (gleiche Falle, die archive-product.php mit seinem eigenen vollspannigen Titel-`<div>` vor dem
// Produkt-Loop vermeidet). Die Produktkarten selbst liegen als `<ul class="contents">`
// (`display: contents`) direkt als Kind von `.wrapper` -- dieselbe Technik wie archive-product.php,
// damit content-product.php's eigene `col-span-12 sm:col-span-6 lg:col-span-3` je `<li>` direkt
// gegen DIESES `.wrapper`-Grid greift statt gegen ein zweites, verschachteltes.
//
// Dunkler Hintergrund (`bg-grey-dark`, siehe tokens.css) + `color: 'light'`-Typography -- gleiches
// "weisser Text auf dunklem Grund"-Muster wie buehne/render.php's Content-Box. Der
// "Alle Produkte"-Button faellt ohne eigene `buttonUrl` auf die WooCommerce-Shop-Seite zurueck
// (`wc_get_page_permalink('shop')`), damit der Block auch ohne manuelle Verlinkung sofort
// funktioniert.

if (!is_array($attributes ?? null)) {
    return;
}

if (
    !function_exists('wc_get_page_permalink') ||
    !function_exists('hengegroup_theme_render_produkte_grid')
) {
    return;
}

$heading = trim((string) ($attributes['heading'] ?? ''));
$heading_tag = strtolower(trim((string) ($attributes['headingTag'] ?? 'p')));

if (!in_array($heading_tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'], true)) {
    $heading_tag = 'p';
}

$text = trim((string) ($attributes['text'] ?? ''));
$button_text = trim((string) ($attributes['buttonText'] ?? ''));
$button_url = trim((string) ($attributes['buttonUrl'] ?? ''));
$product_ids = array_map('intval', (array) ($attributes['productIds'] ?? []));

$grid_markup = hengegroup_theme_render_produkte_grid($product_ids);

if ($grid_markup === '') {
    return;
}

echo '<section class="bg-grey-dark py-16 md:py-24 lg:py-35">';
echo '<div class="wrapper gap-y-10">';
echo '<div class="col-span-12 max-w-2xl">';

if ($heading !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'text' => $heading,
            'variant' => 'headline-base',
            'tag' => $heading_tag,
            'color' => 'light',
            'class' => 'mb-4',
        ],
    ]);
}

if ($text !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'text' => $text,
            'variant' => 'body-base',
            'color' => 'light',
            'class' => $button_text !== '' ? 'mb-8' : '',
        ],
    ]);
}

if ($button_text !== '') {
    $shop_url = (string) wc_get_page_permalink('shop');

    get_template_part('template-parts/base/button', null, [
        'config' => [
            'text' => $button_text,
            'href' => $button_url !== '' ? $button_url : $shop_url,
            'variant' => 'grey-light',
            'size' => 'lg',
        ],
    ]);
}

echo '</div>';
echo $grid_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</div>';
echo '</section>';
