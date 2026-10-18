<?php

declare(strict_types=1);

// Block "Kontakt": Ueberschrift, Kontaktkarte (eigene Daten oder Einstellungen > Footer) und
// Anfrageformular.
// Kopf als volle Rasterzeile, sonst rutscht die Karte per Auto-Placement daneben.
// `id="kontakt"` ist Sprungziel und Redirect-Ziel, daher `supports.multiple: false`.

if (
    !is_array($attributes ?? null) ||
    !function_exists('hengegroup_theme_render_company_contact_row')
) {
    return;
}

$heading = trim((string) ($attributes['heading'] ?? ''));
$text = trim((string) ($attributes['text'] ?? ''));

echo '<section id="kontakt" class="scroll-mt-24 section-spacing" data-slot="kontakt">';
echo '<div class="wrapper gap-y-12">';

if ($heading !== '' || $text !== '') {
    echo '<div class="col-span-12 max-w-3xl">';

    if ($heading !== '') {
        get_template_part('template-parts/base/typography', null, [
            'config' => [
                'variant' => 'headline-sm',
                'tag' => 'h2',
                'text' => $heading,
                'accent_markup' => true,
                'class' => 'mb-5 lg:text-[42px]',
            ],
        ]);
    }

    if ($text !== '') {
        get_template_part('template-parts/base/typography', null, [
            'config' => [
                'variant' => 'body-base',
                'text' => $text,
                'class' => 'lg:text-[22px] lg:leading-[1.4]',
            ],
        ]);
    }

    echo '</div>';
}

$contact_row = hengegroup_theme_render_company_contact_row(
    hengegroup_theme_get_contact_block_contact($attributes),
    (string) ($attributes['accent'] ?? 'henge-grey'),
);

echo $contact_row; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</div>';
echo '</section>';
