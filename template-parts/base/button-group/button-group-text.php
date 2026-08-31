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
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind, classes taken 1:1 from shadcn's own
// ButtonGroupText (registry/new-york-v4/ui/button-group.tsx, live-checked 2026-08-30), with two
// deviations:
//   - `rounded-md` swapped for `rounded-lg`, same field-surface radius convention as button-group.php's
//     own rounded-r-md -> rounded-r-lg swap (see that file's header) and input.php/input-group.php/
//     native-select.php before it -- this text segment reads as a field-adjacent label, not a
//     button, so it stays out of button.php's fully-pill rounded-full family.
//   - a bare `border` gained an explicit `border-border` alongside it: shadcn's own source relies on
//     their global `@layer base { * { border-color: var(--border); } }` Preflight override (shipped
//     in every shadcn project's globals.css) to give unqualified `border` the right color -- this
//     theme doesn't carry that global reset (same reasoning as button.php's variant classes always
//     pairing `border` with an explicit color utility, e.g. `border-input`/`border-grey-dark`), so
//     the color has to be spelled out here instead of silently falling back to Tailwind's own
//     `currentColor` default.
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

$base_classes =
    'flex items-center gap-2 rounded-lg border border-border bg-muted px-4 text-sm font-medium ' .
    "shadow-xs [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4";

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

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
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);
