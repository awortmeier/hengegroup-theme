<?php

declare(strict_types=1);

// shadcn/ui's FieldSeparator: a divider between sections inside field-group.php, optionally with
// inline content in the middle (e.g. "Or continue with"). Live-checked against shadcn's own source
// 2026-08-30: it's NOT a single-element ::before/::after job as an earlier version of this file's
// comment assumed -- shadcn nests their own Separator component (horizontal, absolutely positioned
// and vertically centered) inside a positioning wrapper, plus a `bg-background` span sitting on top
// of that line to visually "erase" it behind the text. Ported here the same way: this file nests
// template-parts/base/separator/separator.php unchanged (same reuse pattern button-group.php's own
// header documents for its vertical divider) instead of re-implementing a line via raw
// border/pseudo-element CSS.
//
// Phase 2 (CLAUDE.md Regel 1): classes taken 1:1 from shadcn's own FieldSeparator (registry/
// new-york-v4/ui/field.tsx, live-checked 2026-08-30). `group-data-[variant=outline]/field-group`
// has no matching component yet -- no `variant` config on field-group.php in this theme (shadcn's
// own FieldGroup doesn't expose one either; that class only fires when a future project-specific
// variant sets `data-variant="outline"` on a `<div data-slot="field-group">` ancestor) -- kept 1:1,
// same forward-compatible-selector reasoning as field-set.php/field-group.php above.
//
// Supported config:
//   text   string   optional inline content in the middle of the divider; omit for a plain,
//                    content-less line
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$base_classes = 'relative -my-2 h-5 text-sm group-data-[variant=outline]/field-group:-mb-2';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'field-separator';
$element_attributes['data-content'] = $text !== '' ? 'true' : 'false';

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

ob_start();
get_template_part('template-parts/base/separator/separator', null, [
    'config' => ['class' => 'absolute inset-0 top-1/2'],
]);
$separator_markup = (string) ob_get_clean();

$content_markup =
    $text !== ''
        ? sprintf(
            '<span class="relative mx-auto block w-fit bg-background px-2 text-muted-foreground" data-slot="field-separator-content">%s</span>',
            esc_html($text),
        )
        : '';

printf(
    '<div%1$s>%2$s%3$s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $separator_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
