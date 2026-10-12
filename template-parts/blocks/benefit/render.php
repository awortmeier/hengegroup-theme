<?php

declare(strict_types=1);

// Benefit im Block "Benefits" (<li>). Unbekannte Icons fallen auf das erste zurueck. Ohne Titel
// keine Ausgabe.

if (!is_array($attributes ?? null) || !function_exists('hengegroup_theme_get_benefit_icons')) {
    return;
}

$title = trim((string) ($attributes['title'] ?? ''));

if ($title === '') {
    return;
}

$text = trim((string) ($attributes['text'] ?? ''));

$title_markup = hengegroup_theme_render_typography([
    'variant' => 'body-base',
    'tag' => 'h3',
    'text' => $title,
    'color' => 'light',
    'class' => 'mb-1.5 font-bold',
]);
$text_markup =
    $text !== ''
        ? hengegroup_theme_render_typography([
            'variant' => 'body-sm',
            'text' => $text,
            'class' => 'text-grey-light/85',
        ])
        : '';

printf(
    '<li class="flex items-start gap-4"><span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-linear-135 from-henge-grey via-henge-green to-henge-blue">%1$s</span><div>%2$s%3$s</div></li>',
    hengegroup_theme_render_benefit_icon((string) ($attributes['icon'] ?? '')), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $title_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $text_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
