<?php

declare(strict_types=1);

// Rendert template-parts/blocks/buehne/block.json ueber WordPress' natives `render`-Feld (Block
// API "render"-Property, seit WP 6.1) -- WP_Block_Type::render() injiziert $attributes/$content/
// $block automatisch in den Scope dieser Datei, keine manuelle render_callback-Registrierung
// noetig (siehe inc/setup/theme-blocks.php). Reine Komposition aus template-parts/base/*, keine
// eigene Markup-/Styling-Logik jenseits der Anordnung -- siehe docs/entscheidungen.md fuer die
// Architekturentscheidung "Phase-3-Block-Konvention" dahinter.
//
// Folienwechsel laeuft als Opacity-Crossfade, kein Scroll-Effekt -- siehe docs/entscheidungen.md
// "Buehne: Opacity-Crossfade statt Scroll-Snap" fuer die Kehrtwende gegenueber der urspruenglichen
// Scroll-Snap-Entscheidung. Nur carousel.php (Root, `role="region"`) und carousel-item.php (je
// Folie `role="group"`) werden noch komponiert -- carousel-content.php wird bewusst NICHT genutzt,
// dessen `tabindex="0"`-Scroll-Container waere hier ein totes, fokussierbares Element ohne
// Funktion, da nichts mehr scrollt. Folien liegen stattdessen absolut uebereinander gestapelt
// (`absolute inset-0` je carousel-item.php) im `relative` Root von carousel.php, `data-state`
// steuert per `data-[state=active]:opacity-100` die Blende; inaktive Folien bekommen zusaetzlich
// `aria-hidden="true"` + `inert`, damit ihre Buttons/Links waehrend der Unsichtbarkeit nicht per
// Tastatur/Screenreader erreichbar sind.
//
// Dot-Navigation unten ist bewusst eigenes, Block-spezifisches Markup statt eines weiteren
// carousel-*.php-Aufrufs -- template-parts/base/carousel/carousel.php dokumentiert im eigenen
// Kopfkommentar, dass Dots kein Teil der Komponente sind (nur optionale Previous-/Next-Buttons).
// Autoplay + Dot-Klick werden von assets/js/template-parts/blocks/buehne.js verdrahtet, ueber
// data-buehne-dot[s]-Attribute unten -- der Folienwechsel setzt dort direkt `data-state`/
// `aria-hidden`/`inert` je Folie, kein IntersectionObserver mehr noetig (der Aktiv-Index wird jetzt
// selbst gehalten statt aus einer Scroll-Position abgeleitet). assets/js/template-parts/base/
// carousel.js selbst bleibt unangetastet -- dessen Scroll-Snap-Verdrahtung wird hier schlicht nicht
// mehr eingebunden (kein `[data-slot="carousel-content"]` mehr im Markup).
//
// Dot-STYLING (nicht die Position/Logik) ist auf eine Design-Vorgabe umgestellt: statt runder
// Pill-Buttons jetzt schmale Text-Tabs mit Unterstreichung (fett/kursiv/Grossbuchstaben), siehe
// docs/entscheidungen.md "Buehne: Dot-Navigation als Text-Tabs statt Pillen" fuer die Design-
// Referenz und Details. Nur der AKTIVE Dot traegt die Akzentfarbe seiner Folie (Text + Unterstrich,
// `$accent_dot_classes` oben) -- inaktive bleiben immer neutral grau, unabhaengig vom
// Folien-Akzent, exakt wie in der Referenz.
//
// Kicker-BILD-Dots (siehe weiter unten) bekommen denselben aktiv/inaktiv-Kontrast wie die Text-Dots
// oben, nur ueber Filter statt Farbe: inaktive Logos sind entsaettigt + aufgehellt
// (`grayscale brightness-[1.6]`, exakt der Referenz-Wert -- Tailwinds eigene Brightness-Stufen
// haben keinen 160%-Stop) und leicht transparent (`opacity-80`), der aktive Dot zeigt sein Logo in
// Originalfarbe/-helligkeit (`group-data-[active=true]:grayscale-0 group-data-[active=true]:
// brightness-100 group-data-[active=true]:opacity-100`) -- `group` auf dem Dot-Button darunter ist
// dafuer noetig, damit `group-data-[active=true]:*` das verschachtelte `<img>` erreicht (Bugfix,
// siehe docs/entscheidungen.md "Buehne: Kicker-Logo-Filter fuer inaktive Dots").
//
// Dot-Reihe (`data-buehne-dots`) bleibt `position: absolute` -- sie liegt als EINE Reihe ueber
// allen Folien (Sibling des Carousels, nicht Teil des Crossfade-Stacks), waehrend die Content-Box
// pro Folie dupliziert im Stack steckt; beide sind also keine Flow-Geschwister, zwischen denen ein
// Grid-`gap` wirken koennte -- der `pb-24` oben bleibt deshalb bewusst der Sicherheitsabstand
// dafuer. Was die Dot-Reihe stattdessen von `.wrapper` uebernimmt: dieselbe
// `mx-auto max-w-page px-4 sm:px-6 lg:px-8`-Skala (kein `.wrapper` direkt, das braeuchte
// `display: grid` statt der eigenen `flex flex-wrap justify-center`-Zentrierung hier) --
// `max-w-page` ist dasselbe Token wie `.wrapper`s eigene Deckelung (`--container-page`,
// tokens.css), nicht ein eigener Arbitrary-Value, weil genau diese Dopplung schon einmal
// auseinandergelaufen ist. Damit faellt die Dot-Reihe auf jedem Breakpoint/jeder Bildschirmbreite
// mit denselben Raendern wie die Content-Box und der Rest des Sites-Contents zusammen.
//
// Mobile-Groessen/-Abstaende sind bewusst kleiner (`text-xs`/`h-4`/`gap-2`/`pt-2 pb-1`,
// `sm:`-Stufen heben auf die Ausgangswerte zurueck) -- Ziel: fuenf Dots (Text oder Kicker-Bild)
// passen auf einem schmalen Mobile-Viewport in eine Reihe, statt via `flex-wrap` in eine zweite
// Zeile umzubrechen (bleibt trotzdem als Sicherheitsnetz aktiv, falls ein Slide-Label ungewoehnlich
// lang ist). Text-Dots nutzen `break-words` statt `whitespace-nowrap` (Bugfix): ein einzelnes,
// zusammengesetztes Wort ohne Leerzeichen (z. B. "Mineralverarbeitung") haette mit `nowrap` gar
// nicht umbrechen koennen und waere ueber den Dot/die Reihe hinaus geragt -- `min-w-0` auf dem
// Button selbst, weil ein Flex-Kind ohne das per Default nicht unter seine eigene
// Min-Content-Breite schrumpft (gleiches Muster/gleicher Bug wie beim Mobile-Overflow der
// Content-Box, siehe docs/entscheidungen.md "Buehne: `justify-self-start` von der Content-Box
// entfernt").

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

// Aktiver Dot-Zustand je Folien-Akzent (Design-Referenz: nur der aktive Dot traegt Farbe/
// Unterstreichung in der Akzentfarbe seiner Folie, inaktive bleiben immer neutral grau -- siehe
// Kopfkommentar der Dot-Navigation weiter unten).
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
    $show_primary_button = !empty($slide['showPrimaryButton'] ?? true);
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

    $has_primary_button = $show_primary_button && $primary_button_text !== '';

    if ($has_primary_button || $secondary_button_text !== '') {
        // Unter sm: Buttons volle Breite + untereinander (flex-col); ab sm zurueck zur
        // urspruenglichen Reihe (flex-row, rechtsbuendig, auto-Breite je Button).
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

    // Kicker-Bild sitzt im Dot-Button statt in der Content-Box (Design-Entscheidung) -- der
    // Button braucht dann ein aria-label, weil ein rein dekoratives <img> (siehe image.php's
    // `decorative`) dem Button sonst keinen Accessible Name gibt; bei reinem Text-Label liefert
    // der sichtbare Inhalt den Namen bereits selbst, kein zusaetzliches aria-label noetig.
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

if ($autoplay) {
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

printf(
    '<div class="relative">%1$s<div class="absolute inset-x-0 bottom-7 z-10 mx-auto flex ' .
        'max-w-page flex-wrap justify-center gap-2 px-4 sm:gap-5 sm:px-6 lg:px-8" ' .
        'data-buehne-dots>%2$s</div></div>',
    $carousel_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $dots, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
