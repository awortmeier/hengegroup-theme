<?php

declare(strict_types=1);

// Der Titel ist eine Display-Groesse ausserhalb der Typografie-Skala (wie die 404), darum ohne
// typography.php.

if (!is_array($attributes ?? null)) {
    return;
}

$image_id = (int) ($attributes['imageId'] ?? 0);
$image_alt = trim((string) ($attributes['imageAlt'] ?? ''));
$title = trim((string) ($attributes['title'] ?? ''));
$title_tag = strtolower(trim((string) ($attributes['titleTag'] ?? 'h1')));
$text = trim((string) ($attributes['text'] ?? ''));

if ($image_id <= 0 && $title === '' && $text === '') {
    return;
}

if (!in_array($title_tag, ['h1', 'h2', 'p'], true)) {
    $title_tag = 'h1';
}

echo '<section class="relative h-[70vh] min-h-[480px] w-full overflow-hidden bg-grey-dark" ' .
    'data-block="buehne-statisch">';

if ($image_id > 0) {
    get_template_part('template-parts/base/image', null, [
        'config' => [
            'attachment_id' => $image_id,
            'alt' => $image_alt,
            'decorative' => $image_alt === '',
            'class' => 'absolute inset-0 size-full object-cover object-[center_40%]',
            'loading' => 'eager',
        ],
    ]);
}

echo '<div class="pointer-events-none absolute inset-0 bg-linear-to-b from-grey-dark/25 to-grey-dark/70"></div>';
echo '<div class="absolute inset-x-0 bottom-0 pb-10 md:pb-14">';
echo '<div class="wrapper">';
echo '<div class="col-span-12">';

if ($title !== '') {
    printf(
        '<%1$s class="m-0 font-accent text-[clamp(2.375rem,6vw,4rem)] leading-[1.05] font-semibold text-grey-light">%2$s</%1$s>',
        esc_html($title_tag),
        esc_html($title),
    );
}

if ($text !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'text' => $text,
            'variant' => 'body-lg',
            'color' => 'light',
            'class' => 'max-w-2xl font-medium' . ($title !== '' ? ' mt-3.5' : ''),
        ],
    ]);
}

echo '</div>';
echo '</div>';
echo '</div>';
echo '</section>';
