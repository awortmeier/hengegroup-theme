<?php

declare(strict_types=1);

// Karte im Block "Unternehmen der Gruppe" (<li>): weiss mit Akzentlinie links oder einfarbig, Logo
// als Leading-Visual. Mit Button-Text ist nur der Button der Link, sonst die ganze Karte. Der
// Alternativtext des Logos kommt aus der Mediathek. Ohne Logo und Text keine Ausgabe.

if (!is_array($attributes ?? null)) {
    return;
}

$logo_id = (int) ($attributes['logoId'] ?? 0);
$text = trim((string) ($attributes['text'] ?? ''));

if ($logo_id <= 0 && $text === '') {
    return;
}

$button_text = trim((string) ($attributes['buttonText'] ?? ''));
$button_url = trim((string) ($attributes['buttonUrl'] ?? ''));
$card = hengegroup_theme_get_block_card_config($attributes, $block ?? null);
$link_attributes = hengegroup_theme_get_link_attributes($button_url);
$footer = '';

if ($button_text !== '' && $button_url !== '') {
    ob_start();
    get_template_part('template-parts/base/button', null, [
        'config' => [
            'text' => $button_text,
            'href' => $button_url,
            'variant' => hengegroup_theme_get_card_button_variant($card['variant']),
            'class' => 'ml-auto',
            'attributes' => $link_attributes,
        ],
    ]);
    $footer = (string) ob_get_clean();
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo hengegroup_theme_render_card(
    [
        'accent_border' => $card['variant'] === 'default',
        'href' => $button_url,
        'link_attributes' => $link_attributes,
        'leading' =>
            $logo_id > 0
                ? '<div class="flex h-14 items-center">' .
                    hengegroup_theme_render_image([
                        'attachment_id' => $logo_id,
                        'size' => 'medium',
                        'loading' => 'lazy',
                        'class' => 'h-auto max-h-13 w-auto max-w-55',
                    ]) .
                    '</div>'
                : '',
        'content' =>
            $text !== ''
                ? hengegroup_theme_render_typography([
                    'variant' => 'body-sm',
                    'text' => $text,
                    'class' => 'text-pretty',
                ])
                : '',
        'footer' => $footer,
    ] + $card,
);
