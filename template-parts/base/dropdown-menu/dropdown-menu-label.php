<?php

declare(strict_types=1);

// shadcn/ui's DropdownMenuLabel: plain, non-interactive section-heading text inside
// dropdown-menu.php's `content` (e.g. "My Account" above a group of related items). No ARIA role
// beyond what the plain text already provides -- not a form <label>, unrelated to
// template-parts/base/label.php.
//
// Supported config:
//   text     string   required. Visible content (plain text, escaped)
//   inset    bool     adds a data-inset="true" hook so this label can align with icon-having
//                      sibling items (project-CSS padding, same convention as
//                      dropdown-menu-item.php's `inset`)
//   class / attributes / data_attributes   passthrough, as in the other base parts
//
// Phase 2 (CLAUDE.md Regel 1): styled via shadcn's own real stock DropdownMenuLabel class recipe
// (live-checked against current docs) -- see dropdown-menu.php's own header comment for why this
// isn't traced to the Claude-Design reference like every prior Phase 2 entry.

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ''));
$inset = !empty($config['inset']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($text === '') {
    return;
}

$base_classes = 'px-2 py-1.5 text-sm font-medium text-foreground data-[inset=true]:pl-8';

$element_attributes = $attributes;

$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'dropdown-menu-label';

if ($inset) {
    $element_attributes['data-inset'] = 'true';
}

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
