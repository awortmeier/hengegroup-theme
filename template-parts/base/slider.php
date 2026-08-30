<?php

declare(strict_types=1);

// shadcn/ui's Slider wraps a headless UI primitive (historically Radix UI; shadcn now also ships
// Base UI/React Aria variants of many components): JS-driven pointer/keyboard handling, values
// as an array (one entry per thumb) to support both a single value and a multi-thumb range
// selection (e.g. a min/max price filter). The single-value case -- shadcn's own default/primary
// example (`defaultValue={[33]}`) -- is fully covered by a native <input type="range">: keyboard
// (arrow keys/Home/End/Page Up/Down), touch/drag and accessibility support all built in, zero JS
// needed (see CLAUDE.md #1). This component only covers that single-value case.
//
// Deliberately NOT supported here -- a genuinely different, JS-driven component, not a variant of
// this one (same reasoning as native-select.php vs. select.php):
//   - multi-thumb / range selection (an array of 2+ values) -- no native HTML equivalent at all
//   - a live "current value" display (native <output>) -- without JS to update it on input, a
//     static <output> would just show a stale value once the user drags the thumb, which is worse
//     than not showing one; wiring that up needs a small JS module, not added unprompted
//   - `inverted` fill direction -- not exposed here
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind using the `accent-color` property (Tailwind's
// `accent-*` utility), same rationale as checkbox.php/radio.php -- `accent-color` is built
// specifically to recolor a native `<input type="range">`'s own track-fill/thumb while leaving the
// browser's native rendering/dragging/keyboard behaviour untouched. Deliberately NOT using
// `appearance-none` + hand-drawn `::-webkit-slider-thumb`/`::-moz-range-thumb` pseudo-elements
// (unlike switch.php's pill/thumb, which has no native equivalent at all) -- `accent-color`
// already gets the on-brand look shadcn's own Slider has (a colored fill + colored thumb) without
// giving up any native slider behaviour, so the heavier vendor-prefixed rebuild isn't needed here.
//
// Supported config:
//   min / max / step   string|int   native attributes, only rendered when given (browser
//                       defaults apply otherwise: min=0, max=100, step=1)
//   value               string|int  native `value`
//   name                string      native `name`
//   disabled            bool        native attribute, plus a mirrored data-disabled="true" CSS
//                       hook (shadcn's own convention). No `required`/`readonly` -- neither is
//                       meaningful/valid on <input type="range">, so not offered here
//   aria_invalid        bool        sets aria-invalid="true" plus a mirrored data-invalid="true" --
//                       same error-state hooks as input.php/checkbox.php/switch.php. Unlike
//                       `required`/`readonly` above, aria-invalid IS valid on <input type="range">,
//                       so this one is offered
//   orientation          string      horizontal (default) | vertical -- sets data-orientation only;
//                       actual vertical rendering needs the project's CSS (e.g.
//                       `writing-mode: vertical-lr` on `[data-orientation="vertical"]`), not baked
//                       in here (see CLAUDE.md #1 -- no Tailwind/CSS in the component itself)
//   id                  string      native `id`; auto-generated via wp_unique_id() when omitted
//                       (needed to pair with `label` via its `for` attribute)
//   label               string      optional visible label text; when given, nests
//                       template-parts/base/label.php before the slider, both wrapped in a plain
//                       <div data-slot="slider-field">. Omit for full manual control.
//   aria_label          string      accessible name when no visible `label` is given
//   class / attributes / data_attributes   passthrough onto the <input> itself (not onto the
//                       optional wrapper div, which stays a plain, unstyled hook)

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$min = trim((string) ($config['min'] ?? ''));
$max = trim((string) ($config['max'] ?? ''));
$step = trim((string) ($config['step'] ?? ''));
$value = trim((string) ($config['value'] ?? ''));
$name = trim((string) ($config['name'] ?? ''));
$disabled = !empty($config['disabled']);
$aria_invalid = !empty($config['aria_invalid']);
$orientation = trim((string) ($config['orientation'] ?? 'horizontal'));
$id = trim((string) ($config['id'] ?? ''));
$label_text = trim((string) ($config['label'] ?? ''));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_orientations = ['horizontal', 'vertical'];

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'horizontal';
}

if ($id === '') {
    $id = 'hengegroup-theme-slider-' . wp_unique_id();
}

$base_classes =
    'accent-henge-green h-1.5 w-full cursor-pointer rounded-full outline-none ' .
    'focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 ' .
    'disabled:cursor-not-allowed disabled:opacity-50';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['type'] = 'range';
$element_attributes['data-slot'] = 'slider';
$element_attributes['data-orientation'] = $orientation;
$element_attributes['id'] = $id;

if ($name !== '') {
    $element_attributes['name'] = $name;
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

if ($value !== '') {
    $element_attributes['value'] = $value;
}

if ($disabled) {
    $element_attributes['disabled'] = true;
    $element_attributes['data-disabled'] = 'true';
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

$slider_markup = '<input' . hengegroup_theme_render_attributes($element_attributes) . '>';

if ($label_text === '') {
    echo $slider_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    return;
}

ob_start();
get_template_part('template-parts/base/label', null, [
    'config' => ['text' => $label_text, 'for' => $id],
]);
$label_markup = (string) ob_get_clean();

printf(
    '<div class="flex flex-col gap-2" data-slot="slider-field">%1$s%2$s</div>',
    $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $slider_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
