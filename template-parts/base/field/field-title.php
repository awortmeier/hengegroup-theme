<?php

declare(strict_types=1);

// shadcn/ui's FieldTitle: a small text atom with label-like styling, used inside
// field-content.php (e.g. naming the control when the real field-label.php sits beside rather than
// above it, or when there's no single control to `for`-pair with at all). Plain text, no ARIA role
// beyond what it already provides -- unlike field-label.php it is never `for`-paired to a control
// (that's field-label.php's job), so it stays a plain <div>, not a <label>.
//
// Phase 2 (CLAUDE.md Regel 1): classes taken 1:1 from shadcn's own FieldTitle (registry/
// new-york-v4/ui/field.tsx, live-checked 2026-08-30). `group-data-[disabled=true]/field:opacity-50`
// dims this text when it sits inside a `field.php` carrying `data-disabled="true"` -- field.php
// itself doesn't set that attribute today (no `disabled` config of its own, see that file's
// header), so this stays inert until a caller adds it via field.php's own `attributes` passthrough.
//
// Supported config:
//   text   string   required. Visible content (plain text, escaped)
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($text === '') {
    return;
}

$base_classes =
    'flex w-fit items-center gap-2 text-sm leading-snug font-medium ' .
    'group-data-[disabled=true]/field:opacity-50';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'field-title';

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
    esc_html($text),
);
