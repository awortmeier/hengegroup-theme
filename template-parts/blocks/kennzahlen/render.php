<?php

declare(strict_types=1);

// Block "Kennzahlen": dunkle Flaeche, Ueberschrift + Kind-Bloecke (kennzahl) als <ul>. Das Raster
// fuellt sich je nach Anzahl selbst (auto-fit).

if (!is_array($attributes ?? null)) {
    return;
}

$items = trim((string) ($content ?? ''));

if ($items === '') {
    return;
}

$heading = trim((string) ($attributes['heading'] ?? ''));
$heading_tag = strtolower(trim((string) ($attributes['headingTag'] ?? 'h2')));

if (!in_array($heading_tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'], true)) {
    $heading_tag = 'h2';
}

echo '<section class="section-dark section-spacing">';
echo '<div class="wrapper gap-y-12">';

if ($heading !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'text' => $heading,
            'accent_markup' => true,
            'variant' => 'headline-sm',
            'tag' => $heading_tag,
            'color' => 'light',
            'class' => 'col-span-12',
        ],
    ]);
}

echo '<ul class="col-span-12 grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-6">';
echo $items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</ul>';
echo '</div>';
echo '</section>';
