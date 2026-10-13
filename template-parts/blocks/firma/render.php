<?php

declare(strict_types=1);

// Karte im Block "Unternehmen der Gruppe" (<li>) mit Akzentlinie links. Der Alternativtext des Logos
// kommt aus der Mediathek. Fremde Links oeffnen in neuem Tab. Ohne Logo und Text keine Ausgabe.

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
$accent = hengegroup_theme_get_accent_classes((string) ($attributes['accent'] ?? ''));

$content = '';

if ($logo_id > 0) {
    $content .=
        '<div class="mb-4.5 flex h-14 items-center">' .
        hengegroup_theme_render_image([
            'attachment_id' => $logo_id,
            'size' => 'medium',
            'loading' => 'lazy',
            'class' => 'h-auto max-h-13 w-auto max-w-55',
        ]) .
        '</div>';
}

if ($text !== '') {
    $content .= hengegroup_theme_render_typography([
        'variant' => 'body-sm',
        'text' => $text,
        'class' => 'text-pretty',
    ]);
}

$footer = '';

if ($button_text !== '' && $button_url !== '') {
    $link_host = (string) wp_parse_url($button_url, PHP_URL_HOST);
    $is_external =
        $link_host !== '' && $link_host !== (string) wp_parse_url(home_url('/'), PHP_URL_HOST);

    ob_start();
    get_template_part('template-parts/base/button', null, [
        'config' => [
            'text' => $button_text,
            'href' => $button_url,
            'variant' => 'outline',
            'class' => 'ml-auto',
            'attributes' => $is_external ? ['target' => '_blank', 'rel' => 'noopener'] : [],
        ],
    ]);
    $footer = (string) ob_get_clean();
}

get_template_part('template-parts/base/card', null, [
    'config' => [
        'tag' => 'li',
        'elevation' => 'raised',
        'size' => 'lg',
        'class' => 'border-l-4 [&>[data-slot=card-content]]:flex-1 ' . $accent['border'],
        'content' => $content,
        'footer' => $footer,
    ],
]);
