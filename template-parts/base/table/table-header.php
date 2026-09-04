<?php

declare(strict_types=1);

// shadcn/ui's TableHeader: a plain <thead>, no ARIA/JS beyond what native table sectioning
// already provides (CLAUDE.md #1). Content-agnostic wrapper, same nesting pattern as
// table.php/button-group.php: buffer one or more table-row.php calls (each built
// from table-head.php cells) and pass the combined HTML as `content`.
//
// Phase 2 (CLAUDE.md Regel 1): base class taken 1:1 from shadcn's own TableHeader
// (registry/new-york-v4/ui/table.tsx, live-checked 2026-09-03): `[&_tr]:border-b` -- an
// arbitrary-variant selector that puts the divider on the header's own <tr> rather than the
// <thead> element itself (native table sectioning elements can't carry a visible border the way a
// row can). No reference-driven deviation here; see table-head.php for the reference's own header-
// cell look.
//
// Supported config:
//   content   string   required. Pre-rendered HTML to wrap (buffered table-row.php calls)
//   class / attributes / data_attributes   passthrough onto the outer
//                       <thead data-slot="table-header"> wrapper

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
    '[&_tr]:border-b' . ($class_name !== '' ? ' ' . $class_name : ''),
);
$element_attributes['data-slot'] = 'table-header';

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<thead%1$s>%2$s</thead>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
