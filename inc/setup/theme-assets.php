<?php

declare(strict_types=1);

function hengegroup_theme_get_vite_manifest_path(): string
{
    return get_template_directory() . '/assets/.vite/manifest.json';
}

function hengegroup_theme_log_vite_error(string $message): void
{
    static $seen_messages = [];

    $message = trim($message);
    if ($message === '' || isset($seen_messages[$message])) {
        return;
    }

    $seen_messages[$message] = true;
    error_log('[hengegroup-theme-vite] ' . $message);
}

function hengegroup_theme_get_vite_manifest(): array
{
    static $manifest = null;

    if ($manifest !== null) {
        return $manifest;
    }

    $manifest_path = hengegroup_theme_get_vite_manifest_path();
    if (!file_exists($manifest_path)) {
        hengegroup_theme_log_vite_error('Vite manifest fehlt: ' . $manifest_path);
        $manifest = [];
        return $manifest;
    }

    $decoded = json_decode((string) file_get_contents($manifest_path), true);
    if (!is_array($decoded)) {
        hengegroup_theme_log_vite_error('Vite manifest ist ungueltig JSON: ' . $manifest_path);
        $manifest = [];
        return $manifest;
    }

    $manifest = $decoded;
    return $manifest;
}

/**
 * Resolves the THEME-RELATIVE path (e.g. "assets/css/app-xxxx.css", NOT an absolute URI) of a Vite
 * manifest entry's compiled CSS -- e.g. the `app` entry's compiled Tailwind output. Used by
 * hengegroup_theme_theme_setup() (inc/setup/theme-setup.php) to pass the same compiled stylesheet
 * to add_editor_style(), which loads it into the block editor's iframed canvas.
 *
 * Deliberately theme-RELATIVE, not the absolute URI hengegroup_theme_get_vite_asset_uri() would
 * give (bug fixed 2026-09-22, see docs/entscheidungen.md): WordPress's add_editor_style()
 * special-cases the two shapes differently (get_block_editor_theme_styles() in WP core). A
 * relative path is read directly off disk (get_theme_file_path()) and gets a correct `baseURL` the
 * editor iframe uses to rewrite this stylesheet's relative `url(...)` references (this theme's
 * `@font-face src: url(../fonts/...)` in particular) against. A full "https://..."-URI instead
 * gets fetched ONCE via `wp_remote_get()` and inlined WITHOUT that baseURL rewrite -- every
 * relative `url(...)` in it then resolves against nothing, breaking e.g. the accent font
 * specifically inside the editor while the frontend's normally `<link>`-loaded copy of the exact
 * same stylesheet stays unaffected (relative URLs there resolve against the linked file itself).
 *
 * Returns null when the entry/file can't be resolved (missing manifest, missing on disk), same
 * "log via hengegroup_theme_log_vite_error(), never fatal" contract as the other Vite helpers in
 * this file.
 */
function hengegroup_theme_get_vite_style_relative_path(string $entry): ?string
{
    $manifest = hengegroup_theme_get_vite_manifest();
    $entry_asset = $manifest[$entry] ?? null;

    if (!is_array($entry_asset)) {
        hengegroup_theme_log_vite_error('Vite style entry fehlt im Manifest: ' . $entry);
        return null;
    }

    $file = $entry_asset['file'] ?? null;
    $file = is_string($file) ? trim($file) : '';

    if ($file === '' || !str_ends_with($file, '.css')) {
        $css_files = is_array($entry_asset['css'] ?? null) ? $entry_asset['css'] : [];
        $file = is_string($css_files[0] ?? null) ? trim($css_files[0]) : '';
    }

    if ($file === '') {
        hengegroup_theme_log_vite_error('Vite style entry hat kein CSS im Manifest: ' . $entry);
        return null;
    }

    $absolute_path = hengegroup_theme_get_vite_asset_path($file);
    if (!file_exists($absolute_path)) {
        hengegroup_theme_log_vite_error('Vite stylesheet fehlt: ' . $absolute_path);
        return null;
    }

    return 'assets/' . ltrim($file, '/');
}

function hengegroup_theme_get_vite_asset_path(string $relative_path): string
{
    return get_template_directory() . '/assets/' . ltrim($relative_path, '/');
}

function hengegroup_theme_get_vite_asset_uri(string $relative_path): string
{
    return get_template_directory_uri() . '/assets/' . ltrim($relative_path, '/');
}

function hengegroup_theme_enqueue_vite_entry_styles(string $handle, array $entry_asset): void
{
    $css_files = $entry_asset['css'] ?? [];
    if (!is_array($css_files)) {
        return;
    }

    foreach ($css_files as $index => $css_file) {
        $css_file = is_string($css_file) ? trim($css_file) : '';
        if ($css_file === '') {
            continue;
        }

        $absolute_path = hengegroup_theme_get_vite_asset_path($css_file);
        if (!file_exists($absolute_path)) {
            hengegroup_theme_log_vite_error('Vite stylesheet fehlt: ' . $absolute_path);
            continue;
        }

        wp_enqueue_style(
            $handle . '-css-' . $index,
            hengegroup_theme_get_vite_asset_uri($css_file),
            [],
            filemtime($absolute_path),
        );
    }
}

function hengegroup_theme_enqueue_vite_style_entry(string $handle, string $entry): bool
{
    $manifest = hengegroup_theme_get_vite_manifest();
    $entry_asset = $manifest[$entry] ?? null;

    if (!is_array($entry_asset)) {
        hengegroup_theme_log_vite_error('Vite style entry fehlt im Manifest: ' . $entry);
        return false;
    }

    $file = $entry_asset['file'] ?? null;
    $file = is_string($file) ? trim($file) : '';

    if ($file !== '' && str_ends_with($file, '.css')) {
        $absolute_path = hengegroup_theme_get_vite_asset_path($file);
        if (!file_exists($absolute_path)) {
            hengegroup_theme_log_vite_error('Vite stylesheet fehlt: ' . $absolute_path);
            return false;
        }

        wp_enqueue_style(
            $handle,
            hengegroup_theme_get_vite_asset_uri($file),
            [],
            filemtime($absolute_path),
        );

        return true;
    }

    hengegroup_theme_enqueue_vite_entry_styles($handle, $entry_asset);

    return true;
}

function hengegroup_theme_enqueue_vite_entry(string $handle, string $entry): bool
{
    $manifest = hengegroup_theme_get_vite_manifest();
    $entry_asset = $manifest[$entry] ?? null;

    if (!is_array($entry_asset)) {
        hengegroup_theme_log_vite_error('Vite entry fehlt im Manifest: ' . $entry);
        return false;
    }

    $file = $entry_asset['file'] ?? null;
    $file = is_string($file) ? trim($file) : '';

    if ($file === '') {
        hengegroup_theme_log_vite_error('Vite entry hat keine Datei im Manifest: ' . $entry);
        return false;
    }

    $absolute_path = hengegroup_theme_get_vite_asset_path($file);
    if (!file_exists($absolute_path)) {
        hengegroup_theme_log_vite_error('Vite bundle fehlt: ' . $absolute_path);
        return false;
    }

    wp_enqueue_script(
        $handle,
        hengegroup_theme_get_vite_asset_uri($file),
        [],
        filemtime($absolute_path),
        true,
    );

    hengegroup_theme_enqueue_vite_entry_styles($handle, $entry_asset);

    return true;
}

function hengegroup_theme_enqueue_assets(): void
{
    hengegroup_theme_enqueue_vite_entry('hengegroup-theme-app', 'assets/js/app.js');
}
add_action('wp_enqueue_scripts', 'hengegroup_theme_enqueue_assets');
