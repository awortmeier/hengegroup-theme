<?php

declare(strict_types=1);

// API modeled on shadcn/ui's Badge (variant vocabulary, polymorphic root element via `href`, same
// asChild/Slot analog as button.php) but intentionally unstyled: no Tailwind/utility classes are
// applied here, only data-attributes -- a project's own CSS targets e.g.
// [data-slot="badge"][data-variant="destructive"]. Unlike button.php, shadcn's Badge has no
// `size`/`loading`/`disabled` -- it's a static label, not a form control, so none of those are
// added here (see CLAUDE.md #1: adopt shadcn's vocabulary, don't invent beyond it).
//
// Supported config:
//   text / label   string   visible label (omit for an icon-only badge)
//   href           string   renders <a> instead of <span> (shadcn's asChild/Slot analog, same
//                            idiom as button.php's `href`)
//   variant        string   default | secondary | destructive | outline | ghost | link (matches
//                           shadcn's current Badge variant scale -- `ghost`/`link` used to not
//                           exist in shadcn's Badge, they do now, so this now matches button.php's
//                           variant scale exactly)
//   icon           array    icon.php config, e.g. ['name' => 'check', 'set' => 'lucide'].
//                           Rendered with a data-icon="inline-start"|"inline-end" attribute
//                           (matching shadcn's own icon-spacing convention) reflecting
//                           `icon_position`, unless the badge is icon-only
//   icon_position  string   start | end (ignored for icon-only badges)
//   aria_label     string   required for icon-only badges (no visible text to name them)
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ($config['label'] ?? '')));
$href = trim((string) ($config['href'] ?? ''));
$variant = trim((string) ($config['variant'] ?? 'default'));
$icon_config = is_array($config['icon'] ?? null) ? $config['icon'] : null;
$icon_position = trim((string) ($config['icon_position'] ?? 'start'));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$has_icon = $icon_config !== null;

if ($text === '' && !$has_icon) {
    return;
}

$allowed_variants = ['default', 'secondary', 'destructive', 'outline', 'ghost', 'link'];

if (!in_array($variant, $allowed_variants, true)) {
    $variant = 'default';
}

if ($icon_position !== 'end') {
    $icon_position = 'start';
}

$is_icon_only = $text === '';

if ($has_icon && !$is_icon_only) {
    $icon_config['data_attributes'] = array_merge(
        is_array($icon_config['data_attributes'] ?? null) ? $icon_config['data_attributes'] : [],
        ['icon' => $icon_position === 'end' ? 'inline-end' : 'inline-start'],
    );
}

$icon_markup = $has_icon ? base_theme_render_icon($icon_config) : '';

if ($is_icon_only) {
    $inner_html = $icon_markup;
} elseif ($icon_markup !== '') {
    $inner_html =
        $icon_position === 'end' ? esc_html($text) . $icon_markup : $icon_markup . esc_html($text);
} else {
    $inner_html = esc_html($text);
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'badge';
$element_attributes['data-variant'] = $variant;

if ($is_icon_only && $aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
}

foreach ($data_attributes as $name => $value) {
    $data_name = trim((string) $name);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $value;
}

$tag = 'span';

if ($href !== '') {
    $tag = 'a';
    $element_attributes['href'] = $href;
}

printf(
    '<%1$s%2$s>%3$s</%1$s>',
    esc_html($tag),
    base_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
