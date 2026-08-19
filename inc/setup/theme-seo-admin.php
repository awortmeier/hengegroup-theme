<?php

declare(strict_types=1);

// Backend fields for the SEO meta strategy described in docs/to-do.md #3 --
// deliberately no SEO plugin (Yoast/RankMath/...), own admin UI instead:
//   - Settings > SEO (this file): site-wide defaults (fallback title template, fallback
//     description, fallback social image, robots defaults) -- always apply unless overridden.
//   - a "SEO" meta box on every post type in hengegroup_theme_get_seo_post_types() (default: post,
//     page; filterable via `hengegroup_theme_seo_post_types` for project themes with custom post
//     types): per-page overrides. Left empty, the site-wide default applies -- see
//     theme-seo-output.php, which resolves the actual per-request fallback chain.
// robots.txt/XML-Sitemap are intentionally not reimplemented here: WordPress core already serves
// both (virtual robots.txt via the `do_robots` hook, respecting Settings > Reading's "discourage
// search engines" checkbox; core XML sitemaps since WP 5.5 via WP_Sitemaps) without any theme
// code -- this file only closes the meta-tag gap core has no opinion on.
//
// Shares one small wp.media() image-picker widget (markup + assets/js/admin/theme-seo.js) between
// this settings page's "Standard Social-Bild" field and the meta box's own "Social-Bild" field,
// see hengegroup_theme_render_seo_image_picker_field()/hengegroup_theme_enqueue_seo_media_picker() below --
// same reuse-over-duplication idea as helpers.php's shared-helper principle, just for admin-side markup instead of a
// template-part. Inline `style` attributes in this file's admin markup are wp-admin UI, not
// front-end component output -- outside CLAUDE.md Regel 1's Tailwind-only scope (see docs
// #3/README "ausserhalb des Drei-Phasen-Modells").

const BASE_THEME_SEO_OPTION = 'hengegroup_theme_seo_options';

/**
 * Returns the global SEO defaults, merged over hard-coded fallbacks so every key is always present
 * regardless of what has been saved so far (e.g. right after theme activation).
 */
function hengegroup_theme_get_seo_options(): array
{
    $defaults = [
        'title_template' => '%title% %sep% %sitename%',
        'title_separator' => '-',
        'description' => '',
        'og_image_id' => 0,
        'twitter_handle' => '',
        'robots_noindex' => false,
        'robots_nofollow' => false,
    ];

    $stored = get_option(BASE_THEME_SEO_OPTION, []);

    return array_merge($defaults, is_array($stored) ? $stored : []);
}

/**
 * Post types that get the "SEO" meta box. Filterable so a project theme with custom post types
 * (e.g. a "Produkt"/"Team"-CPT) can add its own without touching this file.
 */
function hengegroup_theme_get_seo_post_types(): array
{
    $post_types = apply_filters('hengegroup_theme_seo_post_types', ['post', 'page']);

    return is_array($post_types) ? $post_types : ['post', 'page'];
}

function hengegroup_theme_sanitize_seo_options($input): array
{
    $input = is_array($input) ? $input : [];
    $defaults = hengegroup_theme_get_seo_options();

    $title_template = trim((string) ($input['title_template'] ?? ''));
    $title_separator = trim((string) ($input['title_separator'] ?? ''));

    return [
        'title_template' =>
            $title_template !== ''
                ? sanitize_text_field($title_template)
                : $defaults['title_template'],
        'title_separator' =>
            $title_separator !== ''
                ? sanitize_text_field($title_separator)
                : $defaults['title_separator'],
        'description' => sanitize_textarea_field((string) ($input['description'] ?? '')),
        'og_image_id' => absint($input['og_image_id'] ?? 0),
        'twitter_handle' => sanitize_text_field((string) ($input['twitter_handle'] ?? '')),
        'robots_noindex' => !empty($input['robots_noindex']),
        'robots_nofollow' => !empty($input['robots_nofollow']),
    ];
}

function hengegroup_theme_action_admin_init_register_seo_settings(): void
{
    register_setting('hengegroup_theme_seo_options_group', BASE_THEME_SEO_OPTION, [
        'type' => 'array',
        'sanitize_callback' => 'hengegroup_theme_sanitize_seo_options',
        'default' => [],
    ]);
}
add_action('admin_init', 'hengegroup_theme_action_admin_init_register_seo_settings');

function hengegroup_theme_action_admin_menu_seo_settings(): void
{
    add_options_page(
        __('SEO', 'hengegroup-theme'),
        __('SEO', 'hengegroup-theme'),
        'manage_options',
        'hengegroup-theme-seo',
        'hengegroup_theme_render_seo_settings_page',
    );
}
add_action('admin_menu', 'hengegroup_theme_action_admin_menu_seo_settings');

/**
 * wp.media()-backed "select image" field (hidden attachment-id input + preview + select/remove
 * buttons). Used both by the global "Standard Social-Bild" field below and by the per-post SEO
 * meta box's own image field -- assets/js/admin/theme-seo.js wires up every
 * `.hengegroup-theme-seo-image-picker` on the page generically, so this markup is the only thing either
 * caller needs to render.
 */
function hengegroup_theme_render_seo_image_picker_field(
    string $field_id,
    string $field_name,
    int $attachment_id,
): void {
    $image_url = $attachment_id > 0 ? wp_get_attachment_image_url($attachment_id, 'medium') : ''; ?>
    <div class="hengegroup-theme-seo-image-picker">
        <input
            type="hidden"
            id="<?php echo esc_attr($field_id); ?>"
            name="<?php echo esc_attr($field_name); ?>"
            value="<?php echo esc_attr((string) $attachment_id); ?>"
        />
        <div class="hengegroup-theme-seo-image-picker__preview" style="margin-bottom: 8px">
            <?php if (is_string($image_url) && $image_url !== ''): ?>
                <img
                    src="<?php echo esc_url($image_url); ?>"
                    alt=""
                    style="display: block; height: auto; max-width: 200px"
                />
            <?php endif; ?>
        </div>
        <button type="button" class="button hengegroup-theme-seo-image-picker__select">
            <?php esc_html_e('Bild auswaehlen', 'hengegroup-theme'); ?>
        </button>
        <button
            type="button"
            class="button hengegroup-theme-seo-image-picker__remove"
            <?php echo $attachment_id > 0 ? '' : 'style="display: none"'; ?>
        >
            <?php esc_html_e('Entfernen', 'hengegroup-theme'); ?>
        </button>
    </div>
    <?php
}

function hengegroup_theme_enqueue_seo_media_picker(): void
{
    static $enqueued = false;

    if ($enqueued) {
        return;
    }

    $enqueued = true;

    wp_enqueue_media();

    $script_path = get_template_directory() . '/assets/js/admin/theme-seo.js';

    wp_enqueue_script(
        'hengegroup-theme-seo-admin',
        get_template_directory_uri() . '/assets/js/admin/theme-seo.js',
        ['media-editor'],
        file_exists($script_path) ? (string) filemtime($script_path) : false,
        true,
    );
}

function hengegroup_theme_action_admin_enqueue_scripts_seo(string $hook_suffix): void
{
    if ($hook_suffix === 'settings_page_hengegroup-theme-seo') {
        hengegroup_theme_enqueue_seo_media_picker();
        return;
    }

    if ($hook_suffix !== 'post.php' && $hook_suffix !== 'post-new.php') {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, hengegroup_theme_get_seo_post_types(), true)) {
        return;
    }

    hengegroup_theme_enqueue_seo_media_picker();
}
add_action('admin_enqueue_scripts', 'hengegroup_theme_action_admin_enqueue_scripts_seo');

/**
 * Renders "Settings > SEO": site-wide defaults used whenever a post/page's own "SEO" meta box
 * (see below) doesn't set a value -- see theme-seo-output.php for the actual fallback resolution.
 */
function hengegroup_theme_render_seo_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $options = hengegroup_theme_get_seo_options();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('SEO', 'hengegroup-theme'); ?></h1>
        <p>
            <?php esc_html_e(
                'Diese Werte gelten site-weit und werden verwendet, wenn eine einzelne Seite oder ' .
                    'ein Beitrag im eigenen "SEO"-Bereich keine eigenen Angaben hinterlegt.',
                'hengegroup-theme',
            ); ?>
        </p>
        <form action="options.php" method="post">
            <?php settings_fields('hengegroup_theme_seo_options_group'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="hengegroup_theme_seo_options_title_template">
                                <?php esc_html_e('Titel-Vorlage', 'hengegroup-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="hengegroup_theme_seo_options_title_template"
                                name="<?php echo esc_attr(
                                    BASE_THEME_SEO_OPTION,
                                ); ?>[title_template]"
                                value="<?php echo esc_attr($options['title_template']); ?>"
                                class="regular-text"
                            />
                            <p class="description">
                                <?php esc_html_e(
                                    'Platzhalter: %title%, %sitename%, %sep%. Gilt fuer jede Seite ' .
                                        'ohne eigenen SEO-Titel.',
                                    'hengegroup-theme',
                                ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hengegroup_theme_seo_options_title_separator">
                                <?php esc_html_e('Titel-Trenner', 'hengegroup-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="hengegroup_theme_seo_options_title_separator"
                                name="<?php echo esc_attr(
                                    BASE_THEME_SEO_OPTION,
                                ); ?>[title_separator]"
                                value="<?php echo esc_attr($options['title_separator']); ?>"
                                class="small-text"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hengegroup_theme_seo_options_description">
                                <?php esc_html_e('Standard Meta-Beschreibung', 'hengegroup-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <textarea
                                id="hengegroup_theme_seo_options_description"
                                name="<?php echo esc_attr(BASE_THEME_SEO_OPTION); ?>[description]"
                                rows="3"
                                class="large-text"
                            ><?php echo esc_textarea($options['description']); ?></textarea>
                            <p class="description">
                                <?php esc_html_e(
                                    'Fallback, wenn eine Seite keine eigene Meta-Beschreibung ' .
                                        'hinterlegt.',
                                    'hengegroup-theme',
                                ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e(
                            'Standard Social-Bild',
                            'hengegroup-theme',
                        ); ?></th>
                        <td>
                            <?php hengegroup_theme_render_seo_image_picker_field(
                                'hengegroup_theme_seo_options_og_image',
                                BASE_THEME_SEO_OPTION . '[og_image_id]',
                                (int) $options['og_image_id'],
                            ); ?>
                            <p class="description">
                                <?php esc_html_e(
                                    'Fallback fuer og:image/twitter:image, wenn weder die Seite ' .
                                        'noch ihr Beitragsbild eines liefert.',
                                    'hengegroup-theme',
                                ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hengegroup_theme_seo_options_twitter_handle">
                                <?php esc_html_e('Twitter/X-Handle', 'hengegroup-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="hengegroup_theme_seo_options_twitter_handle"
                                name="<?php echo esc_attr(
                                    BASE_THEME_SEO_OPTION,
                                ); ?>[twitter_handle]"
                                value="<?php echo esc_attr($options['twitter_handle']); ?>"
                                class="regular-text"
                                placeholder="@marke"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e(
                            'Indexierung (Standard)',
                            'hengegroup-theme',
                        ); ?></th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="<?php echo esc_attr(
                                        BASE_THEME_SEO_OPTION,
                                    ); ?>[robots_noindex]"
                                    value="1"
                                    <?php checked($options['robots_noindex']); ?>
                                />
                                <?php esc_html_e(
                                    'Seiten standardmaessig von der Suche ausschliessen (noindex)',
                                    'hengegroup-theme',
                                ); ?>
                            </label>
                            <br />
                            <label>
                                <input
                                    type="checkbox"
                                    name="<?php echo esc_attr(
                                        BASE_THEME_SEO_OPTION,
                                    ); ?>[robots_nofollow]"
                                    value="1"
                                    <?php checked($options['robots_nofollow']); ?>
                                />
                                <?php esc_html_e(
                                    'Links standardmaessig nicht folgen (nofollow)',
                                    'hengegroup-theme',
                                ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e(
                                    'Pro Seite im "SEO"-Bereich der Seite/des Beitrags ' .
                                        'ueberschreibbar.',
                                    'hengegroup-theme',
                                ); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function hengegroup_theme_action_add_meta_boxes_seo(): void
{
    foreach (hengegroup_theme_get_seo_post_types() as $post_type) {
        add_meta_box(
            'hengegroup_theme_seo',
            __('SEO', 'hengegroup-theme'),
            'hengegroup_theme_render_seo_meta_box',
            $post_type,
            'normal',
            'high',
        );
    }
}
add_action('add_meta_boxes', 'hengegroup_theme_action_add_meta_boxes_seo');

/**
 * Renders the per-post "SEO" meta box. Every field left empty falls back to the site-wide
 * Settings > SEO default (see theme-seo-output.php for the resolution order).
 */
function hengegroup_theme_render_seo_meta_box(WP_Post $post): void
{
    wp_nonce_field('hengegroup_theme_seo_save_' . $post->ID, 'hengegroup_theme_seo_nonce');

    $title = (string) get_post_meta($post->ID, '_hengegroup_theme_seo_title', true);
    $description = (string) get_post_meta($post->ID, '_hengegroup_theme_seo_description', true);
    $image_id = (int) get_post_meta($post->ID, '_hengegroup_theme_seo_image_id', true);
    $canonical = (string) get_post_meta($post->ID, '_hengegroup_theme_seo_canonical', true);
    $robots_index = (string) get_post_meta($post->ID, '_hengegroup_theme_seo_robots_index', true);
    $robots_follow = (string) get_post_meta($post->ID, '_hengegroup_theme_seo_robots_follow', true);

    $options = hengegroup_theme_get_seo_options();
    ?>
    <p>
        <label for="hengegroup_theme_seo_title">
            <strong><?php esc_html_e('SEO-Titel', 'hengegroup-theme'); ?></strong>
        </label>
        <br />
        <input
            type="text"
            id="hengegroup_theme_seo_title"
            name="hengegroup_theme_seo[title]"
            value="<?php echo esc_attr($title); ?>"
            class="large-text"
        />
        <span class="description">
            <?php echo esc_html(
                sprintf(
                    /* translators: %s: site-wide title template from Settings > SEO. */
                    __('Leer = automatisch aus Titel-Vorlage ("%s").', 'hengegroup-theme'),
                    $options['title_template'],
                ),
            ); ?>
        </span>
    </p>
    <p>
        <label for="hengegroup_theme_seo_description">
            <strong><?php esc_html_e('Meta-Beschreibung', 'hengegroup-theme'); ?></strong>
        </label>
        <br />
        <textarea
            id="hengegroup_theme_seo_description"
            name="hengegroup_theme_seo[description]"
            rows="3"
            class="large-text"
        ><?php echo esc_textarea($description); ?></textarea>
        <span class="description">
            <?php esc_html_e(
                'Leer = allgemeine Standard-Beschreibung bzw. Auszug dieser Seite.',
                'hengegroup-theme',
            ); ?>
        </span>
    </p>
    <p>
        <label for="hengegroup_theme_seo_canonical">
            <strong><?php esc_html_e('Kanonische URL', 'hengegroup-theme'); ?></strong>
        </label>
        <br />
        <input
            type="url"
            id="hengegroup_theme_seo_canonical"
            name="hengegroup_theme_seo[canonical]"
            value="<?php echo esc_attr($canonical); ?>"
            class="large-text"
            placeholder="<?php echo esc_attr((string) get_permalink($post)); ?>"
        />
        <span class="description">
            <?php esc_html_e(
                'Nur bei Duplicate-Content-Faellen noetig, sonst leer lassen.',
                'hengegroup-theme',
            ); ?>
        </span>
    </p>
    <p>
        <strong><?php esc_html_e('Social-Bild', 'hengegroup-theme'); ?></strong>
        <br />
        <?php hengegroup_theme_render_seo_image_picker_field(
            'hengegroup_theme_seo_image_id',
            'hengegroup_theme_seo[image_id]',
            $image_id,
        ); ?>
        <span class="description">
            <?php esc_html_e(
                'Leer = allgemeines Standard-Bild bzw. Beitragsbild dieser Seite.',
                'hengegroup-theme',
            ); ?>
        </span>
    </p>
    <p>
        <label for="hengegroup_theme_seo_robots_index">
            <strong><?php esc_html_e('Indexierung', 'hengegroup-theme'); ?></strong>
        </label>
        <br />
        <select id="hengegroup_theme_seo_robots_index" name="hengegroup_theme_seo[robots_index]">
            <option value="" <?php selected($robots_index, ''); ?>>
                <?php esc_html_e('Standard verwenden', 'hengegroup-theme'); ?>
            </option>
            <option value="index" <?php selected($robots_index, 'index'); ?>>
                <?php esc_html_e('Indexieren', 'hengegroup-theme'); ?>
            </option>
            <option value="noindex" <?php selected($robots_index, 'noindex'); ?>>
                <?php esc_html_e('Nicht indexieren (noindex)', 'hengegroup-theme'); ?>
            </option>
        </select>
    </p>
    <p>
        <label for="hengegroup_theme_seo_robots_follow">
            <strong><?php esc_html_e('Links folgen', 'hengegroup-theme'); ?></strong>
        </label>
        <br />
        <select id="hengegroup_theme_seo_robots_follow" name="hengegroup_theme_seo[robots_follow]">
            <option value="" <?php selected($robots_follow, ''); ?>>
                <?php esc_html_e('Standard verwenden', 'hengegroup-theme'); ?>
            </option>
            <option value="follow" <?php selected($robots_follow, 'follow'); ?>>
                <?php esc_html_e('Folgen', 'hengegroup-theme'); ?>
            </option>
            <option value="nofollow" <?php selected($robots_follow, 'nofollow'); ?>>
                <?php esc_html_e('Nicht folgen (nofollow)', 'hengegroup-theme'); ?>
            </option>
        </select>
    </p>
    <?php
}

function hengegroup_theme_update_or_delete_post_meta(int $post_id, string $meta_key, string $value): void
{
    if ($value === '') {
        delete_post_meta($post_id, $meta_key);
        return;
    }

    update_post_meta($post_id, $meta_key, $value);
}

function hengegroup_theme_action_save_post_seo(int $post_id): void
{
    if (!isset($_POST['hengegroup_theme_seo_nonce'])) {
        return;
    }

    $nonce = sanitize_text_field(wp_unslash($_POST['hengegroup_theme_seo_nonce']));
    if (!wp_verify_nonce($nonce, 'hengegroup_theme_seo_save_' . $post_id)) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (!in_array(get_post_type($post_id), hengegroup_theme_get_seo_post_types(), true)) {
        return;
    }

    // Every value pulled from $data below is sanitized individually right after (sanitize_text_field()/
    // sanitize_textarea_field()/esc_url_raw()/absint()/in_array() allow-list) before it reaches
    // update_post_meta() -- phpcs can't see that deferred sanitization from this line alone.
    $data =
        isset($_POST['hengegroup_theme_seo']) && is_array($_POST['hengegroup_theme_seo'])
            ? wp_unslash($_POST['hengegroup_theme_seo']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            : [];

    hengegroup_theme_update_or_delete_post_meta(
        $post_id,
        '_hengegroup_theme_seo_title',
        sanitize_text_field((string) ($data['title'] ?? '')),
    );
    hengegroup_theme_update_or_delete_post_meta(
        $post_id,
        '_hengegroup_theme_seo_description',
        sanitize_textarea_field((string) ($data['description'] ?? '')),
    );
    hengegroup_theme_update_or_delete_post_meta(
        $post_id,
        '_hengegroup_theme_seo_canonical',
        esc_url_raw((string) ($data['canonical'] ?? '')),
    );

    $image_id = absint($data['image_id'] ?? 0);
    if ($image_id > 0) {
        update_post_meta($post_id, '_hengegroup_theme_seo_image_id', $image_id);
    } else {
        delete_post_meta($post_id, '_hengegroup_theme_seo_image_id');
    }

    $robots_index = (string) ($data['robots_index'] ?? '');
    hengegroup_theme_update_or_delete_post_meta(
        $post_id,
        '_hengegroup_theme_seo_robots_index',
        in_array($robots_index, ['index', 'noindex'], true) ? $robots_index : '',
    );

    $robots_follow = (string) ($data['robots_follow'] ?? '');
    hengegroup_theme_update_or_delete_post_meta(
        $post_id,
        '_hengegroup_theme_seo_robots_follow',
        in_array($robots_follow, ['follow', 'nofollow'], true) ? $robots_follow : '',
    );
}
add_action('save_post', 'hengegroup_theme_action_save_post_seo');
