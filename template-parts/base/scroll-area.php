<?php

declare(strict_types=1);

// shadcn/ui's ScrollArea wraps a headless UI primitive (historically Radix UI; shadcn now also
// ships Base UI/React Aria variants of many components): a Root/Viewport/Scrollbar/Thumb/Corner
// tree whose entire purpose, per shadcn's own docs, is to "augment native scroll functionality for
// custom, cross-browser styling" -- a JS-synced fake thumb/track drawn over a real scrolling
// viewport, because browsers used to disagree too much on how far a native scrollbar could be
// restyled.
//
// That gap has closed: the CSS Scrollbars Module (`scrollbar-width: auto|thin|none` +
// `scrollbar-color: <thumb> <track>`) is now a real, standardized, cross-browser way to restyle a
// native scrollbar, and WebKit's own `::-webkit-scrollbar`/`-thumb`/`-track`/`-corner`
// pseudo-elements cover the remaining engines that don't yet honor the standard properties -- full
// custom-styled scrolling with zero JS and zero extra DOM nodes (the scrollbar itself is painted by
// the browser from CSS, not built as a JS-managed `<div>` thumb, see CLAUDE.md #1). This collapses
// shadcn's two-piece ScrollArea/ScrollBar tree into one native wrapper here, same simplification as
// progress.php folding Root+Indicator into a single native <progress> element.
//
// Deliberately NOT offered, no reliable native equivalent: `type`/`scrollHideDelay` (Radix's
// auto/always/scroll/hover scrollbar-visibility modes) -- a native scrollbar's show/hide behaviour
// is governed by the OS/browser's own scrollbar settings, not something CSS or this component can
// override cross-browser; treat as a documented gap, not a silent one.
//
// Content-agnostic wrapper, same nesting pattern as button-group.php/kbd-group.php:
// buffer the scrollable content and pass it as `content`.
//
// Phase 2 (CLAUDE.md Regel 1): both the functional `overflow-*` per `orientation` and the visual
// thin/subtle native scrollbar are baked in below as regular Tailwind classes -- `scrollbar-width`/
// `scrollbar-color`/`::-webkit-scrollbar-*` are all reachable via Tailwind's arbitrary-property/
// arbitrary-variant bracket syntax (`[scrollbar-color:var(--color-border)_transparent]`,
// `[&::-webkit-scrollbar-thumb]:bg-border`, ...), same idiom progress-circle.php's own
// `[--pc-track:var(--color-border)]` already uses -- no raw-CSS exception needed here, unlike e.g.
// attachment-group.php's own `mask-image` edge fade.
// `scrollbar: 'none'` (see config below) swaps to a fully hidden native scrollbar instead --
// attachment-group.php uses that for its edge-faded horizontal row rather than reinventing the hide
// via its own raw CSS (previously a project-CSS `[data-slot="attachment-group"]` rule in app.css).
// That's a mutually exclusive PHP config branch, not two competing Tailwind classes for the same
// `scrollbar-width` property on one element -- the utilities layer gives no source-order guarantee
// between two same-specificity utility classes, so a caller-supplied override class could not
// reliably have beaten this file's own default the other way round.
//
// Supported config:
//   content       string   required. Pre-rendered HTML to wrap (caller's responsibility to
//                          escape/build)
//   orientation   string   vertical (default) | horizontal | both -- sets data-orientation AND the
//                          matching default `overflow-*` Tailwind classes (vertical: scroll y only,
//                          horizontal: scroll x only, both: scroll both axes)
//   scrollbar     string   thin (default) | none -- thin: subtle themed native scrollbar
//                          (`--color-border`, see tokens.css). none: scrollbar fully hidden
//                          (content still scrolls, e.g. via touch/trackpad/keyboard) -- for a caller
//                          that draws its own affordance instead (attachment-group.php's edge fade)
//   data_slot     string   overrides the root `data-slot` value (default: 'scroll-area') -- same
//                          composing-parent escape hatch as input.php's/textarea.php's `data_slot`;
//                          e.g. attachment-group.php requests 'attachment-group' here instead of
//                          duplicating this file's scrolling logic for what shadcn's own
//                          AttachmentGroup docs describe as just a horizontally-scrolling wrapper
//                          Leave unset for standalone use.
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$orientation = trim((string) ($config['orientation'] ?? 'vertical'));
$scrollbar = trim((string) ($config['scrollbar'] ?? 'thin'));
$data_slot = trim((string) ($config['data_slot'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

if ($data_slot === '') {
    $data_slot = 'scroll-area';
}

$allowed_orientations = ['vertical', 'horizontal', 'both'];

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'vertical';
}

$allowed_scrollbars = ['thin', 'none'];

if (!in_array($scrollbar, $allowed_scrollbars, true)) {
    $scrollbar = 'thin';
}

$overflow_classes = [
    'vertical' => 'overflow-y-auto overflow-x-hidden',
    'horizontal' => 'overflow-x-auto overflow-y-hidden',
    'both' => 'overflow-auto',
];

$scrollbar_classes = [
    'thin' =>
        '[scrollbar-width:thin] [scrollbar-color:var(--color-border)_transparent] ' .
        '[&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar]:h-2 ' .
        '[&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:rounded-full ' .
        '[&::-webkit-scrollbar-thumb]:bg-border',
    'none' => '[scrollbar-width:none] [&::-webkit-scrollbar]:hidden',
];

$base_classes = trim($overflow_classes[$orientation] . ' ' . $scrollbar_classes[$scrollbar]);

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = $data_slot;
$element_attributes['data-orientation'] = $orientation;
// tabindex="0" makes the scroll container itself focusable -- a scrollable region without a
// focusable child is unreachable by keyboard otherwise (WCAG 2.1.1). Same fix as
// carousel-content.php's identical `overflow` container. table.php (data_slot:
// 'table-container') and attachment-group.php both nest this component, so this single fix
// covers all three.
$element_attributes['tabindex'] = '0';

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
