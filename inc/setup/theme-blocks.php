<?php

declare(strict_types=1);

// Phase-3-Block-Registrierung (siehe docs/entscheidungen.md "Phase-3-Block-Architektur"). Jeder
// Block lebt unter template-parts/blocks/<name>/ (block.json + render.php, gleiche
// Ordner-Konvention wie template-parts/base/<name>/) und wird hier ueber register_block_type()
// registriert. Der Editor-Script-Handle muss VOR register_block_type() bereits existieren, damit
// block.json's `"editorScript"` (ein blosser Handle-Name, kein `file:`-Pfad) ihn findet --
// register_block_type() selbst registriert keine neuen Script-Handles fuer bereits vorhandene
// Namen, nur fuer `file:`-Pfade.
//
// Jeder Block hat sein eigenes Editor-Bundle, gebaut von einer eigenen vite.config.editor-<name>.js
// (siehe vite.config.editor.factory.js's Kopfkommentar) als IIFE gegen WordPress' eigene
// wp-*-Globals -- deshalb die wp-blocks/wp-element/... Dependency-Liste unten, nicht automatisch aus
// einer .asset.php-Datei gelesen wie bei @wordpress/scripts-Setups.
//
// hengegroup_theme_register_theme_block() buendelt die Editor-Script-Registrierung +
// register_block_type()-Aufruf, die beide Bloecke (bis auf Name/Pfad) identisch brauchen -- erst
// herausgezogen, als ein zweiter Block (`ueberschrift-text`) das tatsaechlich verdoppelte, nicht
// vorsorglich beim ersten Block.
//
// Eigene Block-Kategorie "Henge" (statt der Core-Kategorie "theme", die jeder block.json vorher
// nutzte) -- auf expliziten Wunsch als eigene, klar erkennbare Gruppe in der Block-Auswahl, ganz
// oben per array_unshift() statt hinten angehaengt (WordPress rendert die Kategorien-Akkordeons im
// Inserter in der Reihenfolge dieses gefilterten Arrays).
//
// hengegroup_theme_enqueue_editor_assets() laedt zusaetzlich ein block-editor-WEITES Script (kein
// einzelnes block.json's editorScript, siehe assets/js/editor/editor-customizations.js's
// Kopfkommentar) -- ueber enqueue_block_editor_assets statt register_block_type(), weil es fuer
// JEDEN Block (Core wie eigene) gilt, nicht nur fuer die beiden hier registrierten.

function hengegroup_theme_register_block_categories(array $block_categories): array
{
    array_unshift($block_categories, [
        'slug' => 'henge',
        'title' => __('Henge', 'hengegroup-theme'),
        'icon' => null,
    ]);

    return $block_categories;
}
add_filter('block_categories_all', 'hengegroup_theme_register_block_categories');

function hengegroup_theme_register_theme_block(
    string $block_dir,
    string $editor_script_handle,
    string $editor_script_relative_path,
): void {
    $editor_script_path = hengegroup_theme_get_vite_asset_path($editor_script_relative_path);

    if (file_exists($editor_script_path)) {
        wp_register_script(
            $editor_script_handle,
            hengegroup_theme_get_vite_asset_uri($editor_script_relative_path),
            // 'wp-core-data' registriert nur den `core`-Datenstore (buehne-edit.js's Seiten-Picker
            // ruft ihn per String `select('core')` auf, kein direkter `@wordpress/core-data`-Import
            // -- siehe buehne/edit.jsx's Kopfkommentar) -- ohne dieses Script waere der Store beim
            // ersten Aufruf u. U. noch nicht registriert.
            [
                'wp-blocks',
                'wp-element',
                'wp-block-editor',
                'wp-components',
                'wp-core-data',
                'wp-data',
                'wp-html-entities',
                'wp-i18n',
                'wp-rich-text',
                'wp-server-side-render',
            ],
            filemtime($editor_script_path),
            true,
        );
    } else {
        hengegroup_theme_log_vite_error('Vite-Bundle fehlt: ' . $editor_script_path);
    }

    register_block_type(get_template_directory() . '/template-parts/blocks/' . $block_dir);
}

function hengegroup_theme_register_blocks(): void
{
    hengegroup_theme_register_theme_block(
        'buehne',
        'hengegroup-theme-buehne-editor',
        'js/blocks/buehne-edit.js',
    );
    hengegroup_theme_register_theme_block(
        'ueberschrift-text',
        'hengegroup-theme-ueberschrift-text-editor',
        'js/blocks/ueberschrift-text-edit.js',
    );
    hengegroup_theme_register_theme_block(
        'produkte',
        'hengegroup-theme-produkte-editor',
        'js/blocks/produkte-edit.js',
    );

    // Kein hengegroup_theme_register_theme_block()-Aufruf: produkte-raster hat KEIN eigenes
    // Editor-Bundle (block.json ohne "editorScript") -- produkte/edit.jsx registriert es
    // zusaetzlich clientseitig, huckepack im selben Bundle (siehe dessen Kopfkommentar/
    // render.php-Kopfkommentar fuer die Begruendung: `ServerSideRender` braucht den Blocknamen in
    // der Client-Registry, eine rein serverseitige Registrierung wie diese hier reicht dafuer
    // NICHT). Diese Zeile deckt nur die serverseitige Haelfte ab (den `/wp/v2/block-renderer/...`-
    // REST-Endpunkt).
    register_block_type(get_template_directory() . '/template-parts/blocks/produkte-raster');
}
add_action('init', 'hengegroup_theme_register_blocks');

function hengegroup_theme_enqueue_editor_assets(): void
{
    $script_relative_path = 'js/editor/editor-customizations.js';
    $script_path = hengegroup_theme_get_vite_asset_path($script_relative_path);

    if (!file_exists($script_path)) {
        hengegroup_theme_log_vite_error('Vite-Bundle fehlt: ' . $script_path);
        return;
    }

    wp_enqueue_script(
        'hengegroup-theme-editor-customizations',
        hengegroup_theme_get_vite_asset_uri($script_relative_path),
        // 'wp-edit-post' registriert den `core/edit-post`-Datenstore, dessen `hideBlockTypes()`
        // editor-customizations.js aufruft. 'wp-block-library' registriert saemtliche Core-Bloecke
        // inkl. der `core/embed`-Varianten (siehe editor-customizations.js's Kopfkommentar zu
        // `unregisterBlockVariation()`) -- ohne diese Dependency waere die Ladereihenfolge nicht
        // garantiert, `unregisterBlockVariation()` liefe dann u. U. ins Leere.
        ['wp-block-library', 'wp-blocks', 'wp-data', 'wp-dom-ready', 'wp-edit-post', 'wp-hooks'],
        filemtime($script_path),
        true,
    );
}
add_action('enqueue_block_editor_assets', 'hengegroup_theme_enqueue_editor_assets');
