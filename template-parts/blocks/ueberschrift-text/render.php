<?php

declare(strict_types=1);

// Block "Ueberschrift & Text". containerWidth waehlt .wrapper-small (default) oder .wrapper.
// Im Editor setzt edit.jsx `alignfull`, sonst begrenzt theme.json contentSize beide Breiten.

if (!is_array($attributes ?? null)) {
    return;
}

$heading = trim((string) ($attributes['heading'] ?? ''));
$heading_tag = strtolower(trim((string) ($attributes['headingTag'] ?? 'p')));

if (!in_array($heading_tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'], true)) {
    $heading_tag = 'p';
}

$text = trim((string) ($attributes['text'] ?? ''));

if ($heading === '' && $text === '') {
    return;
}

$accent_words = is_array($attributes['accentWords'] ?? null) ? $attributes['accentWords'] : [];
$text_align = trim((string) ($attributes['textAlign'] ?? 'center'));
$container_width = trim((string) ($attributes['containerWidth'] ?? 'small'));

if (!in_array($text_align, ['center', 'left'], true)) {
    $text_align = 'center';
}

if (!in_array($container_width, ['default', 'small'], true)) {
    $container_width = 'small';
}

$align_class = $text_align === 'center' ? 'text-center' : 'text-left';
$wrapper_class = $container_width === 'small' ? 'wrapper-small' : 'wrapper';

echo '<section class="py-16 md:py-24 lg:py-35">';
echo '<div class="' . esc_attr($wrapper_class) . '">';
echo '<div class="col-span-12 ' . esc_attr($align_class) . '">';

if ($heading !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'text' => $heading,
            'variant' => 'headline-base',
            'tag' => $heading_tag,
            'accent_words' => $accent_words,
            'class' => $text !== '' ? 'mb-7' : '',
        ],
    ]);
}

if ($text !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'text' => $text,
            'variant' => 'body-lg',
        ],
    ]);
}

echo '</div>';
echo '</div>';
echo '</section>';
