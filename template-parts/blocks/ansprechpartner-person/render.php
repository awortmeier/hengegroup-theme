<?php

declare(strict_types=1);

// Karte im Block "Ansprechpartner" (<li>); Akzentfarbe eigene oder per Block-Context vom Eltern-Block.

if (!is_array($attributes ?? null)) {
    return;
}

$card = hengegroup_theme_render_contact_card(
    array_map(
        static fn($value): string => trim((string) $value),
        array_intersect_key($attributes, array_flip(['name', 'role', 'email', 'phone', 'fax'])),
    ),
    hengegroup_theme_get_block_card_config($attributes, $block ?? null, [
        'lower' => 'contact',
        'accent' => hengegroup_theme_get_child_card_accent(
            $attributes,
            $block ?? null,
            'hengegroup-theme/ansprechpartner-accent',
        ),
        'image' => (int) ($attributes['imageId'] ?? 0),
        'class' => 'h-full',
    ]),
);

echo $card; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
