<?php

declare(strict_types=1);

// shadcn/ui's Table has no headless UI primitive underneath at all (unlike most components here)
// -- just semantic <table> markup plus a horizontally-scrolling container div around it. The
// underlying markup problem (a table wider than its container needs a scroll wrapper, not a
// squeeze) is structurally identical to what template-parts/base/scroll-area.php already solves;
// nested here via its `data_slot` escape hatch (`'table-container'`) with
// `orientation: 'horizontal'`, same trick attachment-group.php already uses, not reimplemented.
// calendar.php is the other native-<table> base component, but it bakes in its own
// day-grid markup for a specific use case -- this component stays generic/content-agnostic instead.
//
// Content-agnostic wrapper, same nesting pattern as button-group.php/field-group.php (CLAUDE.md
// #2): buffer an optional table-caption.php call, a table-header.php call, a table-body.php call
// and an optional table-footer.php call, then pass the combined HTML as `content`. Per the HTML
// spec <caption> must be the FIRST child of <table> -- order it first when building `content`, not
// last (shadcn's own JSX example lists <TableCaption> first for the same reason).
//
//   ob_start();
//   get_template_part('template-parts/base/table/table-caption', null, ['config' => ['text' => 'A list of recent invoices.']]);
//   get_template_part('template-parts/base/table/table-header', null, ['config' => ['content' => $header_rows]]);
//   get_template_part('template-parts/base/table/table-body', null, ['config' => ['content' => $body_rows]]);
//   $content = (string) ob_get_clean();
//
//   get_template_part('template-parts/base/table/table', null, ['config' => ['content' => $content]]);
//
// `class`/`attributes`/`data_attributes` apply to the inner <table data-slot="table"> element
// itself, matching shadcn (className/props spread onto <table>, not the wrapping scroll
// container) -- the container div is a fixed, non-configurable <div data-slot="table-container">
// wrapper, same division as scroll-area.php's own nesting callers.
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind. Base classes on <table> taken 1:1 from
// shadcn's own Table (registry/new-york-v4/ui/table.tsx, live-checked 2026-09-03):
// `w-full caption-bottom text-sm`. The <div data-slot="table-container"> wrapper is rendered by
// the nested scroll-area.php call below, not this file directly -- shadcn's own stock class there
// is `relative w-full overflow-x-auto`; this file passes that 1:1 as scroll-area.php's `class`
// (scroll-area.php itself stays unstyled/Phase-1, see its own header -- table.php owns this
// specific instance's classes the same way any composing caller supplies scroll-area.php's
// `class`, not a change to scroll-area.php's own defaults), PLUS a card treatment
// (`rounded-xl border border-border bg-card shadow-xs`) on the strength of the Claude-Design
// reference "Hengegroup" (same `.dc.html` reference workflow as button.php's/kbd.php's own
// entries in docs/entscheidungen.md -- see that file for this component's entry): every non-dark
// section of the reference wraps its table in exactly this card look, so it becomes this file's
// own default rather than something every caller has to repeat.
//
// `striped` (new, on explicit request from the reference's own "Gestreift" section) adds a
// zebra-row hook targeting `<tbody>` rows via an arbitrary-variant selector on <table> itself --
// same `[&_x]:` descendant-targeting idiom shadcn's own table-header.php/table-body.php use below,
// not a new component-specific mechanism. `bg-muted/50` reuses the same tint TableRow's own
// `hover:bg-muted/50` already uses (shadcn stock, see table-row.php), so a striped row and a
// hovered row read as the same "slightly recessed" surface rather than two competing tints.
//
// `card` (new, off for the reference's own "Kompakt, ohne Rahmen" section) toggles the container
// div's card treatment on/off. This has to be a dedicated config rather than something a caller
// reaches via the existing `class`/`attributes` passthrough: that passthrough only ever applied to
// the INNER <table> element (see above, matching shadcn's own division of props), while the card
// look lives on the OUTER scroll-area.php container div one level up -- no combination of this
// file's own `class`/`attributes` could ever reach it. `card: false` swaps the container to
// shadcn's own bare stock class (`relative w-full overflow-x-auto`, no card treatment at all)
// instead of trying to strip individual card classes back off (fragile string-surgery, same
// "not guaranteed to win a conflicting utility" problem `class` passthrough already has, see
// below) -- a clean two-value switch instead.
//
// Supported config:
//   content   string   required. Pre-rendered HTML to wrap (see composition example above)
//   striped   bool     default false. Zebra-stripes `<tbody>` rows (even rows tinted), see Phase 2
//                       note above
//   card      bool     default true. Wraps the table in the card look described above; `false`
//                       renders shadcn's own bare container instead -- the reference's "Kompakt,
//                       ohne Rahmen" section combines this with composing table-head.php calls
//                       using `scope: 'row'` for a left-hand label column instead of a <thead>
//                       header row (see page-component-showcase-table.php's own "Kompakt" example)
//   class / attributes / data_attributes   passthrough onto the inner
//                       <table data-slot="table"> element (NOT the outer container div, see `card`
//                       above) -- `class` is appended AFTER the computed base/striped classes
//                       (plain string concat, same caveat as button.php's own `class` doc): fine
//                       for additive classes, not guaranteed to win a conflicting utility over the
//                       computed ones

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$striped = !empty($config['striped']);
$card = !array_key_exists('card', $config) || !empty($config['card']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

$table_attributes = $attributes;

$table_classes = 'w-full caption-bottom text-sm';

if ($striped) {
    $table_classes .= ' [&_tbody>tr:nth-child(even)]:bg-muted/50';
}

$table_attributes['class'] = trim($table_classes . ($class_name !== '' ? ' ' . $class_name : ''));
$table_attributes['data-slot'] = 'table';

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $table_attributes['data-' . $data_name] = $attribute_value;
}

$table_markup = sprintf(
    '<table%1$s>%2$s</table>',
    hengegroup_theme_render_attributes($table_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

$container_classes = 'relative w-full overflow-x-auto';

if ($card) {
    $container_classes .= ' rounded-xl border border-border bg-card shadow-xs';
}

get_template_part('template-parts/base/scroll-area', null, [
    'config' => [
        'content' => $table_markup,
        'orientation' => 'horizontal',
        'data_slot' => 'table-container',
        'class' => $container_classes,
    ],
]);
