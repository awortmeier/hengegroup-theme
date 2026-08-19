<?php

declare(strict_types=1);

// shadcn/ui's Tooltip wraps a headless UI primitive (historically Radix UI; shadcn now also ships
// Base UI/React Aria variants of many components): a JS-driven floating panel, shown on
// hover/focus, positioned relative to its trigger. Unlike most other components in this theme,
// the plain native `title` attribute does NOT functionally replace this -- it can't be styled,
// carries no rich content, and has unreliable/no touch support, so it's not a faithful substitute
// (unlike e.g. native-select.php, where the native element genuinely covers shadcn's behaviour).
// This is therefore -- like select.php -- a case where real JS is warranted.
//
// Progressive enhancement: with the `text` config, this component's wrapper gets a native
// `title="..."` attribute for free -- a real, if unstyled, tooltip works with zero JS. On top of
// that, assets/js/template-parts/base/tooltip.js finds these on page load and enhances them into
// a styled floating panel (show on hover/focus with a delay, hide on mouseleave/blur/Escape,
// simple single-axis flip if the preferred `side` doesn't fit -- not full collision detection,
// deferred if ever needed) and removes the native `title` to avoid a duplicate browser tooltip.
//
// Accessibility: the tooltip content is NEVER given the `hidden` attribute (that would remove it
// from the accessibility tree and break `aria-describedby`). It's always in the DOM, described via
// `aria-describedby`, and only visually hidden by default -- project CSS must hide/show it via the
// `data-state="open"|"closed"` attribute the JS toggles on the wrapper (e.g. opacity/
// pointer-events), never via `hidden`/`display:none` directly.
//
// Composition: `trigger` is caller-provided, pre-rendered HTML (same convention as
// aspect-ratio.php's `content`) -- e.g. a buffered button.php/icon.php call. It should already be
// focusable itself (a button, a link, or something the caller gave `tabindex="0"`); this component
// does not add its own tabindex, since wrapping an already-focusable trigger in another focusable
// element would create a duplicate tab stop.
//
// Supported config:
//   trigger          string   required. Pre-rendered HTML for the trigger element
//   text             string   tooltip content as plain text (escaped); also becomes the wrapper's
//                              native `title` fallback automatically. Takes priority over `content`.
//   content          string   tooltip content as pre-rendered HTML, for richer bodies than plain
//                              text; caller's responsibility to escape/build
//   trigger_title    string   optional plain-text native `title` fallback when using `content`
//                              instead of `text` (which sets it automatically)
//   side             string   top (default) | right | bottom | left -- sets data-side; the JS uses
//                              it as the preferred placement, flipping to the opposite side if it
//                              doesn't fit in the viewport
//   delay            int      hover delay in ms before showing (default: 700, matches that
//                              headless implementation's own default), read by the JS via data-delay
//   id               string   id for the tooltip content; auto-generated via wp_unique_id() when
//                              omitted (needed for the trigger's aria-describedby)
//   class / attributes / data_attributes   passthrough onto the outer <span data-slot="tooltip">
//                              wrapper (not onto `trigger`, which the caller already controls)

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$trigger = (string) ($config['trigger'] ?? '');
$text = trim((string) ($config['text'] ?? ''));
$content = (string) ($config['content'] ?? '');
$trigger_title = trim((string) ($config['trigger_title'] ?? ''));
$side = trim((string) ($config['side'] ?? 'top'));
$delay = trim((string) ($config['delay'] ?? '700'));
$id = trim((string) ($config['id'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($trigger) === '' || ($text === '' && trim($content) === '')) {
    return;
}

$allowed_sides = ['top', 'right', 'bottom', 'left'];

if (!in_array($side, $allowed_sides, true)) {
    $side = 'top';
}

if (!is_numeric($delay)) {
    $delay = '700';
}

if ($id === '') {
    $id = 'base-theme-tooltip-' . wp_unique_id();
}

$content_markup = $text !== '' ? esc_html($text) : $content;
$title_value = $text !== '' ? $text : $trigger_title;

$wrapper_attributes = $attributes;

if ($class_name !== '') {
    $wrapper_attributes['class'] = $class_name;
}

$wrapper_attributes['data-slot'] = 'tooltip';
$wrapper_attributes['data-state'] = 'closed';
$wrapper_attributes['data-side'] = $side;
$wrapper_attributes['data-delay'] = $delay;

if ($title_value !== '') {
    $wrapper_attributes['title'] = $title_value;
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $wrapper_attributes['data-' . $data_name] = $attribute_value;
}

$trigger_markup = sprintf(
    '<span data-slot="tooltip-trigger" aria-describedby="%1$s">%2$s</span>',
    esc_attr($id),
    $trigger, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

$content_element_markup = sprintf(
    '<span data-slot="tooltip-content" role="tooltip" id="%1$s">%2$s</span>',
    esc_attr($id),
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

printf(
    '<span%1$s>%2$s%3$s</span>',
    base_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_element_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
