<?php

declare(strict_types=1);

// SVG-Upload-Support fuer die Media Library, standardmaessig von WordPress blockiert -- eine SVG-
// Datei kann <script>/Event-Handler-Attribute/externe Referenzen enthalten (gespeichertes XSS).
// Siehe docs/entscheidungen.md "SVG-Upload-Support" fuer die volle Begruendung. Zwei Ebenen:
//   1. Nur Nutzer mit hengegroup_theme_svg_upload_capability() (Default: 'manage_options', per
//      Filter anpassbar, siehe docs/how-to.md) duerfen SVG ueberhaupt als Dateityp waehlen.
//   2. JEDE hochgeladene SVG-Datei wird ueber enshrined/svg-sanitize bereinigt (Script-Tags/
//      Event-Handler/externe Referenzen entfernt), BEVOR sie an ihren finalen Ort verschoben wird.
// Fail-closed: fehlt die Sanitizer-Bibliothek (z. B. `composer install` vergessen, vendor/ nicht
// mitgeliefert), bleibt SVG fuer NIEMANDEN erlaubt -- niemals ungesanitizt durchlassen, nur weil
// die Bibliothek fehlt.

function hengegroup_theme_svg_upload_capability(): string
{
    /**
     * Filtert die Capability, die fuer SVG-Uploads in der Media Library noetig ist.
     *
     * @param string $capability Default: 'manage_options' (i. d. R. nur Administratoren).
     */
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
 * WordPress' eigene Mime-Sniffing-Pruefung (wp_check_filetype_and_ext(), nutzt intern
 * finfo/getimagesize) erkennt image/svg+xml oft nicht zuverlaessig anhand des Dateiinhalts und
 * liefert dann ein leeres `type`, obwohl die Endung `.svg` bereits ueber wp_check_filetype()
 * gegen die (fuer diesen Nutzer erweiterte) Mime-Liste erlaubt waere -- Standard-Fix.
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
 * Bereinigt eine hochgeladene SVG-Datei ueber enshrined/svg-sanitize, BEVOR WordPress sie per
 * move_uploaded_file() an ihren finalen Ort verschiebt ($file['tmp_name'] ist zu diesem Zeitpunkt
 * noch die PHP-Temp-Datei). Kein `$file['error']`-Wert -> WordPress bricht den Upload mit dieser
 * Meldung ab, statt die Datei zu speichern -- greift bei fehlgeschlagenem Sanitizing (kaputtes
 * XML) UND bei fehlender Capability/Bibliothek (defense in depth: der `upload_mimes`-Filter oben
 * sollte SVG fuer diesen Fall bereits gar nicht erst als Option angeboten haben).
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
 * Parst width/height aus dem <svg>-Root-Element einer Datei -- reine Funktion, kein WP-
 * Funktionsaufruf, direkt mit PHPUnit testbar (siehe tests/Unit/SvgSupportTest.php). Faellt auf
 * viewBox zurueck, wenn width/height als Attribute fehlen (z. B. bei rein prozentual/responsiv
 * exportierten SVGs). Gibt null bei ungueltigem/fehlendem SVG-Root zurueck.
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
 * wp_get_attachment_image_src()/damit template-parts/base/image.php's `attachment_id`-Aufloesung
 * bekommt fuer SVG-Attachments sonst kein width/height (WordPress' eigene getimagesize()-basierte
 * Metadaten-Generierung versteht kein SVG).
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
