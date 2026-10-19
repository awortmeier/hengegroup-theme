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

$background = hengegroup_theme_get_section_background((string) ($attributes['background'] ?? ''));
$spacing_class = hengegroup_theme_get_section_spacing_class(
    (string) ($attributes['spacing'] ?? ''),
);

printf(
    '<section id="kontakt" class="%1$s" data-slot="kontakt" data-background="%2$s">',
    esc_attr(trim('scroll-mt-24 ' . $spacing_class . ' ' . $background['class'])),
    esc_attr($background['name']),
);
echo '<div class="wrapper gap-y-10">';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo hengegroup_theme_render_section_header([
    'title' => $heading,
    'description' => $text,
    'color' => $background['color'],
    'class' => 'col-span-12 max-w-3xl',
]);

$contact_row = hengegroup_theme_render_company_contact_row(
    hengegroup_theme_get_contact_block_contact($attributes),
    (string) ($attributes['accent'] ?? 'henge-grey'),
    $background['dark'],
);

echo $contact_row; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</div>';
echo '</section>';
