<?php

declare(strict_types=1);

// shadcn/ui's TableBody: a plain <tbody>, no ARIA/JS beyond what native table sectioning already
// provides (CLAUDE.md #1). Content-agnostic wrapper, same nesting pattern as
// table.php/table-header.php: buffer one or more table-row.php calls (each built
// from table-cell.php cells) and pass the combined HTML as `content`.
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

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

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
