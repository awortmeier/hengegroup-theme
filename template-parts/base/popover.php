<?php

declare(strict_types=1);

// Non-modal popover on <details>/<summary> (role="dialog"). popover.js moves focus into the panel
// on open and closes on outside click/Escape. Position is fixed at render time (no flip).
// `trigger` must not contain a focusable element.
//
// Config:
//   trigger      string   inner HTML of the <summary> (required)
//   content      string   pre-rendered (required)
//   side         string   top | right | bottom (default) | left
//   align        string   start | center (default) | end
//   aria_label   string   when content has no heading of its own
//   id           string   default wp_unique_id()
//   class / attributes / data_attributes
if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$trigger = (string) ($config['trigger'] ?? '');
$content = (string) ($config['content'] ?? '');
$side = trim((string) ($config['side'] ?? 'bottom'));
$align = trim((string) ($config['align'] ?? 'center'));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$id = trim((string) ($config['id'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($trigger) === '' || trim($content) === '') {
    return;
}

$allowed_sides = ['top', 'right', 'bottom', 'left'];
$allowed_aligns = ['start', 'center', 'end'];

if (!in_array($side, $allowed_sides, true)) {
    $side = 'bottom';
}

if (!in_array($align, $allowed_aligns, true)) {
    $align = 'center';
}

if ($id === '') {
    $id = 'hengegroup-theme-popover-' . wp_unique_id();
}

$wrapper_base_classes = 'relative inline-flex';

$wrapper_attributes = $attributes;

$wrapper_attributes['class'] = trim(
    $wrapper_base_classes . ($class_name !== '' ? ' ' . $class_name : ''),
);

$wrapper_attributes['data-slot'] = 'popover';
$wrapper_attributes['id'] = $id;

$wrapper_attributes = hengegroup_theme_merge_data_attributes($wrapper_attributes, $data_attributes);

$trigger_markup = sprintf(
    '<summary class="list-none cursor-pointer [&::-webkit-details-marker]:hidden" ' .
        'data-slot="popover-trigger" aria-haspopup="dialog">%s</summary>',
    $trigger, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

$content_classes =
    'absolute z-50 w-72 rounded-2xl border border-border bg-popover p-4 text-popover-foreground ' .
    'shadow-overlay outline-hidden ' .
    'animate-[hg-popover-in_140ms_ease-out] ' .
    hengegroup_theme_floating_position_classes($side, $align);

// Arrow: the two visible borders depend on which edges form the tip after rotate-45.
$arrow_primary_axis_classes = [
    'top' => 'top-full -mt-1 border-b border-r',
    'bottom' => 'bottom-full -mb-1 border-t border-l',
    'left' => 'left-full -ml-1 border-t border-r',
    'right' => 'right-full -mr-1 border-l border-b',
][$side];

$arrow_cross_axis_classes = in_array($side, ['top', 'bottom'], true)
    ? ['start' => 'left-4', 'center' => 'left-1/2 -translate-x-1/2', 'end' => 'right-4'][$align]
    : ['start' => 'top-4', 'center' => 'top-1/2 -translate-y-1/2', 'end' => 'bottom-4'][$align];

$arrow_classes =
    'absolute size-2 rotate-45 border-border bg-inherit ' .
    $arrow_primary_axis_classes .
    ' ' .
    $arrow_cross_axis_classes;

$content_attributes = [
    'class' => $content_classes,
    'data-slot' => 'popover-content',
    'role' => 'dialog',
    // Fallback focus target for popover.js when content has nothing focusable.
    'tabindex' => '-1',
    'data-side' => $side,
    'data-align' => $align,
];

if ($aria_label !== '') {
    $content_attributes['aria-label'] = $aria_label;
}

$arrow_attributes = [
    'class' => $arrow_classes,
    'data-slot' => 'popover-arrow',
    'data-side' => $side,
    'data-align' => $align,
    'aria-hidden' => 'true',
];

$content_markup = sprintf(
    '<div%1$s>%2$s<span%3$s></span></div>',
    hengegroup_theme_render_attributes($content_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    hengegroup_theme_render_attributes($arrow_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

printf(
    '<details%1$s>%2$s%3$s</details>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
