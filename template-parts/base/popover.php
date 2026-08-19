<?php

declare(strict_types=1);

// shadcn/ui's Popover now wraps Base UI's Popover primitive (historically Radix UI) -- checked
// live against shadcn's/Base UI's current docs before writing this file:
// Popover.Root/Trigger/Positioner/Popup, non-modal by default (`modal: false`), Positioner
// defaults `side: 'bottom'`/`align: 'center'`, the popup gets `role="dialog"`, focus moves to the
// first tabbable descendant on open and returns to the trigger on close, Escape and an outside
// click both dismiss it.
//
// date-picker.php's own header comment already flagged this exact gap: "this project has no
// standalone popover.php ... rather than inventing one that nothing else needs yet, this file
// mirrors dropdown-menu.php's own recipe instead". This file is that popover.php, finally built --
// same native <details>/<summary> zero-JS shell as dropdown-menu.php/date-picker.php (CLAUDE.md
// #1), generalized: `content` is arbitrary caller-supplied HTML (content-agnostic wrapper, same
// convention as aspect-ratio.php/scroll-area.php), not menu items, so the panel gets
// `role="dialog"` instead of dropdown-menu.php's `role="menu"` -- no roving tabindex, no
// type-ahead, just a generic non-modal panel. Unlike date-picker.php's trigger (which deliberately
// declined `aria-haspopup="dialog"` because ITS panel has no real dialog semantics -- "no focus
// trap/aria-modal, a plain floating <div>"), this file's trigger DOES use
// `aria-haspopup="dialog"` -- earned here because popover.js actually builds the matching focus
// behaviour (see below), the same "don't announce a role you haven't earned yet" principle
// date-picker.php stated, just resolved the other way now that the behaviour exists.
//
// Zero-JS baseline: native <details>/<summary> gives click-to-toggle and keeps any focusable
// content in normal tab order, same as dropdown-menu.php. Two honest, documented gaps, both closed
// by assets/js/template-parts/base/popover.js: focus does not move into the panel on open (the
// WAI-ARIA dialog pattern expects initial focus inside), and there's no outside-click/Escape
// auto-close. popover.js closes both -- on open it focuses the first tabbable descendant of
// `content` (falling back to `content` itself via its own `tabindex="-1"`, the same
// fallback-focus-target technique native <dialog> provides automatically, see dialog.php), Escape
// and outside-click close the panel, and Escape additionally returns focus to the trigger. Base
// UI's own nuance of focusing the popup itself rather than a descendant when opened via touch (to
// avoid triggering a virtual keyboard) is not replicated here -- a documented simplification, not
// a silently dropped requirement.
//
// Composition: `trigger`/`content` are both caller-provided, pre-rendered HTML,
// same convention as dropdown-menu.php's own `trigger`/`content`. Same constraint as
// dropdown-menu.php's trigger note: `trigger` must not itself be/contain a focusable element --
// `<summary>` is already the one interactive control.
//
// Deliberately out of scope for v1, bigger/different components rather than a variant of this one
// (same reasoning as dropdown-menu.php's deferred DropdownMenuSub): `modal: true` (needs a focus
// trap + overlay + top-layer rendering -- shadcn's own Dialog already covers that ground, see
// dialog.php) and PopoverAnchor (decoupling the visual anchor point from the trigger element).
//
// Supported config:
//   trigger       string   required. Pre-rendered HTML for the <summary> trigger's inner content
//                          (see the composition note above)
//   content       string   required. Pre-rendered HTML for the popover panel
//   side          string   top | right | bottom (default) | left -- sets data-side only, actual
//                          floating placement is project-CSS (see CLAUDE.md #1, same as
//                          dropdown-menu.php's own `side`)
//   align         string   start | center (default) | end -- sets data-align only, same as `side`;
//                          default is `center` (Base UI's own Popover.Positioner default), not
//                          dropdown-menu.php's `start` default -- checked live against current
//                          docs, see above
//   aria_label    string   optional accessible name for the popover panel (role="dialog"); set it
//                          when `content` doesn't already contain its own heading/label element
//   id            string   native `id` on the outer <details>; auto-generated via wp_unique_id()
//                          when omitted
//   class / attributes / data_attributes   passthrough onto the outer
//                          <details data-slot="popover"> wrapper

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$trigger = (string) ($config['trigger'] ?? '');
$content = (string) ($config['content'] ?? '');
$side = trim((string) ($config['side'] ?? 'bottom'));
$align = trim((string) ($config['align'] ?? 'center'));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$id = trim((string) ($config['id'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($trigger) === '' || trim($content) === '') {
    return;
}

$allowed_sides = ['top', 'right', 'bottom', 'left'];
$allowed_aligns = ['start', 'center', 'end'];

if (!in_array($side, $allowed_sides, true)) {
    $side = 'bottom';
}

if (!in_array($align, $allowed_aligns, true)) {
    $align = 'center';
}

if ($id === '') {
    $id = 'hengegroup-theme-popover-' . wp_unique_id();
}

$wrapper_attributes = $attributes;

if ($class_name !== '') {
    $wrapper_attributes['class'] = $class_name;
}

$wrapper_attributes['data-slot'] = 'popover';
$wrapper_attributes['id'] = $id;

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $wrapper_attributes['data-' . $data_name] = $attribute_value;
}

$trigger_markup = sprintf(
    '<summary data-slot="popover-trigger" aria-haspopup="dialog">%s</summary>',
    $trigger, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

$content_attributes = [
    'data-slot' => 'popover-content',
    'role' => 'dialog',
    // Fallback focus target for popover.js when `content` holds no focusable descendant at all --
    // not part of normal tab order (only reachable programmatically), same idiom native <dialog>
    // uses internally.
    'tabindex' => '-1',
    'data-side' => $side,
    'data-align' => $align,
];

if ($aria_label !== '') {
    $content_attributes['aria-label'] = $aria_label;
}

$content_markup = sprintf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($content_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

printf(
    '<details%1$s>%2$s%3$s</details>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
