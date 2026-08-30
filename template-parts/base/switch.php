<?php

declare(strict_types=1);

// shadcn/ui's Switch wraps a headless UI primitive (historically Radix UI; shadcn now also ships
// Base UI/React Aria variants of many components): a <button role="switch" aria-checked> plus a
// hidden native input, driven by JS state (checked/onCheckedChange) -- the same architecture as
// Checkbox/Toggle. Unlike Toggle's checkbox-vs-button situation (see toggle.php's header comment),
// Switch has a genuinely trade-off-free native equivalent: `role="switch"` is an explicitly
// PERMITTED ARIA role transformation on `<input type="checkbox">` (unlike role="button", which
// isn't in checkbox's allowed role list -- that's exactly why toggle.php documents a semantic gap
// and this file doesn't need to). A plain `<input type="checkbox" role="switch">` is announced by
// screen readers as "switch, on/off", matching shadcn's own Switch 1:1 -- full native
// checked/disabled/required/keyboard/touch/form-submission behaviour, zero JS (see CLAUDE.md #1).
// This component is that input, data-attributed, optionally paired with
// template-parts/base/label.php (same convenience pattern as checkbox.php's
// `label` config).
//
// `data-state="checked"|"unchecked"` mirrors shadcn's own Switch vocabulary (distinct from
// toggle.php's "on"/"off") but, like data-state elsewhere in this theme (progress.php/avatar.php),
// only reflects the value at render time -- project CSS should style off of the native
// `:checked` pseudo-class for anything that needs to stay live after a user interaction, not this
// attribute; it's provided purely so `[data-slot="switch"][data-state="..."]` selectors are
// available for parity with shadcn's own styling convention.
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind. Unlike checkbox.php/radio.php (where
// `accent-color` recolors the browser's own native widget and no visual re-implementation is
// needed), a Switch's whole visual point IS the pill-track-plus-sliding-thumb shape -- there is no
// native `accent-color` (or any other CSS-only) way to make a checkbox LOOK like a pill toggle, so
// this is the one native-input form control where matching shadcn's actual look requires
// `appearance-none` plus a hand-drawn presentation, same trade-off toggle.php's own header comment
// already documents for a different reason. Since this file renders a SINGLE
// `<input type="checkbox" role="switch">` (no separate Root+Thumb elements like shadcn's real
// two-element Switch), the sliding thumb is drawn as a `content-['']` pseudo-element via Tailwind's
// `after:` variant instead -- one native input, one CSS-only pseudo-element, zero JS, same classes
// shadcn's own Switch uses for track/thumb colors mapped onto `after:`. `size`'s dimensions
// (default | sm) are the one thing genuinely new here (shadcn's own Switch size axis has no shipped
// pixel values in its docs at time of writing -- picked to roughly match shadcn's historical
// h-5/w-9 default scaled down one step for `sm`, consistent with this project's other sm/base/lg
// size steps elsewhere).
//
// Supported config:
//   checked        bool     default false. Native `checked` attribute.
//   disabled       bool     native `disabled` attribute, plus a mirrored `data-disabled="true"`
//                            CSS hook (matches checkbox.php/shadcn's own convention)
//   required       bool     native `required` attribute.
//   aria_invalid   bool     sets aria-invalid="true" plus a mirrored data-invalid="true" CSS hook,
//                            same error-state hooks as checkbox.php/input.php
//   size           string   default | sm (default: default) -- shadcn's Switch size axis (as of
//                            its Base UI multi-backend rewrite); sets data-size only, actual
//                            dimensions are project-CSS (see CLAUDE.md #1)
//   name / value   string   native form attributes, only rendered when given
//   id             string   native `id`; auto-generated via wp_unique_id() when omitted (needed to
//                            pair with `label` via its `for` attribute)
//   label          string   optional visible label text; when given, nests
//                            template-parts/base/label.php right after the input, both wrapped in a
//                            plain <span data-slot="switch-field">. Omit for full manual control,
//                            same escape hatch as checkbox.php
//   aria_label     string   accessible name when no visible `label` is given
//   class / attributes / data_attributes   passthrough onto the <input> itself (not onto the
//                            optional wrapper span, which stays a plain, unstyled hook)

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$checked = !empty($config['checked']);
$disabled = !empty($config['disabled']);
$required = !empty($config['required']);
$aria_invalid = !empty($config['aria_invalid']);
$size = trim((string) ($config['size'] ?? 'default'));
$name = trim((string) ($config['name'] ?? ''));
$value = trim((string) ($config['value'] ?? ''));
$id = trim((string) ($config['id'] ?? ''));
$label_text = trim((string) ($config['label'] ?? ''));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_sizes = ['default', 'sm'];

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'default';
}

if ($id === '') {
    $id = 'hengegroup-theme-switch-' . wp_unique_id();
}

$base_classes =
    'peer relative inline-flex shrink-0 cursor-pointer appearance-none items-center ' .
    'rounded-full border border-transparent bg-input shadow-xs transition-colors outline-none ' .
    'after:absolute after:top-1/2 after:left-0 after:-translate-y-1/2 after:rounded-full ' .
    "after:bg-background after:shadow-sm after:transition-transform after:content-[''] " .
    'checked:bg-henge-green checked:after:translate-x-3.5 focus-visible:border-ring ' .
    'focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive ' .
    'aria-invalid:ring-destructive/20 disabled:cursor-not-allowed disabled:opacity-50';

// The thumb sits flush against the track's inner (padding-box) edge at rest -- `left-0`, not an
// extra `left-0.5` on top of that -- so the track's own 1px `border` is what provides its visual
// inset from the track's true outer edge, the same reference frame shadcn's own (flex-based, not
// absolutely-positioned) Switch Thumb uses. `checked:after:translate-x-3.5` (14px) is a fixed
// pixel shift, not shadcn's own thumb-relative `calc(100% - 2px)` -- that formula only cancels out
// correctly for shadcn's own single w-8/size-4 pair (where track width happens to be exactly 2x
// the thumb width); it does NOT generalize to this project's smaller `sm` step (w-7/size-3, no
// longer a clean 2x ratio). 14px is the constant, track-width-independent shift that lands the
// thumb flush against the opposite inner edge for BOTH sizes here (their track/thumb width
// difference is the same 16px either way), fixing a real ~2px overshoot the old
// thumb-relative formula produced when combined with the (now removed) extra `left-0.5`.
$size_classes = [
    'default' => 'h-[1.15rem] w-8 after:size-4',
    'sm' => 'h-4 w-7 after:size-3',
];

$computed_class = "{$base_classes} {$size_classes[$size]}";

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['type'] = 'checkbox';
$element_attributes['role'] = 'switch';
$element_attributes['data-slot'] = 'switch';
$element_attributes['data-size'] = $size;
$element_attributes['data-state'] = $checked ? 'checked' : 'unchecked';
$element_attributes['id'] = $id;

if ($name !== '') {
    $element_attributes['name'] = $name;
}

if ($value !== '') {
    $element_attributes['value'] = $value;
}

if ($checked) {
    $element_attributes['checked'] = true;
}

if ($disabled) {
    $element_attributes['disabled'] = true;
    $element_attributes['data-disabled'] = 'true';
}

if ($required) {
    $element_attributes['required'] = true;
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
    '<span class="inline-flex items-center gap-2" data-slot="switch-field">%1$s%2$s</span>',
    $input_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
