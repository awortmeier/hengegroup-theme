<?php

declare(strict_types=1);

// shadcn/ui has no Image primitive: an image has no state/behaviour to wrap, so there's nothing
// beyond the framework's native <img> (or next/image) + utility classes. This component is the
// direct PHP equivalent: pure attribute plumbing around <img>, no Tailwind/utility classes baked
// in (see CLAUDE.md #1). Anything beyond "render an <img>" is handled by composition, not by
// growing this file:
//   - fixed aspect ratio             -> wrap the rendered output with
//                                        template-parts/base/aspect-ratio.php (shadcn's
//                                        AspectRatio equivalent), same nesting pattern as
//                                        button.php + icon.php
//   - captions / text+image layouts  -> project-specific molecule in template-parts/components/
//                                        that composes this file with
//                                        template-parts/base/typography.php
//
// Supported config:
//   src              string   ready-made image URL (checked first)
//   attachment_id    int      WordPress Media Library attachment ID (checked second, when no src
//                              is given): resolves via wp_get_attachment_image_src()/
//                              wp_get_attachment_image_srcset()/wp_get_attachment_image_sizes(),
//                              incl. WordPress's own responsive srcset/sizes generation. `alt`
//                              falls back to the attachment's own alt-text meta when not given
//                              explicitly; `width`/`height`/`srcset`/`sizes` config below still
//                              override the attachment-derived values when given.
//   size             string|array   WP image size for attachment_id (e.g. "medium", "large",
//                              "full", or [width, height]); default "full"
//   name / set       string   fallback: resolves a file from assets/images/<set>/<name> in the
//                              theme itself (e.g. bundled/decorative graphics) when neither src
//                              nor attachment_id resolve to anything
//   alt              string   alt text (ignored/emptied when decorative is true)
//   decorative       bool     true -> empty alt + aria-hidden (purely presentational image)
//   loading          string   native <img loading>; auto-set to "lazy" when `lazy` is true
//   decoding         string   native <img decoding> (default: async)
//   fetchpriority    string   native <img fetchpriority>
//   srcset / sizes / width / height   string   passed through as-is when given
//   lazy             bool     shorthand for loading="lazy" + data-lazy="true" (for a JS-driven
//                              lazy-load setup instead of the native attribute)
//   lazy_src / lazy_srcset    string   deferred src/srcset for a JS lazy-loader (data-src/
//                              data-srcset), independent of the `lazy` flag
//   class / attributes / data_attributes   passthrough, as in the other base parts
//
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

foreach ($data_attributes as $name => $value) {
    $data_name = trim((string) $name);

    if ($data_name === '') {
        continue;
    }

    $image_attributes['data-' . $data_name] = $value;
}

$image_attributes = array_merge($image_attributes, $attributes);

echo '<img' . hengegroup_theme_render_attributes($image_attributes) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
