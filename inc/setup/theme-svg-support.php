<?php

declare(strict_types=1);

// SVG-Uploads: nur mit hengegroup_theme_svg_upload_capability() und immer ueber
// enshrined/svg-sanitize bereinigt. Fehlt die Bibliothek, sind SVGs fuer niemanden erlaubt.

function hengegroup_theme_svg_upload_capability(): string
{
    return (string) apply_filters('hengegroup_theme_svg_upload_capability', 'manage_options');
}

function hengegroup_theme_current_user_can_upload_svg(): bool
{
    return class_exists(\enshrined\svgSanitize\Sanitizer::class) &&
        current_user_can(hengegroup_theme_svg_upload_capability());
}

function hengegroup_theme_add_svg_upload_mime(array $mimes): array
{
    if (!hengegroup_theme_current_user_can_upload_svg()) {
        return $mimes;
    }

    $mimes['svg'] = 'image/svg+xml';

    return $mimes;
}
add_filter('upload_mimes', 'hengegroup_theme_add_svg_upload_mime');

/**
 * WordPress erkennt image/svg+xml am Inhalt oft nicht und liefert ein leeres `type`.
 */
function hengegroup_theme_fix_svg_filetype_check(
    array $data,
    string $file,
    string $filename,
    $mimes,
): array {
    if (!empty($data['type']) || !hengegroup_theme_current_user_can_upload_svg()) {
        return $data;
    }

    $check = wp_check_filetype($filename, is_array($mimes) ? $mimes : null);

    if ($check['ext'] === 'svg') {
        $data['ext'] = 'svg';
        $data['type'] = 'image/svg+xml';
    }

    return $data;
}
add_filter('wp_check_filetype_and_ext', 'hengegroup_theme_fix_svg_filetype_check', 10, 4);

/**
 * Bereinigt die SVG vor move_uploaded_file(); bei Fehler oder fehlender Berechtigung/Bibliothek
 * wird der Upload per `error` abgebrochen.
 */
function hengegroup_theme_sanitize_svg_upload(array $file): array
{
    if (($file['type'] ?? '') !== 'image/svg+xml') {
        return $file;
    }

    if (!hengegroup_theme_current_user_can_upload_svg()) {
        $file['error'] = __(
            'SVG-Uploads sind fuer deine Benutzerrolle nicht erlaubt.',
            'hengegroup-theme',
        );

        return $file;
    }

    $original = file_get_contents($file['tmp_name']);

    if ($original === false || trim($original) === '') {
        $file['error'] = __('SVG-Datei konnte nicht gelesen werden.', 'hengegroup-theme');

        return $file;
    }

    $sanitizer = new \enshrined\svgSanitize\Sanitizer();
    $sanitizer->removeRemoteReferences(true);
    $clean = $sanitizer->sanitize($original);

    if ($clean === false || trim($clean) === '') {
        $file['error'] = __(
            'SVG-Datei konnte nicht sicher bereinigt werden und wurde abgelehnt.',
            'hengegroup-theme',
        );

        return $file;
    }

    file_put_contents($file['tmp_name'], $clean);

    return $file;
}
add_filter('wp_handle_upload_prefilter', 'hengegroup_theme_sanitize_svg_upload');

/**
 * width/height aus dem <svg>-Root, sonst aus viewBox; null bei ungueltigem SVG.
 */
function hengegroup_theme_get_svg_dimensions(string $file_path): ?array
{
    if (!is_file($file_path)) {
        return null;
    }

    $content = file_get_contents($file_path);

    if ($content === false || trim($content) === '') {
        return null;
    }

    $previous_setting = libxml_use_internal_errors(true);
    $xml = simplexml_load_string($content);
    libxml_clear_errors();
    libxml_use_internal_errors($previous_setting);

    if ($xml === false) {
        return null;
    }

    $attributes = $xml->attributes();
    $width = (string) ($attributes['width'] ?? '');
    $height = (string) ($attributes['height'] ?? '');

    if (is_numeric($width) && is_numeric($height) && (float) $width > 0 && (float) $height > 0) {
        return ['width' => (int) round((float) $width), 'height' => (int) round((float) $height)];
    }

    $view_box = trim((string) ($attributes['viewBox'] ?? ''));

    if ($view_box === '') {
        return null;
    }

    $view_box_parts = preg_split('/[\s,]+/', $view_box);

    if (!is_array($view_box_parts) || count($view_box_parts) !== 4) {
        return null;
    }

    [, , $view_box_width, $view_box_height] = $view_box_parts;

    if (
        !is_numeric($view_box_width) ||
        !is_numeric($view_box_height) ||
        (float) $view_box_width <= 0 ||
        (float) $view_box_height <= 0
    ) {
        return null;
    }

    return [
        'width' => (int) round((float) $view_box_width),
        'height' => (int) round((float) $view_box_height),
    ];
}

/**
 * width/height fuer SVG-Attachments (getimagesize() kann kein SVG).
 */
function hengegroup_theme_generate_svg_attachment_metadata($metadata, int $attachment_id)
{
    if (get_post_mime_type($attachment_id) !== 'image/svg+xml') {
        return $metadata;
    }

    $file_path = get_attached_file($attachment_id);

    if (!is_string($file_path) || $file_path === '') {
        return $metadata;
    }

    $dimensions = hengegroup_theme_get_svg_dimensions($file_path);

    if ($dimensions === null) {
        return $metadata;
    }

    $metadata = is_array($metadata) ? $metadata : [];
    $metadata['width'] = $dimensions['width'];
    $metadata['height'] = $dimensions['height'];

    return $metadata;
}
add_filter(
    'wp_generate_attachment_metadata',
    'hengegroup_theme_generate_svg_attachment_metadata',
    10,
    2,
);
