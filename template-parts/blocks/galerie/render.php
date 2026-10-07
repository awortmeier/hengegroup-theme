<?php

declare(strict_types=1);

// Rendert template-parts/blocks/galerie/block.json (Design "Karriereseite", Abschnitt "Galerie")
// -- siehe template-parts/blocks/buehne/render.php's Kopfkommentar fuer die allgemeine
// Phase-3-Block-Konvention. Bento-Raster (explizite Vorgabe 2026-10-07): Kacheln in 1x1, 2x1, 1x2
// und 3x1, die bei jeder Bildanzahl buendig als Rechteck abschliessen -- die Formen liefert
// hengegroup_theme_get_bento_tile_shapes() (inc/template-parts/helpers.php), `grid-flow-dense`
// setzt sie luckenlos. Bilder werden per `object-cover` auf ihre Kachel zugeschnitten. Mobil eine
// Spalte im 4:3-Format. Alternativtexte kommen aus der Mediathek, damit sie an einer Stelle
// gepflegt werden.

if (!is_array($attributes ?? null)) {
    return;
}

$heading = trim((string) ($attributes['heading'] ?? ''));
$heading_tag = strtolower(trim((string) ($attributes['headingTag'] ?? 'h2')));

if (!in_array($heading_tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'], true)) {
    $heading_tag = 'h2';
}

$image_ids = array_values(
    array_filter(
        array_map('intval', (array) ($attributes['imageIds'] ?? [])),
        static fn(int $id): bool => $id > 0 && wp_attachment_is_image($id),
    ),
);

if ($image_ids === []) {
    return;
}

echo '<section class="py-16 md:py-24 lg:py-25">';
echo '<div class="wrapper gap-y-10">';

if ($heading !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'text' => $heading,
            'variant' => 'headline-sm',
            'tag' => $heading_tag,
            'class' => 'col-span-12',
        ],
    ]);
}

echo '<ul class="col-span-12 grid grid-cols-1 gap-5 sm:grid-flow-dense sm:grid-cols-3">';

// Literale Klassen je Form (Tailwind muss sie im Quelltext finden), identisch zu
// assets/js/blocks/galerie/layout.js's TILE_CLASSNAMES (Editor-Vorschau).
$tile_classes = [
    'square' => 'aspect-[4/3] sm:aspect-square',
    'wide' => 'aspect-[4/3] sm:col-span-2 sm:aspect-[2/1]',
    'tall' => 'aspect-[4/3] sm:row-span-2 sm:aspect-auto',
    'full' => 'aspect-[4/3] sm:col-span-3 sm:aspect-[3/1]',
];

foreach (hengegroup_theme_get_bento_tile_shapes(count($image_ids)) as $index => $shape) {
    $image_id = $image_ids[$index];
    $alt = trim((string) get_post_meta($image_id, '_wp_attachment_image_alt', true));
    $image_markup = hengegroup_theme_render_image([
        'attachment_id' => $image_id,
        'size' => 'large',
        'alt' => $alt,
        'decorative' => $alt === '',
        'loading' => 'lazy',
        'class' => 'size-full rounded-2xl object-cover',
    ]);

    printf(
        '<li class="%1$s">%2$s</li>',
        esc_attr($tile_classes[$shape]),
        $image_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

echo '</ul>';
echo '</div>';
echo '</section>';
