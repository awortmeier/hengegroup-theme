<?php

declare(strict_types=1);

// shadcn/ui's TableBody: a plain <tbody>, no ARIA/JS beyond what native table sectioning already
// provides (CLAUDE.md #1). Content-agnostic wrapper, same nesting pattern as
// table.php/table-header.php: buffer one or more table-row.php calls (each built
// from table-cell.php cells) and pass the combined HTML as `content`.
//
// Phase 2 (CLAUDE.md Regel 1): base class taken 1:1 from shadcn's own TableBody
// (registry/new-york-v4/ui/table.tsx, live-checked 2026-09-03): `[&_tr:last-child]:border-0` --
// drops the last row's own bottom divider (table-row.php's own `border-b`) so it doesn't double up
// against table.php's card edge / table-footer.php's own top border.
//
// `[&_tr[data-last-visible]]:border-0` (bug fix, 2026-09-04) is a second, additive hook for the
// same purpose: `:last-child` is a DOM-position selector, blind to `hidden` -- data-table.php's
// client-side pagination (see data-table.js) hides rows past the current page rather than removing
// them, so the true last `<tr>` (still `:last-child`) usually sits on a LATER, currently-hidden
// page while some earlier, visible row is the one that actually needs its bottom border dropped.
// Unused/inert unless something sets `data-last-visible` on a row (data-table.js is the only
// current consumer, keeping it in sync with its own paging on every render) -- a plain table.php
// user with no hidden rows is untouched, `:last-child` alone already covers that case correctly.
//
// Supported config:
//   content   string   required. Pre-rendered HTML to wrap (buffered table-row.php calls)
//   class / attributes / data_attributes   passthrough onto the outer
//                       <tbody data-slot="table-body"> wrapper

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
    '[&_tr:last-child]:border-0 [&_tr[data-last-visible]]:border-0' .
        ($class_name !== '' ? ' ' . $class_name : ''),
);
$element_attributes['data-slot'] = 'table-body';

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<tbody%1$s>%2$s</tbody>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
