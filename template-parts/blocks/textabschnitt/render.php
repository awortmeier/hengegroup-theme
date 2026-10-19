<?php

declare(strict_types=1);

// Block "Textabschnitt". heading/text sind RichText-HTML mit dem Format "Akzent-Schrift".
// containerWidth waehlt .wrapper-small (default) oder .wrapper.

if (!is_array($attributes ?? null)) {
    return;
}

$heading = trim((string) ($attributes['heading'] ?? ''));
$text = trim((string) ($attributes['text'] ?? ''));
$has_heading = trim(wp_strip_all_tags($heading)) !== '';
$has_text = trim(wp_strip_all_tags($text)) !== '';

if (!$has_heading && !$has_text) {
    return;
}

$heading_tag = strtolower(trim((string) ($attributes['headingTag'] ?? 'p')));
$text_align = trim((string) ($attributes['textAlign'] ?? 'center'));
$container_width = trim((string) ($attributes['containerWidth'] ?? 'small'));

if (!in_array($heading_tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'], true)) {
    $heading_tag = 'p';
}

if (!in_array($text_align, ['center', 'left'], true)) {
    $text_align = 'center';
}

if (!in_array($container_width, ['default', 'small'], true)) {
    $container_width = 'small';
}

$wrapper_class = $container_width === 'small' ? 'wrapper-small' : 'wrapper';

$spacing = (string) ($attributes['spacing'] ?? 'both');

// Altes Attribut `spacingBottom: false` (= nur oben) gilt, solange `spacing` nicht gesetzt ist.
if ($spacing === 'both' && ($attributes['spacingBottom'] ?? true) === false) {
    $spacing = 'top';
}

$spacing_class = hengegroup_theme_get_section_spacing_class($spacing);

$background = hengegroup_theme_get_section_background((string) ($attributes['background'] ?? ''));

printf(
    '<section class="%1$s" data-background="%2$s">',
    esc_attr(trim($spacing_class . ' ' . $background['class'])),
    esc_attr($background['name']),
);
echo '<div class="' . esc_attr($wrapper_class) . '">';
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo hengegroup_theme_render_section_header([
    'size' => 'base',
    'align' => $text_align === 'center' ? 'center' : 'start',
    'title' => $heading,
    'title_tag' => $heading_tag,
    'description' => $text,
    'color' => $background['color'],
    'class' => 'col-span-12',
]);
echo '</div>';
echo '</section>';
