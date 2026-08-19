<?php

declare(strict_types=1);

// Direct translation of shadcn/ui's ButtonGroupText: a plain, styled div/span for non-button
// content inside a template-parts/base/button-group/button-group.php (e.g. a static prefix label
// like "https://" next to an input, or a text-only segment between buttons). No JS, no ARIA
// beyond what the plain text itself already provides.
//
// shadcn's own ButtonGroupText additionally has an `asChild` prop (e.g. to render as a <label>
// pairing with a nested input-group.php control instead of a plain <div>). This component's
// snake_case equivalent is `tag` -- same escape-hatch shape as aspect-ratio.php's own `tag`
// config, rather than the render-prop-style `asChild` itself, which has no direct PHP analog.
//
// Supported config:
//   text   string   required. Visible content (plain text, escaped)
//   tag    string   div (default) | span | label -- shadcn's `asChild` equivalent for rendering as
//                    something other than a plain <div>, e.g. `label` to pair with a nested
//                    control's `for`/`id` via the `attributes` passthrough below
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ''));
$tag = trim((string) ($config['tag'] ?? 'div'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($text === '') {
    return;
}

$allowed_tags = ['div', 'span', 'label'];

if (!in_array($tag, $allowed_tags, true)) {
    $tag = 'div';
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'button-group-text';

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<%1$s%2$s>%3$s</%1$s>',
    esc_html($tag), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    base_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);
