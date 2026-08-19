<?php

declare(strict_types=1);

function hengegroup_theme_dequeue_and_deregister_style(string $handle): void
{
    wp_dequeue_style($handle);
    wp_deregister_style($handle);
}

function hengegroup_theme_dequeue_and_deregister_script(string $handle): void
{
    wp_dequeue_script($handle);
    wp_deregister_script($handle);
}

function hengegroup_theme_filter_body_class_woocommerce_cleanup(array $classes): array
{
    $classes = array_values(array_diff($classes, ['woocommerce-no-js']));
    remove_action('wp_footer', 'wc_no_js');

    return $classes;
}
add_filter('body_class', 'hengegroup_theme_filter_body_class_woocommerce_cleanup', 20);

function hengegroup_theme_disable_woocommerce_gallery_noscript(): void
{
    remove_action('wp_head', 'wc_gallery_noscript');
}
add_action('wp', 'hengegroup_theme_disable_woocommerce_gallery_noscript');

function hengegroup_theme_filter_disable_wp_img_auto_sizes(): bool
{
    return false;
}
add_filter('wp_img_tag_add_auto_sizes', 'hengegroup_theme_filter_disable_wp_img_auto_sizes');

function hengegroup_theme_cleanup_woocommerce_frontend_styles(): void
{
    if (is_admin()) {
        return;
    }

    $style_handles = [
        'woocommerce-inline',
        'woocommerce-layout',
        'woocommerce-smallscreen',
        'woocommerce-general',
        'wc-blocks-style',
    ];

    foreach ($style_handles as $style_handle) {
        hengegroup_theme_dequeue_and_deregister_style($style_handle);
    }
}
add_action('wp_enqueue_scripts', 'hengegroup_theme_cleanup_woocommerce_frontend_styles', 999);

function hengegroup_theme_cleanup_woocommerce_frontend_scripts(): void
{
    if (is_admin()) {
        return;
    }

    $script_handles = [
        'jquery-core',
        'jquery-migrate',
        'wc-jquery-blockui',
        'wc-add-to-cart',
        'js-cookie',
        'woocommerce',
    ];

    foreach ($script_handles as $script_handle) {
        hengegroup_theme_dequeue_and_deregister_script($script_handle);
    }
}
add_action('wp_enqueue_scripts', 'hengegroup_theme_cleanup_woocommerce_frontend_scripts', 999);
