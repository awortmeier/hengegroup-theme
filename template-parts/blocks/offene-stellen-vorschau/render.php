<?php

declare(strict_types=1);

// Editor-Vorschau von offene-stellen: Stellenliste oder Ansprechpartner-Karte je `part`
// (siehe produkte-raster/render.php).

if (!is_array($attributes ?? null) || !function_exists('hengegroup_theme_render_open_jobs_list')) {
    return;
}

$on_dark = hengegroup_theme_get_section_background((string) ($attributes['background'] ?? ''))[
    'dark'
];

if (($attributes['part'] ?? 'list') === 'contact') {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo hengegroup_theme_render_contact_card(hengegroup_theme_get_open_jobs_contact($attributes), [
        'variant' => $on_dark ? 'default' : 'filled',
    ]);
    return;
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo hengegroup_theme_render_open_jobs_list((string) ($attributes['emptyText'] ?? ''), $on_dark);
