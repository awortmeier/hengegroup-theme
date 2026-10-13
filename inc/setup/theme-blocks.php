<?php

declare(strict_types=1);

// Block-Registrierung. Der Editor-Script-Handle muss vor register_block_type() existieren, weil
// block.json's editorScript nur ein Handle ist. Eigene Kategorie "henge" ganz oben im Inserter.

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
            // Stellt den `core`-Datenstore sicher (buehne nutzt select('core')).
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
        'buehne-statisch',
        'hengegroup-theme-buehne-statisch-editor',
        'js/blocks/buehne-statisch-edit.js',
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

    // Ohne eigenes Bundle; produkte/edit.jsx registriert ihn clientseitig (ServerSideRender braucht
    // beide Registrierungen).
    register_block_type(get_template_directory() . '/template-parts/blocks/produkte-raster');

    hengegroup_theme_register_theme_block(
        'offene-stellen',
        'hengegroup-theme-offene-stellen-editor',
        'js/blocks/offene-stellen-edit.js',
    );
    register_block_type(
        get_template_directory() . '/template-parts/blocks/offene-stellen-vorschau',
    );
    hengegroup_theme_register_theme_block(
        'karriere-teaser',
        'hengegroup-theme-karriere-teaser-editor',
        'js/blocks/karriere-teaser-edit.js',
    );

    register_block_type(get_template_directory() . '/template-parts/blocks/karriere-teaser-liste');

    hengegroup_theme_register_theme_block(
        'benefits',
        'hengegroup-theme-benefits-editor',
        'js/blocks/benefits-edit.js',
    );
    hengegroup_theme_register_theme_block(
        'auszeichnungen',
        'hengegroup-theme-auszeichnungen-editor',
        'js/blocks/auszeichnungen-edit.js',
    );
    hengegroup_theme_register_theme_block(
        'galerie',
        'hengegroup-theme-galerie-editor',
        'js/blocks/galerie-edit.js',
    );

    hengegroup_theme_register_theme_block(
        'stellen-liste',
        'hengegroup-theme-stellen-liste-editor',
        'js/blocks/stellen-liste-edit.js',
    );

    hengegroup_theme_register_theme_block(
        'produktkategorie',
        'hengegroup-theme-produktkategorie-editor',
        'js/blocks/produktkategorie-edit.js',
    );
    hengegroup_theme_register_theme_block(
        'anwendungsgruppe',
        'hengegroup-theme-anwendungsgruppe-editor',
        'js/blocks/anwendungsgruppe-edit.js',
    );
    hengegroup_theme_register_theme_block(
        'kontakt',
        'hengegroup-theme-kontakt-editor',
        'js/blocks/kontakt-edit.js',
    );
    register_block_type(get_template_directory() . '/template-parts/blocks/kontakt-vorschau');

    hengegroup_theme_register_theme_block(
        'historie',
        'hengegroup-theme-historie-editor',
        'js/blocks/historie-edit.js',
    );
    hengegroup_theme_register_theme_block(
        'personen',
        'hengegroup-theme-personen-editor',
        'js/blocks/personen-edit.js',
    );
    hengegroup_theme_register_theme_block(
        'kennzahlen',
        'hengegroup-theme-kennzahlen-editor',
        'js/blocks/kennzahlen-edit.js',
    );
    hengegroup_theme_register_theme_block(
        'firmen',
        'hengegroup-theme-firmen-editor',
        'js/blocks/firmen-edit.js',
    );

    // Kind-Bloecke, clientseitig im edit.jsx des Eltern-Blocks registriert.
    foreach (
        ['auszeichnung', 'benefit', 'historie-eintrag', 'person', 'kennzahl', 'firma']
        as $child
    ) {
        register_block_type(get_template_directory() . '/template-parts/blocks/' . $child);
    }
}
add_action('init', 'hengegroup_theme_register_blocks');

/**
 * Benefit-Icons inkl. SVG fuer den Editor (dieselbe Liste wie im Frontend).
 */
function hengegroup_theme_enqueue_benefit_icons_for_editor(): void
{
    $icons = [];

    foreach (hengegroup_theme_get_benefit_icons() as $key => $icon) {
        $icons[$key] = [
            'label' => (string) $icon[0],
            'svg' => hengegroup_theme_render_benefit_icon((string) $key),
        ];
    }

    wp_add_inline_script(
        'hengegroup-theme-benefits-editor',
        'window.hengegroupThemeBenefitIcons = ' . wp_json_encode($icons) . ';',
        'before',
    );
}
add_action('enqueue_block_editor_assets', 'hengegroup_theme_enqueue_benefit_icons_for_editor');

function hengegroup_theme_enqueue_job_list_types_for_editor(): void
{
    wp_add_inline_script(
        'hengegroup-theme-stellen-liste-editor',
        'window.hengegroupThemeJobListTypes = ' .
            wp_json_encode(hengegroup_theme_get_job_list_types()) .
            ';',
        'before',
    );
}
add_action('enqueue_block_editor_assets', 'hengegroup_theme_enqueue_job_list_types_for_editor');

/**
 * Per Inline-Script statt REST; ohne "Unkategorisiert".
 */
function hengegroup_theme_enqueue_product_categories_for_editor(): void
{
    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'exclude' => [(int) get_option('default_product_cat', 0)],
    ]);
    $categories = [];

    if (is_array($terms)) {
        $by_parent = [];

        foreach ($terms as $term) {
            $by_parent[(int) $term->parent][] = $term;
        }

        $walk = static function (int $parent, int $depth) use (
            &$walk,
            &$categories,
            $by_parent,
        ): void {
            foreach ($by_parent[$parent] ?? [] as $term) {
                $categories[] = [
                    'id' => (int) $term->term_id,
                    'name' => str_repeat('— ', $depth) . html_entity_decode($term->name),
                ];
                $walk((int) $term->term_id, $depth + 1);
            }
        };
        $walk(0, 0);
    }

    wp_add_inline_script(
        'hengegroup-theme-produktkategorie-editor',
        'window.hengegroupThemeProductCategories = ' . wp_json_encode($categories) . ';',
        'before',
    );
}
add_action('enqueue_block_editor_assets', 'hengegroup_theme_enqueue_product_categories_for_editor');

function hengegroup_theme_enqueue_anwendung_groups_for_editor(): void
{
    $terms = get_terms([
        'taxonomy' => HENGEGROUP_THEME_ANWENDUNG_TAXONOMY,
        'parent' => 0,
        'hide_empty' => false,
    ]);
    $groups = array_map(
        static fn(WP_Term $term): array => [
            'id' => (int) $term->term_id,
            'name' => html_entity_decode($term->name),
        ],
        hengegroup_theme_sort_anwendung_terms(is_array($terms) ? $terms : []),
    );

    wp_add_inline_script(
        'hengegroup-theme-anwendungsgruppe-editor',
        'window.hengegroupThemeAnwendungGroups = ' . wp_json_encode($groups) . ';',
        'before',
    );
}
add_action('enqueue_block_editor_assets', 'hengegroup_theme_enqueue_anwendung_groups_for_editor');

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
        // wp-edit-post fuer hideBlockTypes(), wp-block-library damit die Embed-Varianten schon
        // registriert sind, bevor sie entfernt werden.
        ['wp-block-library', 'wp-blocks', 'wp-data', 'wp-dom-ready', 'wp-edit-post', 'wp-hooks'],
        filemtime($script_path),
        true,
    );
}
add_action('enqueue_block_editor_assets', 'hengegroup_theme_enqueue_editor_assets');
