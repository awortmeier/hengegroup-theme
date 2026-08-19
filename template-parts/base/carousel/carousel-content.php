<?php

declare(strict_types=1);

// shadcn/ui's CarouselContent: in their embla-driven version this is actually TWO nested divs --
// an outer `overflow-hidden` viewport plus an inner `flex -ml-4` track that embla moves via
// `transform: translate3d(...)`, because embla itself owns the scroll position. This component
// needs only ONE div: with a native CSS Scroll Snap container (see carousel.php's header comment),
// the browser's own `scrollLeft`/`scrollTop` IS the track position, no separate transform-driven
// element to keep in sync. `tabindex="0"` makes the container itself focusable, which is what
// gives keyboard users native arrow-key scrolling for free -- a focused, scrollable region already
// responds to arrow keys in every evergreen browser, and CSS Scroll Snap re-aligns the result to
// the nearest slide automatically once the key-driven scroll settles, no JS required (CLAUDE.md
// #1). Content-agnostic wrapper, same nesting pattern as carousel.php itself:
// buffer one or more carousel-item.php calls and pass the combined HTML as `content`.
//
// Supported config:
//   content   string   required. Pre-rendered HTML to wrap (buffered carousel-item.php calls)
//   class / attributes / data_attributes   passthrough onto the outer
//                       <div data-slot="carousel-content"> element
//
// Actual scrolling/snapping behaviour (overflow-x/y: auto, scroll-snap-type, gap between items) is
// entirely project CSS, keyed off [data-slot="carousel-content"] and the parent carousel's
// [data-orientation] -- no Tailwind/utility classes are generated here (CLAUDE.md #1).

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'carousel-content';
$element_attributes['tabindex'] = '0';

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
