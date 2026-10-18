<?php

declare(strict_types=1);

// Block "Auszeichnungen": Ueberschrift + Kind-Bloecke (auszeichnung) als <ul>.

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
            'variant' => 'headline-xs',
            'tag' => $heading_tag,
            'class' => 'col-span-12',
        ],
    ]);
}

$grid_class = hengegroup_theme_get_tile_grid_class(
    (string) ($attributes['layout'] ?? ''),
    isset($block) && $block instanceof WP_Block ? count($block->inner_blocks) : 0,
    'md:grid-cols-2',
);

echo '<ul class="' . esc_attr('col-span-12 grid gap-8 ' . $grid_class) . '">';
echo $cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</ul>';
echo '</div>';
echo '</section>';
