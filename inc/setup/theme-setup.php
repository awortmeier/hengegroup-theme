<?php

declare(strict_types=1);

function hengegroup_theme_theme_setup(): void
{
    load_theme_textdomain('hengegroup-theme', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    // Frontend-CSS auch im Editor-Canvas. add_editor_style() braucht einen theme-relativen Pfad,
    // sonst brechen die relativen url()s (Fonts) im Editor.
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
