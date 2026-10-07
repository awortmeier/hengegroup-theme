<?php

declare(strict_types=1);

// Editor-Vorschau-Zwilling von template-parts/blocks/kontakt: nur Kontaktkarte + Formular, die
// Ueberschrift/den Text darueber rendert kontakt/edit.jsx als natives `RichText`. Gleiche
// Begruendung wie offene-stellen-vorschau/render.php. Kein eigenes Editor-Bundle, kontakt/edit.jsx
// registriert ihn clientseitig mit.

if (!function_exists('hengegroup_theme_render_company_contact_row')) {
    return;
}

echo '<div class="wrapper gap-y-12">';
echo hengegroup_theme_render_company_contact_row(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</div>';
