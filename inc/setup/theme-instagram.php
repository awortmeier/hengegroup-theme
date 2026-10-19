<?php

declare(strict_types=1);

// Einstellungen > Instagram: Access-Token des Business-Kontos (Instagram API mit Instagram-Login),
// stuendlicher Abgleich per WP-Cron. Der Block "Instagram" liest nur den gespeicherten Feed.

const HENGEGROUP_THEME_INSTAGRAM_TOKEN_OPTION = 'hengegroup_theme_instagram_token';
const HENGEGROUP_THEME_INSTAGRAM_CRON_HOOK = 'hengegroup_theme_instagram_sync';
const HENGEGROUP_THEME_INSTAGRAM_PAGE = 'hengegroup-theme-instagram';
const HENGEGROUP_THEME_INSTAGRAM_SAVE_ACTION = 'hengegroup_theme_instagram_save';
const HENGEGROUP_THEME_INSTAGRAM_SYNC_ACTION = 'hengegroup_theme_instagram_sync_now';
const HENGEGROUP_THEME_INSTAGRAM_DISCONNECT_ACTION = 'hengegroup_theme_instagram_disconnect';

/**
 * @return array{token: string, refreshed_at: int, expires_at: int}
 */
function hengegroup_theme_get_instagram_token(): array
{
    $stored = get_option(HENGEGROUP_THEME_INSTAGRAM_TOKEN_OPTION, []);
    $stored = is_array($stored) ? $stored : [];

    return [
        'token' => (string) ($stored['token'] ?? ''),
        'refreshed_at' => (int) ($stored['refreshed_at'] ?? 0),
        'expires_at' => (int) ($stored['expires_at'] ?? 0),
    ];
}

function hengegroup_theme_save_instagram_token(array $token): void
{
    // Nicht autoloaden: das Token wird nur im Cron und auf der Einstellungsseite gebraucht.
    update_option(HENGEGROUP_THEME_INSTAGRAM_TOKEN_OPTION, $token, false);
}

/**
 * @return array|WP_Error dekodierte JSON-Antwort
 */
function hengegroup_theme_instagram_api_get(string $path, array $query)
{
    $response = wp_remote_get(
        'https://graph.instagram.com/' . $path . '?' . http_build_query($query),
        ['timeout' => 15],
    );

    if (is_wp_error($response)) {
        return $response;
    }

    $body = json_decode((string) wp_remote_retrieve_body($response), true);
    $code = (int) wp_remote_retrieve_response_code($response);

    if ($code !== 200 || !is_array($body)) {
        $message = is_array($body) ? (string) ($body['error']['message'] ?? '') : '';

        return new WP_Error(
            'hengegroup_theme_instagram_api',
            $message !== '' ? $message : sprintf('HTTP %d', $code),
        );
    }

    return $body;
}

/**
 * Long-lived Tokens gelten 60 Tage und lassen sich ab 24 h Alter verlaengern; woechentlich reicht.
 */
function hengegroup_theme_maybe_refresh_instagram_token(array $token): array
{
    if (time() - $token['refreshed_at'] < WEEK_IN_SECONDS) {
        return $token;
    }

    $result = hengegroup_theme_instagram_api_get('refresh_access_token', [
        'grant_type' => 'ig_refresh_token',
        'access_token' => $token['token'],
    ]);

    if (is_wp_error($result) || empty($result['access_token'])) {
        return $token;
    }

    $token = [
        'token' => (string) $result['access_token'],
        'refreshed_at' => time(),
        'expires_at' => time() + (int) ($result['expires_in'] ?? 0),
    ];
    hengegroup_theme_save_instagram_token($token);

    return $token;
}

function hengegroup_theme_get_instagram_upload_directory(): string
{
    $uploads = wp_upload_dir(null, false);

    return trailingslashit($uploads['basedir']) . HENGEGROUP_THEME_INSTAGRAM_UPLOAD_DIR . '/';
}

/**
 * Laedt das Bild, verkleinert es und speichert es als JPEG (entfernt dabei Metadaten).
 */
function hengegroup_theme_download_instagram_image(string $url, string $destination): bool
{
    require_once ABSPATH . 'wp-admin/includes/file.php';

    $temp_file = download_url($url, 20);

    if (is_wp_error($temp_file)) {
        return false;
    }

    $editor = wp_get_image_editor($temp_file);
    $saved = false;

    if (!is_wp_error($editor)) {
        $editor->resize(1080, 1350);
        $saved = !is_wp_error($editor->save($destination, 'image/jpeg'));
    }

    wp_delete_file($temp_file);

    return $saved;
}

function hengegroup_theme_store_instagram_error(string $message): void
{
    $feed = hengegroup_theme_get_instagram_feed();
    $feed['error'] = $message;
    $feed['error_at'] = time();
    update_option(HENGEGROUP_THEME_INSTAGRAM_FEED_OPTION, $feed);
}

/**
 * Holt Profil und neueste Beitraege. Bei Fehlern bleibt der letzte Stand sichtbar.
 *
 * @return true|WP_Error
 */
function hengegroup_theme_sync_instagram()
{
    $token = hengegroup_theme_get_instagram_token();

    if ($token['token'] === '') {
        return new WP_Error(
            'hengegroup_theme_instagram_token',
            __('Kein Access-Token hinterlegt.', 'hengegroup-theme'),
        );
    }

    $token = hengegroup_theme_maybe_refresh_instagram_token($token);
    $profile = hengegroup_theme_instagram_api_get('me', [
        'fields' => 'username',
        'access_token' => $token['token'],
    ]);
    $media = is_wp_error($profile)
        ? $profile
        : hengegroup_theme_instagram_api_get('me/media', [
            'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp',
            'limit' => HENGEGROUP_THEME_INSTAGRAM_MAX_POSTS,
            'access_token' => $token['token'],
        ]);

    if (is_wp_error($media)) {
        hengegroup_theme_store_instagram_error($media->get_error_message());

        return $media;
    }

    $directory = hengegroup_theme_get_instagram_upload_directory();
    wp_mkdir_p($directory);
    $posts = [];

    foreach ((array) ($media['data'] ?? []) as $item) {
        $post = is_array($item) ? hengegroup_theme_normalize_instagram_media($item) : null;

        if ($post === null) {
            continue;
        }

        $file = $post['id'] . '.jpg';

        if (
            !is_file($directory . $file) &&
            !hengegroup_theme_download_instagram_image($post['image_url'], $directory . $file)
        ) {
            continue;
        }

        $size = wp_getimagesize($directory . $file);
        $posts[] = [
            'file' => $file,
            'width' => is_array($size) ? (int) $size[0] : 0,
            'height' => is_array($size) ? (int) $size[1] : 0,
            'alt' => $post['alt'],
            'permalink' => $post['permalink'],
            'timestamp' => $post['timestamp'],
        ];
    }

    $kept = array_column($posts, 'file');

    foreach (glob($directory . '*.jpg') ?: [] as $path) {
        if (!in_array(basename($path), $kept, true)) {
            wp_delete_file($path);
        }
    }

    update_option(HENGEGROUP_THEME_INSTAGRAM_FEED_OPTION, [
        'username' => (string) ($profile['username'] ?? ''),
        'posts' => $posts,
        'synced_at' => time(),
        'error' => '',
        'error_at' => 0,
    ]);

    return true;
}
add_action(HENGEGROUP_THEME_INSTAGRAM_CRON_HOOK, 'hengegroup_theme_sync_instagram');

function hengegroup_theme_schedule_instagram_sync(): void
{
    $has_token = hengegroup_theme_get_instagram_token()['token'] !== '';
    $scheduled = wp_next_scheduled(HENGEGROUP_THEME_INSTAGRAM_CRON_HOOK);

    if ($has_token && $scheduled === false) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', HENGEGROUP_THEME_INSTAGRAM_CRON_HOOK);
    } elseif (!$has_token && $scheduled !== false) {
        wp_clear_scheduled_hook(HENGEGROUP_THEME_INSTAGRAM_CRON_HOOK);
    }
}
add_action('init', 'hengegroup_theme_schedule_instagram_sync');

function hengegroup_theme_instagram_settings_url(string $notice = ''): string
{
    $url = admin_url('options-general.php?page=' . HENGEGROUP_THEME_INSTAGRAM_PAGE);

    return $notice !== '' ? add_query_arg('instagram_notice', $notice, $url) : $url;
}

function hengegroup_theme_instagram_redirect_after_sync($result): void
{
    wp_safe_redirect(
        hengegroup_theme_instagram_settings_url(is_wp_error($result) ? 'error' : 'synced'),
    );
    exit();
}

function hengegroup_theme_handle_instagram_save(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Keine Berechtigung.', 'hengegroup-theme'), 403);
    }

    check_admin_referer(HENGEGROUP_THEME_INSTAGRAM_SAVE_ACTION);

    $token = isset($_POST['instagram_token'])
        ? sanitize_text_field(wp_unslash($_POST['instagram_token']))
        : '';

    if ($token === '') {
        wp_safe_redirect(hengegroup_theme_instagram_settings_url());
        exit();
    }

    hengegroup_theme_save_instagram_token([
        'token' => $token,
        'refreshed_at' => time(),
        'expires_at' => 0,
    ]);
    hengegroup_theme_schedule_instagram_sync();
    hengegroup_theme_instagram_redirect_after_sync(hengegroup_theme_sync_instagram());
}
add_action(
    'admin_post_' . HENGEGROUP_THEME_INSTAGRAM_SAVE_ACTION,
    'hengegroup_theme_handle_instagram_save',
);

function hengegroup_theme_handle_instagram_sync_now(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Keine Berechtigung.', 'hengegroup-theme'), 403);
    }

    check_admin_referer(HENGEGROUP_THEME_INSTAGRAM_SYNC_ACTION);
    hengegroup_theme_instagram_redirect_after_sync(hengegroup_theme_sync_instagram());
}
add_action(
    'admin_post_' . HENGEGROUP_THEME_INSTAGRAM_SYNC_ACTION,
    'hengegroup_theme_handle_instagram_sync_now',
);

function hengegroup_theme_handle_instagram_disconnect(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Keine Berechtigung.', 'hengegroup-theme'), 403);
    }

    check_admin_referer(HENGEGROUP_THEME_INSTAGRAM_DISCONNECT_ACTION);

    delete_option(HENGEGROUP_THEME_INSTAGRAM_TOKEN_OPTION);
    delete_option(HENGEGROUP_THEME_INSTAGRAM_FEED_OPTION);
    wp_clear_scheduled_hook(HENGEGROUP_THEME_INSTAGRAM_CRON_HOOK);

    foreach (glob(hengegroup_theme_get_instagram_upload_directory() . '*.jpg') ?: [] as $path) {
        wp_delete_file($path);
    }

    wp_safe_redirect(hengegroup_theme_instagram_settings_url('disconnected'));
    exit();
}
add_action(
    'admin_post_' . HENGEGROUP_THEME_INSTAGRAM_DISCONNECT_ACTION,
    'hengegroup_theme_handle_instagram_disconnect',
);

function hengegroup_theme_action_admin_menu_instagram_settings(): void
{
    add_options_page(
        __('Instagram', 'hengegroup-theme'),
        __('Instagram', 'hengegroup-theme'),
        'manage_options',
        HENGEGROUP_THEME_INSTAGRAM_PAGE,
        'hengegroup_theme_render_instagram_settings_page',
    );
}
add_action('admin_menu', 'hengegroup_theme_action_admin_menu_instagram_settings');

function hengegroup_theme_format_instagram_admin_date(int $timestamp): string
{
    return $timestamp > 0
        ? (string) wp_date(get_option('date_format') . ' ' . get_option('time_format'), $timestamp)
        : '–';
}

function hengegroup_theme_render_instagram_action_form(
    string $action,
    string $label,
    string $class = 'secondary',
): void {
    ?>
    <form action="<?php echo esc_url(
        admin_url('admin-post.php'),
    ); ?>" method="post" style="display:inline-block">
        <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>" />
        <?php
        wp_nonce_field($action);
        submit_button($label, $class, 'submit', false);?>
    </form>
    <?php
}

function hengegroup_theme_render_instagram_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $token = hengegroup_theme_get_instagram_token();
    $feed = hengegroup_theme_get_instagram_feed();
    $connected = $token['token'] !== '';
    $notice = isset($_GET['instagram_notice'])
        ? sanitize_key(wp_unslash($_GET['instagram_notice']))
        : '';
    $notices = [
        'synced' => ['success', __('Instagram-Beiträge aktualisiert.', 'hengegroup-theme')],
        'disconnected' => ['success', __('Verbindung getrennt.', 'hengegroup-theme')],
    ];
    $profile_url = hengegroup_theme_get_instagram_profile_url();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Instagram', 'hengegroup-theme'); ?></h1>
        <p>
            <?php esc_html_e(
                'Die neuesten Beiträge des Instagram-Business-Kontos werden stündlich abgeholt und ' .
                    'als Kopie auf dem Server gespeichert. Der Block "Instagram" zeigt sie an.',
                'hengegroup-theme',
            ); ?>
        </p>
        <?php if (isset($notices[$notice])) { ?>
            <div class="notice notice-<?php echo esc_attr($notices[$notice][0]); ?> is-dismissible">
                <p><?php echo esc_html($notices[$notice][1]); ?></p>
            </div>
        <?php } ?>
        <?php if ($feed['error'] !== '') { ?>
            <div class="notice notice-error">
                <p>
                    <?php printf(
                        /* translators: 1: date, 2: error message */
                        esc_html__('Abgleich fehlgeschlagen (%1$s): %2$s', 'hengegroup-theme'),
                        esc_html(hengegroup_theme_format_instagram_admin_date($feed['error_at'])),
                        esc_html($feed['error']),
                    ); ?>
                </p>
            </div>
        <?php } ?>

        <?php if ($connected) { ?>
            <h2><?php esc_html_e('Status', 'hengegroup-theme'); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Konto', 'hengegroup-theme'); ?></th>
                        <td>
                            <?php if ($feed['username'] !== '') { ?>
                                <a href="<?php echo esc_url(
                                    $profile_url,
                                ); ?>" target="_blank" rel="noopener">
                                    @<?php echo esc_html($feed['username']); ?>
                                </a>
                            <?php } else {echo '–';} ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e(
                            'Gespeicherte Beiträge',
                            'hengegroup-theme',
                        ); ?></th>
                        <td><?php echo esc_html((string) count($feed['posts'])); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e(
                            'Letzte Aktualisierung',
                            'hengegroup-theme',
                        ); ?></th>
                        <td><?php echo esc_html(
                            hengegroup_theme_format_instagram_admin_date($feed['synced_at']),
                        ); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e(
                            'Token gültig bis',
                            'hengegroup-theme',
                        ); ?></th>
                        <td>
                            <?php echo esc_html(
                                $token['expires_at'] > 0
                                    ? hengegroup_theme_format_instagram_admin_date(
                                        $token['expires_at'],
                                    )
                                    : __('wird automatisch verlängert', 'hengegroup-theme'),
                            ); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p>
                <?php
                hengegroup_theme_render_instagram_action_form(
                    HENGEGROUP_THEME_INSTAGRAM_SYNC_ACTION,
                    __('Jetzt aktualisieren', 'hengegroup-theme'),
                );
                hengegroup_theme_render_instagram_action_form(
                    HENGEGROUP_THEME_INSTAGRAM_DISCONNECT_ACTION,
                    __('Verbindung trennen', 'hengegroup-theme'),
                    'delete',
                );
                ?>
            </p>
        <?php } ?>

        <h2><?php esc_html_e('Access-Token', 'hengegroup-theme'); ?></h2>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="<?php echo esc_attr(
                HENGEGROUP_THEME_INSTAGRAM_SAVE_ACTION,
            ); ?>" />
            <?php wp_nonce_field(HENGEGROUP_THEME_INSTAGRAM_SAVE_ACTION); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="hengegroup-theme-instagram-token">
                                <?php esc_html_e('Long-lived Access-Token', 'hengegroup-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="password"
                                id="hengegroup-theme-instagram-token"
                                name="instagram_token"
                                class="large-text"
                                autocomplete="off"
                                placeholder="<?php echo esc_attr(
                                    $connected
                                        ? __(
                                            'Gespeichert – nur zum Ersetzen ausfüllen',
                                            'hengegroup-theme',
                                        )
                                        : '',
                                ); ?>"
                            />
                            <p class="description">
                                <?php esc_html_e(
                                    'Meta-App (Instagram API mit Instagram-Login) > Access-Token ' .
                                        'generieren für das Business-Konto. Das Token wird ' .
                                        'automatisch verlängert.',
                                    'hengegroup-theme',
                                ); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button(__('Speichern und abgleichen', 'hengegroup-theme')); ?>
        </form>
    </div>
    <?php
}

/**
 * Feed fuer die Editor-Vorschau des Blocks (ohne Token).
 */
function hengegroup_theme_enqueue_instagram_for_editor(): void
{
    $posts = array_map(
        static fn(array $post): array => ['src' => $post['src'], 'alt' => $post['alt']],
        hengegroup_theme_get_instagram_posts(),
    );

    wp_add_inline_script(
        'hengegroup-theme-instagram-editor',
        'window.hengegroupThemeInstagram = ' .
            wp_json_encode([
                'posts' => $posts,
                'settingsUrl' => current_user_can('manage_options')
                    ? hengegroup_theme_instagram_settings_url()
                    : '',
            ]) .
            ';',
        'before',
    );
}
add_action('enqueue_block_editor_assets', 'hengegroup_theme_enqueue_instagram_for_editor');
