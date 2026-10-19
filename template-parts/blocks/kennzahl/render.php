<?php

declare(strict_types=1);

// Kennzahl im Block "Kennzahlen" (<li>): Linie in Akzentfarbe, Wert, Beschriftung. Ohne Wert keine
// Ausgabe.

if (!is_array($attributes ?? null)) {
    return;
}

$value = trim((string) ($attributes['value'] ?? ''));

if ($value === '') {
    return;
}

$label = trim((string) ($attributes['label'] ?? ''));
$accent = hengegroup_theme_get_accent_classes((string) ($attributes['accent'] ?? ''));

printf('<li class="border-t-2 pt-5 %s">', esc_attr($accent['border']));
get_template_part('template-parts/base/typography', null, [
    'config' => [
        'text' => $value,
        'variant' => 'headline-base',
        'tag' => 'p',
        'class' => '!leading-none tabular-nums',
    ],
]);

if ($label !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'text' => $label,
            'variant' => 'body-sm',
            'class' => 'mt-2.5',
        ],
    ]);
}

echo '</li>';
