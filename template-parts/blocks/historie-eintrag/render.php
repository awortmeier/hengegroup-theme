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
$block = isset($block) && $block instanceof WP_Block ? $block : null;
$accent = hengegroup_theme_get_accent_classes((string) ($attributes['accent'] ?? ''));
// Der Rand des Punkts hat die Sektionsfarbe und trennt ihn so von der Linie.
$ring_classes = [
    'light' => 'border-grey-light',
    'muted' => 'border-neutral-200',
    'dark' => 'border-grey-dark',
];
$ring_class =
    $ring_classes[
        hengegroup_theme_get_section_background(
            (string) ($block?->context['hengegroup-theme/historie-background'] ?? ''),
        )['name']
    ];

echo '<li class="grid grid-cols-[4.5rem_2rem_minmax(0,1fr)] items-start md:grid-cols-[6.875rem_2.75rem_minmax(0,1fr)]">';
printf(
    '<span class="pt-5 text-right font-accent text-xl md:text-[1.625rem]">%s</span>',
    esc_html($year),
);
printf(
    '<span class="flex justify-center pt-7.5" aria-hidden="true"><span class="relative z-1 size-3.5 rounded-full border-3 %s"></span></span>',
    esc_attr($ring_class . ' ' . $accent['bg']),
);
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo hengegroup_theme_render_card(
    hengegroup_theme_get_block_card_config($attributes, $block, [
        // `tag`: the <li> is the timeline row; `accent` colours its dot, not the card.
        'tag' => 'div',
        'accent' => '',
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
    ]),
);
echo '</li>';
