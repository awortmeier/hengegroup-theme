<?php

declare(strict_types=1);

// Direct translation of shadcn/ui's Separator (wraps a headless UI primitive -- historically
// Radix UI, shadcn now also ships Base UI/React Aria variants of many components). That primitive
// has no interactive/JS behaviour at all -- it's just a <div> whose role toggles
// between "separator" (semantic, announced by screen readers) and "none" (purely decorative,
// removed from the accessibility tree) depending on the `decorative` prop, plus a
// `data-orientation` attribute for horizontal/vertical CSS. Native HTML's own <hr> only covers
// the horizontal + always-semantic case, so a plain <div> (matching that primitive's own choice)
// is used here to support both orientations and the decorative toggle faithfully (see CLAUDE.md #1).
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind, classes taken 1:1 from shadcn's own Separator
// (registry/new-york-v4/ui/separator.tsx, live-checked 2026-08-30) -- no deviations, this one has
// no `dark:`-prefixed classes to drop and no size/radius scale that this project's other
// deviations (rounded-full vs. rounded-lg, brand-color renames) touch. `bg-border` reads the
// generic --color-border role from assets/css/tokens.css unchanged (project's own brand-grey names
// like grey-dark/grey-light only apply where a component's variant vocabulary itself expects them,
// e.g. button.php/badge.php -- a plain divider is exactly shadcn's own neutral role).
//
// Supported config:
//   orientation   string   horizontal | vertical (default: horizontal)
//   decorative    bool     default true. true -> role="none" (purely visual, hidden from
//                          assistive tech); false -> role="separator" (+ aria-orientation for
//                          vertical, since horizontal is the ARIA default and doesn't need it)
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$orientation = trim((string) ($config['orientation'] ?? 'horizontal'));
$decorative = !array_key_exists('decorative', $config) || !empty($config['decorative']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_orientations = ['horizontal', 'vertical'];

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'horizontal';
}

$base_classes =
    'bg-border shrink-0 data-[orientation=horizontal]:h-px ' .
    'data-[orientation=horizontal]:w-full data-[orientation=vertical]:h-full ' .
    'data-[orientation=vertical]:w-px';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'separator';
$element_attributes['data-orientation'] = $orientation;

if ($decorative) {
    $element_attributes['role'] = 'none';
} else {
    $element_attributes['role'] = 'separator';

    if ($orientation === 'vertical') {
        $element_attributes['aria-orientation'] = 'vertical';
    }
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<div%s></div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
