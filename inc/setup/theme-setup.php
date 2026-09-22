<?php

declare(strict_types=1);

function hengegroup_theme_theme_setup(): void
{
    load_theme_textdomain('hengegroup-theme', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    // 'align-wide' passt zu den in theme.json bereits gesetzten settings.layout.contentSize/
    // wideSize-Werten (sonst bleiben die ungenutzt).
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    // 'editor-styles' + add_editor_style(): laedt das kompilierte Tailwind-Stylesheet (dasselbe
    // wie das Frontend, siehe hengegroup_theme_enqueue_assets()) zusaetzlich in den iframe-
    // isolierten Block-Editor-Canvas -- ohne das wuerde z. B. template-parts/blocks/buehne/
    // render.php's ServerSideRender-Vorschau unstyled erscheinen (siehe
    // docs/entscheidungen.md "Phase-3-Block-Architektur"). Vormals bewusst zurueckgestellt (siehe
    // docs/to-do.md), jetzt sinnvoll, seit es mit den Phase-2-gestylten Base-Komponenten echtes
    // CSS zum Laden gibt.
    //
    // add_editor_style() bekommt bewusst einen THEME-RELATIVEN Pfad statt einer absoluten URI
    // (siehe hengegroup_theme_get_vite_style_relative_path()'s Kopfkommentar/docs/entscheidungen.md
    // fuer den Bugfix-Hintergrund) -- sonst brechen relative url()s im Stylesheet (die Akzent-Font
    // per @font-face) speziell im Editor-Canvas, waehrend das Frontend unbeeinflusst bleibt.
    add_theme_support('editor-styles');
    $editor_style_path = hengegroup_theme_get_vite_style_relative_path('assets/js/app.js');
    if ($editor_style_path !== null) {
        add_editor_style($editor_style_path);
    }
    add_theme_support('custom-logo', [
        'height' => 80,
        'width' => 240,
        'flex-height' => true,
        'flex-width' => true,
    ]);
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');

    register_nav_menus([
        'primary' => __('Hauptmenü', 'hengegroup-theme'),
        'footer' => __('Footermenü', 'hengegroup-theme'),
    ]);
}
add_action('after_setup_theme', 'hengegroup_theme_theme_setup');
