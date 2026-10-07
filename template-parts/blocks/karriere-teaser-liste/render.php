<?php

declare(strict_types=1);

// Editor-Vorschau-Zwilling von template-parts/blocks/karriere-teaser (nur die Stellenliste) --
// gleiche Aufteilung und Begruendung wie produkte-raster/render.php: Ueberschrift/Text/Button
// sind im Editor natives `RichText`, ein `ServerSideRender` gegen den ganzen Teaser-Block wuerde
// sie doppelt zeigen. Kein eigenes Editor-Bundle, karriere-teaser/edit.jsx registriert diesen
// Block clientseitig mit.

if (!is_array($attributes ?? null) || !function_exists('hengegroup_theme_render_job_teaser_list')) {
    return;
}

$list_markup = hengegroup_theme_render_job_teaser_list((int) ($attributes['limit'] ?? 4));

if ($list_markup !== '') {
    echo $list_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    return;
}

get_template_part('template-parts/base/typography', null, [
    'config' => [
        'variant' => 'body-base',
        'color' => 'neutral',
        'text' => __('Aktuell sind keine Stellen ausgeschrieben.', 'hengegroup-theme'),
    ],
]);
