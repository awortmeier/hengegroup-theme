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
