<?php

declare(strict_types=1);

// Karte im Block "Standorte" (<li>) als Kontaktkarte (split oder einfarbig); Akzentfarbe eigene oder per Block-Context.

if (!is_array($attributes ?? null)) {
    return;
}

$card = hengegroup_theme_render_contact_card(
    [
        'name' => trim((string) ($attributes['title'] ?? '')),
        'address' => (string) ($attributes['address'] ?? ''),
    ],
    hengegroup_theme_get_block_card_config($attributes, $block ?? null, [
        'lower' => 'hours',
        'accent' => hengegroup_theme_get_child_card_accent(
            $attributes,
            $block ?? null,
            'hengegroup-theme/standorte-accent',
        ),
        'hours' => [
            'label' => trim((string) ($attributes['hoursLabel'] ?? '')),
            'lines' => explode("\n", (string) ($attributes['hours'] ?? '')),
        ],
        'class' => 'h-full',
    ]),
);

echo $card; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
