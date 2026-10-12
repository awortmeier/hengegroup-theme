<?php

declare(strict_types=1);

// Dropdown menu on <details>/<summary>: opens and closes without JS. dropdown-menu.js adds the
// WAI-ARIA menu pattern (roving tabindex, arrow keys, type-ahead, outside click/Escape).
// aria-expanded comes from <details> itself and is not set here.
// `trigger` must not contain a focusable element (<summary> already is one).
//
// Config:
//   trigger   string   inner HTML of the <summary> (required)
//   content   string   menu items, labels, separator.php (required)
//   side      string   top | right | bottom | left (default bottom)
//   align     string   start (default) | center | end
//   id        string   default wp_unique_id()
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$trigger = (string) ($config['trigger'] ?? '');
$content = (string) ($config['content'] ?? '');
$side = trim((string) ($config['side'] ?? 'bottom'));
$align = trim((string) ($config['align'] ?? 'start'));
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
    $align = 'start';
}

if ($id === '') {
    $id = 'hengegroup-theme-dropdown-menu-' . wp_unique_id();
}

$wrapper_base_classes = 'relative inline-flex';

$wrapper_attributes = $attributes;

$wrapper_attributes['class'] = trim(
    $wrapper_base_classes . ($class_name !== '' ? ' ' . $class_name : ''),
);

$wrapper_attributes['data-slot'] = 'dropdown-menu';
$wrapper_attributes['id'] = $id;

$wrapper_attributes = hengegroup_theme_merge_data_attributes($wrapper_attributes, $data_attributes);

// Hide the native disclosure marker.
$trigger_markup = sprintf(
    '<summary class="list-none cursor-pointer [&::-webkit-details-marker]:hidden" ' .
        'data-slot="dropdown-menu-trigger" aria-haspopup="menu">%s</summary>',
    $trigger, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

$content_classes =
    'absolute z-50 min-w-32 rounded-2xl border border-border bg-popover p-1 ' .
    'text-popover-foreground shadow-[0_12px_32px_rgba(0,0,0,0.14)] ' .
    'animate-[hg-popover-in_140ms_ease-out] ' .
    hengegroup_theme_floating_position_classes($side, $align);

$content_markup = sprintf(
    '<div class="%1$s" data-slot="dropdown-menu-content" role="menu" data-side="%2$s" data-align="%3$s">%4$s</div>',
    esc_attr($content_classes),
    esc_attr($side),
    esc_attr($align),
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

printf(
    '<details%1$s>%2$s%3$s</details>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
