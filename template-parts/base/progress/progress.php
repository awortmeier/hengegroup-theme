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
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind, classes adapted from shadcn's own Progress
// (registry/new-york-v4/ui/progress.tsx, live-checked 2026-09-02: Root `"relative h-2 w-full
// overflow-hidden rounded-full bg-primary/20"`, Indicator `"h-full w-full flex-1 bg-primary
// transition-all"`, positioned via an inline `translateX` shadcn computes from `value` in JS) --
// `bg-primary`/`bg-primary/20` become this project's own `variant` vocabulary (see below, same
// brand-color-name pattern as button.php's `variant`), and the track/indicator two-element
// Root+Indicator split becomes the native `<progress>` element's own
// `::-webkit-progress-bar`/`::-webkit-progress-value` (`::-moz-progress-bar` for Firefox, which has
// no separate "value" pseudo-element to target) pseudo-elements instead of two real DOM nodes --
// `appearance-none` first, so the browser's own default progress chrome (each engine renders it
// completely differently, e.g. macOS/Safari honoring the user's OS accent-color setting) gets out
// of the way for these to apply predictably everywhere. All expressible as plain Tailwind arbitrary
// pseudo-element variants (`[&::-webkit-progress-value]:...`, same technique as `date-picker.php`'s/
// `accordion.php`'s own `[&::-webkit-details-marker]:hidden`) -- no raw-CSS exception needed for
// the base look. The indeterminate state's animated sweep (no `value` given, see above) is the
// browser's own built-in behaviour; recoloring `::-webkit-progress-value`/`::-moz-progress-bar`
// doesn't disable it.
//
// Design reference: https://claude.ai/code/artifact/742f972a-483b-4310-a64e-fc82e6b1d2d4
// ("Basis"/"Groessen"/"Zustaende" sections -- "Ring" and the dark segmented "Schritte" section
// aren't a linear <progress> at all, see progress-circle.php/progress-steps.php in this same
// folder instead). Two deviations from that reference:
//   - the reference's "Basis"/"Zustaende" bars use a 4px (`rounded`) radius while its own
//     "Groessen" section uses a full pill (`rounded-full`) at every size -- inconsistent within the
//     one artifact. Resolved in favour of the full pill everywhere, matching shadcn's own real
//     Progress spec (`rounded-full`, see file header above) AND this file's pre-existing choice
//     before this pass, not treated as a new decision to relitigate.
//   - color vocabulary renamed to this project's own brand-color names (`henge-green` default
//     instead of a bare accent), consistent with every other variant-driven base component
//     (button.php, badge.php, ...) -- see `variant` below.
//
// `variant` (design request 2026-09-02): the reference's "Zustaende" section colors real
// value-driven progress (uploading/complete) in its green accent, system/automatic states without
// a caller-known percentage (its own custom "indeterminate" mock, the "striped" processing example)
// in blue, and a cancelled/failed bar in red -- three distinct colorings, not baked into `data-state`
// (which stays purely value-derived, see above) because the same `data-state="loading"` bar can be
// either a real user upload (green) or a striped batch-processing bar (blue) with no way to tell
// those apart from value/max alone. Left fully to the caller via `variant` instead of inferring it
// from `data-state`/`striped` -- explicit beats implicit here, same reasoning button.php's `variant`
// isn't auto-picked from `disabled`/`loading` either.
//
// `striped` (design request 2026-09-02): the reference's "Charge wird gesiebt" example animates a
// diagonal two-tone stripe sweep across the filled portion -- no stock Tailwind utility for a
// repeating-linear-gradient background + animated background-position (unlike `animate-pulse`/
// `animate-spin`, which DO ship as utilities), so this is a documented Regel-1 raw-CSS exception
// (assets/css/app.css, scoped to `[data-slot="progress"][data-striped="true"]`). Built on
// `currentColor` (`text-{variant}` + `bg-current` below) so ONE raw-CSS block covers every variant
// instead of one per color -- the stripe gradient's `currentColor` stops just pick up whatever
// `variant` already set as this element's `color`, same value the solid (non-striped) fill uses via
// `bg-current`.
//
// Supported config:
//   value           int|string   current value; omit entirely for the native indeterminate/
//                                 animated state (matches shadcn's indeterminate concept)
//   max             int|string   default 100
//   size            string       sm | base (default) | lg -- track thickness, real Tailwind steps
//                                 (h-1/h-2/h-3.5 = 4/8/14px), same size vocabulary/naming as
//                                 button.php (sm/base/lg)
//   variant         string       henge-green (default) | henge-blue | destructive -- track/fill
//                                 color pairing, see the `variant` note above for when to use which
//   striped         bool         default false. Animated diagonal stripe fill instead of a solid
//                                 one, see the `striped` note above -- combinable with any variant
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
$size = trim((string) ($config['size'] ?? 'base'));
$variant = trim((string) ($config['variant'] ?? 'henge-green'));
$striped = !empty($config['striped']);
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

$allowed_sizes = ['sm', 'base', 'lg'];

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'base';
}

$allowed_variants = ['henge-green', 'henge-blue', 'destructive'];

if (!in_array($variant, $allowed_variants, true)) {
    $variant = 'henge-green';
}

$size_classes = ['sm' => 'h-1', 'base' => 'h-2', 'lg' => 'h-3.5'];

// Track = bg-{variant}/opacity on the root element itself (shows through
// ::-webkit-progress-bar's own bg-transparent below); fill = text-{variant} + bg-current on the
// value/moz pseudo-elements (see the `striped` note above for why bg-current instead of a second
// bg-{variant} class).
$variant_classes = [
    'henge-green' => 'bg-henge-green/20 text-henge-green',
    'henge-blue' => 'bg-henge-blue/15 text-henge-blue',
    'destructive' => 'bg-destructive/15 text-destructive',
];

$base_classes =
    'block w-full appearance-none overflow-hidden rounded-full border-none ' .
    '[&::-webkit-progress-bar]:rounded-full [&::-webkit-progress-bar]:bg-transparent ' .
    '[&::-webkit-progress-value]:rounded-full [&::-webkit-progress-value]:bg-current ' .
    '[&::-webkit-progress-value]:transition-[width] [&::-webkit-progress-value]:duration-300 ' .
    '[&::-moz-progress-bar]:rounded-full [&::-moz-progress-bar]:bg-current';

$computed_class = "{$base_classes} {$size_classes[$size]} {$variant_classes[$variant]}";

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['data-slot'] = 'progress';
$element_attributes['data-state'] = $state;
$element_attributes['data-size'] = $size;
$element_attributes['data-variant'] = $variant;
$element_attributes['max'] = $max;

if ($striped) {
    $element_attributes['data-striped'] = 'true';
}

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
