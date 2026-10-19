<?php

declare(strict_types=1);

// Block "Produkte": Sektion mit Kopf und Produktraster. Kopf als volle Rasterzeile,
// sonst rutscht die erste Karte per Auto-Placement daneben; die Karten liegen per
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

$background = hengegroup_theme_get_section_background((string) ($attributes['background'] ?? ''));
$spacing_class = hengegroup_theme_get_section_spacing_class(
    (string) ($attributes['spacing'] ?? ''),
);

$actions = '';

if ($button_text !== '') {
    ob_start();
    get_template_part('template-parts/base/button', null, [
        'config' => [
            'text' => $button_text,
            'href' => $button_url !== '' ? $button_url : hengegroup_theme_get_products_page_url(),
            'variant' => $background['dark'] ? 'grey-light' : 'grey-dark',
            'size' => 'lg',
        ],
    ]);
    $actions = (string) ob_get_clean();
}

printf(
    '<section class="%1$s" data-background="%2$s">',
    esc_attr(trim($spacing_class . ' ' . $background['class'])),
    esc_attr($background['name']),
);
echo '<div class="wrapper gap-y-10">';
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo hengegroup_theme_render_section_header([
    'size' => 'base',
    'color' => $background['color'],
    'title' => $heading,
    'title_tag' => $heading_tag,
    'description' => $text,
    'actions' => $actions,
    'class' => 'col-span-12 max-w-3xl',
]);
echo $grid_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</div>';
echo '</section>';
