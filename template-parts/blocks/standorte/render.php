<?php

declare(strict_types=1);

// Block "Standorte": Ueberschrift + Kind-Bloecke (standort) als <ul>.

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

echo '<section class="section-spacing" data-slot="standorte">';
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

$grid_class = hengegroup_theme_get_tile_grid_class(
    ($attributes['layout'] ?? '') === 'full' ? 'full' : 'grid-4',
    isset($block) && $block instanceof WP_Block ? count($block->inner_blocks) : 0,
    '',
);

echo '<ul class="' . esc_attr('col-span-12 grid gap-6 ' . $grid_class) . '">';
echo $cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</ul>';
echo '</div>';
echo '</section>';
