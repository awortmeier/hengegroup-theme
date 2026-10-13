<?php

declare(strict_types=1);

// Block "Benefits": dunkle Flaeche, Ueberschrift, Einleitung, Kind-Bloecke (benefit) als <ul>.
// `variant` (list | cards) geht per Block-Context an die Kinder.

if (!is_array($attributes ?? null)) {
    return;
}

$heading = trim((string) ($attributes['heading'] ?? ''));
$heading_tag = strtolower(trim((string) ($attributes['headingTag'] ?? 'h2')));

if (!in_array($heading_tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'], true)) {
    $heading_tag = 'h2';
}

$text = trim((string) ($attributes['text'] ?? ''));
$is_cards = ($attributes['variant'] ?? 'list') === 'cards';
$items = trim((string) ($content ?? ''));

if ($items === '' && $heading === '') {
    return;
}

$anchor = trim((string) ($attributes['anchor'] ?? ''));

printf(
    '<section%s class="scroll-mt-24 section-dark section-spacing">',
    $anchor !== '' ? ' id="' . esc_attr($anchor) . '"' : '',
);
echo '<div class="wrapper gap-y-12">';

if ($heading !== '' || $text !== '') {
    echo '<div class="col-span-12 max-w-3xl">';

    if ($heading !== '') {
        get_template_part('template-parts/base/typography', null, [
            'config' => [
                'text' => $heading,
                'accent_markup' => true,
                'variant' => 'headline-sm',
                'tag' => $heading_tag,
                'color' => 'light',
                'class' => 'mb-5',
            ],
        ]);
    }

    if ($text !== '') {
        get_template_part('template-parts/base/typography', null, [
            'config' => [
                'text' => $text,
                'variant' => 'body-base',
                'color' => 'light',
            ],
        ]);
    }

    echo '</div>';
}

if ($items !== '') {
    printf(
        '<ul class="col-span-12 grid %s sm:grid-cols-2 lg:grid-cols-3">',
        $is_cards ? 'gap-6' : 'gap-7',
    );
    echo $items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '</ul>';
}

echo '</div>';
echo '</section>';
