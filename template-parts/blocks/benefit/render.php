<?php

declare(strict_types=1);

// Benefit im Block "Benefits" (<li>) als Karte. Ohne eigene Variante gilt die fruehere Eltern-
// `variant` (cards = Karte, sonst transparent). Unbekannte Icons fallen auf das erste zurueck. Ohne
// Titel keine Ausgabe.

if (!is_array($attributes ?? null) || !function_exists('hengegroup_theme_get_benefit_icons')) {
    return;
}

$title = trim((string) ($attributes['title'] ?? ''));

if ($title === '') {
    return;
}

$block = isset($block) && $block instanceof WP_Block ? $block : null;
$text = trim((string) ($attributes['text'] ?? ''));
$card = hengegroup_theme_get_block_card_config(
    $attributes,
    $block,
    [],
    ($block?->context['hengegroup-theme/benefitsVariant'] ?? 'list') === 'cards'
        ? 'default'
        : 'ghost',
);

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo hengegroup_theme_render_card(
    [
        'title' => $title,
        'title_variant' => 'body-base',
        'icon' => hengegroup_theme_render_benefit_icon((string) ($attributes['icon'] ?? '')),
        'icon_class' => 'bg-linear-135 brand-gradient text-grey-light',
        'content' =>
            $text !== ''
                ? hengegroup_theme_render_typography([
                    'variant' => 'body-sm',
                    'text' => $text,
                    'class' => 'text-pretty' . ($card['variant'] === 'ghost' ? ' opacity-85' : ''),
                ])
                : '',
    ] + $card,
);
