<?php

declare(strict_types=1);

function base_theme_filter_wp_headers_remove_pingback(array $headers): array
{
    unset($headers['X-Pingback']);

    return $headers;
}

/**
 * Baseline security headers that are safe on every request (front-end, wp-admin, login) --
 * none of them restrict what scripts/styles are allowed to run, so unlike CSP below they can't
 * break wp-admin or a plugin's own inline scripts. See docs/entscheidungen.md for why
 * these run everywhere but the CSP below is front-end only.
 */
function base_theme_send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // frame-ancestors below (CSP) is the modern equivalent for browsers that support it; this is
    // the fallback for the few that don't.
    header('X-Frame-Options: SAMEORIGIN');
    // Off by default for browser features this theme has no built-in use for. Payment/fullscreen/
    // autoplay etc. are deliberately left alone -- WooCommerce (theme-admin-woocommerce.php) and
    // project-specific embeds may need those.
    header(
        'Permissions-Policy: camera=(), microphone=(), geolocation=(), gyroscope=(), magnetometer=(), usb=()',
    );
}
add_action('send_headers', 'base_theme_send_security_headers');
add_action('admin_init', 'base_theme_send_security_headers');
add_action('login_init', 'base_theme_send_security_headers');

/**
 * A deliberately loose starter Content-Security-Policy, front-end only. `frame-ancestors 'self'`
 * is already meaningfully restrictive; before relying on this for real CSP protection, drop
 * 'unsafe-inline'/'unsafe-eval' and narrow default-src/script-src/style-src/etc. to the actual
 * hosts this project loads from. See docs/entscheidungen.md for why it starts this
 * loose.
 */
function base_theme_send_front_end_csp_header(): void
{
    if (headers_sent() || is_admin()) {
        return;
    }

    header(
        "Content-Security-Policy: default-src 'self' https: data: blob:; " .
            "script-src 'self' https: 'unsafe-inline' 'unsafe-eval'; " .
            "style-src 'self' https: 'unsafe-inline'; " .
            "img-src 'self' https: data: blob:; " .
            "font-src 'self' https: data:; " .
            "connect-src 'self' https: wss:; " .
            "frame-src 'self' https:; " .
            "frame-ancestors 'self';",
    );
}
add_action('send_headers', 'base_theme_send_front_end_csp_header');

function base_theme_filter_empty_admin_footer_text(): string
{
    return '';
}

function base_theme_remove_admin_color_scheme_picker(): void
{
    remove_action('admin_color_scheme_picker', 'admin_color_scheme_picker');
}
add_action('admin_init', 'base_theme_remove_admin_color_scheme_picker');

function base_theme_disable_global_styles_output(): void
{
    remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
    remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
    remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
}
add_action('init', 'base_theme_disable_global_styles_output');

function base_theme_cleanup_core_frontend_styles(): void
{
    if (is_admin()) {
        return;
    }

    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('classic-theme-styles');
}
add_action('wp_enqueue_scripts', 'base_theme_cleanup_core_frontend_styles', 999);

// Kommentare sind fuer normale Beitraege/Seiten standardmaessig zu (persoenliche Praeferenz fuer
// dieses Base Theme, siehe docs/to-do.md). Bewusst NICHT auf 'product' (oder
// andere Custom Post Types) angewendet: WooCommerce-Produktbewertungen laufen technisch ueber
// denselben comments_open()-Mechanismus und sollen weiterhin funktionieren, falls ein Projekt sie
// braucht. Kein eigenes comments.php noetig, solange comments_open() ueberall false liefert.
function base_theme_filter_close_comments_for_posts_and_pages(bool $open, int $post_id): bool
{
    if (in_array(get_post_type($post_id), ['post', 'page'], true)) {
        return false;
    }

    return $open;
}
add_filter('comments_open', 'base_theme_filter_close_comments_for_posts_and_pages', 10, 2);
add_filter('pings_open', 'base_theme_filter_close_comments_for_posts_and_pages', 10, 2);

function base_theme_action_remove_comments_support_for_posts_and_pages(): void
{
    remove_post_type_support('post', 'comments');
    remove_post_type_support('post', 'trackbacks');
    remove_post_type_support('page', 'comments');
    remove_post_type_support('page', 'trackbacks');
}
add_action('init', 'base_theme_action_remove_comments_support_for_posts_and_pages');

function base_theme_delete_default_themes_after_core_update($upgrader, array $hook_extra): void
{
    $action = $hook_extra['action'] ?? '';
    $type = $hook_extra['type'] ?? '';

    if ($action !== 'update' || $type !== 'core') {
        return;
    }

    if (!function_exists('delete_theme')) {
        require_once ABSPATH . 'wp-admin/includes/theme.php';
    }

    $active_stylesheet = (string) get_stylesheet();
    $active_template = (string) get_template();

    foreach (wp_get_themes() as $stylesheet => $theme) {
        if (!is_string($stylesheet) || !preg_match('/^twentytwenty/i', $stylesheet)) {
            continue;
        }

        if ($stylesheet === $active_stylesheet || $stylesheet === $active_template) {
            continue;
        }

        delete_theme($stylesheet);
    }
}
add_action(
    'upgrader_process_complete',
    'base_theme_delete_default_themes_after_core_update',
    10,
    2,
);

remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'wp_oembed_add_host_js');
remove_action('template_redirect', 'rest_output_link_header', 11);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('admin_print_styles', 'print_emoji_styles');

add_filter('emoji_svg_url', '__return_false');
add_filter('xmlrpc_enabled', '__return_false');
add_filter('show_admin_bar', '__return_false');
add_filter('admin_footer_text', 'base_theme_filter_empty_admin_footer_text');
add_filter('wp_headers', 'base_theme_filter_wp_headers_remove_pingback');
add_filter('should_load_separate_core_block_assets', '__return_true');
