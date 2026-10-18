<?php

declare(strict_types=1);

// Block "Produkte": dunkle Sektion mit Kopf und Produktraster. Kopf als volle Rasterzeile
// (max-w-2xl), sonst rutscht die erste Karte per Auto-Placement daneben; die Karten liegen per
// `<ul class="contents">` direkt im .wrapper-Grid. Ohne Button-URL: Produktuebersicht.

if (!is_array($attributes ?? null)) {
    return;
}

if (!function_exists('hengegroup_theme_render_product_cards')) {
    return;
}

$heading = trim((string) ($attributes['heading'] ?? ''));
$heading_tag = strtolower(trim((string) ($attributes['headingTag'] ?? 'p')));

if (!in_array($heading_tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'], true)) {
    $heading_tag = 'p';
}

$text = trim((string) ($attributes['text'] ?? ''));
$button_text = trim((string) ($attributes['buttonText'] ?? ''));
$button_url = trim((string) ($attributes['buttonUrl'] ?? ''));
$product_ids = array_map('intval', (array) ($attributes['productIds'] ?? []));

$grid_markup = hengegroup_theme_render_product_cards($product_ids);

if ($grid_markup === '') {
    return;
}

echo '<section class="bg-grey-dark section-spacing">';
echo '<div class="wrapper gap-y-10">';
echo '<div class="col-span-12 max-w-2xl">';

if ($heading !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'text' => $heading,
            'accent_markup' => true,
            'variant' => 'headline-base',
            'tag' => $heading_tag,
            'color' => 'light',
            'class' => 'mb-4',
        ],
    ]);
}

if ($text !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'text' => $text,
            'variant' => 'body-base',
            'color' => 'light',
            'class' => $button_text !== '' ? 'mb-8' : '',
        ],
    ]);
}

if ($button_text !== '') {
    get_template_part('template-parts/base/button', null, [
        'config' => [
            'text' => $button_text,
            'href' => $button_url !== '' ? $button_url : hengegroup_theme_get_products_page_url(),
            'variant' => 'grey-light',
            'size' => 'lg',
        ],
    ]);
}

echo '</div>';
echo $grid_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</div>';
echo '</section>';
