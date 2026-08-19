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
// Supported config:
//   content   string   optional. Pre-rendered HTML (plain escaped text or nested component markup,
//                       e.g. an icon.php/checkbox.php call). Omit/empty for a deliberately blank
//                       header cell -- see note above
//   scope     string   col (default) | row | colgroup | rowgroup -- native <th scope> value
//   class / attributes / data_attributes   passthrough onto the outer
//                       <th data-slot="table-head"> element

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$scope = trim((string) ($config['scope'] ?? 'col'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_scopes = ['col', 'row', 'colgroup', 'rowgroup'];

if (!in_array($scope, $allowed_scopes, true)) {
    $scope = 'col';
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'table-head';
$element_attributes['scope'] = $scope;

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
