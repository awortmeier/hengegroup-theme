<?php

declare(strict_types=1);

// shadcn/ui's NavigationMenu wraps a headless UI primitive for a horizontal, top-level site nav:
// a row of items that are either plain links or hover/click-triggered dropdown ("mega menu")
// panels holding richer content. Live-checked against shadcn's current docs:
// shadcn's NavigationMenu page itself now just points at Base UI's own Navigation Menu docs for
// the API reference -- like toast.php's own header comment already notes for Toast, this is
// another component shadcn has moved onto a Base UI primitive rather than Radix UI. Root's real,
// current props are `delay`/`closeDelay` (both default 50ms) and `orientation`
// ('horizontal' | 'vertical', default 'horizontal'); Link's real `active` prop (default false)
// is unchanged from what navigation-menu-link.php already implements.
//
// Zero-JS baseline, same core technique as dropdown-menu.php: native <details>/<summary> per
// trigger item gives a real, working disclosure -- click (or Enter/Space on) a trigger to
// show/hide its panel, no JS required (CLAUDE.md #1). What this file adds on top, which
// dropdown-menu.php never needed (a single, standalone <details>, no siblings to coordinate):
// every trigger item's <details> shares one `name` attribute (derived from this instance's own
// `id`) -- the exact same native-only exclusivity trick accordion.php's `type: 'single'` already
// uses, applied here because a nav bar with two panels open at once would be a broken menu, not a
// stylistic choice. "Only one mega-menu panel open at a time" is therefore a genuinely native,
// zero-JS guarantee, not something JS enforces.
//
// Two honest, documented gaps in that zero-JS baseline, closed by
// assets/js/template-parts/base/navigation-menu.js: it doesn't auto-close on outside click or
// Escape (same gap dropdown-menu.php's own header comment documents), and it only opens on click
// -- a real top nav bar is expected to also open on hover, which no plain <details> can do on its
// own. navigation-menu.js adds hover-intent opening/closing, timed by `delay`/`close_delay`
// below (this theme's own snake_case of the real `delay`/`closeDelay` props above), plus
// horizontal Left/Right-arrow roving navigation across the top-level items (links and triggers
// alike, Home/End to jump to the first/last item) -- a reasonable, documented approximation of
// the underlying primitive's own keyboard behaviour, not a byte-for-byte reimplementation of its
// internal focus machinery.
//
// Deliberately out of scope for v1, not silently dropped:
//   - NavigationMenuIndicator (Base UI: Arrow): the small arrow/pointer that tracks the
//     currently active trigger. Purely decorative, has no function without Phase-2 CSS
//     transitions driving its movement (CLAUDE.md #1) -- nothing to build server-side yet.
//   - NavigationMenuViewport (Base UI: Positioner/Popup/Viewport): the underlying primitive's own
//     shared floating-panel plumbing that a content panel's markup gets teleported into,
//     collision-aware positioned, size-animated between panels. This theme has no standalone
//     popover.php to route through either (see date-picker.php's own header comment, same
//     reasoning) -- each trigger's content stays inline directly under its own <details>, the
//     same local-panel architecture dropdown-menu.php/date-picker.php already use, not a shared
//     abstraction. Phase 2 (below) confirms this is still the case: the design reference's own
//     "Weitergleiten" rule (an open panel glides to the next trigger's position/size when the
//     pointer moves between triggers, instead of closing and reopening) is exactly what a real
//     Viewport buys you -- still not built here, same deferral, now just visually confirmed as a
//     real gap rather than a hypothetical one (same "confirmed, not closed" note dropdown-menu.php's
//     own Phase 2 entry left for DropdownMenuSub). Each panel opens/closes independently, directly
//     under its own trigger.
//
// Composition: plain top-level items nest navigation-menu-link.php (`items`
// config below). A trigger item's mega-menu panel is caller-provided, pre-rendered HTML
// (content-agnostic wrapper, same convention as dropdown-menu.php's own `content`) -- typically
// itself built from several navigation-menu-link.php calls plus e.g. typography.php headings, see
// the recipe below.
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind on the strength of the Claude-Design reference
// "Hengegroup" (https://claude.ai/code/artifact/5cb1e148-c394-4f71-bc4b-61912a213332), "Basis"
// section for the default look, "Auf dunklem Grund" for `color: light`. The reference is a live
// interactive prototype (hover-intent open, a shared morphing viewport, 140ms close delay) rather
// than a set of static states -- browser automation couldn't drive its hover/click interactions
// in this session (same class of gap dropdown-menu.php's own Phase 2 entry documents for ITS
// reference), but the prototype's own source (a small React-ish component embedded in the
// artifact's exported markup) was readable directly and made every value below a literal, not a
// guess: colors, spacing, radii, the shadow, and the exact behavioural rules now written up in
// this file's own "Deliberately out of scope" section above and the `color` config note below.
//
//   - **No file-per-variant split, no further folder move** (the task explicitly asked to check
//     both, same as dropdown-menu.php's own Phase 2 entry) -- this component already lives in its
//     own `navigation-menu/` folder, already split into one file per sub-part
//     (navigation-menu.php/navigation-menu-link.php) since Phase 1. "Basis"/"Auf dunklem Grund"
//     aren't a `variant`-shaped split either (see `color` below) -- one config value, not two
//     files, same reasoning as accordion.php's/typography.php's own `color` axis.
//   - **New `color` config** (see below), not a shadcn prop -- same vocabulary/meaning and same
//     "this project has no dark-mode strategy yet, so this is an explicit per-component config
//     value instead of a `dark:`/`prefers-color-scheme` toggle" reasoning as accordion.php's own
//     `color: light` (see that file's header comment and docs/entscheidungen.md). Same
//     simplification too: the reference's dark-surface variant picks a bespoke lighter green
//     (`#8fd6ab`) for its open-trigger/link text instead of the brand `henge-green`, for contrast
//     against the dark panel -- kept as the single `henge-green` brand accent for both `color`
//     values here as well (still passes WCAG AA at this weight/size against the dark surface),
//     one fewer one-off visual pattern for the design system to carry. Only the OPEN/hover
//     background tint and the panel's own card surface (bg/border/shadow) adapt per `color` --
//     the menubar row itself has no background of its own in either case (sits directly on
//     whatever the caller's page background is, same "text/border only, surface is the caller's
//     job" component-boundary convention as accordion.php), because unlike accordion's inline
//     content, a mega-menu panel floats OVER arbitrary page content and needs its own real card
//     surface regardless of `color` -- same self-contained-card boundary as popover.php's/
//     dropdown-menu.php's own content, not accordion.php's boundary.
//   - **Chevron swapped from the reference's literal "▾" text glyph to this project's own
//     `chevron-down`/lucide icon.php convention** (same icon accordion.php/select.php already use
//     for an identical "this trigger has a panel" affordance) -- one fewer one-off visual pattern.
//     It flips open via `group-open:-scale-y-100` (see accordion.php's own `group`/`group-open:`
//     mechanism, applied here as a vertical flip instead of accordion.php's own rotation --
//     user-requested deviation from this file's own original `rotate-180`, see the `$icon_class`
//     comment below for why). No separate icon color class needed: the icon's
//     `stroke="currentColor"` inherits the trigger button's own `text-*` color, exactly like the
//     reference's own unstyled `<span>` glyph inherits its parent button's `color`.
//   - **Panel position is per-trigger, not a shared sliding viewport** (see "Deliberately out of
//     scope" above) -- each `<details>` gets `relative` so its own `<div data-slot=
//     "navigation-menu-content">` can be `absolute`-positioned directly under it (`top-full`,
//     `left-0`), the same local-panel positioning dropdown-menu.php's own Phase 2 entry uses, not
//     `hengegroup_theme_floating_position_classes()` (that helper's `side`/`align` matrix is for a
//     single anchored popover; here every trigger already has an implicit "bottom/start" anchor to
//     its own position, nothing to choose between).
//   - **Card look reuses popover.php's/dropdown-menu.php's own established floating-card tokens**
//     for `color: default` (`rounded-2xl`/`border-border`/`bg-popover`/`text-popover-foreground`/
//     `animate-[hg-popover-in_140ms_ease-out]`), including normalizing the reference's own literal
//     shadow (`0 18px 44px rgba(0,0,0,0.14)`) to the same shared `shadow-[0_12px_32px_
//     rgba(0,0,0,0.14)]` used everywhere else, for the same cross-component-consistency reasoning
//     dropdown-menu.php's own entry already gives. `color: light`'s panel is a genuinely different
//     surface (`bg-neutral-800` -- matches the reference's own literal `rgb(38,37,35)` almost
//     exactly -- `border-grey-light/15`, `text-grey-light`), so it keeps the reference's own,
//     visibly heavier literal shadow (`shadow-[0_20px_48px_rgba(0,0,0,0.45)]`) instead: a light
//     hairline reads on a dark card where the default shadow barely would.
//   - **`min-w-72` floor on the panel, real width is the caller's content** -- the reference hands
//     each of its own three demo panels a bespoke pixel width (660/520/400px, content-driven mega-
//     menu columns), which this generic, content-agnostic `content` slot has no way to know ahead
//     of render; same reasoning as dropdown-menu.php's own `min-w-32` floor, just a wider floor
//     for a mega-menu's typically multi-column content. A multi-column `content` grid still needs
//     its OWN `minmax()` track floor from the caller (see the recipe below and
//     page-component-showcase-navigation-menu.php) -- bare `grid-cols-2` lets tracks shrink to 0,
//     this floor alone doesn't stop that (user-reported "menu items too narrow" fix).
//   - **The reference's own featured "Gesamtsortiment" tile is a caller-composed panel item, not a
//     new prop here** -- same content-agnostic boundary as the recipe below already documents;
//     a caller wanting it can nest a styled `<a>` (or reuse card.php) inside `content` alongside
//     navigation-menu-link.php calls, no new config needed.
//   - **navigation-menu-link.php's own styling is now split by role, not one fixed look** -- see
//     that file's own header comment. This file computes and passes the full top-level trigger
//     button recipe via `class` for plain link items (below); a panel-internal "list item" look is
//     the CALLER's own `class`, documented in the recipe below (same reasoning as shadcn's own
//     real docs, which style top-level links via a shared `navigationMenuTriggerStyle()` helper but
//     leave panel-internal links to a caller-defined `ListItem` wrapper, not NavigationMenuLink
//     itself).
//   - **Delay defaults (50ms/50ms) are unchanged**, even though the reference's own "Regeln"
//     section calls out a 140ms close delay -- those PHP defaults already track the real
//     underlying primitive's own actual prop defaults (see the top of this file), a factual API-
//     fidelity concern, not a visual styling choice Phase 2 owns. A caller wanting the reference's
//     exact close feel can pass `close_delay: 140` explicitly.
//
// Supported config:
//   items       array   required. Each entry:
//     text / label   string   required. Visible label
//     href           string   presence makes this a plain link item, rendered via
//                              navigation-menu-link.php
//     content        string   presence makes this a trigger+panel item instead (pre-rendered
//                              HTML for the mega-menu panel); wins over `href` if both are given
//                              -- a trigger opens a panel, it doesn't also navigate
//     active         bool     forwarded to navigation-menu-link.php for link items
//                              (data-active/aria-current, see that file); also recolors the link
//                              itself `henge-green`/semibold in place of the idle `color` text
//                              tone (Phase 2)
//     id             string   native id for this item's <details> (trigger items only);
//                              auto-generated via wp_unique_id() when omitted
//                       Entries with neither `href` nor `content`, or no `text`, are silently
//                       skipped, same "invalid entry -> skip" convention as toast.php's `toasts`
//   orientation  string   horizontal (default) | vertical -- sets data-orientation only
//                            (matches the underlying primitive's real `orientation` prop, see
//                            above); Phase 1/2 have no visual difference between the two, same
//                            "hook now, style later" split as card.php's own `size`
//   color        string   default | light (default: default) -- same vocabulary/meaning as
//                            typography.php's/accordion.php's own `color` config: "light" recolors
//                            text for placement on a dark page background (the panel gets its own
//                            dark card surface regardless, see the Phase 2 note above; the caller
//                            still supplies the dark backdrop behind the menubar row itself, same
//                            component-boundary convention as accordion.php)
//   delay        int      hover-intent delay in ms before a trigger's panel opens (default: 50,
//                            the underlying primitive's own current default), read by the JS via
//                            data-delay
//   close_delay  int      grace period in ms before an open panel closes once the pointer leaves
//                            both trigger and content (default: 50, same source default), read
//                            by the JS via data-close-delay
//   aria_label   string   accessible name for the <nav> landmark -- recommended whenever more
//                            than one <nav> exists on a page (e.g. distinguishing a header nav
//                            from a footer nav), same reasoning as toast.php's viewport
//                            `aria-label`
//   id           string   native id on the outer <nav>; also seeds the shared
//                            <details name="..."> exclusivity group for this instance (see
//                            above) -- auto-generated via wp_unique_id() when omitted
//   class / attributes / data_attributes   passthrough onto the outer
//                            <nav data-slot="navigation-menu">
//
// Recipe for a trigger item's mega-menu panel (plain links; a panel-internal "list item" look --
// title + optional description, hover tint -- is this project's own recipe, not a shadcn prop, so
// it's a `class` the caller supplies, not something navigation-menu-link.php bakes in):
//
//   ob_start();
//   get_template_part('template-parts/base/navigation-menu/navigation-menu-link', null, [
//       'config' => [
//           'href' => '/docs',
//           'class' => 'flex flex-col gap-1 rounded-lg p-2.5 hover:bg-henge-green/10',
//           'content' => '<span class="text-sm font-semibold">Introduction</span>' .
//               '<span class="text-sm text-muted-foreground">What this project is.</span>',
//       ],
//   ]);
//   get_template_part('template-parts/base/navigation-menu/navigation-menu-link', null, [
//       'config' => [
//           'href' => '/docs/install',
//           'class' => 'flex flex-col gap-1 rounded-lg p-2.5 hover:bg-henge-green/10',
//           'content' => '<span class="text-sm font-semibold">Installation</span>',
//       ],
//   ]);
//   $panel_content = ob_get_clean();
//
//   get_template_part('template-parts/base/navigation-menu/navigation-menu', null, [
//       'config' => [
//           'aria_label' => 'Main',
//           'items' => [
//               ['text' => 'Docs', 'content' => $panel_content],
//               ['text' => 'Pricing', 'href' => '/pricing'],
//           ],
//       ],
//   ]);

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$items_config = is_array($config['items'] ?? null) ? $config['items'] : [];
$orientation = trim((string) ($config['orientation'] ?? 'horizontal'));
$color = trim((string) ($config['color'] ?? 'default'));
$delay = trim((string) ($config['delay'] ?? '50'));
$close_delay = trim((string) ($config['close_delay'] ?? '50'));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$id = trim((string) ($config['id'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_orientations = ['horizontal', 'vertical'];
$allowed_colors = ['default', 'light'];

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'horizontal';
}

if (!in_array($color, $allowed_colors, true)) {
    $color = 'default';
}

if (!is_numeric($delay)) {
    $delay = '50';
}

if (!is_numeric($close_delay)) {
    $close_delay = '50';
}

if ($id === '') {
    $id = 'hengegroup-theme-navigation-menu-' . wp_unique_id();
}

$group_name = $id . '-group';

// Per-`color` computed classes (see Phase 2 note above): the trigger row shares idle text color
// plus the open/hover accent tint across both trigger buttons and plain links; the panel's own
// card surface (border/bg/text/shadow) only matters for trigger items, but lives here so both
// branches of the loop below read from one place.
$color_classes = [
    'default' => [
        'idle' => 'text-foreground/85',
        'hover' =>
            'hover:bg-henge-green/10 hover:text-henge-green group-open:bg-henge-green/10 group-open:text-henge-green',
        'panel_border' => 'border-border',
        'panel_bg' => 'bg-popover',
        'panel_text' => 'text-popover-foreground',
        'panel_shadow' => 'shadow-[0_12px_32px_rgba(0,0,0,0.14)]',
    ],
    'light' => [
        'idle' => 'text-grey-light/85',
        'hover' =>
            'hover:bg-grey-light/10 hover:text-henge-green group-open:bg-grey-light/10 group-open:text-henge-green',
        'panel_border' => 'border-grey-light/15',
        'panel_bg' => 'bg-neutral-800',
        'panel_text' => 'text-grey-light',
        'panel_shadow' => 'shadow-[0_20px_48px_rgba(0,0,0,0.45)]',
    ],
];

$trigger_button_base =
    'inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2.5 text-sm font-medium outline-none ' .
    'transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50 ' .
    $color_classes[$color]['hover'];

// A vertical flip (`-scale-y-100`), not a rotation -- user-requested change away from this
// file's own original `group-open:rotate-180` (still true of accordion.php's own chevron, kept
// there): a rotation spins the glyph through a sideways-pointing midpoint, a flip instead
// squashes it flat and un-squashes mirrored, reading as "this points the other way now" instead
// of "this span".
$icon_class =
    'pointer-events-none size-3.5 shrink-0 transition-transform duration-200 group-open:-scale-y-100';

$content_classes = trim(
    'absolute left-0 top-full z-50 mt-2.5 min-w-72 overflow-hidden rounded-2xl border p-3.5 ' .
        $color_classes[$color]['panel_border'] .
        ' ' .
        $color_classes[$color]['panel_bg'] .
        ' ' .
        $color_classes[$color]['panel_text'] .
        ' ' .
        $color_classes[$color]['panel_shadow'] .
        ' animate-[hg-popover-in_140ms_ease-out]',
);

$items_markup = '';

foreach ($items_config as $item) {
    if (!is_array($item)) {
        continue;
    }

    $item_text = trim((string) ($item['text'] ?? ($item['label'] ?? '')));
    $item_href = trim((string) ($item['href'] ?? ''));
    $item_content = (string) ($item['content'] ?? '');
    $item_active = !empty($item['active']);
    $item_id = trim((string) ($item['id'] ?? ''));

    if ($item_text === '') {
        continue;
    }

    if (trim($item_content) !== '') {
        if ($item_id === '') {
            $item_id = $id . '-item-' . wp_unique_id();
        }

        $trigger_class =
            trim($color_classes[$color]['idle'] . ' ' . $trigger_button_base) .
            ' list-none cursor-pointer [&::-webkit-details-marker]:hidden';

        $icon_markup = hengegroup_theme_render_icon([
            'name' => 'chevron-down',
            'set' => 'lucide',
            'class' => $icon_class,
        ]);

        $trigger_markup = sprintf(
            '<summary class="%1$s" data-slot="navigation-menu-trigger" aria-haspopup="true">%2$s%3$s</summary>',
            esc_attr($trigger_class),
            esc_html($item_text),
            $icon_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );

        $content_markup = sprintf(
            '<div class="%1$s" data-slot="navigation-menu-content">%2$s</div>',
            esc_attr($content_classes),
            $item_content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );

        $items_markup .= sprintf(
            '<li data-slot="navigation-menu-item">' .
                '<details class="group relative" data-slot="navigation-menu-trigger-item" name="%1$s" id="%2$s">%3$s%4$s</details>' .
                '</li>',
            esc_attr($group_name),
            esc_attr($item_id),
            $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );

        continue;
    }

    if ($item_href === '') {
        continue;
    }

    // Idle text color is only contributed here when NOT active -- navigation-menu-link.php's own
    // `active` handling already recolors it `text-henge-green font-semibold` unconditionally (see
    // that file's header comment), so leaving this class out when active avoids two competing
    // text-color utilities landing on the same element (the exact class-ordering pitfall
    // button.php's own header comment documents -- see navigation-menu-link.php's Phase 2 note).
    $link_idle_class = $item_active ? '' : $color_classes[$color]['idle'];
    $link_class = trim($link_idle_class . ' ' . $trigger_button_base);

    ob_start();
    get_template_part('template-parts/base/navigation-menu/navigation-menu-link', null, [
        'config' => [
            'text' => $item_text,
            'href' => $item_href,
            'active' => $item_active,
            'class' => $link_class,
        ],
    ]);
    $link_markup = (string) ob_get_clean();

    if (trim($link_markup) === '') {
        continue;
    }

    $items_markup .= sprintf(
        '<li data-slot="navigation-menu-item">%s</li>',
        $link_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

if (trim($items_markup) === '') {
    return;
}

$list_markup = sprintf(
    '<ul class="flex flex-wrap items-center gap-0.5" data-slot="navigation-menu-list">%s</ul>',
    $items_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'navigation-menu';
$element_attributes['data-orientation'] = $orientation;
$element_attributes['data-color'] = $color;
$element_attributes['data-delay'] = $delay;
$element_attributes['data-close-delay'] = $close_delay;
$element_attributes['id'] = $id;

if ($aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<nav%1$s>%2$s</nav>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $list_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
