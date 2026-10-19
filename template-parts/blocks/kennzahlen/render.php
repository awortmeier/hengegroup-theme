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

$background = hengegroup_theme_get_section_background((string) ($attributes['background'] ?? ''));
$spacing_class = hengegroup_theme_get_section_spacing_class(
    (string) ($attributes['spacing'] ?? ''),
);

printf(
    '<section class="%1$s" data-background="%2$s">',
    esc_attr(trim($spacing_class . ' ' . $background['class'])),
    esc_attr($background['name']),
);
echo '<div class="wrapper gap-y-10">';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo hengegroup_theme_render_section_header([
    'title' => $heading,
    'title_tag' => $heading_tag,
    'color' => $background['color'],
    'class' => 'col-span-12',
]);

echo '<ul class="col-span-12 grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-6">';
echo $items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</ul>';
echo '</div>';
echo '</section>';
