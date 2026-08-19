<?php

declare(strict_types=1);

// shadcn/ui's AspectRatio equivalent (wraps a headless UI primitive -- historically Radix UI,
// shadcn now also ships Base UI/React Aria variants of many components): a content-agnostic
// wrapper that forces its content into a fixed ratio via the CSS `aspect-ratio` property. Not
// image-specific — wraps any pre-rendered markup (image.php output, an iframe, a video, ...).
//
// Rule 1 exception: the wrapper's `aspect-ratio` value is computed at request time from `ratio`
// (any '<number>/<number>'), so Tailwind's build-time JIT scanner cannot pre-generate an arbitrary-
// value class for it (`aspect-[<ratio>]` needs the literal value present in scanned source, not a
// PHP variable) — inline `style="aspect-ratio: ...;"` is the documented exception here, not
// optical styling.
//
// Nesting pattern (same as button.php nesting icon.php): buffer the inner component's output via
// ob_start()/get_template_part()/ob_get_clean() and pass the resulting HTML string as `content`.
//
//   ob_start();
//   get_template_part('template-parts/base/image', null, ['config' => [...]]);
//   $image_markup = (string) ob_get_clean();
//
//   get_template_part('template-parts/base/aspect-ratio', null, [
//       'config' => ['ratio' => '16/9', 'content' => $image_markup],
//   ]);
//
// Supported config:
//   content        string   required. Pre-rendered HTML to wrap (caller is responsible for
//                            escaping/building this, e.g. via template-parts/base/image.php)
//   ratio          string   CSS aspect-ratio value, e.g. '16/9', '4/3', '1/1' (default: '1/1');
//                            validated against a strict number[/number] pattern, falls back to
//                            '1/1' when invalid
//   tag            string   div | figure | span (default: div) — e.g. `figure` when the wrapped
//                            image will get a <figcaption> from the calling component
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$ratio = trim((string) ($config['ratio'] ?? '1/1'));
$tag = strtolower(trim((string) ($config['tag'] ?? 'div')));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

$allowed_tags = ['div', 'figure', 'span'];

if (!in_array($tag, $allowed_tags, true)) {
    $tag = 'div';
}

if (!preg_match('/^\d+(\.\d+)?(\/\d+(\.\d+)?)?$/', $ratio)) {
    $ratio = '1/1';
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'aspect-ratio';
$element_attributes['style'] = 'aspect-ratio: ' . $ratio . ';';

foreach ($data_attributes as $name => $value) {
    $data_name = trim((string) $name);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $value;
}

printf(
    '<%1$s%2$s>%3$s</%1$s>',
    esc_html($tag),
    base_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
