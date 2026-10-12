<?php

declare(strict_types=1);

// Karte im Block "Auszeichnungen" (<li>). Fremde Links oeffnen in neuem Tab. Ohne Titel keine Ausgabe.

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

ob_start();
echo '<div class="flex items-start gap-6">';

if ($image_id > 0) {
    $image_markup = hengegroup_theme_render_image([
        'attachment_id' => $image_id,
        'size' => 'thumbnail',
        'alt' => $image_alt,
        'decorative' => $image_alt === '',
        'class' => 'size-19 shrink-0 object-cover ' . $image_shape,
    ]);

    printf(
        '%s',
        $image_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

echo '<div class="flex min-w-0 flex-1 flex-col">';
get_template_part('template-parts/base/typography', null, [
    'config' => [
        'variant' => 'body-base',
        'tag' => 'h3',
        'text' => $title,
        'class' => 'mb-2 font-bold',
    ],
]);

if ($text !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => 'body-sm',
            'text' => $text,
            'class' => 'mb-4',
        ],
    ]);
}

if ($button_text !== '' && $button_url !== '') {
    $link_host = (string) wp_parse_url($button_url, PHP_URL_HOST);
    $is_external =
        $link_host !== '' && $link_host !== (string) wp_parse_url(home_url('/'), PHP_URL_HOST);

    get_template_part('template-parts/base/button', null, [
        'config' => [
            'text' => $button_text,
            'href' => $button_url,
            'variant' => 'outline',
            'size' => 'base',
            'class' => 'self-end',
            'attributes' => $is_external ? ['target' => '_blank', 'rel' => 'noopener'] : [],
        ],
    ]);
}

echo '</div>';
echo '</div>';

// Das Logo ist kein Cover-Bild, darum steht die ganze Zeile als `content` in card.php.
get_template_part('template-parts/base/card', null, [
    'config' => [
        'tag' => 'li',
        'elevation' => 'raised',
        'size' => 'lg',
        'content' => (string) ob_get_clean(),
    ],
]);
