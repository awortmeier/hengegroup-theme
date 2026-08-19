<?php

declare(strict_types=1);

// API modeled on shadcn/ui's Carousel family (Carousel / CarouselContent / CarouselItem /
// CarouselPrevious / CarouselNext) -- unlike almost every other base component, shadcn's own
// Carousel wraps a genuine third-party JS library (embla-carousel-react), not a headless UI
// primitive (Radix/Base UI/React Aria). But the actual behaviour embla exists for -- a snappable,
// swipeable strip of slides -- has a real native equivalent by now: the CSS Scroll Snap module
// (`scroll-snap-type`/`scroll-snap-align`, set in project CSS off carousel-content.php's
// data-slot, not here) gives touch/trackpad drag, mouse-wheel and the native scrollbar snapping
// behaviour for free, no JS at all (same "Rule 1, but via browser progress instead of an
// always-available HTML feature" case as scroll-area.php's own header comment). This is the root
// wrapper: `role="region" aria-roledescription="carousel"` per the WAI-ARIA APG Carousel pattern,
// content-agnostic -- buffer a carousel-content.php call plus optional
// carousel-previous.php/carousel-next.php calls and pass the combined HTML as `content`, same
// nesting pattern as button-group.php/field-group.php.
//
//   ob_start();
//   get_template_part('template-parts/base/carousel/carousel-content', null, ['config' => ['content' => $items_markup]]);
//   get_template_part('template-parts/base/carousel/carousel-previous', null, ['config' => []]);
//   get_template_part('template-parts/base/carousel/carousel-next', null, ['config' => []]);
//   $content = (string) ob_get_clean();
//
//   get_template_part('template-parts/base/carousel/carousel', null, ['config' => ['content' => $content]]);
//
// Supported config:
//   content       string   required. Pre-rendered HTML to wrap (see composition example above)
//   orientation   string   horizontal (default) | vertical -- matches shadcn's own Carousel
//                           `orientation` prop; drives the scroll axis via
//                           [data-orientation="..."] in project CSS (scroll-snap-type: x vs y,
//                           flex-direction: row vs column)
//   loop          bool     default false. A project addition on top of shadcn's own vocabulary --
//                           shadcn passes embla options through a generic, untyped `opts` bag
//                           instead of first-class props, and `loop` is the single most common one
//                           consumers actually set. Reflected as `data-loop` for
//                           assets/js/template-parts/base/carousel.js to read: whether
//                           Previous/Next wrap around at the bounds instead of disabling
//   aria_label    string   recommended. Accessible name for the `role="region"` (WAI-ARIA APG
//                           Carousel pattern requires one); omitted from the markup, not a hard
//                           requirement to render, when left empty
//   class / attributes / data_attributes   passthrough onto the outer
//                           <div data-slot="carousel"> element
//
// Bewusst nicht enthalten: `align`/`dragFree`/autoplay/multi-item-sync-index and every other embla
// `opts` beyond `loop` -- these are either pure per-item CSS concerns already covered by
// carousel-item.php's own `basis` (see that file) plus project CSS (`scroll-snap-align`), or a
// separate, larger JS undertaking with no concrete consumer yet (same deferred-extension-point
// spirit as slider.php's multi-thumb note).

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$orientation = trim((string) ($config['orientation'] ?? 'horizontal'));
$loop = !empty($config['loop']);
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

$allowed_orientations = ['horizontal', 'vertical'];

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'horizontal';
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'carousel';
$element_attributes['data-orientation'] = $orientation;
$element_attributes['role'] = 'region';
$element_attributes['aria-roledescription'] = 'carousel';

if ($loop) {
    $element_attributes['data-loop'] = 'true';
}

if ($aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
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
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
