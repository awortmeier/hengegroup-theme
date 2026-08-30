<?php

declare(strict_types=1);

// shadcn/ui's InputGroupAddon: a positioned slot inside input-group.php for icons, buttons or text
// next to the control (`template-parts/base/input-group/input-group-text.php` for plain text, `button.php`
// with `size: 'sm'`/`'icon-sm'`, `variant: 'ghost'` for a button -- this project's nearest
// equivalent to shadcn's own InputGroupButton default variant/size, both already valid values in
// button.php's own allow-lists (see docs/entscheidungen.md for button.php's sm/base/lg size
// vocabulary) -- no separate input-group-button.php needed for what button.php already covers).
// Content-agnostic, same nesting pattern as input-group.php's own `content` and
// button-group.php/aspect-ratio.php/kbd-group.php elsewhere.
//
// shadcn's real InputGroupAddon also focuses the sibling control when the addon's non-interactive
// area is clicked (e.g. clicking the icon padding next to a search input, not just the input
// itself). No native HTML equivalent when the addon contains interactive children like a button
// (wrapping in a <label> would nest a second interactive control inside it, invalid/confusing) --
// deferred, documented JS enhancement, not added unprompted (see CLAUDE.md #1/#9); the input stays
// fully usable by clicking directly on it in the meantime.
//
// Phase 2 (CLAUDE.md Regel 1): classes taken from shadcn's own InputGroupAddon (registry/
// new-york-v4/ui/input-group.tsx), trimmed to the alignments this file actually exposes and
// re-padded to this project's `px-3.5`/`py-3` field-surface rhythm (same design-reference mapping
// as input.php) instead of shadcn's own `px-3`/`py-1.5`.
//
// Supported config:
//   content   string   required. Pre-rendered HTML to wrap (an icon.php call, a button.php call,
//                       an input-group-text.php call, or plain escaped text)
//   align     string   inline-start (default) | inline-end | block-start | block-end -- shadcn's
//                       own InputGroupAddon positioning axis, sets data-align only (actual
//                       placement/spacing is project-CSS, see CLAUDE.md #1)
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$align = trim((string) ($config['align'] ?? 'inline-start'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

$allowed_aligns = ['inline-start', 'inline-end', 'block-start', 'block-end'];

if (!in_array($align, $allowed_aligns, true)) {
    $align = 'inline-start';
}

$base_classes =
    'text-muted-foreground flex items-center justify-center gap-2 py-3 text-base font-medium ' .
    "select-none md:text-sm [&_svg:not([class*='size-'])]:size-4 [&_svg]:pointer-events-none " .
    'data-[align=inline-start]:order-first data-[align=inline-start]:pl-3.5 ' .
    'data-[align=inline-end]:order-last data-[align=inline-end]:pr-3.5 ' .
    'data-[align=block-start]:order-first data-[align=block-start]:w-full ' .
    'data-[align=block-start]:justify-start data-[align=block-start]:px-3.5 ' .
    'data-[align=block-start]:pt-3 data-[align=block-end]:order-last ' .
    'data-[align=block-end]:w-full data-[align=block-end]:justify-start ' .
    'data-[align=block-end]:px-3.5 data-[align=block-end]:pb-3';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'input-group-addon';
$element_attributes['data-align'] = $align;

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
