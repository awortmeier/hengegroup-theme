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
// Supported config:
//   content   string   optional. Pre-rendered HTML (plain escaped text or nested component markup,
//                       e.g. an icon.php/badge.php/button.php call). Omit/empty for a deliberately
//                       blank cell -- see note above
//   class / attributes / data_attributes   passthrough onto the outer
//                       <td data-slot="table-cell"> element

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'table-cell';

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
