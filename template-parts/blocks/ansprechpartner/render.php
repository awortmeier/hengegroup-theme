<?php

declare(strict_types=1);

// Block "Ansprechpartner": Ueberschrift + Kind-Bloecke (ansprechpartner-person) als <ul>.

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

echo '<section class="bg-neutral-200 section-spacing">';
echo '<div class="wrapper gap-y-10">';

if ($heading !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'text' => $heading,
            'accent_markup' => true,
            'variant' => 'headline-sm',
            'tag' => $heading_tag,
            'class' => 'col-span-12',
        ],
    ]);
}

$layout = (string) ($attributes['layout'] ?? 'grid-4');
$grid_class = hengegroup_theme_get_tile_grid_class(
    $layout === 'full' ? 'full' : 'grid-4',
    isset($block) && $block instanceof WP_Block ? count($block->inner_blocks) : 0,
    '',
);

// Karten mit Porträt sind breiter, daher im 4er-Raster dann nur zwei Spalten.
if ($layout !== 'full') {
    $grid_class .= ' lg:has-[[data-slot=card-media]]:grid-cols-2';
}

echo '<ul class="' . esc_attr('col-span-12 grid gap-6 ' . $grid_class) . '">';
echo $cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</ul>';
echo '</div>';
echo '</section>';
