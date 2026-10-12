<?php

declare(strict_types=1);

// SEO-Ausgabe: title, description, canonical, robots, Open Graph/Twitter, JSON-LD. Fallback je
// Wert: SEO-Box der Seite -> Einstellungen > SEO -> berechneter Default -> weglassen.

/**
 * Post-ID, deren SEO-Box gilt; 0 bei Archiven/Suche/404. Die Beitragsseite zaehlt mit.
 */
function hengegroup_theme_seo_current_post_id(): int
{
    if (is_singular()) {
        return (int) get_queried_object_id();
    }

    if (is_home()) {
        $page_for_posts = (int) get_option('page_for_posts');
        if ($page_for_posts > 0) {
            return $page_for_posts;
        }
    }

    return 0;
}

function hengegroup_theme_filter_pre_get_document_title(string $title): string
{
    $post_id = hengegroup_theme_seo_current_post_id();
    if ($post_id <= 0) {
        return $title;
    }

    $seo_title = trim((string) get_post_meta($post_id, '_hengegroup_theme_seo_title', true));
    if ($seo_title !== '') {
        return $seo_title;
    }

    $options = hengegroup_theme_get_seo_options();
    $template = trim($options['title_template']);
    if ($template === '') {
        return $title;
    }

    $replacements = [
        '%title%' => get_the_title($post_id),
        '%sitename%' => get_bloginfo('name'),
        '%sep%' => $options['title_separator'],
    ];

    return trim(strtr($template, $replacements));
}
add_filter('pre_get_document_title', 'hengegroup_theme_filter_pre_get_document_title', 20);

/**
 * Filterbar ueber `hengegroup_theme_seo_description`.
 */
function hengegroup_theme_get_seo_description(): string
{
    $post_id = hengegroup_theme_seo_current_post_id();

    return (string) apply_filters(
        'hengegroup_theme_seo_description',
        hengegroup_theme_resolve_seo_description($post_id),
        $post_id,
    );
}

function hengegroup_theme_resolve_seo_description(int $post_id): string
{
    if ($post_id > 0) {
        $meta = trim((string) get_post_meta($post_id, '_hengegroup_theme_seo_description', true));
        if ($meta !== '') {
            return $meta;
        }
    }

    $options = hengegroup_theme_get_seo_options();
    if ($options['description'] !== '') {
        return $options['description'];
    }

    if ($post_id > 0) {
        $excerpt = trim(wp_strip_all_tags(get_the_excerpt($post_id)));
        if ($excerpt !== '') {
            return $excerpt;
        }
    }

    return '';
}

function hengegroup_theme_get_seo_canonical(): string
{
    if (is_404()) {
        return '';
    }

    $post_id = hengegroup_theme_seo_current_post_id();

    if ($post_id > 0) {
        $meta = trim((string) get_post_meta($post_id, '_hengegroup_theme_seo_canonical', true));
        if ($meta !== '') {
            return esc_url_raw($meta);
        }

        $canonical = wp_get_canonical_url($post_id);
        if (is_string($canonical) && $canonical !== '') {
            return $canonical;
        }
    }

    if (is_front_page()) {
        return home_url('/');
    }

    global $wp;
    if (isset($wp) && is_object($wp)) {
        return home_url(add_query_arg([], $wp->request));
    }

    return '';
}

function hengegroup_theme_get_seo_robots(): string
{
    $options = hengegroup_theme_get_seo_options();
    $noindex = (bool) $options['robots_noindex'];
    $nofollow = (bool) $options['robots_nofollow'];

    $post_id = hengegroup_theme_seo_current_post_id();
    if ($post_id > 0) {
        $robots_index = (string) get_post_meta(
            $post_id,
            '_hengegroup_theme_seo_robots_index',
            true,
        );
        if ($robots_index === 'index') {
            $noindex = false;
        } elseif ($robots_index === 'noindex') {
            $noindex = true;
        }

        $robots_follow = (string) get_post_meta(
            $post_id,
            '_hengegroup_theme_seo_robots_follow',
            true,
        );
        if ($robots_follow === 'follow') {
            $nofollow = false;
        } elseif ($robots_follow === 'nofollow') {
            $nofollow = true;
        }
    }

    if (is_search() || is_404()) {
        $noindex = true;
    }

    if ((int) get_option('blog_public') === 0) {
        $noindex = true;
    }

    return ($noindex ? 'noindex' : 'index') . ', ' . ($nofollow ? 'nofollow' : 'follow');
}

/**
 * ['url', 'width', 'height'] oder []: SEO-Box -> Standardbild -> Beitragsbild.
 */
function hengegroup_theme_get_seo_image(): array
{
    $attachment_id = 0;
    $post_id = hengegroup_theme_seo_current_post_id();

    if ($post_id > 0) {
        $meta_id = (int) get_post_meta($post_id, '_hengegroup_theme_seo_image_id', true);
        if ($meta_id > 0) {
            $attachment_id = $meta_id;
        }
    }

    if ($attachment_id <= 0) {
        $options = hengegroup_theme_get_seo_options();
        if ((int) $options['og_image_id'] > 0) {
            $attachment_id = (int) $options['og_image_id'];
        }
    }

    if ($attachment_id <= 0 && $post_id > 0 && has_post_thumbnail($post_id)) {
        $attachment_id = (int) get_post_thumbnail_id($post_id);
    }

    if ($attachment_id <= 0) {
        return [];
    }

    $image_source = wp_get_attachment_image_src($attachment_id, 'large');
    if (!is_array($image_source)) {
        return [];
    }

    return [
        'url' => (string) $image_source[0],
        'width' => (int) ($image_source[1] ?? 0),
        'height' => (int) ($image_source[2] ?? 0),
    ];
}

function hengegroup_theme_get_seo_organization_schema(): array
{
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => get_bloginfo('name'),
        'url' => home_url('/'),
    ];

    $description = trim(get_bloginfo('description'));
    if ($description !== '') {
        $schema['description'] = $description;
    }

    if (has_custom_logo()) {
        $logo_id = (int) get_theme_mod('custom_logo');
        $logo_source = $logo_id > 0 ? wp_get_attachment_image_src($logo_id, 'full') : false;
        if (is_array($logo_source)) {
            $schema['logo'] = $logo_source[0];
        }
    }

    return $schema;
}

/**
 * Je Schema ein eigenes <script>. Filter `hengegroup_theme_seo_structured_data` ($schemas, $post_id).
 */
function hengegroup_theme_get_seo_structured_data(): array
{
    $schemas = apply_filters(
        'hengegroup_theme_seo_structured_data',
        [hengegroup_theme_get_seo_organization_schema()],
        hengegroup_theme_seo_current_post_id(),
    );

    return array_values(array_filter(is_array($schemas) ? $schemas : [], 'is_array'));
}

function hengegroup_theme_action_wp_head_seo_structured_data(): void
{
    foreach (hengegroup_theme_get_seo_structured_data() as $schema) {
        // wp_json_encode() escaped "/" und verhindert so ein "</script>" im Wert.
        $json = wp_json_encode($schema, JSON_UNESCAPED_UNICODE);
        if (!is_string($json) || $json === '') {
            continue;
        }

        echo '<script type="application/ld+json">' . $json . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
add_action('wp_head', 'hengegroup_theme_action_wp_head_seo_structured_data', 5);

function hengegroup_theme_action_wp_head_seo_meta_tags(): void
{
    $description = hengegroup_theme_get_seo_description();
    $canonical = hengegroup_theme_get_seo_canonical();
    $robots = hengegroup_theme_get_seo_robots();
    $image = hengegroup_theme_get_seo_image();
    $options = hengegroup_theme_get_seo_options();
    $title = wp_get_document_title();
    $type = is_singular('post') ? 'article' : 'website';

    echo '<meta name="robots" content="' . esc_attr($robots) . '">' . "\n";

    if ($description !== '') {
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }

    if ($canonical !== '') {
        echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    }

    echo '<meta property="og:type" content="' . esc_attr($type) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";

    if ($description !== '') {
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    }

    if ($canonical !== '') {
        echo '<meta property="og:url" content="' . esc_url($canonical) . '">' . "\n";
    }

    echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";

    if (!empty($image['url'])) {
        echo '<meta property="og:image" content="' . esc_url($image['url']) . '">' . "\n";

        if (!empty($image['width'])) {
            echo '<meta property="og:image:width" content="' .
                esc_attr((string) $image['width']) .
                '">' .
                "\n";
        }

        if (!empty($image['height'])) {
            echo '<meta property="og:image:height" content="' .
                esc_attr((string) $image['height']) .
                '">' .
                "\n";
        }
    }

    echo '<meta name="twitter:card" content="' .
        esc_attr(!empty($image['url']) ? 'summary_large_image' : 'summary') .
        '">' .
        "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";

    if ($description !== '') {
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
    }

    if (!empty($image['url'])) {
        echo '<meta name="twitter:image" content="' . esc_url($image['url']) . '">' . "\n";
    }

    if ($options['twitter_handle'] !== '') {
        echo '<meta name="twitter:site" content="' .
            esc_attr($options['twitter_handle']) .
            '">' .
            "\n";
    }
}
add_action('wp_head', 'hengegroup_theme_action_wp_head_seo_meta_tags', 1);

// Nur ein canonical (unserer beruecksichtigt die SEO-Box).
remove_action('wp_head', 'rel_canonical');
