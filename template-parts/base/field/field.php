<?php

declare(strict_types=1);

// Form field layout: wraps a field-label + bare control + optional field-description/field-error.
// The caller wires aria-describedby, e.g. via hengegroup_theme_field_describedby($control_id).
// No role on the wrapper: a single control is not a group (use field-set.php for that).
//
// Config:
//   content       string   pre-rendered HTML (required)
//   orientation   string   vertical (default) | horizontal | responsive (needs field-group.php)
//   invalid       bool     sets data-invalid (styling only; aria-invalid belongs on the control)
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$orientation = trim((string) ($config['orientation'] ?? 'vertical'));
$invalid = !empty($config['invalid']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

$allowed_orientations = ['vertical', 'horizontal', 'responsive'];

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'vertical';
}

$base_classes =
    'group/field flex w-full gap-3 data-[invalid=true]:text-destructive ' .
    'data-[orientation=vertical]:flex-col data-[orientation=vertical]:[&>*]:w-full ' .
    'data-[orientation=vertical]:[&>.sr-only]:w-auto ' .
    // horizontal: a checkbox/radio stays aligned with the first label line
    'data-[orientation=horizontal]:flex-row data-[orientation=horizontal]:items-center ' .
    'data-[orientation=horizontal]:[&>[data-slot=field-label]]:flex-auto ' .
    'data-[orientation=horizontal]:has-[>[data-slot=field-content]]:items-start ' .
    'data-[orientation=horizontal]:has-[>[data-slot=field-content]]:[&>[role=checkbox],[role=radio]]:mt-px ' .
    'data-[orientation=responsive]:flex-col data-[orientation=responsive]:[&>*]:w-full ' .
    'data-[orientation=responsive]:[&>.sr-only]:w-auto ' .
    'data-[orientation=responsive]:@md/field-group:flex-row ' .
    'data-[orientation=responsive]:@md/field-group:items-center ' .
    'data-[orientation=responsive]:@md/field-group:[&>*]:w-auto ' .
    'data-[orientation=responsive]:@md/field-group:[&>[data-slot=field-label]]:flex-auto ' .
    'data-[orientation=responsive]:@md/field-group:has-[>[data-slot=field-content]]:items-start ' .
    'data-[orientation=responsive]:@md/field-group:has-[>[data-slot=field-content]]:' .
    '[&>[role=checkbox],[role=radio]]:mt-px';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'field';
$element_attributes['data-orientation'] = $orientation;

if ($invalid) {
    $element_attributes['data-invalid'] = 'true';
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
