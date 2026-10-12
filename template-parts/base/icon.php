<?php

declare(strict_types=1);

// Renders a synced SVG from assets/images/icons/<set>/<name>.svg (see docs/how-to.md, "Icons").
//
// Config:
//   name         string   required, e.g. 'arrow-right'
//   set          string   e.g. 'lucide', 'tabler/outline', 'tabler/filled'
//   class        string   merged with the SVG's own class
//   decorative   bool     default true (aria-hidden); false = role="img" + aria-label/<title>
//   title        string   accessible name when not decorative
//   attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$name = trim((string) ($config['name'] ?? ''));
$set = trim((string) ($config['set'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$decorative = !array_key_exists('decorative', $config) || !empty($config['decorative']);
$title = trim((string) ($config['title'] ?? ''));

if ($name === '') {
    return;
}

$icon_base_directory = trailingslashit(get_template_directory()) . 'assets/images/icons/';
$relative_directory = '';

if ($set !== '') {
    $set = str_replace('\\', '/', $set);
    $set = trim($set, '/');

    if ($set !== '' && preg_match('/^[a-zA-Z0-9\/_-]+$/', $set)) {
        $relative_directory = $set . '/';
    }
}

$file_name = str_replace('\\', '/', $name);
$file_name = trim($file_name, '/');

if (!preg_match('/^[a-zA-Z0-9._-]+$/', basename($file_name))) {
    return;
}

if (!str_ends_with(strtolower($file_name), '.svg')) {
    $file_name .= '.svg';
}

$icon_path = '';
$candidate_paths = [$icon_base_directory . $relative_directory . $file_name];

if (!str_starts_with(strtolower(basename($file_name)), 'icon-')) {
    $candidate_paths[] = $icon_base_directory . $relative_directory . 'icon-' . $file_name;
}

foreach ($candidate_paths as $candidate_path) {
    if (is_file($candidate_path)) {
        $icon_path = $candidate_path;
        break;
    }
}

if ($icon_path === '') {
    return;
}

$svg = file_get_contents($icon_path);

if (!is_string($svg) || trim($svg) === '') {
    return;
}

// Merge into the SVG's existing class attribute; a second one would be ignored.
$existing_class = '';
$svg =
    preg_replace_callback(
        '/<svg\b([^>]*)>/i',
        static function (array $matches) use (&$existing_class): string {
            $tag_attributes = $matches[1];

            if (preg_match('/\sclass="([^"]*)"/i', $tag_attributes, $class_matches)) {
                $existing_class = trim($class_matches[1]);
                $tag_attributes =
                    preg_replace('/\sclass="[^"]*"/i', '', $tag_attributes) ?? $tag_attributes;
            }

            return '<svg' . $tag_attributes . '>';
        },
        $svg,
        1,
    ) ?? $svg;

$class_name = trim($existing_class . ' ' . $class_name);

$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$svg_attributes = [];

if ($class_name !== '') {
    $svg_attributes['class'] = $class_name;
}

$svg_attributes['data-slot'] = 'icon';

if ($decorative) {
    $svg_attributes['aria-hidden'] = 'true';
    $svg_attributes['focusable'] = 'false';
} else {
    $svg_attributes['role'] = 'img';

    if ($title === '') {
        $svg_attributes['aria-label'] = $name;
    } else {
        $title_id = 'hengegroup-theme-icon-title-' . wp_unique_id();
        $svg_attributes['aria-labelledby'] = $title_id;
        $title_markup = '<title id="' . esc_attr($title_id) . '">' . esc_html($title) . '</title>';
        $svg = preg_replace('/<svg\b[^>]*>/i', '$0' . $title_markup, $svg, 1) ?? $svg;
    }
}

$svg_attributes = hengegroup_theme_merge_data_attributes($svg_attributes, $data_attributes);

$svg_attributes = array_merge($svg_attributes, $attributes);

$attribute_string = hengegroup_theme_render_attributes($svg_attributes);
$svg = preg_replace('/<svg\b([^>]*)>/i', '<svg$1' . $attribute_string . '>', $svg, 1) ?? $svg;

echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
