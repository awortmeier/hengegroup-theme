<?php

declare(strict_types=1);

// Rendert template-parts/blocks/offene-stellen/block.json (Design "Karriereseite", Abschnitt
// "Offene Stellen") -- siehe template-parts/blocks/buehne/render.php's Kopfkommentar fuer die
// allgemeine Phase-3-Block-Konvention. Links alle aktiven Stellen gruppiert nach Unternehmen
// (hengegroup_theme_render_jobs_grouped(), inc/template-parts/careers.php), rechts die
// Ansprechpartner-Karte (Standard-Ansprechpartner aus Karriere > Einstellungen). Abgelaufene
// Stellen filtert die gemeinsame Abfrage bereits heraus, der Block braucht dafuer keine eigene
// Logik.
//
// `id="stellen"` ist das Sprungziel des Menuepunkts "Offene Stellen" aus dem Design; der Block ist
// deshalb nur einmal pro Seite erlaubt (`supports.multiple: false`).
//
// Die Ueberschrift ueber der Ansprechpartner-Karte ist im Editor direkt in der Canvas editierbar
// (`RichText`, explizite Nachfrage 2026-10-07); Stellenliste und Karte kommen dort ueber den
// inserter-versteckten Zwilling offene-stellen-vorschau -- gleiche Aufteilung wie
// produkte/produkte-raster, siehe offene-stellen/edit.jsx.

if (!is_array($attributes ?? null) || !function_exists('hengegroup_theme_render_open_jobs_list')) {
    return;
}

$show_contact = !empty($attributes['showContact'] ?? true);
$contact_heading = trim((string) ($attributes['contactHeading'] ?? ''));
$list_markup = hengegroup_theme_render_open_jobs_list();
$contact_markup = $show_contact
    ? hengegroup_theme_render_job_contact_card(hengegroup_theme_get_job_contact(null))
    : '';
$list_span = $contact_markup !== '' ? 'lg:col-span-8' : '';

echo '<section id="stellen" class="py-16 md:py-20 lg:py-25">';
echo '<div class="wrapper items-start gap-y-12">';
printf('<div class="col-span-12 %s">', esc_attr($list_span));
echo $list_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</div>';

if ($contact_markup !== '') {
    echo '<div class="col-span-12 lg:col-span-4">';

    if ($contact_heading !== '') {
        get_template_part('template-parts/base/typography', null, [
            'config' => [
                'variant' => 'headline-xs',
                'tag' => 'h2',
                'text' => $contact_heading,
                'class' => 'mb-5',
            ],
        ]);
    }

    echo $contact_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '</div>';
}

echo '</div>';
echo '</section>';
