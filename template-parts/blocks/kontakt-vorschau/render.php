<?php

declare(strict_types=1);

// Editor-Vorschau von kontakt (Karte + Formular; siehe produkte-raster/render.php).

if (!function_exists('hengegroup_theme_render_company_contact_row')) {
    return;
}

$contact_row = hengegroup_theme_render_company_contact_row(
    hengegroup_theme_get_contact_block_contact(is_array($attributes ?? null) ? $attributes : []),
    (string) ($attributes['accent'] ?? 'henge-grey'),
    hengegroup_theme_get_section_background((string) ($attributes['background'] ?? ''))['dark'],
);

echo '<div class="wrapper gap-y-12">';
echo $contact_row; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</div>';
