<?php

declare(strict_types=1);

// Rich preview panel on hover/focus. Without JS only `trigger_title` as a native title.
// hover-card.js positions it (shared with tooltip.js), keeps it open while the pointer is over the
// panel, and removes the title. Never `hidden`: visibility runs via data-state. No translate in the
// transition because the positioning JS sets `translate` inline.
//
// Config:
//   trigger         string   pre-rendered, focusable (required)
//   content         string   pre-rendered (required)
//   trigger_title   string   native title fallback
//   side            string   top | right | bottom (default) | left
//   align           string   start | center (default) | end
//   open_delay      int      default 700 ms
//   close_delay     int      default 300 ms
//   id              string   default wp_unique_id()
//   class / attributes / data_attributes   on the wrapper

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
    $id = 'hengegroup-theme-hover-card-' . wp_unique_id();
}

// inline-flex: avoids line-box ghost space that skews the position measurements.
$wrapper_base_classes = 'group relative inline-flex';

$wrapper_attributes = $attributes;

$wrapper_attributes['class'] = trim(
    $wrapper_base_classes . ($class_name !== '' ? ' ' . $class_name : ''),
);

$wrapper_attributes['data-slot'] = 'hover-card';
$wrapper_attributes['data-state'] = 'closed';
$wrapper_attributes['data-side'] = $side;
$wrapper_attributes['data-align'] = $align;
$wrapper_attributes['data-open-delay'] = $open_delay;
$wrapper_attributes['data-close-delay'] = $close_delay;

if ($trigger_title !== '') {
    $wrapper_attributes['title'] = $trigger_title;
}

$wrapper_attributes = hengegroup_theme_merge_data_attributes($wrapper_attributes, $data_attributes);

$trigger_markup = sprintf(
    '<span class="inline-flex" data-slot="hover-card-trigger">%s</span>',
    $trigger, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

// Position classes only paint the pre-JS state; hover-card.js overrides them inline.
$content_classes =
    'absolute z-50 w-64 rounded-2xl border border-border bg-popover p-4 text-popover-foreground ' .
    'shadow-overlay opacity-0 scale-[0.98] pointer-events-none ' .
    'transition-[opacity,scale] duration-[160ms] ease-out ' .
    'group-data-[state=open]:pointer-events-auto group-data-[state=open]:opacity-100 ' .
    'group-data-[state=open]:scale-100 ' .
    hengegroup_theme_floating_position_classes($side, $align, 12);

// The arrow keeps a group-data-[side] matrix because the JS may flip the side and does not position it.
$arrow_classes =
    'absolute size-2 rotate-45 border-border bg-inherit ' .
    'group-data-[side=top]:top-full group-data-[side=top]:left-1/2 ' .
    'group-data-[side=top]:-mt-1 group-data-[side=top]:-translate-x-1/2 ' .
    'group-data-[side=top]:border-b group-data-[side=top]:border-r ' .
    'group-data-[side=bottom]:bottom-full group-data-[side=bottom]:left-1/2 ' .
    'group-data-[side=bottom]:-mb-1 group-data-[side=bottom]:-translate-x-1/2 ' .
    'group-data-[side=bottom]:border-t group-data-[side=bottom]:border-l ' .
    'group-data-[side=left]:top-1/2 group-data-[side=left]:left-full ' .
    'group-data-[side=left]:-ml-1 group-data-[side=left]:-translate-y-1/2 ' .
    'group-data-[side=left]:border-t group-data-[side=left]:border-r ' .
    'group-data-[side=right]:top-1/2 group-data-[side=right]:right-full ' .
    'group-data-[side=right]:-mr-1 group-data-[side=right]:-translate-y-1/2 ' .
    'group-data-[side=right]:border-l group-data-[side=right]:border-b ' .
    'group-data-[align=start]:group-data-[side=top]:left-4 ' .
    'group-data-[align=start]:group-data-[side=top]:translate-x-0 ' .
    'group-data-[align=start]:group-data-[side=bottom]:left-4 ' .
    'group-data-[align=start]:group-data-[side=bottom]:translate-x-0 ' .
    'group-data-[align=end]:group-data-[side=top]:left-auto ' .
    'group-data-[align=end]:group-data-[side=top]:right-4 ' .
    'group-data-[align=end]:group-data-[side=top]:translate-x-0 ' .
    'group-data-[align=end]:group-data-[side=bottom]:left-auto ' .
    'group-data-[align=end]:group-data-[side=bottom]:right-4 ' .
    'group-data-[align=end]:group-data-[side=bottom]:translate-x-0 ' .
    'group-data-[align=start]:group-data-[side=left]:top-4 ' .
    'group-data-[align=start]:group-data-[side=left]:translate-y-0 ' .
    'group-data-[align=start]:group-data-[side=right]:top-4 ' .
    'group-data-[align=start]:group-data-[side=right]:translate-y-0 ' .
    'group-data-[align=end]:group-data-[side=left]:top-auto ' .
    'group-data-[align=end]:group-data-[side=left]:bottom-4 ' .
    'group-data-[align=end]:group-data-[side=left]:translate-y-0 ' .
    'group-data-[align=end]:group-data-[side=right]:top-auto ' .
    'group-data-[align=end]:group-data-[side=right]:bottom-4 ' .
    'group-data-[align=end]:group-data-[side=right]:translate-y-0';

$content_markup = sprintf(
    '<div class="%1$s" data-slot="hover-card-content" id="%2$s">%3$s' .
        '<span class="%4$s" data-slot="hover-card-arrow" aria-hidden="true"></span></div>',
    esc_attr($content_classes),
    esc_attr($id),
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_attr($arrow_classes),
);

printf(
    '<span%1$s>%2$s%3$s</span>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
