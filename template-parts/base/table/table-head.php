<?php

declare(strict_types=1);

// shadcn/ui's TableHead: a plain <th>, styled column (or row) header cell. Content-agnostic, same
// nesting pattern as input-group-addon.php: `content` accepts plain escaped text or
// nested component markup (e.g. a checkbox.php call for a "select all" header cell) -- caller's
// responsibility to escape/build, same convention as everywhere else `content` is used.
//
// Unlike most content-agnostic wrappers here, `content` is intentionally allowed to be empty: an
// empty header cell (e.g. above a checkbox or actions column) is a normal, common table shape, not
// a missing-config case -- rendering nothing there (rather than an empty <th>) would silently drop
// a column and misalign every row underneath it, so this file does NOT early-return on blank
// content the way e.g. table-header.php does.
//
// `scope` is exposed as a first-class config (not left to the caller's `attributes` passthrough)
// because it's genuinely required a11y for a table to be understood by assistive tech beyond the
// single-column-header-row case shadcn's own demo covers -- the same `scope="col"` calendar.php
// already sets on its own weekday <th> cells, just data-driven here instead of hardcoded, plus
// `row` for a first-cell-per-row header (e.g. a name column) that shadcn's stock TableHead has no
// dedicated prop for either.
//
// `colspan`/`rowspan` have no dedicated config key -- pass them via the `attributes` passthrough
// (e.g. `attributes: ['colspan' => '2']`), same as every other native HTML attribute this file
// doesn't special-case.
//
// Phase 2 (CLAUDE.md Regel 1): base classes taken from shadcn's own TableHead (registry/
// new-york-v4/ui/table.tsx, live-checked 2026-09-03) -- `h-10 [&:has([role=checkbox])]:pr-0
// [&>[role=checkbox]]:translate-y-[2px]` kept 1:1 -- with the text treatment itself replaced on the
// strength of the Claude-Design reference "Hengegroup" (same `.dc.html` reference workflow as
// pagination.php's own entry, see docs/entscheidungen.md): every section's header row uses a small
// uppercase, wide-tracked, muted label (`font-size:12px;font-weight:600;letter-spacing:0.08em;
// text-transform:uppercase;color:rgba(30,29,28,0.55)`) instead of shadcn's own plain
// `font-medium text-foreground` -- mapped to the nearest real Tailwind steps (no arbitrary values):
// `text-xs` (12px), `font-semibold` (600), `tracking-widest` (0.1em, nearer to 0.08em than
// `tracking-wider`'s 0.05em), `text-muted-foreground`, `uppercase`.
//
// `align` (new, data-table.php's own previously-undelivered "for project CSS" hook -- see its
// header comment history) is now a first-class config instead: sets `text-left`/`text-center`/
// `text-right` directly (no more relying on a project-CSS `[data-align="end"]` rule that never
// existed). The `data-align` attribute is still mirrored for any caller/test that wants the raw
// value as a hook, same "config key AND data-* mirror" convention as `selected`/`data-state` on
// table-row.php.
//
// Supported config:
//   content   string   optional. Pre-rendered HTML (plain escaped text or nested component markup,
//                       e.g. an icon.php/checkbox.php call). Omit/empty for a deliberately blank
//                       header cell -- see note above
//   scope     string   col (default) | row | colgroup | rowgroup -- native <th scope> value
//   align     string   start (default) | center | end -- text alignment, mirrored as
//                       `data-align` (see Phase 2 note above)
//   class / attributes / data_attributes   passthrough onto the outer
//                       <th data-slot="table-head"> element -- `class` is appended AFTER the
//                       computed base/align classes (plain string concat, same caveat as
//                       button.php's own `class` doc): fine for additive classes, not guaranteed to
//                       win a conflicting utility over the computed ones

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$scope = trim((string) ($config['scope'] ?? 'col'));
$align = trim((string) ($config['align'] ?? 'start'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_scopes = ['col', 'row', 'colgroup', 'rowgroup'];

if (!in_array($scope, $allowed_scopes, true)) {
    $scope = 'col';
}

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
    "h-10 px-4 align-middle text-xs font-semibold tracking-widest text-muted-foreground uppercase whitespace-nowrap [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-[2px] {$align_classes[$align]}" .
        ($class_name !== '' ? ' ' . $class_name : ''),
);
$element_attributes['data-slot'] = 'table-head';
$element_attributes['scope'] = $scope;

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
    '<th%1$s>%2$s</th>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
