<?php

declare(strict_types=1);

// NOT a shadcn/ui component -- same situation as progress-circle.php in this folder (see that
// file's header for the precedent this repeats): shadcn's own Progress is the linear bar in
// progress.php only. Added because the design reference's "Schritte" section calls for a
// segmented multi-step indicator ("for multi-step flows, e.g. a job application") -- N equal-width
// bars filling in as discrete steps complete, not a continuous 0-100 value.
//
// Markup: one outer `role="progressbar"` wrapper around `steps` equal-width `flex-1` segment
// `<div>`s -- deliberately NOT built on the native <progress> element progress.php uses (a native
// <progress> has exactly one continuous value/max pair, no concept of N discrete equal segments
// with individually addressable fill state), so this is plain presentational div markup with
// data-attributed segments instead, closer to shadcn's own pre-primitive "styled native HTML"
// components (badge.php/attachment.php) than to progress.php's headless-primitive translation.
//
// Only renders the segment bars themselves, NOT the step name labels underneath them the design
// reference shows ("Daten"/"Unterlagen"/...) -- same "component renders only the indicator, the
// caller composes labels/surrounding chrome" minimalism as progress.php/progress-circle.php (see
// the showcase page for how to build the labelled version, reusing the same `current`/`steps`
// count to color each label to match).
//
// `color` (design request 2026-09-02): the design reference frames this component specifically as
// "auf dunklem Grund" (on a dark surface) -- its own incomplete-segment color
// (rgba(250,249,245,0.18)) only reads on a dark card, not a light one. Rather than assuming a dark
// surface as this component's only supported context, `color` exposes both: `default` for a light
// surface (this file's own baseline), `light` for a dark one -- same vocabulary/meaning as
// accordion.php's/typography.php's own `color` config (project-wide convention for "this component
// also needs to work sitting on a dark surface", not invented here). `light`'s "done" segment uses
// `color-mix()` against the same henge-green token instead of the reference's unrelated literal
// hex (#8fd6ab) -- a lighter tint of the real brand color instead of a one-off color with no token
// behind it, real Tailwind arbitrary value (Regel 1 permits arbitrary values), not a raw-CSS
// exception.
//
// Design reference: https://claude.ai/code/artifact/742f972a-483b-4310-a64e-fc82e6b1d2d4
// ("Schritte" section) -- the dark card wrapper itself (radial-gradient wash, rounded-2xl,
// padding) and the Zurueck/Weiter buttons are showcase composition, not part of this component.
//
// Supported config:
//   steps           int      total number of segments, default 1
//   current         int      number of completed (filled) segments from the start, default 0,
//                             clamped to 0..steps
//   color           string   default | light -- see the `color` note above
//   aria_label      string   accessible name, e.g. 'Bewerbung, Schritt 2 von 4'
//   class / attributes / data_attributes   passthrough onto the outer
//                             <div data-slot="progress-steps">

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$steps = max(1, (int) ($config['steps'] ?? 1));
$current = max(0, min($steps, (int) ($config['current'] ?? 0)));
$color = trim((string) ($config['color'] ?? 'default'));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_colors = ['default', 'light'];

if (!in_array($color, $allowed_colors, true)) {
    $color = 'default';
}

$segment_classes = [
    'default' => [
        'complete' => 'bg-henge-green',
        'upcoming' => 'bg-border',
    ],
    'light' => [
        'complete' => 'bg-[color-mix(in_oklab,var(--color-henge-green)_65%,white)]',
        'upcoming' => 'bg-white/20',
    ],
];

$element_attributes = $attributes;
$element_attributes['class'] = trim('flex gap-2' . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['role'] = 'progressbar';
$element_attributes['data-slot'] = 'progress-steps';
$element_attributes['data-color'] = $color;
$element_attributes['aria-valuemin'] = '0';
$element_attributes['aria-valuemax'] = (string) $steps;
$element_attributes['aria-valuenow'] = (string) $current;

if ($aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

$segments_markup = '';

for ($i = 0; $i < $steps; $i++) {
    $segment_state = $i < $current ? 'complete' : 'upcoming';

    $segments_markup .= sprintf(
        '<div class="h-1.5 flex-1 rounded-full transition-colors duration-300 %1$s" data-slot="progress-steps-segment" data-state="%2$s"></div>',
        esc_attr($segment_classes[$color][$segment_state]),
        esc_attr($segment_state),
    );
}

printf(
    '<div%s>%s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $segments_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
