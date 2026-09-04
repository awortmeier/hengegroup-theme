<?php

declare(strict_types=1);

// shadcn/ui's TableRow: a plain <tr>, no ARIA/JS beyond what native table rows already provide
// (CLAUDE.md #1). Content-agnostic wrapper, same nesting pattern as table-header.php/
// table-body.php: buffer one or more table-head.php (inside table-header.php) or
// table-cell.php (inside table-body.php/table-footer.php) calls and pass the combined HTML as
// `content`.
//
// shadcn's own TableRow styles `data-[state=selected]`, set by a caller integrating something
// like TanStack Table's row-selection state -- exposed here as `selected`, same
// config-key-instead-of-manual-data-attribute convention as toggle.php's `pressed`.
//
// Phase 2 (CLAUDE.md Regel 1): base classes taken 1:1 from shadcn's own TableRow
// (registry/new-york-v4/ui/table.tsx, live-checked 2026-09-03) -- `border-b transition-colors
// hover:bg-muted/50 has-aria-expanded:bg-muted/50` -- with one deliberate deviation on the
// strength of the Claude-Design reference "Hengegroup" (same `.dc.html` reference workflow as
// pagination.php's own entry, see docs/entscheidungen.md): the selected-row tint is
// `data-[state=selected]:bg-henge-green/5` instead of shadcn's own flat `bg-muted` -- a brand-color
// wash instead of a neutral one, matching the reference's own selected-row look (`rgba(27,110,70,
// 0.06)`, i.e. henge-green at ~6% -- `/5` is the nearest real Tailwind opacity step, same
// nearest-real-scale-step convention as button.php's font-size mapping) and the same henge-green-
// for-"current/selected" precedent pagination.php's own active-page item already established.
//
// Supported config:
//   content    string   required. Pre-rendered HTML to wrap (buffered table-head.php/
//                        table-cell.php calls)
//   selected   bool     default false. Sets data-state="selected" (pure styling hook, e.g. a
//                        highlighted row in a selectable data table)
//   class / attributes / data_attributes   passthrough onto the outer
//                        <tr data-slot="table-row"> wrapper

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$selected = !empty($config['selected']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    'border-b transition-colors hover:bg-muted/50 has-aria-expanded:bg-muted/50 data-[state=selected]:bg-henge-green/5' .
        ($class_name !== '' ? ' ' . $class_name : ''),
);
$element_attributes['data-slot'] = 'table-row';

if ($selected) {
    $element_attributes['data-state'] = 'selected';
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<tr%1$s>%2$s</tr>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
