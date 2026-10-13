<?php

declare(strict_types=1);

// Benefit im Block "Benefits" (<li>), als Listeneintrag oder Karte je nach Eltern-`variant`.
// Unbekannte Icons fallen auf das erste zurueck. Ohne Titel keine Ausgabe.

if (!is_array($attributes ?? null) || !function_exists('hengegroup_theme_get_benefit_icons')) {
    return;
}

$title = trim((string) ($attributes['title'] ?? ''));

if ($title === '') {
    return;
}

$text = trim((string) ($attributes['text'] ?? ''));
$icon_markup = hengegroup_theme_render_benefit_icon((string) ($attributes['icon'] ?? ''));

if (($block->context['hengegroup-theme/benefitsVariant'] ?? 'list') === 'cards') {
    get_template_part('template-parts/base/card', null, [
        'config' => [
            'tag' => 'li',
            'elevation' => 'raised',
            'size' => 'lg',
            'title' => $title,
            'title_variant' => 'body-base',
            'icon' => $icon_markup,
            'icon_class' => 'bg-linear-135 brand-gradient',
            'content' =>
                $text !== ''
                    ? hengegroup_theme_render_typography([
                        'variant' => 'body-sm',
                        'text' => $text,
                        'class' => 'text-pretty',
                    ])
                    : '',
        ],
    ]);

    return;
}

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
    '<li class="flex items-start gap-4"><span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-linear-135 brand-gradient">%1$s</span><div>%2$s%3$s</div></li>',
    $icon_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $title_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $text_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
