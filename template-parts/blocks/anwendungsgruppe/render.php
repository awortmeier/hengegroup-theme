<?php

declare(strict_types=1);

// Block "Anwendungsgruppe": Kicker/Farbe/Ueberschrift aus der gewaehlten Gruppe, Karten = alle
// Anwendungen der Gruppe. Gruppe ohne Anwendungen rendert nichts. `id` = Gruppen-Slug.

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

$background = ($attributes['background'] ?? 'light') === 'muted' ? 'bg-neutral-200' : '';

printf(
    '<section id="%1$s" class="%2$s" data-slot="anwendungsgruppe">',
    esc_attr($group['slug']),
    esc_attr(trim('scroll-mt-24 section-spacing ' . $background)),
);
echo '<div class="wrapper gap-y-6">';
get_template_part('template-parts/components/section-intro', null, [
    'config' => [
        'kicker' => $group['kicker'],
        'kicker_variant' => $group['variant'],
        'heading' => $group['heading'],
        'text' => $group['description'],
        'class' => 'col-span-12 mb-4',
    ],
]);
echo '<ul class="col-span-12 flex flex-col gap-6">';

foreach ($anwendungen as $anwendung) {
    echo hengegroup_theme_render_anwendung_overview_card($anwendung); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

echo '</ul>';
echo '</div>';
echo '</section>';
