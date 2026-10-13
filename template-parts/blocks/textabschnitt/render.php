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

$align_class = $text_align === 'center' ? 'text-center' : 'text-left';
$wrapper_class = $container_width === 'small' ? 'wrapper-small' : 'wrapper';

echo '<section class="section-spacing-lg">';
echo '<div class="' . esc_attr($wrapper_class) . '">';
echo '<div class="col-span-12 ' . esc_attr($align_class) . '">';

if ($has_heading) {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'text' => $heading,
            'accent_markup' => true,
            'variant' => 'headline-base',
            'tag' => $heading_tag,
            'class' => $has_text ? 'mb-7' : '',
        ],
    ]);
}

if ($has_text) {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'text' => $text,
            'accent_markup' => true,
            'variant' => 'body-lg',
        ],
    ]);
}

echo '</div>';
echo '</div>';
echo '</section>';
