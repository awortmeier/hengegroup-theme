<?php

declare(strict_types=1);

// Block "Unternehmen der Gruppe": Ueberschrift + Kind-Bloecke (firma) als <ul>.

if (!is_array($attributes ?? null)) {
    return;
}

$cards = trim((string) ($content ?? ''));

if ($cards === '') {
    return;
}

$heading = trim((string) ($attributes['heading'] ?? ''));
$heading_tag = strtolower(trim((string) ($attributes['headingTag'] ?? 'h2')));

if (!in_array($heading_tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'], true)) {
    $heading_tag = 'h2';
}

echo '<section class="bg-neutral-200 section-spacing">';
echo '<div class="wrapper gap-y-12">';

if ($heading !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'text' => $heading,
            'variant' => 'headline-sm',
            'tag' => $heading_tag,
            'class' => 'col-span-12',
        ],
    ]);
}

echo '<ul class="col-span-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">';
echo $cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</ul>';
echo '</div>';
echo '</section>';
