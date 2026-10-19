<?php

declare(strict_types=1);

// Editor-Vorschau der Stellenliste von karriere-teaser (siehe produkte-raster/render.php).

if (!is_array($attributes ?? null) || !function_exists('hengegroup_theme_render_job_teaser_list')) {
    return;
}

$list_markup = hengegroup_theme_render_job_teaser_list(
    (int) ($attributes['limit'] ?? 4),
    (int) ($attributes['companyId'] ?? 0),
);

if ($list_markup !== '') {
    echo $list_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    return;
}

$empty_text = trim((string) ($attributes['emptyText'] ?? ''));

if ($empty_text !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => 'body-base',
            'color' => hengegroup_theme_get_section_background(
                (string) ($attributes['background'] ?? ''),
            )['dark']
                ? 'light'
                : 'neutral',
            'text' => $empty_text,
        ],
    ]);
}
