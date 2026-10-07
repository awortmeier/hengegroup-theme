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

if (!is_array($attributes ?? null) || !function_exists('hengegroup_theme_render_jobs_grouped')) {
    return;
}

$show_contact = !empty($attributes['showContact'] ?? true);
$contact_heading = trim((string) ($attributes['contactHeading'] ?? ''));
$contact = hengegroup_theme_get_job_contact(null);
$groups_markup = hengegroup_theme_render_jobs_grouped();
$contact_markup = $show_contact ? hengegroup_theme_render_job_contact_card($contact) : '';
$list_span = $contact_markup !== '' ? 'lg:col-span-8' : '';

echo '<section id="stellen" class="bg-grey-light py-16 md:py-20 lg:py-25">';
echo '<div class="wrapper items-start gap-y-12">';
printf('<div class="col-span-12 %s">', esc_attr($list_span));

if ($groups_markup !== '') {
    echo $groups_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} else {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => 'body-base',
            'text' =>
                $contact['email'] !== ''
                    ? sprintf(
                        /* translators: %s: application e-mail address. */
                        __(
                            'Aktuell sind keine Stellen ausgeschrieben. Initiativbewerbungen sind jederzeit willkommen: %s',
                            'hengegroup-theme',
                        ),
                        $contact['email'],
                    )
                    : __('Aktuell sind keine Stellen ausgeschrieben.', 'hengegroup-theme'),
        ],
    ]);
}

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
