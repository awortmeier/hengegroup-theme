<?php

declare(strict_types=1);

// Editor-Vorschau von kontakt (Karte + Formular; siehe produkte-raster/render.php).

if (!function_exists('hengegroup_theme_render_company_contact_row')) {
    return;
}

echo '<div class="wrapper gap-y-12">';
echo hengegroup_theme_render_company_contact_row(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</div>';
