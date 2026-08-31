<?php

declare(strict_types=1);

// shadcn/ui's Field family is framework-agnostic form-layout markup (works with react-hook-form,
// TanStack Form, Formisch, or nothing at all) -- no headless UI primitive, no JS state of its own,
// just the Label + Control + Description/Error composition every form field needs, generalized
// beyond the ad-hoc `label` config each individual form component here already offers
// (input.php/textarea.php/checkbox.php/native-select.php/select.php/radio.php/switch.php/
// slider.php/combobox.php). Field is the richer, opt-in layer on top for fields that
// also need a description and/or error message -- the existing per-component `label` config isn't
// replaced by it, both remain valid depending on how much a given field needs (CLAUDE.md #1: no JS
// needed, this is presentational markup only).
//
// Content-agnostic wrapper, same nesting pattern as button-group.php/dropdown-menu.php's `content`:
// buffer template-parts/base/field/field-label.php + a BARE control (the target
// form component called WITHOUT its own `label` config -- Field owns the label/description/error
// layout here instead) + optionally field-description.php/field-error.php, then pass the combined
// HTML as `content`.
//
//   $control_id = 'email';
//
//   ob_start();
//   get_template_part('template-parts/base/field/field-label', null, ['config' => ['text' => 'Email', 'for' => $control_id]]);
//   get_template_part('template-parts/base/input', null, [
//       'config' => [
//           'id' => $control_id,
//           'type' => 'email',
//           'attributes' => ['aria-describedby' => hengegroup_theme_field_describedby($control_id)],
//       ],
//   ]);
//   get_template_part('template-parts/base/field/field-description', null, ['config' => ['for' => $control_id, 'text' => 'We will never share your email.']]);
//   $content = (string) ob_get_clean();
//
//   get_template_part('template-parts/base/field/field', null, ['config' => ['content' => $content]]);
//
// Accessibility wiring is the CALLER's responsibility, same as pairing `for`/`id` already is
// elsewhere: field-description.php/field-error.php each accept a `for` (the control's own id --
// derives their rendered `id` from it via hengegroup_theme_field_description_id()/
// hengegroup_theme_field_error_id(), see those files' header comments) or an explicit `id` (auto-generated
// via wp_unique_id() when neither is given, like every other component here). Either way, pass the
// matching id(s) into the control's own `attributes: ['aria-describedby' => ...]` --
// hengegroup_theme_field_describedby($control_id) builds that value from the same $control_id instead of
// the caller inventing/copying an id string by hand in two places (pass `has_description`/
// `has_error` as false to its 2nd/3rd args when only one of the two is actually rendered). Field
// itself still cannot wire this automatically, since it never sees or generates the control -- it
// only wraps caller-supplied `content`; the helper above is what makes the remaining manual step a
// single shared id instead of two independently-typed ones.
//
// `invalid` sets `data-invalid="true"` on the wrapper as a pure styling hook (shadcn's own Field
// prop is literally named this) -- it does NOT set `aria-invalid` anywhere; that belongs on the
// actual control and is already each form component's own `aria_invalid` config responsibility,
// not duplicated here.
//
// No `role` is set on the wrapper (re-checked live: Base UI's own Field.Root, which shadcn's
// Field is built on, renders no ARIA role either -- confirmed against its docs). An earlier
// version of this file set `role="group"` unconditionally, which is redundant/incorrect ARIA for
// the common single-control case (a screen reader would announce every plain text field as its
// own "group") -- `field-set.php`'s real `<fieldset>` already exists for actual multi-control
// grouping, so this file doesn't need to approximate that role itself.
//
// Phase 2 (CLAUDE.md Regel 1): base class taken 1:1 from shadcn's own Field (registry/
// new-york-v4/ui/field.tsx, live-checked 2026-08-30), with its per-orientation branches
// (`vertical`/`horizontal`/`responsive`) expressed as `data-[orientation=...]`-prefixed selectors
// against the `data-orientation` this file already sets below instead of a PHP if/else -- same
// data-attribute-driven styling idiom button.php/badge.php already use for `aria-invalid`.
// `responsive`'s own shadcn classes use `@md/field-group:`-prefixed container-query variants (no
// media query -- reacts to the nearest ancestor's actual width, not the viewport's) that only mean
// something inside the named container field-group.php now provides
// (`@container/field-group`, see that file's own Phase-2 header) -- a bare field.php outside a
// field-group.php simply never matches the `@md/field-group:` variant and stays at its narrow
// (flex-col) layout, same graceful fallback shadcn's own version has.
//
// Supported config:
//   content       string   required. Pre-rendered HTML to wrap (see composition example above)
//   orientation   string   vertical (default) | horizontal | responsive -- shadcn's own Field
//                          orientation axis, sets data-orientation, which then drives the actual
//                          layout classes above; `responsive` only takes effect while nested inside
//                          a field-group.php (its `@container/field-group`, see that file's header)
//   invalid       bool     default false. Sets data-invalid="true" (styling hook only, see above)
//   class / attributes / data_attributes   passthrough onto the outer
//                          <div data-slot="field"> wrapper (no role, see above)

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
    // vertical (default): every child stretches full-width, except a visually-hidden .sr-only one
    'data-[orientation=vertical]:flex-col data-[orientation=vertical]:[&>*]:w-full ' .
    'data-[orientation=vertical]:[&>.sr-only]:w-auto ' .
    // horizontal: label sits beside the control; a nested field-content.php's checkbox/radio stays
    // top-aligned with the first line of its label instead of vertically centering
    'data-[orientation=horizontal]:flex-row data-[orientation=horizontal]:items-center ' .
    'data-[orientation=horizontal]:[&>[data-slot=field-label]]:flex-auto ' .
    'data-[orientation=horizontal]:has-[>[data-slot=field-content]]:items-start ' .
    'data-[orientation=horizontal]:has-[>[data-slot=field-content]]:[&>[role=checkbox],[role=radio]]:mt-px ' .
    // responsive: same as vertical below the field-group.php container's breakpoint, same as
    // horizontal at/above it -- see file header for the @container wiring
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

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
