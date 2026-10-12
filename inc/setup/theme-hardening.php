<?php

declare(strict_types=1);

function hengegroup_theme_filter_wp_headers_remove_pingback(array $headers): array
{
    unset($headers['X-Pingback']);

    return $headers;
}

/**
 * Security-Header ohne Einfluss auf Skripte (sicher auch in wp-admin).
 */
function hengegroup_theme_send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Fallback fuer Browser ohne CSP frame-ancestors.
    header('X-Frame-Options: SAMEORIGIN');
    // Payment/Fullscreen/Autoplay bleiben erlaubt (WooCommerce, Embeds).
    header(
        'Permissions-Policy: camera=(), microphone=(), geolocation=(), gyroscope=(), magnetometer=(), usb=()',
    );
}
add_action('send_headers', 'hengegroup_theme_send_security_headers');
add_action('admin_init', 'hengegroup_theme_send_security_headers');
add_action('login_init', 'hengegroup_theme_send_security_headers');

/**
 * Bewusst lockere Start-CSP, nur Frontend. Fuer echten Schutz unsafe-inline/unsafe-eval entfernen
 * und die Quellen eingrenzen.
 */
function hengegroup_theme_send_front_end_csp_header(): void
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
add_action('send_headers', 'hengegroup_theme_send_front_end_csp_header');

function hengegroup_theme_filter_empty_admin_footer_text(): string
{
    return '';
}

function hengegroup_theme_remove_admin_color_scheme_picker(): void
{
    remove_action('admin_color_scheme_picker', 'admin_color_scheme_picker');
}
add_action('admin_init', 'hengegroup_theme_remove_admin_color_scheme_picker');

function hengegroup_theme_disable_global_styles_output(): void
{
    remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
    remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
    remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
}
add_action('init', 'hengegroup_theme_disable_global_styles_output');

function hengegroup_theme_cleanup_core_frontend_styles(): void
{
    if (is_admin()) {
        return;
    }

    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('classic-theme-styles');
}
add_action('wp_enqueue_scripts', 'hengegroup_theme_cleanup_core_frontend_styles', 999);

// Kommentare zu fuer Beitraege/Seiten, nicht fuer Produkte (Bewertungen nutzen comments_open()).
function hengegroup_theme_filter_close_comments_for_posts_and_pages(bool $open, int $post_id): bool
{
    if (in_array(get_post_type($post_id), ['post', 'page'], true)) {
        return false;
    }

    return $open;
}
add_filter('comments_open', 'hengegroup_theme_filter_close_comments_for_posts_and_pages', 10, 2);
add_filter('pings_open', 'hengegroup_theme_filter_close_comments_for_posts_and_pages', 10, 2);

function hengegroup_theme_action_remove_comments_support_for_posts_and_pages(): void
{
    remove_post_type_support('post', 'comments');
    remove_post_type_support('post', 'trackbacks');
    remove_post_type_support('page', 'comments');
    remove_post_type_support('page', 'trackbacks');
}
add_action('init', 'hengegroup_theme_action_remove_comments_support_for_posts_and_pages');

function hengegroup_theme_delete_default_themes_after_core_update(
    $upgrader,
    array $hook_extra,
): void {
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
    'hengegroup_theme_delete_default_themes_after_core_update',
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
add_filter('admin_footer_text', 'hengegroup_theme_filter_empty_admin_footer_text');
add_filter('wp_headers', 'hengegroup_theme_filter_wp_headers_remove_pingback');
add_filter('should_load_separate_core_block_assets', '__return_true');
