<?php

declare(strict_types=1);

// shadcn/ui's CarouselItem: `role="group" aria-roledescription="slide"` -- a real, valid ARIA
// usage shadcn sets directly on this element itself, not a role-transformation workaround like
// toggle.php's/tabs.php's (CLAUDE.md #1 doesn't apply here, there's no headless primitive or
// native-element mismatch involved at all). Content-agnostic: `content` is
// arbitrary caller-built markup (an image.php call, a nested card.php, plain text, ...) -- a
// carousel slide has no fixed shape, same convention as table-cell.php's `content`.
//
// Supported config:
//   content   string   required. Pre-rendered HTML for the slide body (caller's responsibility to
//                        escape/build)
//   id        string   optional. Rendered as the element's `id` (stable DOM hook for
//                        assets/js/template-parts/base/carousel.js's current-slide tracking);
//                        auto-generated via wp_unique_id() when omitted
//   basis     string   optional. A raw CSS `flex-basis` value (e.g. '50%', '320px', '20rem') for
//                        the common "N slides per view" layout -- validated against a strict
//                        number+unit pattern and rendered as an inline `style` attribute, same
//                        raw-CSS-value-not-a-utility-class idiom as aspect-ratio.php's `ratio`.
//                        Omit to let project CSS size the item instead (e.g. a `class` on this
//                        item, or a blanket rule off [data-slot="carousel-item"])
//   class / attributes / data_attributes   passthrough onto the outer
//                       <div data-slot="carousel-item"> element
//
// `scroll-snap-align` itself is NOT a config key here -- it's the same value across every item in
// a given carousel almost always, so it belongs in project CSS off [data-slot="carousel-item"],
// not repeated per item the way `basis` genuinely needs to vary.

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$id = trim((string) ($config['id'] ?? ''));
$basis = trim((string) ($config['basis'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

if ($id === '') {
    $id = 'base-theme-carousel-item-' . wp_unique_id();
}

if (!preg_match('/^\d+(\.\d+)?(%|px|rem|em|vw|vh)$/', $basis)) {
    $basis = '';
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'carousel-item';
$element_attributes['id'] = $id;
$element_attributes['role'] = 'group';
$element_attributes['aria-roledescription'] = 'slide';

if ($basis !== '') {
    $element_attributes['style'] = 'flex-basis: ' . $basis . ';';
}

foreach ($data_attributes as $name => $value) {
    $data_name = trim((string) $name);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $value;
}

printf(
    '<div%1$s>%2$s</div>',
    base_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
