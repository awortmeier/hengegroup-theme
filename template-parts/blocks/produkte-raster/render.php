<?php

declare(strict_types=1);

// Editor-Vorschau des Produktrasters von "produkte" (nicht im Inserter). Eigener Block, weil
// ServerSideRender gegen den ganzen Block Ueberschrift/Text neben dem RichText doppelt zeigen
// wuerde. Wird in produkte/edit.jsx zusaetzlich clientseitig registriert, weil ServerSideRender
// den Block in der Client-Registry erwartet.

if (!is_array($attributes ?? null)) {
    return;
}

if (!function_exists('hengegroup_theme_render_produkte_grid')) {
    return;
}

$product_ids = array_map('intval', (array) ($attributes['productIds'] ?? []));

$grid_markup = hengegroup_theme_render_produkte_grid($product_ids);

echo $grid_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
