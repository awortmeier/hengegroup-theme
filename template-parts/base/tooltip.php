<?php

declare(strict_types=1);

// shadcn/ui's Tooltip wraps a headless UI primitive (historically Radix UI; shadcn now also ships
// Base UI/React Aria variants of many components): a JS-driven floating panel, shown on
// hover/focus, positioned relative to its trigger. Unlike most other components in this theme,
// the plain native `title` attribute does NOT functionally replace this -- it can't be styled,
// carries no rich content, and has unreliable/no touch support, so it's not a faithful substitute
// (unlike e.g. native-select.php, where the native element genuinely covers shadcn's behaviour).
// This is therefore -- like select.php -- a case where real JS is warranted.
//
// Progressive enhancement: with the `text` config, this component's wrapper gets a native
// `title="..."` attribute for free -- a real, if unstyled, tooltip works with zero JS. On top of
// that, assets/js/template-parts/base/tooltip.js finds these on page load and enhances them into
// a styled floating panel (show on hover/focus with a delay, hide on mouseleave/blur/Escape,
// simple single-axis flip if the preferred `side` doesn't fit -- not full collision detection,
// deferred if ever needed) and removes the native `title` to avoid a duplicate browser tooltip.
//
// Accessibility: the tooltip content is NEVER given the `hidden` attribute (that would remove it
// from the accessibility tree and break `aria-describedby`). It's always in the DOM, described via
// `aria-describedby`, and only visually hidden by default -- project CSS hides/shows it via the
// `data-state="open"|"closed"` attribute the JS toggles on the wrapper (opacity/pointer-events
// below), never via `hidden`/`display:none` directly.
//
// Composition: `trigger` is caller-provided, pre-rendered HTML (same convention as
// aspect-ratio.php's `content`) -- e.g. a buffered button.php/icon.php call. It should already be
// focusable itself (a button, a link, or something the caller gave `tabindex="0"`); this component
// does not add its own tabindex, since wrapping an already-focusable trigger in another focusable
// element would create a duplicate tab stop.
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind on the strength of the Claude-Design reference
// "Hengegroup" (same `.dc.html` reference workflow as toast.php's/tabs.php's own entries, see
// docs/entscheidungen.md for this component's entry). Stayed a single file, no
// `template-parts/base/tooltip/` folder -- Regel 4 only moves a component into a folder once it's
// more than one PHP file, and every "variant" the reference shows (four `side`s, an `align`
// example, an icon-only trigger, a dotted-underline "help" trigger) is markup/config the existing
// single-file component already models or a small addition to it (see `align` below), not a
// structurally different composition the way e.g. separator.php + separator-label.php are.
//
// What carried over from the reference, and what didn't:
//   - the card itself (`rgb(30,29,28)` background / `rgb(250,249,245)` text, 8px radius, 13px/1.4
//     type, 7px/11px padding, `0 6px 18px rgba(0,0,0,.18)` shadow, 10px gap to the trigger, an 8px
//     diamond arrow) is reproduced with real project tokens where they matched closely enough:
//     `rgb(30,29,28)`/`rgb(250,249,245)` are near-exact matches for Tailwind's own
//     `neutral-900`/`neutral-50` (closer than this project's existing `--color-foreground`/
//     `-grey-dark`, which are pinned to `neutral-800`) -- used directly (`bg-neutral-900
//     text-neutral-50`) rather than adding a new token, same "reference Tailwind's stock scales
//     when no project token exists yet" convention tokens.css documents for e.g. toast.php's
//     `warning` amber-600. Deliberately NOT `bg-primary text-primary-foreground` (shadcn's own
//     stock TooltipContent classes) -- this project's `primary` role is the henge-green brand
//     accent, and the reference's card is unambiguously a neutral near-black, not green, on every
//     example including the icon/table ones.
//   - the arrow reuses the content's own background via `bg-inherit` instead of repeating
//     `bg-neutral-900` a second time -- same "derive from the one already-set value instead of a
//     second, driftable source of truth" reasoning as toast.php's `text-current`/`bg-current`
//     accent reuse.
//   - a fixed `width:250px`/`240px` on the reference's two long-text examples (the table-row
//     "SiO₂" tooltip, the dark-background "Siebrückstand" tooltip) was generalized to `max-w-xs`
//     (320px) + `text-pretty` instead of copying either literal width: forcing every tooltip,
//     including short ones, onto one specific caller's fixed pixel width would be over-fitting to
//     that one demo string's length: a max-width that only engages once content is actually long
//     enough serves both the short (`Basis`/`Positionen`/icon) and long (table-row) examples with
//     one rule, closer to what a real per-call `text`/`content` value of arbitrary length needs
//     from a *component*, not a caption written for one demo.
//   - `align` (start | center (default) | end) is a new config key, not present in Phase 1 --
//     the reference's table-row example is genuinely `align="start"` (its tooltip starts flush
//     with the trigger's left edge instead of centering on it, same shape as the reference's other
//     "align" case in hover-card.php's own header comment). This is real shadcn/Radix
//     TooltipContent vocabulary this file simply hadn't implemented yet (Phase 1 focused on the
//     functional minimum), not invented -- and hover-card.php already has the identical
//     start/center/end vocabulary for the same reason, wired through the same shared
//     utils/floating-position.js `align` option, so tooltip.js now passes `align` through to it
//     exactly like hover-card.js already does.
//   - `align`'s Tailwind side is JS-only (no static CSS shift for it), same documented scope limit
//     as `side`'s own flip: `positionFloatingElement()` computes the trigger-relative
//     `left`/`top` pixel offset for start/center/end directly (it already needs the trigger's
//     real geometry for `side`'s flip math), so a parallel *static* CSS position per align value
//     would only matter for the always-invisible pre-JS resting frame -- not worth the combinatorial
//     class surface. The **arrow**, unlike the content box, gets NO JS positioning at all (same as
//     the reference, which hardcodes each example's arrow inline rather than computing it), so its
//     align shift IS real static CSS: a fixed 16px inset from the content's start/end edge,
//     matching the reference's own literal `left:16px` value for its one shifted example, rather
//     than trying to re-derive the trigger's exact center in pure CSS.
//   - the reference's "Auf dunklem Grund" section was NOT ported, same reason as every other
//     component's Phase-2 entry so far (separator.php/kbd.php/pagination.php/table/*.php/toast.php)
//     -- this theme has no dark-mode/dark-surface strategy yet, see docs/entscheidungen.md.
//   - `page-component-showcase-tooltip.php` new, analog to the other showcase pages.
//
// Supported config:
//   trigger          string   required. Pre-rendered HTML for the trigger element
//   text             string   tooltip content as plain text (escaped); also becomes the wrapper's
//                              native `title` fallback automatically. Takes priority over `content`.
//   content          string   tooltip content as pre-rendered HTML, for richer bodies than plain
//                              text; caller's responsibility to escape/build
//   trigger_title    string   optional plain-text native `title` fallback when using `content`
//                              instead of `text` (which sets it automatically)
//   side             string   top (default) | right | bottom | left -- sets data-side; the JS uses
//                              it as the preferred placement, flipping to the opposite side if it
//                              doesn't fit in the viewport
//   align            string   start | center (default) | end -- sets data-align; the JS uses it
//                              for cross-axis placement (shadcn/Radix's own TooltipContent prop,
//                              see the Phase 2 note above -- same vocabulary as hover-card.php's
//                              own `align`)
//   delay            int      hover delay in ms before showing (default: 700, matches that
//                              headless implementation's own default), read by the JS via data-delay
//   id               string   id for the tooltip content; auto-generated via wp_unique_id() when
//                              omitted (needed for the trigger's aria-describedby)
//   class / attributes / data_attributes   passthrough onto the outer <span data-slot="tooltip">
//                              wrapper (not onto `trigger`, which the caller already controls)

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$trigger = (string) ($config['trigger'] ?? '');
$text = trim((string) ($config['text'] ?? ''));
$content = (string) ($config['content'] ?? '');
$trigger_title = trim((string) ($config['trigger_title'] ?? ''));
$side = trim((string) ($config['side'] ?? 'top'));
$align = trim((string) ($config['align'] ?? 'center'));
$delay = trim((string) ($config['delay'] ?? '700'));
$id = trim((string) ($config['id'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($trigger) === '' || ($text === '' && trim($content) === '')) {
    return;
}

$allowed_sides = ['top', 'right', 'bottom', 'left'];

if (!in_array($side, $allowed_sides, true)) {
    $side = 'top';
}

$allowed_aligns = ['start', 'center', 'end'];

if (!in_array($align, $allowed_aligns, true)) {
    $align = 'center';
}

if (!is_numeric($delay)) {
    $delay = '700';
}

if ($id === '') {
    $id = 'hengegroup-theme-tooltip-' . wp_unique_id();
}

$content_markup = $text !== '' ? esc_html($text) : $content;
$title_value = $text !== '' ? $text : $trigger_title;

$wrapper_base_classes = 'group relative inline-flex';

$wrapper_attributes = $attributes;

$wrapper_attributes['class'] = trim(
    $wrapper_base_classes . ($class_name !== '' ? ' ' . $class_name : ''),
);

$wrapper_attributes['data-slot'] = 'tooltip';
$wrapper_attributes['data-state'] = 'closed';
$wrapper_attributes['data-side'] = $side;
$wrapper_attributes['data-align'] = $align;
$wrapper_attributes['data-delay'] = $delay;

if ($title_value !== '') {
    $wrapper_attributes['title'] = $title_value;
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $wrapper_attributes['data-' . $data_name] = $attribute_value;
}

// `inline-flex` here (not the no-display default an unstyled <span> would have) matters for
// correctness, not just looks: a plain inline element wrapping an inline-block child (e.g. a
// button.php button) picks up the surrounding line box's own baseline/line-height "ghost space",
// so getBoundingClientRect() on this wrapper -- which is exactly what the JS position math below
// measures the trigger by -- would come back a few px taller than the actual visible trigger,
// throwing off every side/align calculation by that same amount. A flex container's children
// don't participate in that baseline line-box model, which is why this is the standard fix, not
// a Regel-1 violation (see that rule's own "would the component work incorrectly without this
// class" test).
$trigger_markup = sprintf(
    '<span class="inline-flex" data-slot="tooltip-trigger" aria-describedby="%1$s">%2$s</span>',
    esc_attr($id),
    $trigger, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

// Card look (bg-neutral-900/text-neutral-50, radius, shadow, gap-to-trigger per side) plus the
// open/closed opacity transition -- see the Phase 2 file header for where each value comes from.
// `align`'s own cross-axis shift is intentionally NOT part of this static class list -- it's
// computed by the JS (utils/floating-position.js, same as `side`'s flip), see the header comment.
$content_classes =
    'absolute z-50 max-w-xs rounded-lg bg-neutral-900 px-[11px] py-[7px] text-[13px] ' .
    'leading-[1.4] text-neutral-50 text-pretty shadow-[0_6px_18px_rgba(0,0,0,0.18)] ' .
    'opacity-0 pointer-events-none transition-opacity duration-[140ms] ease-out ' .
    'group-data-[state=open]:pointer-events-auto group-data-[state=open]:opacity-100 ' .
    'group-data-[side=top]:bottom-[calc(100%+10px)] group-data-[side=top]:left-1/2 ' .
    'group-data-[side=top]:-translate-x-1/2 ' .
    'group-data-[side=bottom]:top-[calc(100%+10px)] group-data-[side=bottom]:left-1/2 ' .
    'group-data-[side=bottom]:-translate-x-1/2 ' .
    'group-data-[side=left]:top-1/2 group-data-[side=left]:right-[calc(100%+10px)] ' .
    'group-data-[side=left]:-translate-y-1/2 ' .
    'group-data-[side=right]:top-1/2 group-data-[side=right]:left-[calc(100%+10px)] ' .
    'group-data-[side=right]:-translate-y-1/2';

// The 8px diamond arrow -- `bg-inherit` takes the card's own `bg-neutral-900` instead of repeating
// it (see the Phase 2 file header). Centered on the trigger's cross axis by default; `align`
// start/end shift it to a fixed 16px inset from the content's edge instead (the one place `align`
// DOES get static CSS, see the header comment for why that differs from the content box itself).
$arrow_classes =
    'absolute size-2 rotate-45 bg-inherit ' .
    'group-data-[side=top]:top-full group-data-[side=top]:left-1/2 ' .
    'group-data-[side=top]:-mt-1 group-data-[side=top]:-translate-x-1/2 ' .
    'group-data-[side=bottom]:bottom-full group-data-[side=bottom]:left-1/2 ' .
    'group-data-[side=bottom]:-mb-1 group-data-[side=bottom]:-translate-x-1/2 ' .
    'group-data-[side=left]:top-1/2 group-data-[side=left]:left-full ' .
    'group-data-[side=left]:-ml-1 group-data-[side=left]:-translate-y-1/2 ' .
    'group-data-[side=right]:top-1/2 group-data-[side=right]:right-full ' .
    'group-data-[side=right]:-mr-1 group-data-[side=right]:-translate-y-1/2 ' .
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

$content_element_markup = sprintf(
    '<span class="%1$s" data-slot="tooltip-content" role="tooltip" id="%2$s">%3$s' .
        '<span class="%4$s" data-slot="tooltip-arrow" aria-hidden="true"></span></span>',
    esc_attr($content_classes),
    esc_attr($id),
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_attr($arrow_classes),
);

printf(
    '<span%1$s>%2$s%3$s</span>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_element_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
