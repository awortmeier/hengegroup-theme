<?php

declare(strict_types=1);

// Block "Anwendungsgruppe": Kicker/Farbe/Ueberschrift/Text aus dem Block (leer = aus der Gruppe),
// Karten = alle Anwendungen der Gruppe. Gruppe ohne Anwendungen rendert nichts. `id` = Gruppen-Slug.

if (
    !is_array($attributes ?? null) ||
    !function_exists('hengegroup_theme_get_anwendung_group_data')
) {
    return;
}

$term = get_term((int) ($attributes['groupId'] ?? 0), HENGEGROUP_THEME_ANWENDUNG_TAXONOMY);

if (!($term instanceof WP_Term) || $term->parent !== 0) {
    return;
}

$group = hengegroup_theme_get_anwendung_group_data($term);
$anwendungen = hengegroup_theme_get_group_anwendungen($group['term_id']);

if ($anwendungen === []) {
    return;
}

$background = hengegroup_theme_get_section_background((string) ($attributes['background'] ?? ''));
$spacing_class = hengegroup_theme_get_section_spacing_class(
    (string) ($attributes['spacing'] ?? ''),
);

printf(
    '<section id="%1$s" class="%2$s" data-slot="anwendungsgruppe" data-background="%3$s">',
    esc_attr($group['slug']),
    esc_attr(trim('scroll-mt-24 ' . $spacing_class . ' ' . $background['class'])),
    esc_attr($background['name']),
);
echo '<div class="wrapper gap-y-10">';
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo hengegroup_theme_render_section_header(
    hengegroup_theme_get_term_section_header_config($group, $attributes) + [
        'color' => $background['color'],
        'class' => 'col-span-12',
    ],
);
echo '<ul class="col-span-12 flex flex-col gap-6">';

foreach ($anwendungen as $anwendung) {
    echo hengegroup_theme_render_anwendung_overview_card($anwendung); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

echo '</ul>';
echo '</div>';
echo '</section>';
