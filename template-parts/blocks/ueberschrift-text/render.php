<?php

declare(strict_types=1);

// Rendert template-parts/blocks/ueberschrift-text/block.json ueber WordPress' natives `render`-Feld
// -- siehe template-parts/blocks/buehne/render.php's Kopfkommentar fuer die allgemeine
// Phase-3-Block-Konvention. Reine Komposition aus template-parts/base/typography.php (zweimal:
// Ueberschrift + Text), keine eigene Markup-/Styling-Logik jenseits der Anordnung -- siehe
// docs/entscheidungen.md "Phase-3-Block-Architektur" sowie den neueren Eintrag zu diesem Block fuer
// die "generischer Block statt Intro-Spezialfall"-Entscheidung.
//
// `.wrapper`/`.wrapper-small` (assets/css/app.css) statt eines eigenen Arbitrary-max-width -- per
// `containerWidth`-Attribut wählbar zwischen der schmalen Text-Container-Breite (Default, wie
// jeder andere schmale Block-Inhalt) und der vollen `.wrapper`-Breite (siehe docs/entscheidungen.md
// "12-Spalten-Grid ueber .wrapper"); Inhalt liegt in beiden Fällen auf allen 12 Spalten
// (`col-span-12`), da dieser Block keine Mehrspalten-Aufteilung braucht.
//
// Ueberschrift nutzt typography.php's `accent_words`-Config (bislang ungenutzt, siehe dessen
// Kopfkommentar) fuer die Akzent-Schrift auf einzelnen Woertern -- derselbe `font-accent`-Span-
// Mechanismus, den auch badge.php per `font: 'accent'` anspricht.
//
// block.json's `"align": "full"` (Default) + `"supports": {"align": ["full"]}` (Bugfix, wie bei
// buehne/block.json) ist noetig, damit `containerWidth` im Editor-Canvas ueberhaupt sichtbar wird:
// `theme.json`s `settings.layout.contentSize`/`wideSize` (48rem/72rem) begrenzt jeden Block ohne
// eigene Align-Unterstuetzung im Editor-Iframe automatisch auf diese schmale Spalte -- fuer dieses
// (klassische, nicht Full-Site-Editing-) Theme gilt das NUR im Editor, das Frontend (page.php's
// the_content()) kennt diese Breiten-Beschraenkung gar nicht. Ohne `align: full` waeren `.wrapper`
// (1600px) UND `.wrapper-small` (1000px) im Editor also gleichermassen auf 48rem/768px
// zusammengequetscht und optisch ununterscheidbar, obwohl das Frontend korrekt die gewaehlte
// Breite zeigt.

if (!is_array($attributes ?? null)) {
    return;
}

$heading = trim((string) ($attributes['heading'] ?? ''));
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
