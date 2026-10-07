<?php

declare(strict_types=1);

// Eine Karte im Block "Auszeichnungen" (Kind-Block, nur innerhalb von
// hengegroup-theme/auszeichnungen einfuegbar) -- als <li>, weil der Eltern-Block die Karten in eine
// <ul> setzt. Siegel/Logo 76px (rund oder abgerundet, wie im Design), Titel, Text, Outline-Button
// rechtsbuendig. Links auf fremde Domains oeffnen in neuem Tab (Pressartikel/Verbandsseite -- der
// Besucher soll die Karriereseite nicht verlieren). Ohne Titel wird nichts ausgegeben (leere,
// frisch eingefuegte Karte).

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

echo '<li class="flex items-start gap-6 rounded-[20px] bg-white p-6 shadow-[0_8px_24px_rgba(0,0,0,0.08)] sm:p-8">';

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
printf('<h3 class="mb-2 text-lg font-bold text-grey-dark">%s</h3>', esc_html($title));

if ($text !== '') {
    printf('<p class="mb-4 text-base leading-normal text-grey-dark">%s</p>', esc_html($text));
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
echo '</li>';
