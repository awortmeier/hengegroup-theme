<?php

declare(strict_types=1);

// Karte im Block "Auszeichnungen" (<li>), Siegel/Logo als Leading-Visual. Mit Button-Text ist nur
// der Button der Link, sonst die ganze Karte. Ohne Titel keine Ausgabe.

if (!is_array($attributes ?? null)) {
    return;
}

$title = trim((string) ($attributes['title'] ?? ''));

if ($title === '') {
    return;
}

$image_id = (int) ($attributes['imageId'] ?? 0);
$image_alt = trim((string) ($attributes['imageAlt'] ?? ''));
$image_shape =
    ($attributes['imageShape'] ?? 'circle') === 'rounded' ? 'rounded-xl' : 'rounded-full';
$text = trim((string) ($attributes['text'] ?? ''));
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
        'href' => $button_url,
        'link_attributes' => $link_attributes,
        'leading' =>
            $image_id > 0
                ? hengegroup_theme_render_image([
                    'attachment_id' => $image_id,
                    'size' => 'thumbnail',
                    'alt' => $image_alt,
                    'decorative' => $image_alt === '',
                    'class' => 'size-19 shrink-0 object-cover ' . $image_shape,
                ])
                : '',
        'title' => $title,
        'title_variant' => 'body-base',
        'content' =>
            $text !== ''
                ? hengegroup_theme_render_typography(['variant' => 'body-sm', 'text' => $text])
                : '',
        'footer' => $footer,
    ] + $card,
);
