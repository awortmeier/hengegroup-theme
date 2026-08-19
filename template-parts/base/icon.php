<?php

declare(strict_types=1);

// shadcn/ui has no Icon primitive either: icons are consumed directly as their own importable
// component (typically from lucide-react), e.g. <Button><ArrowRightIcon /> Continue</Button> --
// no wrapper needed because JS/React already gives every icon its own importable module. PHP has
// no equivalent import-per-icon mechanism, so this component is the necessary server-side
// substitute: a generic renderer parameterized by `name`/`set` that resolves to a pre-synced
// static SVG file from lucide-static/@tabler/icons (see README "Icons" section) -- the closest
// practical PHP analog to "importing an icon component", not a deviation from shadcn's approach.
//
// Supported config:
//   name             string   required. Icon file name (without or with .svg), e.g. 'arrow-right'
//   set              string   subdirectory under assets/images/icons/, e.g. 'lucide',
//                              'tabler/outline', 'tabler/filled' (default: none/root)
//   class            string   merged with any class="..." already present on the source SVG
//   decorative       bool     default true. true -> aria-hidden="true" + focusable="false";
//                              false -> role="img" + aria-label (or aria-labelledby + <title> when
//                              `title` is given)
//   title            string   accessible title, only used when decorative is false
//   attributes / data_attributes   passthrough, as in the other base parts

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

// Source SVGs (e.g. lucide-static) ship their own class="..." on the <svg> tag;
// strip it out and merge it with the caller's class instead of appending a
// second class attribute, which browsers would otherwise silently ignore.
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

foreach ($data_attributes as $attribute_key => $value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $svg_attributes['data-' . $data_name] = $value;
}

$svg_attributes = array_merge($svg_attributes, $attributes);

$attribute_string = hengegroup_theme_render_attributes($svg_attributes);
$svg = preg_replace('/<svg\b([^>]*)>/i', '<svg$1' . $attribute_string . '>', $svg, 1) ?? $svg;

echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
