<?php

declare(strict_types=1);

function base_theme_action_wp_enqueue_scripts_cleanup(): void
{
    wp_deregister_script('wp-polyfill');
    wp_deregister_script('regenerator-runtime');
}

function base_theme_action_login_enqueue_scripts(): void
{
    base_theme_enqueue_vite_style_entry('base-theme-login-styles', 'assets/js/login.js');
}

function base_theme_action_admin_menu_cleanup(): void
{
    $request_uri = isset($_SERVER['REQUEST_URI'])
        ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))
        : '';
    $customizer_url = add_query_arg(
        'return',
        urlencode(remove_query_arg(wp_removable_query_args(), $request_uri)),
        'customize.php',
    );
    $site_editor_url = add_query_arg('path', '/wp_template', 'site-editor.php');
    $patterns_editor_url = add_query_arg('p', '/pattern', 'site-editor.php');
    $theme_submenu_pages = [
        $customizer_url,
        'site-editor.php',
        $site_editor_url,
        $patterns_editor_url,
        'site-editor.php?path=%2Fwp_template',
        'site-editor.php?path=/wp_template',
        'site-editor.php?p=%2Fpattern',
        'site-editor.php?p=/pattern',
        // Kein registrierter Sidebar-/Widget-Bereich (siehe docs/to-do.md) —
        // die Widgets-Seite waere ohne Zielbereich nur eine leere, verwirrende Admin-Seite.
        'widgets.php',
    ];

    remove_menu_page('edit.php');
    remove_menu_page('edit-comments.php');
    remove_menu_page('customize.php');
    remove_menu_page('site-editor.php');
    remove_submenu_page('index.php', 'index.php');
    remove_submenu_page('index.php', 'my-sites.php');
    remove_submenu_page('tools.php', 'tools.php');
    remove_submenu_page('tools.php', 'ms-delete-site.php');
    remove_submenu_page('tools.php', 'import.php');
    remove_submenu_page('tools.php', 'export.php');
    remove_submenu_page('tools.php', 'export-personal-data.php');
    remove_submenu_page('tools.php', 'erase-personal-data.php');
    remove_submenu_page('options-general.php', 'options-media.php');
    remove_submenu_page('options-general.php', 'options-discussion.php');
    remove_submenu_page('options-general.php', 'options-writing.php');
    remove_menu_page('post-new.php');

    foreach ($theme_submenu_pages as $theme_submenu_page) {
        remove_submenu_page('themes.php', $theme_submenu_page);
    }
}

function base_theme_action_wp_dashboard_setup_cleanup(): void
{
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
    remove_meta_box('dashboard_activity', 'dashboard', 'normal');
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('woocommerce_dashboard_recent_reviews', 'dashboard', 'normal');
    remove_action('welcome_panel', 'wp_welcome_panel');
}

function base_theme_action_wp_network_dashboard_setup_cleanup(): void
{
    remove_meta_box('network_dashboard_right_now', 'dashboard-network', 'normal');
    remove_meta_box('woocommerce_network_orders', 'dashboard-network', 'normal');
    remove_meta_box('dashboard_primary', 'dashboard-network', 'side');
}

function base_theme_action_admin_bar_menu_cleanup(): void
{
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('wp-logo');
    $wp_admin_bar->remove_node('new-content');
    $wp_admin_bar->remove_node('site-name');
    $wp_admin_bar->remove_node('view-site');
    $wp_admin_bar->remove_node('view');
    $wp_admin_bar->remove_node('view-store');
    $wp_admin_bar->remove_node('archive');
}

function base_theme_action_admin_bar_multisite_cleanup(): void
{
    if (!is_multisite()) {
        return;
    }

    global $wp_admin_bar;

    foreach (get_blogs_of_user(get_current_user_id()) as $site) {
        $site_id = (string) $site->userblog_id;

        $wp_admin_bar->remove_node('blog-' . $site_id . '-d');
        $wp_admin_bar->remove_node('blog-' . $site_id . '-n');
        $wp_admin_bar->remove_node('blog-' . $site_id . '-c');
        $wp_admin_bar->remove_node('blog-' . $site_id . '-v');
    }
}

function base_theme_action_wp_before_admin_bar_render_cleanup(): void
{
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('comments');
    $wp_admin_bar->remove_menu('customize');
}

function base_theme_action_network_admin_menu_cleanup(): void
{
    remove_submenu_page('themes.php', 'theme-editor.php');
    remove_submenu_page('plugins.php', 'plugin-editor.php');
}

function base_theme_action_block_hidden_admin_pages(): void
{
    if (!is_admin()) {
        return;
    }

    global $pagenow;

    if (
        $pagenow === 'customize.php' ||
        $pagenow === 'site-editor.php' ||
        $pagenow === 'widgets.php'
    ) {
        wp_safe_redirect(admin_url());
        exit();
    }

    if (
        is_network_admin() &&
        ($pagenow === 'theme-editor.php' || $pagenow === 'plugin-editor.php')
    ) {
        wp_safe_redirect(network_admin_url());
        exit();
    }
}

function base_theme_register_admin_hooks(): void
{
    add_action('wp_enqueue_scripts', 'base_theme_action_wp_enqueue_scripts_cleanup');
    add_action('login_enqueue_scripts', 'base_theme_action_login_enqueue_scripts');
    add_action('admin_menu', 'base_theme_action_admin_menu_cleanup', 999);
    add_action('network_admin_menu', 'base_theme_action_network_admin_menu_cleanup', 999);
    add_action('wp_dashboard_setup', 'base_theme_action_wp_dashboard_setup_cleanup');
    add_action(
        'wp_network_dashboard_setup',
        'base_theme_action_wp_network_dashboard_setup_cleanup',
    );
    add_action('admin_bar_menu', 'base_theme_action_admin_bar_menu_cleanup', 999);
    add_action('admin_bar_menu', 'base_theme_action_admin_bar_multisite_cleanup', 999);
    add_action(
        'wp_before_admin_bar_render',
        'base_theme_action_wp_before_admin_bar_render_cleanup',
    );
    add_action('admin_init', 'base_theme_action_block_hidden_admin_pages');
}

base_theme_register_admin_hooks();
