<?php

declare(strict_types=1);

// Block "Buehne": Folien liegen gestapelt in carousel.php und wechseln per Opacity-Crossfade
// (buehne.js). Inaktive Folien sind aria-hidden + inert. Die Dot-Reihe liegt absolut ueber allen
// Folien (darum pb-24 als Abstand) und nutzt max-w-page wie .wrapper. Kicker-Logos sind inaktiv
// entsaettigt (group-data-[active]). Dots brechen per break-words/min-w-0 um statt ueberzulaufen.

if (!is_array($attributes ?? null)) {
    return;
}

$slides = is_array($attributes['slides'] ?? null) ? $attributes['slides'] : [];

if ($slides === []) {
    return;
}

$autoplay = !empty($attributes['autoplay'] ?? true);
$autoplay_interval = (int) ($attributes['autoplayInterval'] ?? 6000);
$loop = !empty($attributes['loop'] ?? true);
$aria_label = trim((string) ($attributes['ariaLabel'] ?? ''));

if ($autoplay_interval < 1000) {
    $autoplay_interval = 6000;
}

$allowed_accents = ['henge-green', 'henge-blue', 'henge-grey'];

$accent_border_classes = [
    'henge-green' => 'border-henge-green',
    'henge-blue' => 'border-henge-blue',
    'henge-grey' => 'border-henge-grey',
];

$accent_dot_classes = [
    'henge-green' => 'data-[active=true]:border-henge-green data-[active=true]:text-henge-green',
    'henge-blue' => 'data-[active=true]:border-henge-blue data-[active=true]:text-henge-blue',
    'henge-grey' => 'data-[active=true]:border-henge-grey data-[active=true]:text-henge-grey',
];

$carousel_items = '';
$dots = '';
$index = 0;

foreach ($slides as $slide) {
    if (!is_array($slide)) {
        continue;
    }

    $image_id = (int) ($slide['imageId'] ?? 0);
    $image_alt = trim((string) ($slide['imageAlt'] ?? ''));
    $kicker_image_id = (int) ($slide['kickerImageId'] ?? 0);
    $kicker_image_alt = trim((string) ($slide['kickerImageAlt'] ?? ''));
    $badge_text = trim((string) ($slide['badgeText'] ?? ''));
    $title = trim((string) ($slide['title'] ?? ''));
    $text = trim((string) ($slide['text'] ?? ''));
    $accent = trim((string) ($slide['accent'] ?? 'henge-green'));
    $primary_button_text = trim((string) ($slide['primaryButtonText'] ?? ''));
    $primary_button_url = trim((string) ($slide['primaryButtonUrl'] ?? ''));
    $secondary_button_text = trim((string) ($slide['secondaryButtonText'] ?? ''));
    $secondary_button_url = trim((string) ($slide['secondaryButtonUrl'] ?? ''));

    if (!in_array($accent, $allowed_accents, true)) {
        $accent = 'henge-green';
    }

    $item_id = 'hengegroup-theme-buehne-slide-' . $index;

    ob_start();

    if ($image_id > 0) {
        get_template_part('template-parts/base/image', null, [
            'config' => [
                'attachment_id' => $image_id,
                'alt' => $image_alt,
                'decorative' => $image_alt === '',
                'class' => 'absolute inset-0 size-full object-cover',
                'loading' => $index === 0 ? 'eager' : 'lazy',
            ],
        ]);
    }

    echo '<div class="absolute inset-0 bg-linear-to-b from-black/15 to-black/75"></div>';
    echo '<div class="relative flex h-full flex-col justify-end">';
    echo '<div class="wrapper pb-24">';
    echo '<div class="col-span-12 max-w-xl rounded-xl border-l-4 ' .
        esc_attr($accent_border_classes[$accent]) .
        ' bg-black/55 p-4 backdrop-blur-md sm:p-5 md:p-6">';

    if ($badge_text !== '') {
        get_template_part('template-parts/base/badge', null, [
            'config' => [
                'text' => $badge_text,
                'variant' => $accent,
                'font' => 'accent',
                'class' => 'mb-2.5',
            ],
        ]);
    }

    if ($title !== '') {
        get_template_part('template-parts/base/typography', null, [
            'config' => [
                'text' => $title,
                'variant' => 'headline-sm',
                'tag' => 'h2',
                'color' => 'light',
                'class' => 'mb-2.5',
            ],
        ]);
    }

    if ($text !== '') {
        get_template_part('template-parts/base/typography', null, [
            'config' => [
                'text' => $text,
                'variant' => 'body-base',
                'color' => 'light',
                'class' => 'mb-5',
            ],
        ]);
    }

    // Button erscheint, sobald sein Text gepflegt ist (altes showPrimaryButton wird ignoriert).
    $has_primary_button = $primary_button_text !== '';

    if ($has_primary_button || $secondary_button_text !== '') {
        echo '<div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:justify-end">';

        if ($has_primary_button) {
            get_template_part('template-parts/base/button', null, [
                'config' => [
                    'text' => $primary_button_text,
                    'href' => $primary_button_url !== '' ? $primary_button_url : '#',
                    'variant' => $accent,
                    'size' => 'lg',
                    'class' => 'w-full sm:w-auto',
                ],
            ]);
        }

        if ($secondary_button_text !== '') {
            get_template_part('template-parts/base/button', null, [
                'config' => [
                    'text' => $secondary_button_text,
                    'href' => $secondary_button_url !== '' ? $secondary_button_url : '#',
                    'variant' => 'grey-light',
                    'size' => 'lg',
                    'class' => 'w-full sm:w-auto',
                ],
            ]);
        }

        echo '</div>';
    }

    echo '</div>'; // Glassmorphism-Content-Box
    echo '</div>'; // .wrapper (deckelt/zentriert den Content wie jeder andere Block-Inhalt)
    echo '</div>'; // Slide-Content-Wrapper

    $slide_content = (string) ob_get_clean();
    $is_first_slide = $index === 0;

    ob_start();
    get_template_part('template-parts/base/carousel/carousel-item', null, [
        'config' => [
            'content' => $slide_content,
            'id' => $item_id,
            'class' =>
                'absolute inset-0 h-full w-full opacity-0 transition-opacity duration-700 ' .
                'ease-in-out data-[state=active]:opacity-100' .
                ($image_id > 0 ? '' : ' bg-grey-dark'),
            'attributes' => [
                'aria-hidden' => $is_first_slide ? null : 'true',
                'inert' => $is_first_slide ? null : true,
            ],
            'data_attributes' => [
                'state' => $is_first_slide ? 'active' : 'inactive',
            ],
        ],
    ]);
    $carousel_items .= (string) ob_get_clean();

    $dot_label =
        $badge_text !== ''
            ? $badge_text
            : sprintf(
                /* translators: %d: Foliennummer (1-basiert) */
                __('Folie %d', 'hengegroup-theme'),
                $index + 1,
            );

    // Mit Kicker-Bild braucht der Dot ein aria-label (das Bild ist dekorativ).
    if ($kicker_image_id > 0) {
        ob_start();
        get_template_part('template-parts/base/image', null, [
            'config' => [
                'attachment_id' => $kicker_image_id,
                'decorative' => true,
                'class' =>
                    'h-4 w-auto object-contain opacity-80 grayscale brightness-[1.6] ' .
                    'transition-all duration-300 group-data-[active=true]:opacity-100 ' .
                    'group-data-[active=true]:grayscale-0 group-data-[active=true]:brightness-100 ' .
                    'sm:h-6',
            ],
        ]);
        $dot_content = (string) ob_get_clean();
        $dot_aria_label_attribute =
            ' aria-label="' .
            esc_attr($kicker_image_alt !== '' ? $kicker_image_alt : $dot_label) .
            '"';
    } else {
        $dot_content = esc_html($dot_label);
        $dot_aria_label_attribute = '';
    }

    $dots .= sprintf(
        '<button type="button" class="group inline-flex min-w-0 items-center border-b-2 ' .
            'border-grey-light/25 pt-2 pb-1 text-xs font-bold tracking-normal text-grey-light/50 ' .
            'uppercase italic break-words transition-colors sm:pt-3 sm:pb-1.5 sm:text-sm ' .
            'sm:tracking-wide %4$s"%1$s ' .
            'data-buehne-dot="%2$s">%3$s</button>',
        $dot_aria_label_attribute, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        esc_attr($item_id),
        $dot_content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        esc_attr($accent_dot_classes[$accent]),
    );

    $index++;
}

if ($carousel_items === '') {
    return;
}

$carousel_data_attributes = ['block' => 'buehne'];

// Bei einer Folie kein Picker und kein Autoplay (buehne.js bricht ohne [data-buehne-dots] ab).
$has_multiple_slides = $index > 1;

if ($autoplay && $has_multiple_slides) {
    $carousel_data_attributes['autoplay'] = 'true';
    $carousel_data_attributes['autoplay-interval'] = (string) $autoplay_interval;
}

ob_start();
get_template_part('template-parts/base/carousel/carousel', null, [
    'config' => [
        'content' => $carousel_items,
        'loop' => $loop,
        'aria_label' => $aria_label !== '' ? $aria_label : __('Bühne', 'hengegroup-theme'),
        'class' => 'relative h-[70vh] min-h-[480px] w-full overflow-hidden',
        'data_attributes' => $carousel_data_attributes,
    ],
]);
$carousel_markup = (string) ob_get_clean();

if (!$has_multiple_slides) {
    printf(
        '<div class="relative">%s</div>',
        $carousel_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );

    return;
}

printf(
    '<div class="relative">%1$s<div class="absolute inset-x-0 bottom-7 z-10 mx-auto flex ' .
        'max-w-page flex-wrap justify-center gap-2 px-4 sm:gap-5 sm:px-6 lg:px-8" ' .
        'data-buehne-dots>%2$s</div></div>',
    $carousel_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $dots, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
