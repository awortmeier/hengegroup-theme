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
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind on the strength of the Claude-Design reference
// "Hengegroup" (https://claude.ai/code/artifact/527a7d35-e7c6-43b4-ab6f-9f85baf2b43c, same
// `.dc.html` reference workflow as tooltip.php's/toast.php's own entries, see
// docs/entscheidungen.md for this component's entry). Unlike dropdown-menu.php's own still-Phase-1
// header comment ("actual floating placement is project-CSS"), THIS file now IS that project CSS --
// but via the shared hengegroup_theme_floating_position_classes() helper
// (inc/template-parts/helpers.php), NOT `data-[side=...]` attribute-selector Tailwind classes: this
// component was never wired to floating-position.js (no flip, unlike tooltip.php/hover-card.php,
// and the reference never shows one either, just four fixed `side`s), so `$side`/`$align` are fixed
// for the element's whole lifetime once PHP renders it -- exactly one position combination to emit,
// not the full side/align matrix. That helper was extracted FROM this exact lookup once
// tooltip.php's own Phase 2 pass needed the identical logic for its resting/pre-JS position -- see
// the helper's own doc comment for the full reasoning, including why tooltip.js's runtime side flip
// doesn't change that conclusion. `data-side`/`data-align` are still set on `popover-content`
// (unchanged from Phase 1), but purely as the stable hooks CLAUDE.md Regel 1 describes, not as
// something this file's own CSS reads. The arrow (`arrow_primary_axis_classes` further down) keeps
// its own, separate per-side lookup -- an arrow's classes differ in kind (border sides, overlap
// margins), not just value, from the content box's plain edge-offset positioning this helper covers.
//
// What carried over from the reference, and what didn't:
//   - the card (rounded-2xl/border-border/bg-popover/p-4/shadow, 10px gap to the trigger, a
//     diamond arrow) reuses this project's own `--color-popover`/`--color-popover-foreground`/
//     `--color-border` tokens (tokens.css already names that exact semantic role "Phase-2-
//     Vorbereitung fuer... Base-Komponenten") rather than the reference's literal
//     `rgb(250,249,245)`/`rgba(0,0,0,0.08)` -- unlike tooltip.php's card, which had no matching
//     project token and fell back to Tailwind's own neutral-900/50 scale, popover already has a
//     purpose-built token pair to reuse directly, same "prefer an existing semantic token over a
//     literal color" precedent as calendar.php's own `bg-card`/`border-border` card.
//   - radius: the reference itself is inconsistent (14px on most cards, 12px on the "Positionen"
//     grid's own smaller demo card) -- standardized on `rounded-2xl` (16px), matching this
//     project's other Phase-2 floating/card surfaces (toast.php's/calendar.php's own `rounded-2xl`)
//     rather than literally copying either reference number.
//   - shadow (`shadow-[0_12px_32px_rgba(0,0,0,0.14)]`) is the reference's own literal value, kept
//     as an arbitrary value like tooltip.php's own `shadow-[0_6px_18px_rgba(0,0,0,0.18)]` -- no
//     stock Tailwind shadow step reaches this size/softness.
//   - width/padding: `w-72`/`p-4` are shadcn's own real stock `PopoverContent` defaults (not
//     invented), close enough to the reference's own literal per-example values (200-300px width,
//     16-18px padding -- each example hand-set for its own demo content) that reproducing shadcn's
//     actual vocabulary was preferred over picking one demo's specific pixel value, same
//     "generalize instead of over-fitting to one demo string" reasoning as tooltip.php's own
//     `max-w-xs` decision. `outline-hidden` is also stock shadcn -- suppresses the default focus
//     ring when popover.js's documented tabindex="-1" fallback focus actually lands on the panel
//     itself (no focusable descendant in `content`).
//   - the reference's "Sortierung"/"Standort waehlen" examples show a second, zero-padding
//     "menu list" look (8px card padding, full-bleed hover rows) instead of this card's own
//     18px-ish free-form padding. NOT reproduced as a second look: v1's only content-side styling
//     hook is this file's fixed `p-4`, and shadcn's own real Popover has the identical limitation --
//     a menu-flavoured popover is just dropdown-menu.php's own item styling nested inside a
//     `content` string here, not a distinct popover variant. Deliberately out of scope, not a
//     silently dropped requirement.
//   - arrow (`[data-slot="popover-arrow"]`) new, an 8px diamond (`size-2 rotate-45`, same size as
//     tooltip.php's/hover-card.php's own arrow for cross-component consistency -- the reference's
//     own 10px was not copied literally). `bg-inherit` takes the card's own `bg-popover` instead of
//     repeating it (tooltip.php's `bg-inherit` reuse). Unlike tooltip.php's borderless card, this
//     card has a real `border-border` hairline, so the arrow gets one too, on whichever two of its
//     four (pre-rotation) edges end up forming its visible tip after the 45deg turn -- e.g.
//     `side="bottom"` (card below the trigger, arrow at the card's top edge pointing up) shows its
//     originally-top and originally-left edges, i.e. `border-t border-l`, exactly the reference's
//     own literal `border-left`+`border-top` on its one side="bottom" example; the other three
//     `side`s were derived from that same rotation geometry (not shown varying in the reference,
//     which reuses one hardcoded border pair on every side of its "Positionen" grid demo -- a
//     mockup simplification this file intentionally does NOT copy, since getting the border pair
//     right per side costs nothing extra and looks correct on every side instead of only one).
//   - `align`'s arrow inset reuses tooltip.php's own fixed 16px (`left-4`/`right-4`) value and
//     technique verbatim (same reasoning: the reference's own per-example insets -- 16/18/20/24px --
//     are one-off eyeballed demo numbers, not a system; the card itself has no static `align` CSS
//     for the same reason tooltip.php's doesn't -- see that file's own header comment).
//   - entrance animation: `hg-popover-in` (opacity + translateY(-4px)+scale(.98) -> resting,
//     reference's own literal keyframe values), a new named `@keyframes` in assets/css/app.css
//     (documented Regel-1 raw-CSS exception, same reasoning as that file's `hg-toast-in`/
//     `hg-progress-stripes` entries -- no stock Tailwind utility composes a named keyframe set).
//     Runs unconditionally on `popover-content` rather than being gated by a JS-toggled state
//     class: native `<details>` already removes/(re)inserts this element from/into the render tree
//     on toggle (no `hidden`-class dance needed, unlike tooltip.php's always-in-DOM, opacity-toggled
//     card), so every open re-triggers the animation for free.
//   - the wrapper (`<details data-slot="popover">`) gets `relative inline-flex`, and the trigger
//     `<summary>` gets `list-none [&::-webkit-details-marker]:hidden cursor-pointer` --
//     `relative`/`absolute` is this file's own project-CSS half of dropdown-menu.php's still-open
//     positioning gap (see that file's header comment); `list-none`/the webkit pseudo-element rule
//     suppress the native disclosure triangle (this component's trigger is meant to look like a
//     plain button, not an accordion row -- same marker-hiding pair date-picker.php's own trigger
//     already uses, unlike accordion.php, which deliberately keeps/styles the native marker as its
//     chevron).
//   - the reference's "Auf dunklem Grund" section was NOT ported, same reason as every other
//     component's Phase-2 entry so far (separator.php/kbd.php/pagination.php/table/*.php/
//     toast.php/tooltip.php) -- this theme has no dark-mode/dark-surface strategy yet, see
//     docs/entscheidungen.md.
//   - No file-per-variant split, no `popover/` folder move (the task explicitly asked to check).
//     Every "variant" the reference shows (a form popup, an info popup, four `side`s, `align="end"`
//     via a right-aligned trigger, the menu-look popups discussed above) is markup/config the
//     existing single-file component already models via `content`/`side`/`align` -- not a
//     structurally different composition the way e.g. separator.php + separator-label.php are, same
//     conclusion tooltip.php's own Phase-2 entry already reached for an almost identical shape.
//   - `page-component-showcase-popover.php` new, analog to the other showcase pages.
//
// Supported config:
//   trigger       string   required. Pre-rendered HTML for the <summary> trigger's inner content
//                          (see the composition note above)
//   content       string   required. Pre-rendered HTML for the popover panel
//   side          string   top | right | bottom (default) | left -- sets data-side, which now also
//                          drives the actual floating placement (project-CSS, see the Phase 2 note
//                          above; was data-side-only in Phase 1)
//   align         string   start | center (default) | end -- sets data-align, same as `side`; default
//                          is `center` (Base UI's own Popover.Positioner default), not
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

$wrapper_base_classes = 'relative inline-flex';

$wrapper_attributes = $attributes;

$wrapper_attributes['class'] = trim(
    $wrapper_base_classes . ($class_name !== '' ? ' ' . $class_name : ''),
);

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
    '<summary class="list-none cursor-pointer [&::-webkit-details-marker]:hidden" ' .
        'data-slot="popover-trigger" aria-haspopup="dialog">%s</summary>',
    $trigger, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

// Card look (bg-popover/border-border/rounded-2xl/shadow, 10px gap to the trigger per side, the
// entrance animation) plus `side`/`align` floating placement -- see the Phase 2 file header for
// where each value comes from. Position comes from the shared
// hengegroup_theme_floating_position_classes() helper (inc/template-parts/helpers.php) -- extracted
// from this exact lookup once tooltip.php's own Phase 2 pass turned out to need the identical
// side/align -> class logic for its resting/pre-JS state (see that helper's own doc comment for why
// a plain PHP lookup, not `data-[side=...]` attribute selectors, is correct here: `$side`/`$align`
// are fixed for this element's whole lifetime once PHP renders it -- popover.php has no JS flip at
// all -- so there is exactly one combination to emit, not the full matrix tooltip.js's runtime side
// flip forces onto ITS OWN arrow). `data-side`/`data-align` are still set on `popover-content`
// below, but purely as the stable hooks CLAUDE.md Regel 1 describes (JS/tests/future project-CSS),
// not as a styling mechanism this file itself reads.
$content_classes =
    'absolute z-50 w-72 rounded-2xl border border-border bg-popover p-4 text-popover-foreground ' .
    'shadow-[0_12px_32px_rgba(0,0,0,0.14)] outline-hidden ' .
    'animate-[hg-popover-in_140ms_ease-out] ' .
    hengegroup_theme_floating_position_classes($side, $align);

// The 8px diamond arrow -- `bg-inherit` takes the card's own `bg-popover` instead of repeating it
// (see the Phase 2 file header). Border pair per side derived from which two of the unrotated
// square's edges form the visible tip after `rotate-45` (see header comment for the full
// derivation); `align` start/end shift it to a fixed 16px inset from the content's edge, same
// technique/value as tooltip.php's own arrow (the card itself has no static `align` CSS, same
// reasoning as tooltip.php's). Same PHP-lookup reasoning as `$content_position_classes` above --
// one combination to emit, not the full combinatorial `data-[align=...]:data-[side=...]:` matrix.
$arrow_primary_axis_classes = [
    'top' => 'top-full -mt-1 border-b border-r',
    'bottom' => 'bottom-full -mb-1 border-t border-l',
    'left' => 'left-full -ml-1 border-t border-r',
    'right' => 'right-full -mr-1 border-l border-b',
][$side];

$arrow_cross_axis_classes = in_array($side, ['top', 'bottom'], true)
    ? ['start' => 'left-4', 'center' => 'left-1/2 -translate-x-1/2', 'end' => 'right-4'][$align]
    : ['start' => 'top-4', 'center' => 'top-1/2 -translate-y-1/2', 'end' => 'bottom-4'][$align];

$arrow_classes =
    'absolute size-2 rotate-45 border-border bg-inherit ' .
    $arrow_primary_axis_classes .
    ' ' .
    $arrow_cross_axis_classes;

$content_attributes = [
    'class' => $content_classes,
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

$arrow_attributes = [
    'class' => $arrow_classes,
    'data-slot' => 'popover-arrow',
    'data-side' => $side,
    'data-align' => $align,
    'aria-hidden' => 'true',
];

$content_markup = sprintf(
    '<div%1$s>%2$s<span%3$s></span></div>',
    hengegroup_theme_render_attributes($content_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    hengegroup_theme_render_attributes($arrow_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

printf(
    '<details%1$s>%2$s%3$s</details>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
