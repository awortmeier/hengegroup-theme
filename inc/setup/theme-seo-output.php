<?php

declare(strict_types=1);

// Resolves + prints the actual SEO tags for the current front-end request: <title> (via the
// pre_get_document_title filter, cooperating with add_theme_support('title-tag') from
// theme-setup.php), meta description, canonical link, robots meta, Open Graph + Twitter Card
// tags, and a JSON-LD Organization schema. Every resolver below follows the same fallback chain:
// per-page "SEO" meta box value (theme-seo-admin.php) -> site-wide Settings > SEO default
// (hengegroup_theme_get_seo_options(), same file) -> a sensible computed default (post excerpt,
// featured image, permalink, ...) -> omitted entirely. See docs/to-do.md #3.
//
// Structured data (hengegroup_theme_get_seo_structured_data() below) is extensible via the
// `hengegroup_theme_seo_structured_data` filter; hreflang is intentionally not implemented here -- see
// docs/how-to.md for the extension HowTo and docs/entscheidungen.md for the multilingual decision.

/**
 * The post id whose "SEO" meta box values apply to the current request, or 0 when none does
 * (archives, search, 404, a blog index that isn't a "page for posts"). Singular views are the
 * obvious case; is_home() additionally needs the "page for posts" special-case, because WordPress
 * treats that view as an archive (is_singular() is false) even though it's backed by a real, SEO-
 * meta-box-editable Page.
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

function hengegroup_theme_get_seo_description(): string
{
    $post_id = hengegroup_theme_seo_current_post_id();

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

    // Search results and 404s are never worth indexing, regardless of the site-wide/per-page
    // setting above -- same convention every major SEO plugin applies.
    if (is_search() || is_404()) {
        $noindex = true;
    }

    // Respect Settings > Reading's "discourage search engines" checkbox site-wide, same as core's
    // own virtual robots.txt already does.
    if ((int) get_option('blog_public') === 0) {
        $noindex = true;
    }

    return ($noindex ? 'noindex' : 'index') . ', ' . ($nofollow ? 'nofollow' : 'follow');
}

/**
 * Resolves the effective social-share image as ['url' => ..., 'width' => ..., 'height' => ...],
 * or [] when nothing resolves. Fallback chain: per-page image -> site-wide default image ->
 * featured image of the current post -> nothing.
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

/**
 * The base Organization schema, always included -- reuses the same signals already trusted
 * elsewhere in the theme instead of adding a redundant admin field: Settings > General's site
 * title/tagline (get_bloginfo()) and the custom logo from add_theme_support('custom-logo')
 * (theme-setup.php), same has_custom_logo()/get_theme_mod('custom_logo') pair header.php already
 * relies on for the visible header logo.
 */
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
 * All JSON-LD schema objects for the current request, each rendered as its own
 * <script type="application/ld+json"> tag (valid per the JSON-LD spec, and simpler for additive
 * filter callbacks than merging into one shared @graph). Starts with the base Organization schema
 * above; the `hengegroup_theme_seo_structured_data` filter is where page-type-specific schema gets
 * added later -- callbacks receive the full array (append their own entry, or replace/remove an
 * existing one by @type) plus the resolved post id for the current request (0 outside a
 * singular/page-for-posts context, see hengegroup_theme_seo_current_post_id()) to decide whether/what
 * to add. See docs/how-to.md for a usage example.
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
        // wp_json_encode() escapes forward slashes by default (no JSON_UNESCAPED_SLASHES), which
        // keeps a literal "</script>" from ever appearing in a string value and breaking out of
        // the tag below -- still valid JSON, just written as "<\/script>".
        $json = wp_json_encode($schema, JSON_UNESCAPED_UNICODE);
        if (!is_string($json) || $json === '') {
            continue;
        }

        // esc_html() would corrupt the JSON (encodes quotes/ampersands); safety instead comes
        // from the escaped slashes above plus wp_json_encode()'s own string escaping of every
        // value.
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
