<?php

declare(strict_types=1);

// Block "Bild & Text". Der Text sind Kind-Bloecke (Absatz, Zwischenueberschrift, Liste); ihr
// Markup wird per Arbitrary Variants gestaltet, nummerierte Listen per CSS-Counter als 01, 02, ...

if (!is_array($attributes ?? null)) {
    return;
}

$image_id = (int) ($attributes['imageId'] ?? 0);
$image_alt = trim((string) ($attributes['imageAlt'] ?? ''));
$heading = trim((string) ($attributes['heading'] ?? ''));
$has_heading = trim(wp_strip_all_tags($heading)) !== '';
$body = trim((string) ($content ?? ''));

if ($image_id <= 0 && !$has_heading && $body === '') {
    return;
}

$heading_tag = strtolower(trim((string) ($attributes['headingTag'] ?? 'h2')));
$image_position = (string) ($attributes['imagePosition'] ?? 'left');
$background = (string) ($attributes['background'] ?? 'light');

if (!in_array($heading_tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'], true)) {
    $heading_tag = 'h2';
}

if (!in_array($image_position, ['left', 'right'], true)) {
    $image_position = 'left';
}

$section_classes = [
    'light' => 'section-spacing',
    'muted' => 'bg-neutral-200 section-spacing',
    'dark' => 'section-dark section-spacing text-grey-light',
];

if (!isset($section_classes[$background])) {
    $background = 'light';
}

$is_dark = $background === 'dark';
$body_class =
    'flex flex-col gap-4.5 text-lg leading-[1.6] [&_a]:underline [&_a]:underline-offset-4 ' .
    '[&_:is(h2,h3,h4)]:mt-1.5 [&_:is(h2,h3,h4)]:text-[15px] [&_:is(h2,h3,h4)]:font-semibold ' .
    '[&_p]:m-0 [&_p]:text-pretty [&_ul]:list-disc [&_ul]:pl-5 ' .
    '[&_ol]:flex [&_ol]:flex-col [&_ol]:[counter-reset:item] ' .
    '[&_ol>li]:flex [&_ol>li]:items-baseline [&_ol>li]:gap-3.5 [&_ol>li]:border-t [&_ol>li]:py-3 ' .
    '[&_ol>li]:text-base [&_ol>li]:leading-[1.45] [&_ol>li]:[counter-increment:item] ' .
    '[&_ol>li:last-child]:border-b [&_ol>li]:before:shrink-0 [&_ol>li]:before:text-[13px] ' .
    '[&_ol>li]:before:font-bold [&_ol>li]:before:tabular-nums ' .
    '[&_ol>li]:before:content-[counter(item,decimal-leading-zero)] ' .
    ($is_dark
        ? '[&_a]:text-henge-blue-light [&_:is(h2,h3,h4)]:text-henge-blue-light ' .
            '[&_ol>li]:border-grey-light/20 [&_ol>li]:before:text-henge-blue-light'
        : '[&_a]:text-henge-blue [&_:is(h2,h3,h4)]:text-henge-blue ' .
            '[&_ol>li]:border-grey-dark/12 [&_ol>li]:before:text-henge-blue');

printf(
    '<section class="%1$s" data-block="bild-text" data-variant="%2$s">',
    esc_attr($section_classes[$background]),
    esc_attr($background),
);
echo '<div class="wrapper items-center gap-y-10">';

if ($image_id > 0) {
    printf(
        '<div class="%s">',
        esc_attr(
            'col-span-12 lg:col-span-6 ' .
                ($image_position === 'right' ? 'lg:order-2 lg:pl-6' : 'lg:pr-6'),
        ),
    );
    get_template_part('template-parts/base/image', null, [
        'config' => [
            'attachment_id' => $image_id,
            'size' => 'large',
            'alt' => $image_alt,
            'decorative' => $image_alt === '',
            'loading' => 'lazy',
            'sizes' => '(min-width: 1024px) 50vw, 100vw',
            'class' => 'aspect-4/3 w-full rounded-card-raised object-cover shadow-card-raised',
        ],
    ]);
    echo '</div>';
}

echo '<div class="' .
    esc_attr($image_id > 0 ? 'col-span-12 lg:col-span-6' : 'col-span-12 max-w-3xl') .
    '">';

if ($has_heading) {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'text' => $heading,
            'accent_markup' => true,
            'variant' => 'headline-sm',
            'tag' => $heading_tag,
            'color' => $is_dark ? 'light' : 'default',
            'class' =>
                'text-balance lg:text-[42px] lg:leading-[1.12]' . ($body !== '' ? ' mb-4.5' : ''),
        ],
    ]);
}

if ($body !== '') {
    echo '<div class="' . esc_attr($body_class) . '" data-slot="bild-text-body">';
    echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '</div>';
}

echo '</div>';
echo '</div>';
echo '</section>';
