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
$background = hengegroup_theme_get_section_background((string) ($attributes['background'] ?? ''));
$spacing_class = hengegroup_theme_get_section_spacing_class(
    (string) ($attributes['spacing'] ?? ''),
);

printf(
    '<section%1$s class="%2$s" data-background="%3$s">',
    $anchor !== '' ? ' id="' . esc_attr($anchor) . '"' : '',
    esc_attr(trim('scroll-mt-24 ' . $spacing_class . ' ' . $background['class'])),
    esc_attr($background['name']),
);
echo '<div class="wrapper gap-y-10">';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo hengegroup_theme_render_section_header([
    'title' => $heading,
    'title_tag' => $heading_tag,
    'description' => $text,
    'color' => $background['color'],
    'class' => 'col-span-12 max-w-3xl',
]);

if ($items !== '') {
    $grid_class = hengegroup_theme_get_tile_grid_class(
        (string) ($attributes['layout'] ?? ''),
        isset($block) && $block instanceof WP_Block ? count($block->inner_blocks) : 0,
        'sm:grid-cols-2 lg:grid-cols-3',
    );

    printf('<ul class="%s">', esc_attr('col-span-12 grid gap-6 ' . $grid_class));
    echo $items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '</ul>';
}

echo '</div>';
echo '</section>';
