<?php

declare(strict_types=1);

// Block "Benefits": dunkle Flaeche, Ueberschrift, Einleitung, Kind-Bloecke (benefit) als <ul>.
// `variant` (list | cards) ist nur noch Fallback fuer Benefits ohne eigene Variante (Block-Context).

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

$anchor = trim((string) ($attributes['anchor'] ?? ''));

printf(
    '<section%s class="scroll-mt-24 section-dark section-spacing">',
    $anchor !== '' ? ' id="' . esc_attr($anchor) . '"' : '',
);
echo '<div class="wrapper gap-y-12">';

if ($heading !== '' || $text !== '') {
    echo '<div class="col-span-12 max-w-3xl">';

    if ($heading !== '') {
        get_template_part('template-parts/base/typography', null, [
            'config' => [
                'text' => $heading,
                'accent_markup' => true,
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
    $grid_class = hengegroup_theme_get_tile_grid_class(
        (string) ($attributes['layout'] ?? ''),
        isset($block) && $block instanceof WP_Block ? count($block->inner_blocks) : 0,
        'sm:grid-cols-2 lg:grid-cols-3',
    );

    printf('<ul class="%s">', esc_attr('col-span-12 grid gap-6 text-grey-light ' . $grid_class));
    echo $items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '</ul>';
}

echo '</div>';
echo '</section>';
