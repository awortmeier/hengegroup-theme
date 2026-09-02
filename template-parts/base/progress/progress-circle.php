<?php

declare(strict_types=1);

// NOT a shadcn/ui component -- shadcn's own Progress is the linear bar in progress.php only, no
// circular/ring variant. Added because the design reference's "Ring" section calls for one
// ("compact variant for KPI figures and tiles") -- same "design reference asks for something
// outside shadcn's real API" situation attachment.php's own `progress` config already documents,
// see that file's header for the precedent.
//
// Markup: a single `role="progressbar"` <div>, no nested indicator element (unlike progress.php's
// two-pseudo-element track/fill split) -- the ring shape itself IS both track and fill in one
// layered background, see below.
//
// Rendering technique: `conic-gradient(var(--pc-color) var(--pc-value), var(--pc-track) 0)` paints
// a solid disc, `mask-image: radial-gradient(...)` then punches a transparent hole out of its
// center to leave a ring -- chosen over the more common "conic-gradient disc + inner div painted
// to match the surrounding background" trick (which is what the design reference itself uses) BE-
// CAUSE that trick only works when the component already knows its surrounding background color;
// this component doesn't (it's used standalone, not just inside the reference's own dark KPI-tile
// cards) -- a `mask`-based hole is real transparency, correct on any background with no extra prop
// needed. `--pc-track`/`--pc-color` are static per-`variant` Tailwind arbitrary-property classes
// (`[--pc-color:var(--color-henge-green)]`), `--pc-value` is the one truly dynamic piece (the
// percentage, computed from `value`/`max` at render time) -- Tailwind's JIT scanner can't
// pre-generate an arbitrary-value class for a PHP-computed number, so it's set via inline
// `style="--pc-value: ...%;"` instead, same documented Regel-1 exception/reasoning as
// aspect-ratio.php's own inline `style="aspect-ratio: ...;"` (see that file's header).
//
// No indeterminate mode (unlike progress.php) -- a ring with no known value isn't a case the
// design reference's own KPI-tile usage needs (every example there has a concrete number), so it's
// out of scope here rather than half-supported; omitting `value` renders a 0% ring, not an
// animated indeterminate spinner (nest spinner.php instead for that case).
//
// Design reference: https://claude.ai/code/artifact/742f972a-483b-4310-a64e-fc82e6b1d2d4 ("Ring"
// section) -- the surrounding KPI-tile card (name/meta text, card background/shadow) is showcase
// composition, not rendered by this file, same "component renders only the indicator itself, the
// caller composes labels around it" minimalism as progress.php (see the showcase page for how to
// build that tile).
//
// Supported config:
//   value           int|string   current value, default 0
//   max             int|string   default 100
//   size            string       sm | base (default) | lg -- diameter, real Tailwind steps
//                                 (size-10/size-14/size-20 = 40/56/80px, `base` matches the design
//                                 reference's own ring exactly), same size vocabulary as
//                                 progress.php/button.php
//   variant         string       henge-green (default) | henge-blue | henge-grey | destructive --
//                                 ring color, project brand-color vocabulary (see progress.php's
//                                 own `variant` for the same naming pattern; `henge-grey` added
//                                 here because the design reference's own third Ring example uses
//                                 it, unlike progress.php's Zustaende examples)
//   label           string       centered text override, e.g. '3 von 4'. Defaults to the
//                                 computed percentage (e.g. '78 %') when omitted
//   show_label      bool         default true. false hides the centered text entirely (a
//                                 decorative-only ring, e.g. sitting right next to its own visible
//                                 value elsewhere)
//   aria_label      string       accessible name; falls back to the visible label (computed or
//                                 explicit) when omitted, same "visible text doubles as the
//                                 accessible name unless overridden" pattern as button.php
//   aria_valuetext  string       optional human-readable value alternative, same as progress.php
//   class / attributes / data_attributes   passthrough onto the outer
//                                 <div data-slot="progress-circle">

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$value = is_numeric($config['value'] ?? null) ? (float) $config['value'] : 0.0;
$max = is_numeric($config['max'] ?? null) ? (float) $config['max'] : 100.0;
$size = trim((string) ($config['size'] ?? 'base'));
$variant = trim((string) ($config['variant'] ?? 'henge-green'));
$label = array_key_exists('label', $config) ? trim((string) $config['label']) : null;
$show_label = !array_key_exists('show_label', $config) || !empty($config['show_label']);
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$aria_valuetext = trim((string) ($config['aria_valuetext'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($max <= 0.0) {
    $max = 100.0;
}

$percent = max(0.0, min(100.0, ($value / $max) * 100));
$percent_rounded = (int) round($percent);
$state = $percent_rounded >= 100 ? 'complete' : 'loading';

$computed_label = $label ?? $percent_rounded . ' %';

$allowed_sizes = ['sm', 'base', 'lg'];

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'base';
}

$allowed_variants = ['henge-green', 'henge-blue', 'henge-grey', 'destructive'];

if (!in_array($variant, $allowed_variants, true)) {
    $variant = 'henge-green';
}

$size_classes = [
    'sm' =>
        'size-10 text-xs [mask-image:radial-gradient(farthest-side,transparent_calc(100%_-_5px),black_calc(100%_-_5px))]',
    'base' =>
        'size-14 text-sm [mask-image:radial-gradient(farthest-side,transparent_calc(100%_-_7px),black_calc(100%_-_7px))]',
    'lg' =>
        'size-20 text-base [mask-image:radial-gradient(farthest-side,transparent_calc(100%_-_10px),black_calc(100%_-_10px))]',
];

$variant_classes = [
    'henge-green' => '[--pc-color:var(--color-henge-green)]',
    'henge-blue' => '[--pc-color:var(--color-henge-blue)]',
    'henge-grey' => '[--pc-color:var(--color-henge-grey)]',
    'destructive' => '[--pc-color:var(--color-destructive)]',
];

$base_classes =
    'relative inline-flex shrink-0 items-center justify-center rounded-full ' .
    '[--pc-track:var(--color-border)] ' .
    'bg-[conic-gradient(var(--pc-color)_var(--pc-value),var(--pc-track)_0)] ' .
    'font-medium tabular-nums';

$computed_class = "{$base_classes} {$size_classes[$size]} {$variant_classes[$variant]}";

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);
$element_attributes['style'] = '--pc-value: ' . $percent_rounded . '%;';

$element_attributes['role'] = 'progressbar';
$element_attributes['data-slot'] = 'progress-circle';
$element_attributes['data-state'] = $state;
$element_attributes['data-size'] = $size;
$element_attributes['data-variant'] = $variant;
$element_attributes['aria-valuemin'] = '0';
$element_attributes['aria-valuemax'] = (string) $max;
$element_attributes['aria-valuenow'] = (string) $value;

$accessible_name = $aria_label !== '' ? $aria_label : $computed_label;

if ($accessible_name !== '') {
    $element_attributes['aria-label'] = $accessible_name;
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
    '<div%s>%s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $show_label
        ? '<span data-slot="progress-circle-label">' . esc_html($computed_label) . '</span>'
        : '',
);
