<?php

declare(strict_types=1);

// Rendert template-parts/blocks/produkte-raster/block.json -- KEIN eigener, im Inserter sichtbarer
// Block (`"supports": {"inserter": false}` in block.json), sondern ausschliesslich das Ziel von
// assets/js/blocks/produkte/edit.jsx's `<ServerSideRender block="hengegroup-theme/produkte-raster"
// .../>`-Aufruf: die Live-Produktraster-Vorschau im Editor-Canvas des `hengegroup-theme/produkte`-
// Blocks.
//
// Grund fuer den eigenen Block statt eines `ServerSideRender` gegen `hengegroup-theme/produkte`
// selbst (explizite Nachfrage 2026-09-23: Ueberschrift/Text sollen direkt im Content-Bereich als
// natives `RichText` editierbar sein, siehe produkte/edit.jsx's Kopfkommentar): `RichText` im
// Editor-Canvas UND ein `ServerSideRender`-Aufruf, der denselben Block (inkl. Ueberschrift/Text)
// ein zweites Mal rendert, wuerden Ueberschrift/Text/Button doppelt zeigen -- einmal editierbar,
// einmal als statisches SSR-Ergebnis direkt daneben. Dieser Block rendert deshalb NUR das Raster
// (ueber hengegroup_theme_render_produkte_grid(), geteilt mit produkte/render.php, siehe dessen
// eigenen Kopfkommentar), keine Ueberschrift/Text/Button -- `ServerSideRender` bekommt dafuer auch
// nur `productCategory`/`numberOfProducts` als Attribute uebergeben, nicht die vollen
// Block-Attribute, damit Ueberschrift-/Text-Tastatureingaben im Editor keinen unnoetigen erneuten
// REST-Request fuer die (davon unabhaengige) Produktraster-Vorschau ausloesen.
//
// Kein EIGENES Editor-Script/keine eigene Vite-Build-Config: `ServerSideRender` prueft den
// uebergebenen Blocknamen zuerst gegen die CLIENTSEITIGE Block-Registry, bevor es WordPress'
// `/wp/v2/block-renderer/<name>`-REST-Endpunkt aufruft -- eine rein server-seitige
// `register_block_type()`-Registrierung (siehe inc/setup/theme-blocks.php) reicht dafuer NICHT
// (Bugfix 2026-09-23: "Block type 'hengegroup-theme/produkte-raster' is not registered.").
// produkte/edit.jsx registriert diesen Block deshalb zusaetzlich clientseitig mit
// `edit`/`save`-No-Ops, huckepack im selben Bundle statt einer eigenen
// vite.config.editor-produkte-raster.js -- siehe dessen Kopfkommentar.
//
// Bekannte Einschraenkung (Editor-Vorschau-only, Frontend unbetroffen): `ServerSideRender`s eigenes
// Wrapper-`<div>` (kein `display: contents`) unterbricht die `display: contents`-Kette zwischen
// `.wrapper`s `grid-cols-12` (produkte/edit.jsx) und den Produktkarten-`<li>`s dieses Blocks --
// die Karten erscheinen im Editor-Canvas deshalb gestapelt statt im 4-Spalten-Raster, obwohl jede
// Karte selbst (Bild/Badge/Titel/Beschreibung/Button) unveraendert echte Produktdaten zeigt. Das
// Frontend (produkte/render.php) nutzt diesen Block gar nicht und ist von dieser Einschraenkung
// nicht betroffen.

if (!is_array($attributes ?? null)) {
    return;
}

if (!function_exists('hengegroup_theme_render_produkte_grid')) {
    return;
}

$product_category = trim((string) ($attributes['productCategory'] ?? ''));
$number_of_products = (int) ($attributes['numberOfProducts'] ?? 4);

$grid_markup = hengegroup_theme_render_produkte_grid($product_category, $number_of_products);

echo $grid_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
