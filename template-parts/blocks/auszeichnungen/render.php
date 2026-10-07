<?php

declare(strict_types=1);

// Rendert template-parts/blocks/auszeichnungen/block.json (Design "Karriereseite", Abschnitt
// "Auszeichnungen & Mitgliedschaften") -- siehe template-parts/blocks/buehne/render.php's
// Kopfkommentar fuer die allgemeine Phase-3-Block-Konvention. Hellgraue Flaeche, Ueberschrift,
// darunter die Karten als Kind-Bloecke (`hengegroup-theme/auszeichnung`, InnerBlocks), die WordPress
// bereits fertig gerendert in `$content` uebergibt -- je Karte ein <li>, siehe
// auszeichnung/render.php. Karten werden im Editor direkt im Block hinzugefuegt/verschoben/
// bearbeitet (explizite Nachfrage 2026-10-07), siehe auszeichnungen/edit.jsx.

if (!is_array($attributes ?? null)) {
    return;
}

$cards = trim((string) ($content ?? ''));

if ($cards === '') {
    return;
}

$heading = trim((string) ($attributes['heading'] ?? ''));
$heading_tag = strtolower(trim((string) ($attributes['headingTag'] ?? 'h2')));

if (!in_array($heading_tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'], true)) {
    $heading_tag = 'h2';
}

echo '<section class="bg-neutral-200 py-16 md:py-24 lg:py-25">';
echo '<div class="wrapper gap-y-10">';

if ($heading !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'text' => $heading,
            'variant' => 'headline-xs',
            'tag' => $heading_tag,
            'class' => 'col-span-12',
        ],
    ]);
}

echo '<ul class="col-span-12 grid gap-8 md:grid-cols-2">';
echo $cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</ul>';
echo '</div>';
echo '</section>';
