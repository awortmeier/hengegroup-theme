<?php

declare(strict_types=1);

// shadcn/ui's DropdownMenu wraps a headless UI primitive (historically Radix UI; shadcn now also
// ships Base UI/React Aria variants of many components): a JS-driven floating panel of menu items,
// portal-rendered, with roving-tabindex arrow-key navigation, type-ahead and outside-click/Escape
// dismissal (role="menu"/"menuitem" WAI-ARIA Menu pattern).
//
// Zero-JS baseline: native <details>/<summary> (the same technique as accordion.php) gives a real,
// working disclosure -- click (or Enter/Space on) the trigger to show/hide the panel, every item
// inside stays normally Tab-reachable, zero JS required (see CLAUDE.md #1). Two things a plain
// <details> does NOT give for free, both real, honestly-documented gaps in the zero-JS path (not
// silently pretended away): it doesn't auto-close on an outside click or Escape, and its content
// sits in normal document flow, not floating -- the latter is still solvable with pure CSS, not
// JS (`[data-slot="dropdown-menu"] { position: relative; }` +
// `[data-slot="dropdown-menu-content"] { position: absolute; }`, project-CSS concern, see
// CLAUDE.md #1). assets/js/template-parts/base/dropdown-menu.js enhances this into the full
// WAI-ARIA Menu pattern (roving tabindex, arrow-key/Home/End/type-ahead navigation,
// outside-click/Escape close) on top, same `data-js` progressive-enhancement gate as
// select.php/tooltip.php/combobox.php.
//
// Unlike accordion.php (where no extra role/aria-expanded is added on top of <details>/<summary>,
// since that content is just a generic panel, not a distinct ARIA widget), the content here
// legitimately gets `role="menu"` -- <details> only supplies disclosure semantics, nothing about
// "menu", so adding it isn't redundant/conflicting the way it would be on accordion.php's plain
// content panel. `aria-haspopup="menu"` on the trigger is likewise added as a static hint (unlike
// `aria-expanded`, which browsers already compute correctly from <summary>'s parent <details>
// `[open]` state, so it's deliberately NOT duplicated here).
//
// Composition: `trigger`/`content` are caller-provided, pre-rendered HTML (same
// convention as tooltip.php's `trigger`/aspect-ratio.php's `content`), built from
// dropdown-menu-item.php/-checkbox-item.php/-radio-group.php/-label.php/-group.php calls plus
// template-parts/base/separator/separator.php reused UNCHANGED for dividers (exactly like
// button-group.php reuses separator.php) and template-parts/base/kbd/kbd.php/kbd-group.php for
// shortcut hints (shadcn's own DropdownMenuShortcut is just styled text -- kbd.php already covers
// that, no new atom needed). IMPORTANT: `trigger` must NOT itself be (or contain) a focusable
// element such as a full button.php call -- <summary> is already the one interactive control here;
// nest only its inner content (e.g. escaped text + a buffered icon.php call), the same way
// button.php composes icon + text without an extra nested interactive wrapper.
//
// Deliberately out of scope for v1, a genuinely different/more complex component, not a variant of
// this one (same reasoning as native-select.php's `multiple`, combobox.php's chips mode):
// DropdownMenuSub (nested submenus) -- deferred, not silently dropped. The Phase 2 reference below
// has its own "Untermenü" section demonstrating exactly this -- still not built here, same
// deferral, now just visually confirmed as a real gap rather than a hypothetical one.
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind on the strength of the Claude-Design reference
// "Hengegroup" (https://claude.ai/code/design/p/37768540-95a8-46e1-a647-33070ca71612?file=Dropdown+Menu.dc.html,
// same `.dc.html` reference workflow as popover.php's/hover-card.php's own entries, see
// docs/entscheidungen.md for this component's entry). Unlike every prior Phase 2 entry, the
// reference's OPEN panel states (item hover/focus, the destructive/disabled/checkbox/radio looks,
// the submenu) could not actually be read off the canvas -- the design tool's pan/zoom/click-to-open
// interactions did not respond to browser automation in this session (documented in
// docs/entscheidungen.md, not silently glossed over). Only the CLOSED triggers ("Aktionen"/
// "Spalten"/"Teilen" buttons) were visible. The open-panel classes below are therefore NOT traced to
// this reference like every other Phase 2 file's are -- they're shadcn's own real stock
// DropdownMenuContent/-Item/-CheckboxItem/-RadioItem/-Label class recipe (live-checked against
// current docs), adapted onto this project's own already-established floating-card tokens (see
// below) instead of shadcn's stock ones, the same adaptation popover.php/hover-card.php already did
// for their own cards.
//
// What carried over from popover.php's/hover-card.php's own precedent, and what didn't:
//   - the card (`border-border`/`bg-popover`/`rounded-2xl`/the same literal shadow) reuses their
//     token choices verbatim, for the same cross-component-consistency reasoning both files already
//     state -- but `p-1` instead of their own `p-4`, because this content is a tight menu list of
//     full-bleed hover rows (shadcn's own real `DropdownMenuContent` is `p-1`, not a spacious
//     content card), a distinction popover.php's own Phase 2 entry already anticipated ("a
//     menu-flavoured popover is just dropdown-menu.php's own item styling nested inside a `content`
//     string ... not a distinct popover variant").
//   - `min-w-32` (shadcn's own real `min-w-[8rem]`, expressed as a Tailwind scale step since it
//     matches exactly -- same "hits the scale, no arbitrary value needed" preference as
//     card.php's/dialog.php's own entries).
//   - entrance animation reuses `hg-popover-in` unconditionally, same reasoning as popover.php's own
//     entry: native `<details>` already removes/reinserts this element from the render tree on
//     toggle, so every open re-triggers the animation for free, no JS-toggled state class needed.
//   - positioning reuses `hengegroup_theme_floating_position_classes()` (inc/template-parts/
//     helpers.php) exactly like popover.php -- this component has no JS side-flip either
//     (dropdown-menu.js only adds keyboard/type-ahead/outside-click behaviour on top, see above), so
//     there is exactly one side/align combination to emit per render, not a reactive matrix.
//   - No arrow, unlike popover.php/hover-card.php/tooltip.php -- shadcn's own real
//     `DropdownMenuContent` has no arrow slot at all (unlike Popover/HoverCard/Tooltip), and nothing
//     in the visible part of the reference contradicts that.
//   - No file-per-variant split, no further folder move (the task explicitly asked to check both) --
//     this component already lives in its own `dropdown-menu/` folder and is already split into one
//     file per sub-part (item/checkbox-item/radio-item/radio-group/group/label) since Phase 1, for
//     the same "genuinely more than one file" reason toggle/radio/button-group live in folders (see
//     CLAUDE.md Regel 4) -- not a NEW split invented for Phase 2, and every item-level "variant"
//     shadcn itself models (`default`/`destructive`) is already a `variant` config value on the one
//     existing dropdown-menu-item.php, same conclusion popover.php's/hover-card.php's/card.php's own
//     entries reached for their own variant-shaped config keys.
//   - `page-component-showcase-dropdown-menu.php` new, analog to the other showcase pages.
//
// Supported config:
//   trigger       string   required. Pre-rendered HTML for the <summary> trigger's inner content
//                          (see the composition note above)
//   content       string   required. Pre-rendered HTML for the menu panel (item components, see
//                          above)
//   side          string   top | right | bottom | left (default: bottom) -- sets data-side only,
//                          actual floating placement is project-CSS (see CLAUDE.md #1)
//   align         string   start (default) | center | end -- sets data-align only, same as `side`
//   id            string   native `id` on the outer <details>; auto-generated via wp_unique_id()
//                          when omitted
//   class / attributes / data_attributes   passthrough onto the outer
//                          <details data-slot="dropdown-menu"> wrapper

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$trigger = (string) ($config['trigger'] ?? '');
$content = (string) ($config['content'] ?? '');
$side = trim((string) ($config['side'] ?? 'bottom'));
$align = trim((string) ($config['align'] ?? 'start'));
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
    $align = 'start';
}

if ($id === '') {
    $id = 'hengegroup-theme-dropdown-menu-' . wp_unique_id();
}

// `relative`/`inline-flex` is this file's own project-CSS half of the positioning (see the Phase 2
// file header) -- same wrapper treatment as popover.php's own `<details data-slot="popover">`.
$wrapper_base_classes = 'relative inline-flex';

$wrapper_attributes = $attributes;

$wrapper_attributes['class'] = trim(
    $wrapper_base_classes . ($class_name !== '' ? ' ' . $class_name : ''),
);

$wrapper_attributes['data-slot'] = 'dropdown-menu';
$wrapper_attributes['id'] = $id;

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $wrapper_attributes['data-' . $data_name] = $attribute_value;
}

// `list-none`/the webkit pseudo-element rule suppress the native disclosure triangle (this
// component's trigger is meant to look like whatever `trigger` itself renders, e.g. a plain
// button.php call, not an accordion row) -- same marker-hiding pair popover.php's own `<summary>`
// already uses.
$trigger_markup = sprintf(
    '<summary class="list-none cursor-pointer [&::-webkit-details-marker]:hidden" ' .
        'data-slot="dropdown-menu-trigger" aria-haspopup="menu">%s</summary>',
    $trigger, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

// Card look (`border-border`/`bg-popover`/`rounded-2xl`/the shared literal shadow) plus `side`/
// `align` floating placement -- see the Phase 2 file header for where each value comes from and how
// it differs from popover.php's own spacious `p-4` card. Position comes from the shared
// hengegroup_theme_floating_position_classes() helper (inc/template-parts/helpers.php), same
// reasoning as popover.php's own call: `$side`/`$align` are fixed for this element's whole lifetime
// once PHP renders it, so there is exactly one combination to emit, not a reactive
// `data-[side=...]` matrix. `data-side`/`data-align` stay as stable hooks (CLAUDE.md Regel 1),
// not something this file's own CSS reads.
$content_classes =
    'absolute z-50 min-w-32 rounded-2xl border border-border bg-popover p-1 ' .
    'text-popover-foreground shadow-[0_12px_32px_rgba(0,0,0,0.14)] ' .
    'animate-[hg-popover-in_140ms_ease-out] ' .
    hengegroup_theme_floating_position_classes($side, $align);

$content_markup = sprintf(
    '<div class="%1$s" data-slot="dropdown-menu-content" role="menu" data-side="%2$s" data-align="%3$s">%4$s</div>',
    esc_attr($content_classes),
    esc_attr($side),
    esc_attr($align),
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

printf(
    '<details%1$s>%2$s%3$s</details>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
