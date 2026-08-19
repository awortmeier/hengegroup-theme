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
// Supported config:
//   content   string   required. Pre-rendered HTML to wrap (see composition example above)
//   class / attributes / data_attributes   passthrough onto the inner
//                       <table data-slot="table"> element

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

$table_attributes = $attributes;

if ($class_name !== '') {
    $table_attributes['class'] = $class_name;
}

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

get_template_part('template-parts/base/scroll-area', null, [
    'config' => [
        'content' => $table_markup,
        'orientation' => 'horizontal',
        'data_slot' => 'table-container',
    ],
]);
