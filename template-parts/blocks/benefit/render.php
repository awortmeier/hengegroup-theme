<?php

declare(strict_types=1);

// Ein Benefit im Block "Benefits" (Kind-Block, nur innerhalb von hengegroup-theme/benefits
// einfuegbar) -- als <li>, weil der Eltern-Block die Benefits in eine <ul> setzt (inhaltlich eine
// Aufzaehlung, Screenreader nennen die Anzahl). Icon-Kachel mit Marken-Verlauf grau -> gruen ->
// blau (wie die Header-Kante), Titel, Text. Icons aus der festen Auswahl
// hengegroup_theme_get_benefit_icons() (inc/template-parts/careers.php), unbekannte Schluessel
// fallen auf das erste Icon zurueck. Ohne Titel wird nichts ausgegeben (leere, frisch eingefuegte
// Karte).

if (!is_array($attributes ?? null) || !function_exists('hengegroup_theme_get_benefit_icons')) {
    return;
}

$title = trim((string) ($attributes['title'] ?? ''));

if ($title === '') {
    return;
}

$text = trim((string) ($attributes['text'] ?? ''));

printf(
    '<li class="flex items-start gap-4"><span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-linear-135 from-henge-grey via-henge-green to-henge-blue">%1$s</span><div><h3 class="mb-1.5 text-lg font-bold text-grey-light">%2$s</h3>%3$s</div></li>',
    hengegroup_theme_render_benefit_icon((string) ($attributes['icon'] ?? '')), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($title),
    $text !== ''
        ? '<p class="text-base leading-normal text-grey-light/85">' . esc_html($text) . '</p>'
        : '',
);
