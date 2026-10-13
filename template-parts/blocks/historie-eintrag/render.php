<?php

declare(strict_types=1);

// Eintrag im Block "Historie" (<li>): Jahr | Punkt in Akzentfarbe | Karte. Ohne Titel keine Ausgabe.

if (!is_array($attributes ?? null)) {
    return;
}

$title = trim((string) ($attributes['title'] ?? ''));

if ($title === '') {
    return;
}

$year = trim((string) ($attributes['year'] ?? ''));
$text = trim((string) ($attributes['text'] ?? ''));
$accent = hengegroup_theme_get_accent_classes((string) ($attributes['accent'] ?? ''));

echo '<li class="grid grid-cols-[4.5rem_2rem_minmax(0,1fr)] items-start md:grid-cols-[6.875rem_2.75rem_minmax(0,1fr)]">';
printf(
    '<span class="pt-5 text-right font-accent text-xl md:text-[1.625rem]">%s</span>',
    esc_html($year),
);
printf(
    '<span class="flex justify-center pt-7.5" aria-hidden="true"><span class="relative z-1 size-3.5 rounded-full border-3 border-grey-light %s"></span></span>',
    esc_attr($accent['bg']),
);
get_template_part('template-parts/base/card', null, [
    'config' => [
        'class' => '!gap-2',
        'title' => $title,
        'title_variant' => 'body-base',
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
echo '</li>';
