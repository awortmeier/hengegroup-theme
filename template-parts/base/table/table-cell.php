<?php

declare(strict_types=1);

// shadcn/ui's TableCell: a plain <td>, no ARIA/JS beyond what native table cells already provide
// (CLAUDE.md #1). Content-agnostic, same nesting pattern as input-group-addon.php:
// `content` accepts plain escaped text or nested component markup (e.g. a badge.php call for a
// status column, a button.php call for an actions column) -- caller's responsibility to
// escape/build, same convention as everywhere else `content` is used.
//
// Like table-head.php, `content` is intentionally allowed to be empty: a genuinely blank data cell
// (e.g. no value for this row/column combination) is a normal table shape, not a missing-config
// case -- rendering nothing there would drop a column and misalign the rest of the row, so this
// file does NOT early-return on blank content the way e.g. table-body.php does.
//
// `colspan`/`rowspan` have no dedicated config key -- pass them via the `attributes` passthrough
// (e.g. `attributes: ['colspan' => '2']`), same as table-head.php.
//
// Phase 2 (CLAUDE.md Regel 1): base classes taken from shadcn's own TableCell (registry/
// new-york-v4/ui/table.tsx, live-checked 2026-09-03) -- `align-middle [&:has([role=checkbox])]:
// pr-0 [&>[role=checkbox]]:translate-y-[2px]` kept 1:1, `p-2` widened to `px-4 py-3` on the
// strength of the Claude-Design reference "Hengegroup" (same `.dc.html` reference workflow as
// table-head.php's own entry, see docs/entscheidungen.md) -- `px-4` matches table-head.php's own
// horizontal padding so header/body columns line up, `py-3` (12px) is the nearest real Tailwind
// step to the reference's own ~13px row padding.
//
// `align` mirrors table-head.php's own new config 1:1 -- see that file's header comment for the
// "for project CSS" -> first-class-config history this closes for the whole column, not just the
// header cell.
//
// Supported config:
//   content   string   optional. Pre-rendered HTML (plain escaped text or nested component markup,
//                       e.g. an icon.php/badge.php/button.php call). Omit/empty for a deliberately
//                       blank cell -- see note above
//   align     string   start (default) | center | end -- text alignment, mirrored as `data-align`
//                       (see table-head.php's own Phase 2 note)
//   class / attributes / data_attributes   passthrough onto the outer
//                       <td data-slot="table-cell"> element -- `class` is appended AFTER the
//                       computed base/align classes (plain string concat, same caveat as
//                       button.php's own `class` doc): fine for additive classes, not guaranteed to
//                       win a conflicting utility (e.g. a different `py-*`) over the computed ones

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$align = trim((string) ($config['align'] ?? 'start'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_aligns = ['start', 'center', 'end'];

if (!in_array($align, $allowed_aligns, true)) {
    $align = 'start';
}

$align_classes = [
    'start' => 'text-left',
    'center' => 'text-center',
    'end' => 'text-right',
];

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    "px-4 py-3 align-middle whitespace-nowrap [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-[2px] {$align_classes[$align]}" .
        ($class_name !== '' ? ' ' . $class_name : ''),
);
$element_attributes['data-slot'] = 'table-cell';

if ($align !== 'start') {
    $element_attributes['data-align'] = $align;
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<td%1$s>%2$s</td>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
