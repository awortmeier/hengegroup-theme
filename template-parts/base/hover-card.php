<?php

declare(strict_types=1);

// shadcn/ui's HoverCard wraps a headless UI primitive (historically Radix UI; shadcn now also
// ships Base UI/React Aria variants), the same "JS-driven floating panel shown on hover/focus,
// positioned relative to its trigger" shape as tooltip.php -- checked live against shadcn's
// current HoverCard docs, closest sibling in this theme. Like tooltip.php, no
// native element replaces this functionally, so real JS is warranted. The
// difference from tooltip.php is what the panel is FOR, and that difference drives several real
// API deltas, not just a rename:
//
//   - HoverCard's content is rich, often-interactive preview content (an avatar, bio, a "Follow"
//     button -- shadcn's own docs example previews a linked user profile), not a short plain-text
//     hint. So there is no `text` shorthand like tooltip.php's -- `content` (pre-rendered HTML,
//     caller-built, same content-agnostic convention as aspect-ratio.php's `content`) is the only
//     way to supply it. A `trigger_title` config still exists for the same native `title`
//     zero-JS-fallback purpose as tooltip.php's own `trigger_title`, since a short plain-text
//     summary is still worth having as a degraded baseline even when the rich version needs JS.
//   - The content gets no `role="tooltip"` and no `aria-describedby` wiring -- unlike tooltip.php's
//     content (a description OF the trigger), this content is supplementary, optional, often
//     independently interactive material; Radix's own HoverCard doesn't add either, and forcing
//     aria-describedby onto rich/interactive content is misleading to assistive tech (same
//     "don't invent ARIA semantics that aren't earned" principle as date-picker.php declining
//     aria-haspopup="dialog" for a panel with no real dialog semantics).
//   - Two separate delays instead of tooltip.php's one shared `delay`: `open_delay` (Radix's own
//     default 700ms, same number tooltip.php also uses) and `close_delay` (Radix's own default
//     300ms) -- a HoverCard is meant to tolerate the user moving the pointer from the trigger onto
//     the content itself (e.g. to click that "Follow" button), so closing needs its own, shorter
//     grace period independent of how long it took to open.
//   - `align` (start | center | end, default center) in addition to `side` -- shadcn/Radix's own
//     HoverCardContent vocabulary; tooltip.php's content is always centered on its trigger and
//     has no such config. `side` itself defaults to `bottom` here (Radix's own Popper-based
//     default for HoverCard/Popover/DropdownMenu -- see dropdown-menu.php's own `side` default),
//     not `top` like tooltip.php's own, deliberately different default.
//
// Zero-JS baseline, same shape as tooltip.php: `trigger_title` (or the wrapper is simply inert if
// omitted) gives a real, if unstyled and plain-text-only, native `title` tooltip. On top of that,
// assets/js/template-parts/base/hover-card.js finds these on page load and enhances them into a
// styled, positioned floating panel -- shown on hover/focus after `open_delay`, hidden on
// mouseleave/blur/Escape after `close_delay` (moving the pointer from the trigger onto the panel
// itself keeps it open, not just hovering the trigger, since the panel may hold interactive
// content) -- and removes the native `title` to avoid a duplicate browser tooltip once the styled
// panel is active. Positioning itself (single-axis flip, viewport clamp) is shared with
// tooltip.js via utils/floating-position.js, not duplicated (the same shared-utility reasoning
// applied to JS).
//
// Accessibility: same rule as tooltip.php -- the content is NEVER given the `hidden` attribute,
// only visually hidden by default via project CSS keyed off `data-state="open"|"closed"`, which
// hover-card.js toggles. `hidden` would also drop any interactive content inside out of the tab
// order/accessibility tree while still nominally "existing", an inconsistent state to leave
// project CSS to paper over.
//
// Composition: `trigger`/`content` are both caller-provided, pre-rendered HTML
// (same convention as tooltip.php's `trigger`). `trigger` should already be focusable itself (a
// link, a button, or something the caller gave `tabindex="0"`) -- this component does not add its
// own tabindex, same reasoning as tooltip.php.
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind on the strength of the Claude-Design reference
// "Hengegroup" (https://claude.ai/code/artifact/d9a5a3e2-3a09-494f-926b-206c5fa23e93, same
// `.dc.html` reference workflow as popover.php's/tooltip.php's own entries, see
// docs/entscheidungen.md for this component's entry).
//
// What carried over from the reference, and what didn't:
//   - the card (rounded-2xl/border-border/bg-popover/p-4/shadow) reuses popover.php's own token
//     choices verbatim, not tooltip.php's dark neutral-900 card -- HoverCard's content is rich,
//     often-structured preview material (an avatar row, a labelled key/value list, a "Datenblatt
//     öffnen" link), the same shape as popover.php's own rich `content`, not a short plain-text
//     hint. The reference's own literal card colors (`rgb(250,249,245)` on most examples, plain
//     `#fff` on a few others -- the reference itself is inconsistent across its own examples)
//     both read as this project's `--color-popover` (`--color-white`) closely enough that reusing
//     the existing semantic token was preferred over adding a second, near-duplicate one -- same
//     "prefer an existing semantic token over a literal color" precedent as popover.php's own entry.
//   - radius: the reference uses 14px card examples and 12/16px elsewhere -- standardized on
//     `rounded-2xl` (16px) instead, same as popover.php's own reasoning (matching this project's
//     other Phase-2 floating/card surfaces rather than literally copying the reference's own
//     inconsistent numbers).
//   - shadow (`shadow-[0_12px_32px_rgba(0,0,0,0.14)]`) is the reference's own literal value on
//     every one of its examples -- happens to be the EXACT value popover.php's own Phase 2 pass
//     already settled on for the identical reason, so no drift between the two.
//   - width: the reference varies per demo (210px/290px/300px/320px, each hand-fit to that one
//     example's own content) -- generalized to shadcn's own real stock `HoverCardContent` default,
//     `w-64` (256px), rather than reproducing any one demo's specific pixel value (same
//     "generalize instead of over-fitting to one demo string" reasoning as tooltip.php's own
//     `max-w-xs` decision, and popover.php's own `w-72` -- HoverCard's real shadcn default is
//     narrower than Popover's).
//   - gap to the trigger: 12px, not popover.php's/tooltip.php's own 10px -- the reference is
//     explicit about this ("Der Abstand ist größer als beim Tooltip, damit der Zeiger die Karte
//     erreicht, ohne sie zu schließen"), a deliberate, documented delta, not a rounding
//     difference. Passed as `hengegroup_theme_floating_position_classes()`'s own `$gap_px` param
//     (inc/template-parts/helpers.php), which defaults to 10 for its other two callers.
//   - the content box's OWN static `side`/`align` classes (its resting, pre-JS paint) come from
//     that same shared helper, same reasoning as tooltip.php's own entry: hover-card.js's
//     `positionFloatingElement()` (shared with tooltip.js via utils/floating-position.js) always
//     overrides the resting paint via inline styles the moment a card actually opens, so there is
//     exactly one side/align combination worth rendering as a class -- the configured one -- not a
//     `group-data-[side=...]` matrix reacting to a runtime flip that only ever matters before JS
//     has run once.
//   - the arrow (`[data-slot="hover-card-arrow"]`), unlike the content box, keeps its own
//     `group-data-[side=...]`/`group-data-[align=...]` reactive matrix (verbatim shape/derivation
//     as popover.php's own per-side border-pair lookup, just reactive via `group-data-` selectors
//     instead of a static PHP lookup) -- same split tooltip.php's own entry already documents:
//     the arrow gets no JS positioning at all, so ITS classes must keep tracking
//     `wrapper.dataset.side` after hover-card.js's own runtime flip, unlike the content box.
//     8px (`size-2`), not the reference's own literal 10px -- popover.php's own Phase 2 entry
//     already flagged this exact number in advance ("same size as tooltip.php's/hover-card.php's
//     own arrow for cross-component consistency"), so this file simply follows through on that.
//     `align`'s 16px arrow inset (`start`/`end`) is the same fixed value/technique as
//     tooltip.php's/popover.php's own arrows, not the reference's own one-off ~22px.
//   - entrance transition: opacity + `scale-[0.98]→scale-100`, NOT the reference's own literal
//     `translateY(-4px) scale(.98) -> translateY(0) scale(1)` keyframe, and NOT a `@keyframes`
//     rule at all (unlike popover.php's own `hg-popover-in`) -- two reasons, both specific to this
//     component's shape: (1) this content is always in the DOM, state toggled via
//     `data-state="open"|"closed"` (see the Accessibility note above), not inserted/removed like
//     popover.php's native `<details>` content -- a `@keyframes` animation only ever plays once on
//     page load for an always-present element, it does not replay on every open the way it does
//     for popover.php's own disclosure-driven content, so a CSS `transition` reacting to the
//     `data-state` class change is the correct tool here, same reasoning tooltip.php's own entry
//     already used for its own opacity fade. (2) the translateY portion specifically is dropped
//     (kept as opacity+scale only) because `positionFloatingElement()` (utils/floating-position.js)
//     unconditionally sets `content.style.translate = "none"` as an inline style every time it
//     repositions an opening card (see that file's own comment on why), which permanently wins
//     over any `translate-y-*` Tailwind class (Tailwind v4 compiles those to the standalone CSS
//     `translate` property, and an inline style always beats a class) from the first open onward
//     -- a `translate-y-*` transition would visibly animate correctly on the very first open, then
//     silently stop working on every open after that, since the class-based starting value gets
//     permanently overridden by the JS-set inline one. `scale`/`opacity` are never touched by that
//     function, so they keep transitioning correctly on every open/close cycle -- dropping just
//     the translateY axis (not the whole entrance effect) avoids the bug rather than papering over
//     it with a one-off fix.
//   - the reference's "Auf dunklem Grund" section was NOT ported, same reason as every other
//     component's Phase-2 entry so far (popover.php/tooltip.php/etc.) -- this theme has no
//     dark-mode/dark-surface strategy yet, see docs/entscheidungen.md.
//   - No file-per-variant split, no `hover-card/` folder move. Every "variant" the reference shows
//     (a rich product-preview card, a contact card, four `side`s, a product-list row reusing the
//     same product-preview shape) is markup/config the existing single-file component already
//     models via `content`/`side`/`align` -- not a structurally different composition, same
//     conclusion popover.php's/tooltip.php's own Phase 2 entries already reached for an almost
//     identical shape. CLAUDE.md Regel 4 only moves a component into its own subfolder once it's
//     genuinely more than one PHP file; this one still isn't.
//   - `page-component-showcase-hover-card.php` new, analog to the other showcase pages.
//
// Supported config:
//   trigger          string   required. Pre-rendered HTML for the trigger element
//   content          string   required. Pre-rendered HTML for the rich preview content; caller's
//                              responsibility to escape/build (see the note above on why there is
//                              no `text` shorthand here, unlike tooltip.php)
//   trigger_title    string   optional plain-text native `title` fallback for the zero-JS
//                              baseline (see above)
//   side             string   top | right | bottom (default) | left -- sets data-side; the JS
//                              uses it as the preferred placement, flipping to the opposite side
//                              if it doesn't fit in the viewport
//   align            string   start | center (default) | end -- sets data-align; the JS uses it
//                              for cross-axis placement (shadcn/Radix's own HoverCardContent prop,
//                              see the note above)
//   open_delay       int      hover delay in ms before showing (default: 700, Radix's own
//                              default), read by the JS via data-open-delay
//   close_delay      int      grace period in ms before hiding once the pointer leaves both
//                              trigger and content (default: 300, Radix's own default), read by
//                              the JS via data-close-delay
//   id               string   id for the hover card content; auto-generated via wp_unique_id()
//                              when omitted
//   class / attributes / data_attributes   passthrough onto the outer <span data-slot="hover-card">
//                              wrapper (not onto `trigger`/`content`, which the caller already
//                              controls)

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$trigger = (string) ($config['trigger'] ?? '');
$content = (string) ($config['content'] ?? '');
$trigger_title = trim((string) ($config['trigger_title'] ?? ''));
$side = trim((string) ($config['side'] ?? 'bottom'));
$align = trim((string) ($config['align'] ?? 'center'));
$open_delay = trim((string) ($config['open_delay'] ?? '700'));
$close_delay = trim((string) ($config['close_delay'] ?? '300'));
$id = trim((string) ($config['id'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($trigger) === '' || trim($content) === '') {
    return;
}

$allowed_sides = ['top', 'right', 'bottom', 'left'];

if (!in_array($side, $allowed_sides, true)) {
    $side = 'bottom';
}

$allowed_aligns = ['start', 'center', 'end'];

if (!in_array($align, $allowed_aligns, true)) {
    $align = 'center';
}

if (!is_numeric($open_delay)) {
    $open_delay = '700';
}

if (!is_numeric($close_delay)) {
    $close_delay = '300';
}

if ($id === '') {
    $id = 'hengegroup-theme-hover-card-' . wp_unique_id();
}

// `group`/`relative` are this file's own project-CSS half of the positioning: `group` lets the
// content/arrow below react to this wrapper's own `data-state`/`data-side`/`data-align` via
// `group-data-[...]:` selectors, `relative` is what the content's `absolute` positioning resolves
// against (see the Phase 2 file header for why the content box only ever needs ONE static
// side/align combination rendered here, not a reactive matrix, unlike the arrow). `inline-flex`
// is the same functional fix as tooltip.php's own wrapper -- a plain inline element wrapping an
// inline-block trigger picks up the surrounding line box's baseline/line-height "ghost space",
// throwing off getBoundingClientRect() measurements the JS position math depends on.
$wrapper_base_classes = 'group relative inline-flex';

$wrapper_attributes = $attributes;

$wrapper_attributes['class'] = trim(
    $wrapper_base_classes . ($class_name !== '' ? ' ' . $class_name : ''),
);

$wrapper_attributes['data-slot'] = 'hover-card';
$wrapper_attributes['data-state'] = 'closed';
$wrapper_attributes['data-side'] = $side;
$wrapper_attributes['data-align'] = $align;
$wrapper_attributes['data-open-delay'] = $open_delay;
$wrapper_attributes['data-close-delay'] = $close_delay;

if ($trigger_title !== '') {
    // Same placement as tooltip.php's own `title`: on the outer wrapper, not the inner trigger
    // span, so the zero-JS native tooltip fires for the whole hover-card group consistently with
    // where hover-card.js itself listens once active (see that file).
    $wrapper_attributes['title'] = $trigger_title;
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $wrapper_attributes['data-' . $data_name] = $attribute_value;
}

// `inline-flex` here is a functional fix, not styling -- see tooltip.php's identical
// `tooltip-trigger` span for why an unstyled inline wrapper throws off the shared
// utils/floating-position.js measurement (its own getBoundingClientRect() picks up the
// surrounding line box's baseline/line-height "ghost space" otherwise).
$trigger_markup = sprintf(
    '<span class="inline-flex" data-slot="hover-card-trigger">%s</span>',
    $trigger, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

// Card look (bg-popover/border-border/rounded-2xl/p-4/shadow, reused from popover.php's own token
// choices) plus the open/closed opacity+scale transition -- see the Phase 2 file header for where
// each value comes from, including why the transition is opacity+scale only (no translateY, unlike
// the reference's own literal keyframe). Position (the `hengegroup_theme_floating_position_classes()`
// call, inc/template-parts/helpers.php, 12px gap) only ever matters for the resting/pre-JS paint:
// hover-card.js's `positionFloatingElement()` always overrides it via inline styles the moment a
// card actually opens (see that shared JS file's own comment on why `top`/`left`/`right`/`bottom`/
// `transform`/`translate` all get explicitly overridden), so there is exactly one side/align
// combination worth rendering here -- the configured one -- not a `group-data-[side=...]` matrix
// reacting to hover-card.js's own runtime flip. That flip still happens (`wrapper.dataset.side`
// changes after a viewport collision), it just no longer needs a matching CSS rule for the CONTENT
// box the way the arrow below still does (no JS override there, see its own comment).
$content_classes =
    'absolute z-50 w-64 rounded-2xl border border-border bg-popover p-4 text-popover-foreground ' .
    'shadow-[0_12px_32px_rgba(0,0,0,0.14)] opacity-0 scale-[0.98] pointer-events-none ' .
    'transition-[opacity,scale] duration-[160ms] ease-out ' .
    'group-data-[state=open]:pointer-events-auto group-data-[state=open]:opacity-100 ' .
    'group-data-[state=open]:scale-100 ' .
    hengegroup_theme_floating_position_classes($side, $align, 12);

// The 8px diamond arrow -- `bg-inherit` takes the card's own `bg-popover` instead of repeating it
// (same reuse as popover.php's/tooltip.php's own arrows). Border pair per side is popover.php's
// own rotation derivation (which of the unrotated square's four edges form the visible tip after
// `rotate-45`), reused verbatim; unlike popover.php's static PHP lookup, this stays a reactive
// `group-data-[side=...]` matrix (tooltip.php's own technique) because hover-card.js's runtime
// side-flip can change `data-side` after the initial render, which the arrow -- unlike the content
// box above -- has no JS positioning of its own to already account for. `align`'s 16px arrow inset
// is the same fixed value/technique as tooltip.php's/popover.php's own arrows.
$arrow_classes =
    'absolute size-2 rotate-45 border-border bg-inherit ' .
    'group-data-[side=top]:top-full group-data-[side=top]:left-1/2 ' .
    'group-data-[side=top]:-mt-1 group-data-[side=top]:-translate-x-1/2 ' .
    'group-data-[side=top]:border-b group-data-[side=top]:border-r ' .
    'group-data-[side=bottom]:bottom-full group-data-[side=bottom]:left-1/2 ' .
    'group-data-[side=bottom]:-mb-1 group-data-[side=bottom]:-translate-x-1/2 ' .
    'group-data-[side=bottom]:border-t group-data-[side=bottom]:border-l ' .
    'group-data-[side=left]:top-1/2 group-data-[side=left]:left-full ' .
    'group-data-[side=left]:-ml-1 group-data-[side=left]:-translate-y-1/2 ' .
    'group-data-[side=left]:border-t group-data-[side=left]:border-r ' .
    'group-data-[side=right]:top-1/2 group-data-[side=right]:right-full ' .
    'group-data-[side=right]:-mr-1 group-data-[side=right]:-translate-y-1/2 ' .
    'group-data-[side=right]:border-l group-data-[side=right]:border-b ' .
    'group-data-[align=start]:group-data-[side=top]:left-4 ' .
    'group-data-[align=start]:group-data-[side=top]:translate-x-0 ' .
    'group-data-[align=start]:group-data-[side=bottom]:left-4 ' .
    'group-data-[align=start]:group-data-[side=bottom]:translate-x-0 ' .
    'group-data-[align=end]:group-data-[side=top]:left-auto ' .
    'group-data-[align=end]:group-data-[side=top]:right-4 ' .
    'group-data-[align=end]:group-data-[side=top]:translate-x-0 ' .
    'group-data-[align=end]:group-data-[side=bottom]:left-auto ' .
    'group-data-[align=end]:group-data-[side=bottom]:right-4 ' .
    'group-data-[align=end]:group-data-[side=bottom]:translate-x-0 ' .
    'group-data-[align=start]:group-data-[side=left]:top-4 ' .
    'group-data-[align=start]:group-data-[side=left]:translate-y-0 ' .
    'group-data-[align=start]:group-data-[side=right]:top-4 ' .
    'group-data-[align=start]:group-data-[side=right]:translate-y-0 ' .
    'group-data-[align=end]:group-data-[side=left]:top-auto ' .
    'group-data-[align=end]:group-data-[side=left]:bottom-4 ' .
    'group-data-[align=end]:group-data-[side=left]:translate-y-0 ' .
    'group-data-[align=end]:group-data-[side=right]:top-auto ' .
    'group-data-[align=end]:group-data-[side=right]:bottom-4 ' .
    'group-data-[align=end]:group-data-[side=right]:translate-y-0';

$content_markup = sprintf(
    '<div class="%1$s" data-slot="hover-card-content" id="%2$s">%3$s' .
        '<span class="%4$s" data-slot="hover-card-arrow" aria-hidden="true"></span></div>',
    esc_attr($content_classes),
    esc_attr($id),
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_attr($arrow_classes),
);

printf(
    '<span%1$s>%2$s%3$s</span>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
