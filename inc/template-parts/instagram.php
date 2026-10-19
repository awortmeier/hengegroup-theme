<?php

declare(strict_types=1);

// Instagram-Feed aus Einstellungen > Instagram (Sync in theme-instagram.php). Bilder liegen als
// lokale Kopien unter uploads/hengegroup-instagram/, Besucher laden nichts von Instagram.

const HENGEGROUP_THEME_INSTAGRAM_FEED_OPTION = 'hengegroup_theme_instagram_feed';
const HENGEGROUP_THEME_INSTAGRAM_UPLOAD_DIR = 'hengegroup-instagram';
const HENGEGROUP_THEME_INSTAGRAM_MAX_POSTS = 12;

/**
 * @return array{username: string, posts: array, synced_at: int, error: string, error_at: int}
 */
function hengegroup_theme_get_instagram_feed(): array
{
    $stored = get_option(HENGEGROUP_THEME_INSTAGRAM_FEED_OPTION, []);
    $stored = is_array($stored) ? $stored : [];

    return [
        'username' => (string) ($stored['username'] ?? ''),
        'posts' => is_array($stored['posts'] ?? null) ? $stored['posts'] : [],
        'synced_at' => (int) ($stored['synced_at'] ?? 0),
        'error' => (string) ($stored['error'] ?? ''),
        'error_at' => (int) ($stored['error_at'] ?? 0),
    ];
}

/**
 * Alt-Text aus der Caption: ohne Hashtags, auf ganze Woerter gekuerzt.
 */
function hengegroup_theme_instagram_alt_text(string $caption, int $max_length = 150): string
{
    $text = preg_replace('/(^|\s)#[^\s#]+/u', ' ', $caption) ?? $caption;
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

    if (mb_strlen($text) <= $max_length) {
        return $text;
    }

    $cut = mb_substr($text, 0, $max_length);
    $last_space = mb_strrpos($cut, ' ');

    if ($last_space !== false && $last_space > $max_length / 2) {
        $cut = mb_substr($cut, 0, $last_space);
    }

    return rtrim($cut, " \t.,;:!?-") . '…';
}

/**
 * Ein Eintrag aus /me/media; null, wenn kein Bild oder unerwartete URLs.
 *
 * @return array{id: string, image_url: string, permalink: string, alt: string, timestamp: int}|null
 */
function hengegroup_theme_normalize_instagram_media(array $item): ?array
{
    $id = (string) ($item['id'] ?? '');
    $type = (string) ($item['media_type'] ?? '');
    $image_url =
        (string) ($type === 'VIDEO' ? $item['thumbnail_url'] ?? '' : $item['media_url'] ?? '');
    $permalink = (string) ($item['permalink'] ?? '');

    if (
        !preg_match('/^\d+$/', $id) ||
        !in_array($type, ['IMAGE', 'CAROUSEL_ALBUM', 'VIDEO'], true) ||
        !str_starts_with($image_url, 'https://') ||
        !preg_match('#^https://(www\.)?instagram\.com/#', $permalink)
    ) {
        return null;
    }

    $timestamp = strtotime((string) ($item['timestamp'] ?? ''));

    return [
        'id' => $id,
        'image_url' => $image_url,
        'permalink' => $permalink,
        'alt' => hengegroup_theme_instagram_alt_text((string) ($item['caption'] ?? '')),
        'timestamp' => $timestamp === false ? 0 : $timestamp,
    ];
}

function hengegroup_theme_get_instagram_profile_url(): string
{
    $username = hengegroup_theme_get_instagram_feed()['username'];

    if (preg_match('/^[A-Za-z0-9._]{1,30}$/', $username)) {
        return 'https://www.instagram.com/' . $username . '/';
    }

    return function_exists('hengegroup_theme_get_footer_options')
        ? (string) hengegroup_theme_get_footer_options()['instagram_url']
        : '';
}

/**
 * Beitraege fuer die Ausgabe; nur solche, deren lokale Bildkopie existiert.
 *
 * @return list<array{src: string, width: int, height: int, alt: string, permalink: string}>
 */
function hengegroup_theme_get_instagram_posts(
    int $limit = HENGEGROUP_THEME_INSTAGRAM_MAX_POSTS,
): array {
    $uploads = wp_upload_dir(null, false);
    $directory = trailingslashit($uploads['basedir']) . HENGEGROUP_THEME_INSTAGRAM_UPLOAD_DIR . '/';
    $base_url = trailingslashit($uploads['baseurl']) . HENGEGROUP_THEME_INSTAGRAM_UPLOAD_DIR . '/';
    $posts = [];

    foreach (hengegroup_theme_get_instagram_feed()['posts'] as $post) {
        $file = (string) ($post['file'] ?? '');

        if (!preg_match('/^\d+\.jpg$/', $file) || !is_file($directory . $file)) {
            continue;
        }

        $alt = (string) ($post['alt'] ?? '');
        $timestamp = (int) ($post['timestamp'] ?? 0);

        // Das Bild ist der einzige Inhalt des Links und braucht deshalb immer einen Alt-Text.
        if ($alt === '') {
            $alt =
                $timestamp > 0
                    ? sprintf(
                        /* translators: %s: date */
                        __('Instagram-Beitrag vom %s', 'hengegroup-theme'),
                        wp_date((string) get_option('date_format'), $timestamp),
                    )
                    : __('Instagram-Beitrag', 'hengegroup-theme');
        }

        $posts[] = [
            'src' => $base_url . $file,
            'width' => (int) ($post['width'] ?? 0),
            'height' => (int) ($post['height'] ?? 0),
            'alt' => $alt,
            'permalink' => (string) ($post['permalink'] ?? ''),
        ];

        if (count($posts) >= $limit) {
            break;
        }
    }

    return $posts;
}
