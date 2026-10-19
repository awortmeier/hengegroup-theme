<?php

declare(strict_types=1);

// Block "Historie": Ueberschrift + Zeitstrahl aus Kind-Bloecken (historie-eintrag) als <ol>. Die
// Linie sitzt mittig in der Punkt-Spalte der Eintraege (Spaltenbreiten in historie-eintrag).

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
echo '<div class="wrapper">';
echo '<div class="col-span-12 flex flex-col gap-10 xl:col-span-10 xl:col-start-2">';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo hengegroup_theme_render_section_header([
    'title' => $heading,
    'title_tag' => $heading_tag,
    'color' => $background['color'],
]);

echo '<ol class="relative flex flex-col gap-5 before:absolute before:inset-y-2 before:left-[calc(5.5rem-1px)] before:w-0.5 before:bg-linear-to-b before:brand-gradient md:before:left-[calc(8.25rem-1px)]">';
echo $items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</ol>';
echo '</div>';
echo '</div>';
echo '</section>';
