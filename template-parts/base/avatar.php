<?php

declare(strict_types=1);

// shadcn/ui's Avatar wraps a headless UI primitive (Root/Image/Fallback; historically Radix UI,
// shadcn now also ships Base UI/React Aria variants of many components): AvatarImage attempts to
// load, and JS state (imageLoadingStatus) swaps in AvatarFallback on error -- the one place that
// primitive has genuine runtime behaviour beyond styling (unlike Label/AspectRatio, which are
// just native HTML with a data-slot added). There is no CSS-only way to detect a failed image
// load (no :error/:broken pseudo-class), so a faithful runtime fallback would need JS -- not
// added unprompted (see CLAUDE.md #1).
//
// Instead, this component resolves the image the same way template-parts/base/image.php already
// does -- server-side, before any HTML is sent -- and nests it: for the `name`/
// `set` (theme-bundled asset) path, image.php already returns nothing when the file doesn't exist
// (is_file() check), so buffering its output and checking whether it's empty gives the exact same
// "fall back on failure" behaviour as that headless Avatar implementation, entirely server-side,
// zero JS.
//
// Limitation: for a `src` given directly (e.g. an external/remote avatar URL), image.php trusts
// it unconditionally and always renders an <img> -- there's no cheap way to verify a remote URL is
// reachable at render time. The fallback only kicks in when `src` is empty/omitted, not when a
// given URL happens to 404 in the browser. Handling that would need a JS onerror handler or a
// cached server-side reachability check -- deferred extension point, not added unprompted.
//
// Supported config:
//   src / name / set / alt / decorative   same as image.php -- passed straight through to a
//                             nested image.php call (see image.php's own config docs for the
//                             name/set resolution)
//   fallback         string   fallback text (e.g. initials like "CN"), shown when the image
//                             doesn't resolve. Takes priority over fallback_icon when both given.
//   fallback_icon    array    icon.php config, alternative to `fallback` for a generic
//                             placeholder icon instead of initials
//   size             string   default | sm | lg (default: default) -- shadcn's own Avatar size
//                             prop (added post-launch on shadcn's side; verified against the live
//                             docs, see CLAUDE.md's note on re-checking vocabulary drift). Sets
//                             data-size only, actual dimensions are a project-CSS concern
//   aria_label       string   accessible name for the avatar as a whole; mainly relevant for the
//                             fallback_icon path (a generic icon has no inherent identity) -- the
//                             image path already gets its accessible name from `alt`
//   class / attributes / data_attributes   passthrough onto the outer <span data-slot="avatar">
//                             wrapper (not onto the nested image -- compose image.php manually for
//                             deeper control, same escape hatch as checkbox.php's `label`)

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$src = trim((string) ($config['src'] ?? ''));
$name = trim((string) ($config['name'] ?? ''));
$set = trim((string) ($config['set'] ?? ''));
$alt = trim((string) ($config['alt'] ?? ''));
$decorative = !empty($config['decorative']);
$fallback_text = trim((string) ($config['fallback'] ?? ''));
$fallback_icon_config = is_array($config['fallback_icon'] ?? null)
    ? $config['fallback_icon']
    : null;
$size = trim((string) ($config['size'] ?? 'default'));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$image_markup = hengegroup_theme_render_image([
    'src' => $src,
    'name' => $name,
    'set' => $set,
    'alt' => $alt,
    'decorative' => $decorative,
    'attributes' => ['data-slot' => 'avatar-image'],
]);

if ($image_markup !== '') {
    $content_markup = $image_markup;
} elseif ($fallback_text !== '') {
    $content_markup = sprintf(
        '<span data-slot="avatar-fallback">%s</span>',
        esc_html($fallback_text),
    );
} elseif ($fallback_icon_config !== null) {
    $content_markup = sprintf(
        '<span data-slot="avatar-fallback">%s</span>',
        hengegroup_theme_render_icon($fallback_icon_config), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
} else {
    return;
}

$allowed_sizes = ['default', 'sm', 'lg'];

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'default';
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'avatar';
$element_attributes['data-size'] = $size;

if ($image_markup === '' && $aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<span%1$s>%2$s</span>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
