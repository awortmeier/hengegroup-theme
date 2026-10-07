<?php

declare(strict_types=1);

// Editor-Vorschau-Zwilling von template-parts/blocks/offene-stellen: rendert je nach `part` nur die
// Stellenliste oder nur die Ansprechpartner-Karte -- die Ueberschrift dazwischen ist im Editor
// natives `RichText` (siehe offene-stellen/edit.jsx). Gleiche Begruendung wie
// produkte-raster/render.php: ein `ServerSideRender` gegen den ganzen Block wuerde die Ueberschrift
// doppelt zeigen. Kein eigenes Editor-Bundle, offene-stellen/edit.jsx registriert ihn clientseitig mit.

if (!is_array($attributes ?? null) || !function_exists('hengegroup_theme_render_open_jobs_list')) {
    return;
}

if (($attributes['part'] ?? 'list') === 'contact') {
    echo hengegroup_theme_render_job_contact_card(hengegroup_theme_get_job_contact(null)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    return;
}

echo hengegroup_theme_render_open_jobs_list(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
