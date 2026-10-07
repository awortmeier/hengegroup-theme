<?php

declare(strict_types=1);

// Rendert template-parts/blocks/benefits/block.json (Design "Karriereseite", Abschnitt "Benefits")
// -- siehe template-parts/blocks/buehne/render.php's Kopfkommentar fuer die allgemeine
// Phase-3-Block-Konvention. Dunkle Flaeche mit zwei weichen Lichtflecken (radiale Verlaeufe als
// Tailwind-Arbitrary-Value, 1:1 aus dem Design) und Innenschatten oben/unten; darauf Ueberschrift,
// Einleitung und das Raster der Kind-Bloecke (`hengegroup-theme/benefit`, InnerBlocks, fertig
// gerendert in `$content`, je Benefit ein <li> -- siehe benefit/render.php). Benefits werden im
// Editor direkt im Block hinzugefuegt/verschoben/bearbeitet (explizite Nachfrage 2026-10-07).

if (!is_array($attributes ?? null)) {
    return;
}

$heading = trim((string) ($attributes['heading'] ?? ''));
$heading_tag = strtolower(trim((string) ($attributes['headingTag'] ?? 'h2')));

if (!in_array($heading_tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'], true)) {
    $heading_tag = 'h2';
}

$text = trim((string) ($attributes['text'] ?? ''));
$items = trim((string) ($content ?? ''));

if ($items === '' && $heading === '') {
    return;
}

echo '<section class="bg-grey-dark bg-[radial-gradient(ellipse_60%_50%_at_20%_20%,rgba(255,255,255,0.08),transparent_60%),radial-gradient(ellipse_50%_40%_at_80%_70%,rgba(255,255,255,0.05),transparent_60%)] py-16 shadow-[inset_0_40px_40px_-40px_rgba(0,0,0,0.5),inset_0_-40px_40px_-40px_rgba(0,0,0,0.5)] md:py-24 lg:py-25">';
echo '<div class="wrapper gap-y-12">';

if ($heading !== '' || $text !== '') {
    echo '<div class="col-span-12 max-w-3xl">';

    if ($heading !== '') {
        get_template_part('template-parts/base/typography', null, [
            'config' => [
                'text' => $heading,
                'variant' => 'headline-sm',
                'tag' => $heading_tag,
                'color' => 'light',
                'class' => 'mb-5',
            ],
        ]);
    }

    if ($text !== '') {
        get_template_part('template-parts/base/typography', null, [
            'config' => [
                'text' => $text,
                'variant' => 'body-base',
                'color' => 'light',
            ],
        ]);
    }

    echo '</div>';
}

if ($items !== '') {
    echo '<ul class="col-span-12 grid gap-7 sm:grid-cols-2 lg:grid-cols-3">';
    echo $items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '</ul>';
}

echo '</div>';
echo '</section>';
