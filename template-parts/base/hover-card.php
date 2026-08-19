<?php

declare(strict_types=1);

// shadcn/ui's HoverCard wraps a headless UI primitive (historically Radix UI; shadcn now also
// ships Base UI/React Aria variants), the same "JS-driven floating panel shown on hover/focus,
// positioned relative to its trigger" shape as tooltip.php -- checked live against shadcn's
// current HoverCard docs, closest sibling in this theme. Like tooltip.php, no
// native element replaces this functionally, so real JS is warranted. The
// difference from tooltip.php is what the panel is FOR, and that difference drives several real
// API deltas, not just a rename:
//
//   - HoverCard's content is rich, often-interactive preview content (an avatar, bio, a "Follow"
//     button -- shadcn's own docs example previews a linked user profile), not a short plain-text
//     hint. So there is no `text` shorthand like tooltip.php's -- `content` (pre-rendered HTML,
//     caller-built, same content-agnostic convention as aspect-ratio.php's `content`) is the only
//     way to supply it. A `trigger_title` config still exists for the same native `title`
//     zero-JS-fallback purpose as tooltip.php's own `trigger_title`, since a short plain-text
//     summary is still worth having as a degraded baseline even when the rich version needs JS.
//   - The content gets no `role="tooltip"` and no `aria-describedby` wiring -- unlike tooltip.php's
//     content (a description OF the trigger), this content is supplementary, optional, often
//     independently interactive material; Radix's own HoverCard doesn't add either, and forcing
//     aria-describedby onto rich/interactive content is misleading to assistive tech (same
//     "don't invent ARIA semantics that aren't earned" principle as date-picker.php declining
//     aria-haspopup="dialog" for a panel with no real dialog semantics).
//   - Two separate delays instead of tooltip.php's one shared `delay`: `open_delay` (Radix's own
//     default 700ms, same number tooltip.php also uses) and `close_delay` (Radix's own default
//     300ms) -- a HoverCard is meant to tolerate the user moving the pointer from the trigger onto
//     the content itself (e.g. to click that "Follow" button), so closing needs its own, shorter
//     grace period independent of how long it took to open.
//   - `align` (start | center | end, default center) in addition to `side` -- shadcn/Radix's own
//     HoverCardContent vocabulary; tooltip.php's content is always centered on its trigger and
//     has no such config. `side` itself defaults to `bottom` here (Radix's own Popper-based
//     default for HoverCard/Popover/DropdownMenu -- see dropdown-menu.php's own `side` default),
//     not `top` like tooltip.php's own, deliberately different default.
//
// Zero-JS baseline, same shape as tooltip.php: `trigger_title` (or the wrapper is simply inert if
// omitted) gives a real, if unstyled and plain-text-only, native `title` tooltip. On top of that,
// assets/js/template-parts/base/hover-card.js finds these on page load and enhances them into a
// styled, positioned floating panel -- shown on hover/focus after `open_delay`, hidden on
// mouseleave/blur/Escape after `close_delay` (moving the pointer from the trigger onto the panel
// itself keeps it open, not just hovering the trigger, since the panel may hold interactive
// content) -- and removes the native `title` to avoid a duplicate browser tooltip once the styled
// panel is active. Positioning itself (single-axis flip, viewport clamp) is shared with
// tooltip.js via utils/floating-position.js, not duplicated (the same shared-utility reasoning
// applied to JS).
//
// Accessibility: same rule as tooltip.php -- the content is NEVER given the `hidden` attribute,
// only visually hidden by default via project CSS keyed off `data-state="open"|"closed"`, which
// hover-card.js toggles. `hidden` would also drop any interactive content inside out of the tab
// order/accessibility tree while still nominally "existing", an inconsistent state to leave
// project CSS to paper over.
//
// Composition: `trigger`/`content` are both caller-provided, pre-rendered HTML
// (same convention as tooltip.php's `trigger`). `trigger` should already be focusable itself (a
// link, a button, or something the caller gave `tabindex="0"`) -- this component does not add its
// own tabindex, same reasoning as tooltip.php.
//
// Supported config:
//   trigger          string   required. Pre-rendered HTML for the trigger element
//   content          string   required. Pre-rendered HTML for the rich preview content; caller's
//                              responsibility to escape/build (see the note above on why there is
//                              no `text` shorthand here, unlike tooltip.php)
//   trigger_title    string   optional plain-text native `title` fallback for the zero-JS
//                              baseline (see above)
//   side             string   top | right | bottom (default) | left -- sets data-side; the JS
//                              uses it as the preferred placement, flipping to the opposite side
//                              if it doesn't fit in the viewport
//   align            string   start | center (default) | end -- sets data-align; the JS uses it
//                              for cross-axis placement (shadcn/Radix's own HoverCardContent prop,
//                              see the note above)
//   open_delay       int      hover delay in ms before showing (default: 700, Radix's own
//                              default), read by the JS via data-open-delay
//   close_delay      int      grace period in ms before hiding once the pointer leaves both
//                              trigger and content (default: 300, Radix's own default), read by
//                              the JS via data-close-delay
//   id               string   id for the hover card content; auto-generated via wp_unique_id()
//                              when omitted
//   class / attributes / data_attributes   passthrough onto the outer <span data-slot="hover-card">
//                              wrapper (not onto `trigger`/`content`, which the caller already
//                              controls)

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$trigger = (string) ($config['trigger'] ?? '');
$content = (string) ($config['content'] ?? '');
$trigger_title = trim((string) ($config['trigger_title'] ?? ''));
$side = trim((string) ($config['side'] ?? 'bottom'));
$align = trim((string) ($config['align'] ?? 'center'));
$open_delay = trim((string) ($config['open_delay'] ?? '700'));
$close_delay = trim((string) ($config['close_delay'] ?? '300'));
$id = trim((string) ($config['id'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($trigger) === '' || trim($content) === '') {
    return;
}

$allowed_sides = ['top', 'right', 'bottom', 'left'];

if (!in_array($side, $allowed_sides, true)) {
    $side = 'bottom';
}

$allowed_aligns = ['start', 'center', 'end'];

if (!in_array($align, $allowed_aligns, true)) {
    $align = 'center';
}

if (!is_numeric($open_delay)) {
    $open_delay = '700';
}

if (!is_numeric($close_delay)) {
    $close_delay = '300';
}

if ($id === '') {
    $id = 'base-theme-hover-card-' . wp_unique_id();
}

$wrapper_attributes = $attributes;

if ($class_name !== '') {
    $wrapper_attributes['class'] = $class_name;
}

$wrapper_attributes['data-slot'] = 'hover-card';
$wrapper_attributes['data-state'] = 'closed';
$wrapper_attributes['data-side'] = $side;
$wrapper_attributes['data-align'] = $align;
$wrapper_attributes['data-open-delay'] = $open_delay;
$wrapper_attributes['data-close-delay'] = $close_delay;

if ($trigger_title !== '') {
    // Same placement as tooltip.php's own `title`: on the outer wrapper, not the inner trigger
    // span, so the zero-JS native tooltip fires for the whole hover-card group consistently with
    // where hover-card.js itself listens once active (see that file).
    $wrapper_attributes['title'] = $trigger_title;
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $wrapper_attributes['data-' . $data_name] = $attribute_value;
}

$trigger_markup = sprintf(
    '<span data-slot="hover-card-trigger">%s</span>',
    $trigger, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

$content_markup = sprintf(
    '<div data-slot="hover-card-content" id="%1$s">%2$s</div>',
    esc_attr($id),
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

printf(
    '<span%1$s>%2$s%3$s</span>',
    base_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
