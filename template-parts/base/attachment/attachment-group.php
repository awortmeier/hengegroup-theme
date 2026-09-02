<?php

declare(strict_types=1);

// shadcn/ui's AttachmentGroup: a horizontally scrollable, snapping row of attachment.php cards
// with an edge fade effect. Structurally that's exactly template-parts/base/scroll-area.php with
// `orientation: 'horizontal'` -- nested here via scroll-area.php's `data_slot`
// escape hatch (requesting `'attachment-group'` instead of the generic `'scroll-area'`) rather than
// reimplementing scrolling.
//
// Phase 2 (CLAUDE.md Regel 1): classes adapted from shadcn's own AttachmentGroup (registry/
// new-york-v4/ui/attachment.tsx, live-checked 2026-09-02), applied here rather than in
// scroll-area.php itself -- scroll-area.php stays the generic, unstyled (Phase 1) primitive per
// its own header comment, this file supplies the attachment-specific look on top via the `class`
// it forwards, same layering as attachment.php's own `href` trigger reusing plain Tailwind classes
// instead of new scroll-area.php API. The snap/scroll/gap classes below are all stock Tailwind
// (`snap-x`/`snap-mandatory`/`overflow-x-auto`/...); shadcn's own `scrollbar-none`/`scroll-fade-x`
// are its OWN custom utilities (no stock Tailwind equivalent, confirmed against the live registry
// fetch), so those two effects are a documented Regel-1 raw-CSS exception instead
// (assets/css/app.css, `@layer components`, scoped to `[data-slot="attachment-group"]`) -- see that
// block's own comment for why (mirrors accordion.php's own `::details-content` exception).
// Deliberately a static full-bleed mask rather than the reference artifact's own extra absolutely-
// positioned fade-overlay div (which fades to a hardcoded page background color, breaking on any
// other background) -- a `mask-image` gradient on the scroller itself fades the actual content,
// works on any background, no extra DOM node. Known simplification, not silently pretended away:
// the fade shows at both edges unconditionally (no scroll-position tracking without JS), so it
// still dims the first/last card even when nothing is actually cut off -- same accepted tradeoff
// shadcn's own always-on `scroll-fade-x` utility implies.
//
// Supported config:
//   content   string   required. Pre-rendered HTML to wrap (buffered attachment.php calls)
//   class / attributes / data_attributes   passthrough onto the outer
//                       <div data-slot="attachment-group"> wrapper (forwarded to scroll-area.php)

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

$base_classes =
    'flex min-w-0 snap-x snap-mandatory gap-3 overflow-x-auto overscroll-x-contain scroll-px-1 ' .
    'px-1 py-1 [&>[data-slot=attachment]]:shrink-0 [&>[data-slot=attachment]]:snap-start';

get_template_part('template-parts/base/scroll-area', null, [
    'config' => [
        'content' => $content,
        'orientation' => 'horizontal',
        'data_slot' => 'attachment-group',
        'class' => trim($base_classes . ($class_name !== '' ? ' ' . $class_name : '')),
        'attributes' => $attributes,
        'data_attributes' => $data_attributes,
    ],
]);
