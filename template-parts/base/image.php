<?php

declare(strict_types=1);

// <img> from a URL, a media library attachment or a theme file.
//
// Config:
//   src                          string         checked first
//   attachment_id                int            checked second; srcset/sizes/alt from WordPress
//   size                         string|array   attachment size, default "full"
//   name / set                   string         fallback: assets/images/<set>/<name>
//   alt                          string
//   decorative                   bool           empty alt + aria-hidden
//   loading / decoding / fetchpriority   string   decoding defaults to async
//   srcset / sizes / width / height      string   override
//   lazy                         bool           loading="lazy" + data-lazy
//   lazy_src / lazy_srcset       string         data-src/data-srcset for a JS loader
//   class / attributes / data_attributes
if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$src = trim((string) ($config['src'] ?? ''));
$name = trim((string) ($config['name'] ?? ''));
$set = trim((string) ($config['set'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$alt = trim((string) ($config['alt'] ?? ''));
$loading = trim((string) ($config['loading'] ?? ''));
$decoding = trim((string) ($config['decoding'] ?? 'async'));
$fetchpriority = trim((string) ($config['fetchpriority'] ?? ''));
$lazy_src = trim((string) ($config['lazy_src'] ?? ''));
$lazy_srcset = trim((string) ($config['lazy_srcset'] ?? ''));
$decorative = !empty($config['decorative']);
$lazy = !empty($config['lazy']) || $lazy_src !== '' || $lazy_srcset !== '';
$attachment_id = (int) ($config['attachment_id'] ?? 0);
$size = $config['size'] ?? 'full';
$attachment_defaults = [];

if ($src === '' && $attachment_id > 0) {
    $attachment_image = wp_get_attachment_image_src($attachment_id, $size);

    if (is_array($attachment_image)) {
        $src = (string) $attachment_image[0];

        if (!empty($attachment_image[1])) {
            $attachment_defaults['width'] = (string) $attachment_image[1];
        }
        if (!empty($attachment_image[2])) {
            $attachment_defaults['height'] = (string) $attachment_image[2];
        }

        $attachment_srcset = (string) wp_get_attachment_image_srcset($attachment_id, $size);
        if ($attachment_srcset !== '') {
            $attachment_defaults['srcset'] = $attachment_srcset;
        }

        $attachment_sizes = (string) wp_get_attachment_image_sizes($attachment_id, $size);
        if ($attachment_sizes !== '') {
            $attachment_defaults['sizes'] = $attachment_sizes;
        }

        if ($alt === '') {
            $alt = trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true));
        }
    }
}

if ($src === '') {
    if ($name === '') {
        return;
    }

    $image_base_directory = trailingslashit(get_template_directory()) . 'assets/images/';
    $image_base_uri = trailingslashit(get_template_directory_uri()) . 'assets/images/';
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

    $image_path = $image_base_directory . $relative_directory . $file_name;

    if (!is_file($image_path)) {
        return;
    }

    $src =
        $image_base_uri .
        str_replace('%2F', '/', rawurlencode($relative_directory . basename($file_name)));
}

$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$image_attributes = [
    'data-slot' => 'image',
    'src' => $src,
    'alt' => $decorative ? '' : $alt,
];

if ($class_name !== '') {
    $image_attributes['class'] = $class_name;
}

if ($decoding !== '') {
    $image_attributes['decoding'] = $decoding;
}

if ($loading === '' && $lazy) {
    $loading = 'lazy';
}

if ($loading !== '') {
    $image_attributes['loading'] = $loading;
}

if ($fetchpriority !== '') {
    $image_attributes['fetchpriority'] = $fetchpriority;
}

foreach (['srcset', 'sizes', 'width', 'height'] as $attribute_name) {
    if (!empty($config[$attribute_name])) {
        $image_attributes[$attribute_name] = (string) $config[$attribute_name];
    } elseif (isset($attachment_defaults[$attribute_name])) {
        $image_attributes[$attribute_name] = $attachment_defaults[$attribute_name];
    }
}

if ($decorative) {
    $image_attributes['aria-hidden'] = 'true';
}

if ($lazy) {
    $image_attributes['data-lazy'] = 'true';
}

if ($lazy_src !== '') {
    $image_attributes['data-src'] = $lazy_src;
}

if ($lazy_srcset !== '') {
    $image_attributes['data-srcset'] = $lazy_srcset;
}

$image_attributes = hengegroup_theme_merge_data_attributes($image_attributes, $data_attributes);

$image_attributes = array_merge($image_attributes, $attributes);

echo '<img' . hengegroup_theme_render_attributes($image_attributes) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
