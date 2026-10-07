<?php

declare(strict_types=1);

// Rendert template-parts/blocks/kontakt/block.json (Design "Produktuebersicht", Abschnitt
// "Kontakt") -- siehe template-parts/blocks/buehne/render.php's Kopfkommentar fuer die allgemeine
// Phase-3-Block-Konvention. Ueberschrift + Text, darunter links die Firmen-Kontaktkarte (Daten aus
// Einstellungen > Footer, dieselbe Quelle wie der Footer), rechts das Anfrageformular mit PLZ/Ort
// (template-parts/components/inquiry-form.php). Anfragen landen im Menuepunkt "Produktanfragen",
// es wird keine E-Mail versendet.
//
// Ueberschrift/Text als volle Rasterzeile mit `max-w-3xl` statt Teil-`col-span`, damit die
// Kontaktkarte nicht per Grid-Auto-Placement daneben rutscht, sondern links neben dem Formular steht
// (Bugfix 2026-10-07).
//
// `id="kontakt"` ist das Sprungziel der "Kontakt"-Links aus dem Design (Header-Button, Buehne) und
// der Redirect nach dem Absenden -- deshalb nur einmal pro Seite erlaubt (`supports.multiple: false`).
// Ueberschrift/Text sind im Editor direkt in der Canvas editierbar (`RichText`); Karte und Formular
// kommen dort ueber den inserter-versteckten Zwilling kontakt-vorschau -- gleiche Aufteilung wie
// offene-stellen/offene-stellen-vorschau.

if (
    !is_array($attributes ?? null) ||
    !function_exists('hengegroup_theme_render_company_contact_row')
) {
    return;
}

$heading = trim((string) ($attributes['heading'] ?? ''));
$text = trim((string) ($attributes['text'] ?? ''));

echo '<section id="kontakt" class="scroll-mt-24 py-16 md:py-25" data-slot="kontakt">';
echo '<div class="wrapper gap-y-12">';

if ($heading !== '' || $text !== '') {
    echo '<div class="col-span-12 max-w-3xl">';

    if ($heading !== '') {
        get_template_part('template-parts/base/typography', null, [
            'config' => [
                'variant' => 'headline-sm',
                'tag' => 'h2',
                'text' => $heading,
                'class' => 'mb-5 lg:text-[42px]',
            ],
        ]);
    }

    if ($text !== '') {
        get_template_part('template-parts/base/typography', null, [
            'config' => [
                'variant' => 'body-base',
                'text' => $text,
                'class' => 'lg:text-[22px] lg:leading-[1.4]',
            ],
        ]);
    }

    echo '</div>';
}

echo hengegroup_theme_render_company_contact_row(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</div>';
echo '</section>';
