<?php

declare(strict_types=1);

// Editor-Vorschau von offene-stellen: Stellenliste oder Ansprechpartner-Karte je `part`
// (siehe produkte-raster/render.php).

if (!is_array($attributes ?? null) || !function_exists('hengegroup_theme_render_open_jobs_list')) {
    return;
}

if (($attributes['part'] ?? 'list') === 'contact') {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo hengegroup_theme_render_contact_card(hengegroup_theme_get_job_contact(null), [
        'variant' => 'dark',
    ]);
    return;
}

echo hengegroup_theme_render_open_jobs_list(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
