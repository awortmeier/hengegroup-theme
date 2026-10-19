<?php

declare(strict_types=1);

// Tooltip. Without JS the native `title` (via `text` or `trigger_title`). tooltip.js shows a styled
// panel on hover/focus (delay, flip, Escape) and removes the title. The content is never `hidden`
// (aria-describedby would break); visibility via data-state. `trigger` must be focusable itself.
//
// Config:
//   trigger         string   pre-rendered (required)
//   text            string   plain text, also the native title (wins over content)
//   content         string   pre-rendered
//   trigger_title   string   title fallback with `content`
//   side            string   top (default) | right | bottom | left
//   align           string   start | center (default) | end
//   delay           int      default 700 ms
//   id              string   default wp_unique_id()
//   class / attributes / data_attributes   on the wrapper

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$trigger = (string) ($config['trigger'] ?? '');
$text = trim((string) ($config['text'] ?? ''));
$content = (string) ($config['content'] ?? '');
$trigger_title = trim((string) ($config['trigger_title'] ?? ''));
$side = trim((string) ($config['side'] ?? 'top'));
$align = trim((string) ($config['align'] ?? 'center'));
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

$allowed_aligns = ['start', 'center', 'end'];

if (!in_array($align, $allowed_aligns, true)) {
    $align = 'center';
}

if (!is_numeric($delay)) {
    $delay = '700';
}

if ($id === '') {
    $id = 'hengegroup-theme-tooltip-' . wp_unique_id();
}

$content_markup = $text !== '' ? esc_html($text) : $content;
$title_value = $text !== '' ? $text : $trigger_title;

$wrapper_base_classes = 'group relative inline-flex';

$wrapper_attributes = $attributes;

$wrapper_attributes['class'] = trim(
    $wrapper_base_classes . ($class_name !== '' ? ' ' . $class_name : ''),
);

$wrapper_attributes['data-slot'] = 'tooltip';
$wrapper_attributes['data-state'] = 'closed';
$wrapper_attributes['data-side'] = $side;
$wrapper_attributes['data-align'] = $align;
$wrapper_attributes['data-delay'] = $delay;

if ($title_value !== '') {
    $wrapper_attributes['title'] = $title_value;
}

$wrapper_attributes = hengegroup_theme_merge_data_attributes($wrapper_attributes, $data_attributes);

// inline-flex: avoids line-box ghost space that skews getBoundingClientRect().
$trigger_markup = sprintf(
    '<span class="inline-flex" data-slot="tooltip-trigger" aria-describedby="%1$s">%2$s</span>',
    esc_attr($id),
    $trigger, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

// Position classes only paint the pre-JS state; tooltip.js overrides them inline.
$content_classes =
    'absolute z-50 max-w-xs rounded-lg bg-neutral-900 px-[11px] py-[7px] text-[13px] ' .
    'leading-[1.4] text-neutral-50 text-pretty shadow-overlay ' .
    'opacity-0 pointer-events-none transition-opacity duration-[140ms] ease-out ' .
    'group-data-[state=open]:pointer-events-auto group-data-[state=open]:opacity-100 ' .
    hengegroup_theme_floating_position_classes($side, $align);

// The arrow gets no JS positioning, so it reacts to data-side/data-align itself.
$arrow_classes =
    'absolute size-2 rotate-45 bg-inherit ' .
    'group-data-[side=top]:top-full group-data-[side=top]:left-1/2 ' .
    'group-data-[side=top]:-mt-1 group-data-[side=top]:-translate-x-1/2 ' .
    'group-data-[side=bottom]:bottom-full group-data-[side=bottom]:left-1/2 ' .
    'group-data-[side=bottom]:-mb-1 group-data-[side=bottom]:-translate-x-1/2 ' .
    'group-data-[side=left]:top-1/2 group-data-[side=left]:left-full ' .
    'group-data-[side=left]:-ml-1 group-data-[side=left]:-translate-y-1/2 ' .
    'group-data-[side=right]:top-1/2 group-data-[side=right]:right-full ' .
    'group-data-[side=right]:-mr-1 group-data-[side=right]:-translate-y-1/2 ' .
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

$content_element_markup = sprintf(
    '<span class="%1$s" data-slot="tooltip-content" role="tooltip" id="%2$s">%3$s' .
        '<span class="%4$s" data-slot="tooltip-arrow" aria-hidden="true"></span></span>',
    esc_attr($content_classes),
    esc_attr($id),
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_attr($arrow_classes),
);

printf(
    '<span%1$s>%2$s%3$s</span>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_element_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
