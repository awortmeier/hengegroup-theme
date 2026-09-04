<?php

declare(strict_types=1);

// shadcn/ui's TableFooter: a plain <tfoot>, no ARIA/JS beyond what native table sectioning already
// provides (CLAUDE.md #1). Optional -- most tables have no footer row (e.g. a totals row), so a
// caller simply omits this component rather than passing an empty one. Content-agnostic wrapper,
// same nesting pattern as table.php/table-header.php: buffer one or more
// table-row.php calls (each built from table-cell.php cells) and pass the combined HTML as
// `content`.
//
// Phase 2 (CLAUDE.md Regel 1): base classes taken 1:1 from shadcn's own TableFooter
// (registry/new-york-v4/ui/table.tsx, live-checked 2026-09-03): `border-t bg-muted/50 font-medium
// [&>tr]:last:border-b-0` -- matches the Claude-Design reference "Hengegroup"'s own Basis-section
// totals row (a tinted, bold "Gesamt" row) closely enough that no reference-driven deviation was
// needed, see docs/entscheidungen.md.
//
// Supported config:
//   content   string   required. Pre-rendered HTML to wrap (buffered table-row.php calls)
//   class / attributes / data_attributes   passthrough onto the outer
//                       <tfoot data-slot="table-footer"> wrapper

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    'border-t bg-muted/50 font-medium [&>tr]:last:border-b-0' .
        ($class_name !== '' ? ' ' . $class_name : ''),
);
$element_attributes['data-slot'] = 'table-footer';

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<tfoot%1$s>%2$s</tfoot>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
