<?php

declare(strict_types=1);

// shadcn/ui's Input has no headless-library primitive behind it either (Radix UI/Base UI/React
// Aria alike) -- it's a styled native <input>, no JS state at all (see CLAUDE.md #1). This
// component is the same: a plain, data-attributed
// <input>, optionally paired with template-parts/base/label.php (same convenience
// pattern as checkbox.php's `label` config, just stacked label-before-input instead of
// input-then-label, matching the usual text-field layout).
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind, classes taken 1:1 from shadcn's own Input
// component (registry/new-york-v4/ui/input.tsx) -- except:
//   - `dark:`-prefixed classes dropped, same reasoning as button.php/badge.php (no dark-mode
//     strategy yet, see docs/to-do.md).
//   - fixed `h-9` dropped in favour of padding-driven height (design request 2026-08-30, per this
//     project's Hengegroup Design-System-Projekt "Bewerbungsformular" reference form: all its text
//     fields use generous `padding:12px 14px`, not a shadcn-style fixed row height) -- padding
//     mapped to the nearest real Tailwind steps (`py-3`/`px-3.5` = 12px/14px exactly).
//   - `rounded-md` swapped for `rounded-lg` -- the reference's `border-radius:10px` sits exactly
//     between Tailwind's `rounded-lg` (8px) and `rounded-xl` (12px) steps; `rounded-lg` was picked
//     to keep fields visually distinct from button.php's/badge.php's fully-pill `rounded-full`.
//   - `selection:bg-primary`/`selection:text-primary-foreground` renamed to this project's
//     henge-green brand tokens, same substitution button.php's variant vocabulary already made.
//   - a `data_slot === 'input-group-control'` branch (see `data_slot` doc below) swaps the
//     standalone field classes for shadcn's separate InputGroupInput treatment (border/background/
//     radius/ring removed -- input-group.php's own wrapper owns those instead) rather than double-
//     rendering a border/background inside input-group.php's own bordered box.
//
// Deliberately NOT included, to stay faithful to shadcn's actual Input API (don't invent
// vocabulary shadcn doesn't have, see CLAUDE.md #1):
//   - an icon slot (leading/trailing icon inside the field) -- stock shadcn Input has none either;
//     compose icon.php + input.php yourself in a project-specific molecule if needed
//   - description/error-message pairing (aria-describedby, validation messages) -- that's
//     shadcn's separate Form/Field system built on top of Input, out of scope for this atom
//
// Supported config:
//   type           string   text | email | password | number | tel | url | search | date |
//                            datetime-local | month | week | time | color | file | range | hidden
//                            (default: text) -- native <input type>
//   name / value / placeholder / autocomplete / min / max / step / minlength / maxlength / pattern
//                    string  native attributes, only rendered when given
//   disabled / required / readonly   bool   native attributes; `disabled` also mirrors as
//                    data-disabled="true" (shadcn's own CSS-hook convention)
//   aria_invalid     bool    sets aria-invalid="true" plus a mirrored data-invalid="true" --
//                    shadcn's error-state styling hooks
//   id               string  native `id`; auto-generated via wp_unique_id() when omitted (needed
//                            to pair with `label` via its `for` attribute)
//   data_slot        string  overrides the root `data-slot` value (default: 'input'). Only meant
//                            for a composing parent that needs its own CSS-scoping hook on the
//                            same underlying native input -- e.g. input-group.php passes
//                            'input-group-control' here, matching shadcn's own separate
//                            InputGroupInput/Input data-slot distinction without duplicating this
//                            file's attribute-building logic. Leave unset for
//                            standalone use.
//   label            string  optional visible label text; when given, nests
//                            template-parts/base/label.php before the input, both wrapped in a
//                            plain <div data-slot="input-field">. Omit for full manual control:
//                            render the bare input, then compose your own label.php call +
//                            wrapper markup at the call site
//   aria_label       string  accessible name when no visible `label` is given
//   class / attributes / data_attributes   passthrough onto the <input> itself (not onto the
//                            optional wrapper div, which stays a plain, unstyled hook)

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$type = trim((string) ($config['type'] ?? 'text'));
$name = trim((string) ($config['name'] ?? ''));
$value = trim((string) ($config['value'] ?? ''));
$placeholder = trim((string) ($config['placeholder'] ?? ''));
$autocomplete = trim((string) ($config['autocomplete'] ?? ''));
$min = trim((string) ($config['min'] ?? ''));
$max = trim((string) ($config['max'] ?? ''));
$step = trim((string) ($config['step'] ?? ''));
$minlength = trim((string) ($config['minlength'] ?? ''));
$maxlength = trim((string) ($config['maxlength'] ?? ''));
$pattern = trim((string) ($config['pattern'] ?? ''));
$disabled = !empty($config['disabled']);
$required = !empty($config['required']);
$readonly = !empty($config['readonly']);
$aria_invalid = !empty($config['aria_invalid']);
$id = trim((string) ($config['id'] ?? ''));
$data_slot = trim((string) ($config['data_slot'] ?? ''));
$label_text = trim((string) ($config['label'] ?? ''));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($data_slot === '') {
    $data_slot = 'input';
}

$allowed_types = [
    'text',
    'email',
    'password',
    'number',
    'tel',
    'url',
    'search',
    'date',
    'datetime-local',
    'month',
    'week',
    'time',
    'color',
    'file',
    'range',
    'hidden',
];

if (!in_array($type, $allowed_types, true)) {
    $type = 'text';
}

if ($id === '') {
    $id = 'hengegroup-theme-input-' . wp_unique_id();
}

$is_group_control = $data_slot === 'input-group-control';

$computed_class = $is_group_control
    ? 'flex-1 min-w-0 rounded-none border-0 bg-transparent px-3.5 py-3 text-base shadow-none ' .
        'outline-none focus-visible:ring-0 placeholder:text-muted-foreground ' .
        'file:text-foreground file:inline-flex file:h-7 file:border-0 file:bg-transparent ' .
        'file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed ' .
        'disabled:opacity-50 md:text-sm'
    : 'file:text-foreground placeholder:text-muted-foreground selection:bg-henge-green ' .
        'selection:text-henge-green-foreground border-input flex w-full min-w-0 rounded-lg ' .
        'border bg-background px-3.5 py-3 text-base shadow-xs transition-[color,box-shadow] ' .
        'outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent ' .
        'file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed ' .
        'disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 ' .
        'focus-visible:ring-[3px] aria-invalid:ring-destructive/20 aria-invalid:border-destructive';

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['type'] = $type;
$element_attributes['data-slot'] = $data_slot;
$element_attributes['id'] = $id;

if ($name !== '') {
    $element_attributes['name'] = $name;
}

if ($value !== '') {
    $element_attributes['value'] = $value;
}

if ($placeholder !== '') {
    $element_attributes['placeholder'] = $placeholder;
}

if ($autocomplete !== '') {
    $element_attributes['autocomplete'] = $autocomplete;
}

if ($min !== '') {
    $element_attributes['min'] = $min;
}

if ($max !== '') {
    $element_attributes['max'] = $max;
}

if ($step !== '') {
    $element_attributes['step'] = $step;
}

if ($minlength !== '') {
    $element_attributes['minlength'] = $minlength;
}

if ($maxlength !== '') {
    $element_attributes['maxlength'] = $maxlength;
}

if ($pattern !== '') {
    $element_attributes['pattern'] = $pattern;
}

if ($disabled) {
    $element_attributes['disabled'] = true;
    $element_attributes['data-disabled'] = 'true';
}

if ($required) {
    $element_attributes['required'] = true;
}

if ($readonly) {
    $element_attributes['readonly'] = true;
}

if ($aria_invalid) {
    $element_attributes['aria-invalid'] = 'true';
    $element_attributes['data-invalid'] = 'true';
}

if ($label_text === '' && $aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

$input_markup = '<input' . hengegroup_theme_render_attributes($element_attributes) . '>';

if ($label_text === '') {
    echo $input_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    return;
}

ob_start();
get_template_part('template-parts/base/label', null, [
    'config' => ['text' => $label_text, 'for' => $id],
]);
$label_markup = (string) ob_get_clean();

printf(
    '<div class="flex flex-col gap-2" data-slot="input-field">%1$s%2$s</div>',
    $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $input_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
