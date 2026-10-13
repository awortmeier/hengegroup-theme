<?php

declare(strict_types=1);

// Sektionskopf mit Kicker-Badge, Ueberschrift und Beschreibung (Produkt- und Anwendungsgruppen).
//
// Config:
//   heading         string   Pflicht
//   heading_tag     string   h1-h6 | p (h2)
//   kicker          string   Badge ueber der Ueberschrift
//   kicker_variant  string   badge.php-Variante (henge-green)
//   text            string   Beschreibung
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$heading = trim((string) ($config['heading'] ?? ''));

if ($heading === '') {
    return;
}

$heading_tag = (string) ($config['heading_tag'] ?? 'h2');
$heading_tag = in_array($heading_tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'], true)
    ? $heading_tag
    : 'h2';
$kicker = trim((string) ($config['kicker'] ?? ''));
$kicker_variant = trim((string) ($config['kicker_variant'] ?? 'henge-green'));
$text = trim((string) ($config['text'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$attributes = hengegroup_theme_merge_data_attributes($attributes, $data_attributes);
$attributes['class'] = $class_name !== '' ? $class_name : null;
$attributes['data-slot'] = 'section-intro';

echo '<div' . hengegroup_theme_render_attributes($attributes) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

if ($kicker !== '') {
    echo '<div class="mb-4.5">';
    get_template_part('template-parts/base/badge', null, [
        'config' => [
            'text' => $kicker,
            'variant' => $kicker_variant,
            'font' => 'accent',
            'class' => 'px-3.5 tracking-[1.5px] uppercase',
        ],
    ]);
    echo '</div>';
}

get_template_part('template-parts/base/typography', null, [
    'config' => [
        'variant' => 'headline-sm',
        'tag' => $heading_tag,
        'text' => $heading,
        'class' => 'mb-4.5 text-pretty lg:text-[34px]',
    ],
]);

if ($text !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => 'body-base',
            'text' => $text,
            'class' => 'max-w-[900px]',
        ],
    ]);
}

echo '</div>';
