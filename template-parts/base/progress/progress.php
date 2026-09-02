<?php

declare(strict_types=1);

// Direct translation of shadcn/ui's Progress (wraps a headless UI primitive -- historically Radix
// UI, shadcn now also ships Base UI/React Aria variants of many components). That primitive has
// no interactive/JS behaviour of its own -- it's a purely presentational/ARIA
// wrapper whose `value` is set and updated by the consuming app. Native HTML already has a real
// progress bar element for exactly this: <progress value="X" max="Y">, with automatic
// role="progressbar" + aria-valuenow/aria-valuemax, AND a built-in indeterminate/animated state
// when `value` is omitted -- matching that primitive's own "indeterminate" concept 1:1, no JS
// needed (see CLAUDE.md #1). If a project later updates the value live (e.g. during a file
// upload), that's just `progressEl.value = x` on this native element -- no special wiring
// required from this component.
//
// `data-state` (loading | complete | indeterminate, mirroring shadcn's own vocabulary) is computed
// server-side from `value`/`max` at render time, purely as a CSS hook -- not kept in sync with
// later client-side value changes (same "reflects render time only" caveat as elsewhere, see
// CLAUDE.md #1).
//
// Styling note: full custom styling of a native <progress> needs the vendor-prefixed
// pseudo-elements (::-webkit-progress-bar/::-webkit-progress-value, ::-moz-progress-bar) rather
// than plain child selectors -- a project-CSS concern, not solved here.
//
// Supported config:
//   value           int|string   current value; omit entirely for the native indeterminate/
//                                 animated state (matches shadcn's indeterminate concept)
//   max             int|string   default 100
//   aria_label      string       accessible name (progress bars aren't form fields, so no
//                                 `label.php` pairing like the form controls -- shadcn's own
//                                 Progress docs don't compose a Label either)
//   aria_valuetext  string       optional human-readable value alternative (e.g. "7 of 10 files"),
//                                 valid as an explicit ARIA override even on a native <progress>
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$has_value =
    array_key_exists('value', $config) && $config['value'] !== null && $config['value'] !== '';
$value = $has_value ? trim((string) $config['value']) : '';
$max = trim((string) ($config['max'] ?? '100'));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$aria_valuetext = trim((string) ($config['aria_valuetext'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($max === '' || !is_numeric($max)) {
    $max = '100';
}

$state = 'indeterminate';

if ($has_value && is_numeric($value)) {
    $state = (float) $value >= (float) $max ? 'complete' : 'loading';
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'progress';
$element_attributes['data-state'] = $state;
$element_attributes['max'] = $max;

if ($has_value) {
    $element_attributes['value'] = $value;
}

if ($aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
}

if ($aria_valuetext !== '') {
    $element_attributes['aria-valuetext'] = $aria_valuetext;
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<progress%s></progress>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
