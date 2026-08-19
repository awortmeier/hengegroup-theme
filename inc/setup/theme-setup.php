<?php

declare(strict_types=1);

function hengegroup_theme_theme_setup(): void
{
    load_theme_textdomain('hengegroup-theme', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    // 'align-wide' passt zu den in theme.json bereits gesetzten settings.layout.contentSize/
    // wideSize-Werten (sonst bleiben die ungenutzt). 'editor-styles' fehlt hier bewusst noch --
    // erst mit Phase 2 (echtes CSS zum Laden per add_editor_style()) sinnvoll, siehe
    // docs/to-do.md.
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
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
        'primary' => __('Primary Menu', 'hengegroup-theme'),
        'footer' => __('Footer Menu', 'hengegroup-theme'),
    ]);
}
add_action('after_setup_theme', 'hengegroup_theme_theme_setup');
