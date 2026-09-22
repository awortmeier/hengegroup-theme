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
            [
                'wp-blocks',
                'wp-element',
                'wp-block-editor',
                'wp-components',
                'wp-i18n',
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
}
add_action('init', 'hengegroup_theme_register_blocks');
