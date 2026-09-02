<?php

declare(strict_types=1);

// shadcn/ui's Accordion wraps a headless UI primitive (historically Radix UI; shadcn now also
// ships Base UI/React Aria variants of many components): JS-driven open/onValueChange state,
// manually wired ARIA (button[aria-expanded] + region[aria-labelledby]). Our PHP/no-JS equivalent
// uses native <details>/<summary> instead: the HTML Living Standard's `name` attribute on
// <details> makes the browser enforce "only one open at a time" natively -- zero JavaScript
// required for shadcn's `type="single"` behaviour (this theme has no JS framework wired up, see
// assets/js/ -- vanilla modules only, no Alpine/similar).
//
// <details>/<summary> already carry correct native disclosure semantics (implicit ARIA, native
// keyboard/focus handling), so no additional role/aria-expanded is added on top -- that would be
// redundant/conflicting ARIA on an already-semantic native element. Any styling for the open/closed
// look must target the native `[open]` attribute -- NOT a custom data-state, which would go stale
// after the user's first click since there is no JS to keep it in sync. Concretely, every
// `<details>` below carries Tailwind's own `group` class so the chevron can react via its built-in
// `group-open:` variant (compiles to a `[open]`-attribute selector under the hood, same target the
// prose above always meant, just authored as a Tailwind utility instead of hand-written project CSS
// now that Phase 2 puts classes directly in this file, same convention as every other styled base
// component).
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind, classes adapted from shadcn's own
// AccordionItem/-Trigger/-Content (registry/new-york-v4/ui/accordion.tsx, live-checked 2026-09-02)
// with the deviations the design reference below calls for:
//   - `data-[state=open]` (Radix, JS-driven) has no equivalent here -- replaced by the `group`/
//     `group-open:` mechanism described above, applied directly on the chevron `<svg>` itself
//     (`group-open:rotate-180`) rather than shadcn's `[&[data-state=open]>svg]` sibling selector.
//   - open/close height animation (shadcn's `data-[state=]:animate-accordion-*`, driven by a
//     Radix-measured CSS var) has no JS-free equivalent -- covered instead by a native CSS-only
//     technique targeting the `::details-content` pseudo-element (the box the UA now generates
//     around every non-`<summary>` child of a `<details>`, Baseline since ~2025) combined with
//     `interpolate-size` so `height` can transition all the way to/from its `auto` value. This is
//     a documented Regel-1 raw-CSS exception (assets/css/app.css, `@layer components`, scoped to
//     `[data-slot="accordion-item"]`) rather than Tailwind utilities: the effect spans two
//     different selectors (the property enabling `auto`-interpolation lives on the `<details>`
//     itself, the transition lives on its `::details-content`) for one cohesive animation, and
//     expressing every piece (the pseudo-element target, `interpolate-size`, `transition-behavior:
//     allow-discrete`) via nested Tailwind arbitrary-property/-variant brackets stopped reading as
//     "using Tailwind" and started reading as CSS smuggled through brackets -- exactly the case
//     Regel 1 carves the exception out for. Progressive enhancement: browsers without
//     `::details-content` support just keep the native instant snap, nothing breaks. Deliberately
//     scoped to accordion items only, NOT `<details>` in general -- dropdown-menu.php also builds
//     on native `<details>`, but its content floats (`position: absolute`) and animating its
//     height would fight that positioning, so the CSS rule is attribute-scoped to
//     `[data-slot="accordion-item"]` rather than a bare `details` selector. Only the chevron
//     rotation, the panel height, and hover/focus colors transition -- nothing else animates.
//   - hover state recolors the trigger label (`hover:text-henge-green`) instead of shadcn's
//     `hover:underline` -- design reference below uses color, not underline, as the affordance.
//   - trigger type scale bumped to `text-lg font-semibold` (shadcn: `text-sm font-medium`) and
//     `gap-5`/`py-5` (shadcn: `gap-4`/`py-4`) to match the reference; content gets `text-base`/
//     `leading-relaxed` (shadcn: `text-sm`) for the same reason, plus `pr-11` reserving room so
//     wrapped body text never runs under the chevron column.
//   - items get a full top+bottom divider box (wrapper `border-t` + every item `border-b`,
//     including the last one) instead of shadcn's `border-b last:border-b-0` -- a deliberate
//     design-reference choice (a fully enclosed list), not an oversight.
//   - `color` (see config below) is this component's own addition, not a shadcn prop -- covers the
//     "FAQ auf dunklem Grund" look from the design reference, mirroring typography.php's own
//     `color: light` vocabulary ("weisser Text auf dunklem Grund" brand convention, see
//     tokens.css) for the same concept. Only text/border colors adapt; the dark card surface
//     itself (background/radius/padding) is the caller's job (e.g. wrap in card.php or a plain
//     styled div), same component-boundary convention as every other base part not self-wrapping
//     in a card.
//   - the reference's dark-surface variant additionally puts the chevron in a filled circular
//     badge and picks a bespoke lighter accent for its hover state; simplified here to the same
//     bare chevron and single `henge-green` brand accent as the default look (still passes
//     WCAG AA at this size/weight against the dark surface) -- one fewer one-off visual pattern
//     for the design system to carry, not a fidelity gap the reference's own values couldn't
//     otherwise justify.
//
// Design reference: https://claude.ai/code/artifact/3071409a-c4e3-4f19-afeb-e8b488d2d1d2
// ("Basis" section for the default look, "FAQ auf dunklem Grund" for `color: light`).
//
// Supported config:
//   type          string   single | multiple (default: single) -- mirrors the Radix UI variant of
//                           shadcn's Accordion; re-checked live, the current Base UI default track
//                           has replaced this with a boolean `multiple` prop instead (no `type` at
//                           all). Kept as `type: single|multiple` here regardless, since it maps
//                           cleanly onto this component's actual implementation technique --
//                           whether one or several <details> share a `name` attribute -- and existing
//                           callers already use this vocabulary. "single": only one item open at a
//                           time (native <details name="..."> grouping). "multiple": items toggle
//                           independently (no name attribute).
//   items         array    required, list of item configs:
//     trigger         string   required. Visible trigger label (plain text, escaped)
//     content         string   required. Pre-rendered HTML for the panel body (caller's
//                                responsibility to escape/build, e.g. via typography.php --
//                                same convention as aspect-ratio.php's `content`)
//     value           string   optional. Rendered as the <details> `id` (deep-linking/CSS/JS
//                                hook); auto-generated via wp_unique_id() when omitted
//     default_open    bool     optional. Sets the native `open` attribute (default: false)
//     class           string   passthrough onto this item's <details>
//   heading_tag   string   '' | h2 | h3 | h4 | h5 | h6 (default: ''). When set, wraps the trigger
//                           label in that heading tag inside <summary> by nesting
//                           template-parts/base/typography.php -- the recommended a11y pattern
//                           for FAQ-style accordions (WAI-ARIA APG Accordion Pattern)
//   icon          array|false   icon.php config for the trigger's chevron indicator (default:
//                           ['name' => 'chevron-down', 'set' => 'lucide']); pass false to omit
//                           and rely on the native <summary> marker instead
//   color         string   default | light (default: default) -- same vocabulary/meaning as
//                           typography.php's own `color` config: "light" recolors text/borders for
//                           placement on a dark/anthracite surface (see design reference above),
//                           it does NOT paint that surface itself (see Phase 2 note above)
//   class / attributes / data_attributes   passthrough onto the outer wrapper
//                           (<div data-slot="accordion">)
//
// Note: no per-item `disabled` yet -- <details>/<summary> has no native disabled attribute, and
// emulating it without JS (pointer-events:none + tabindex="-1" + matching CSS) has no concrete
// consumer yet. Deferred extension point, same spirit as image.php's attachment_id note.

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$type = trim((string) ($config['type'] ?? 'single'));
$heading_tag = strtolower(trim((string) ($config['heading_tag'] ?? '')));
$color = trim((string) ($config['color'] ?? 'default'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];
$items_config = is_array($config['items'] ?? null) ? $config['items'] : [];

$allowed_types = ['single', 'multiple'];

if (!in_array($type, $allowed_types, true)) {
    $type = 'single';
}

$allowed_heading_tags = ['', 'h2', 'h3', 'h4', 'h5', 'h6'];

if (!in_array($heading_tag, $allowed_heading_tags, true)) {
    $heading_tag = '';
}

$allowed_colors = ['default', 'light'];

if (!in_array($color, $allowed_colors, true)) {
    $color = 'default';
}

if (array_key_exists('icon', $config) && $config['icon'] === false) {
    $icon_config = null;
} elseif (is_array($config['icon'] ?? null)) {
    $icon_config = $config['icon'];
} else {
    $icon_config = ['name' => 'chevron-down', 'set' => 'lucide'];
}

// Normalize + filter items; trigger and content are required, invalid items are silently
// skipped (fail-soft, consistent with the rest of the base components).
$items = [];

foreach ($items_config as $item_config) {
    if (!is_array($item_config)) {
        continue;
    }

    $trigger_text = trim((string) ($item_config['trigger'] ?? ''));
    $content = (string) ($item_config['content'] ?? '');

    if ($trigger_text === '' || trim($content) === '') {
        continue;
    }

    $items[] = [
        'trigger' => $trigger_text,
        'content' => $content,
        'value' => trim((string) ($item_config['value'] ?? '')),
        'default_open' => !empty($item_config['default_open']),
        'class' => trim((string) ($item_config['class'] ?? '')),
    ];
}

if ($items === []) {
    return;
}

$group_name = 'hengegroup-theme-accordion-' . wp_unique_id();

// Per-`color` computed classes (see Phase 2 note above): wrapper/item share the same divider
// color, trigger/content/icon each get their own text color. Hover accent is deliberately the
// same `henge-green` brand token for both -- see Phase 2 note above.
$color_classes = [
    'default' => [
        'divider' => 'border-border',
        'trigger_text' => 'text-foreground',
        'content_text' => 'text-muted-foreground',
        'icon_text' => 'text-muted-foreground',
    ],
    'light' => [
        'divider' => 'border-grey-light/20',
        'trigger_text' => 'text-grey-light',
        'content_text' => 'text-grey-light/80',
        'icon_text' => 'text-grey-light/60',
    ],
];

$divider_class = $color_classes[$color]['divider'];

$render_heading = static function (string $tag, string $text): string {
    ob_start();
    get_template_part('template-parts/base/typography', null, [
        // body-base is font-normal by default (see typography.php) -- an accordion trigger
        // needs to stand out as a heading, hence the added emphasis here. Matches the plain-text
        // (no heading_tag) trigger's own `text-lg font-semibold` below so the visible look stays
        // identical either way.
        'config' => [
            'variant' => 'body-base',
            'tag' => $tag,
            'text' => $text,
            'class' => 'font-semibold',
        ],
    ]);

    return (string) ob_get_clean();
};

if ($icon_config !== null) {
    $icon_class = trim(
        'pointer-events-none size-4 shrink-0 transition-transform duration-200 group-open:rotate-180 ' .
            $color_classes[$color]['icon_text'] .
            (isset($icon_config['class']) ? ' ' . trim((string) $icon_config['class']) : ''),
    );
    $icon_config['class'] = $icon_class;
    $icon_markup = hengegroup_theme_render_icon($icon_config);
} else {
    $icon_markup = '';
}

// The native <summary> marker is only suppressed when an icon takes over as the open/closed
// indicator -- with `icon: false` the marker IS the indicator (see config docs above), so hiding
// it there would leave the trigger with no affordance at all.
$trigger_base_class =
    'flex flex-1 cursor-pointer items-center justify-between gap-5 rounded-md py-5 text-left ' .
    'text-lg font-semibold outline-none transition-colors hover:text-henge-green ' .
    'focus-visible:ring-[3px] focus-visible:ring-ring/50 ' .
    $color_classes[$color]['trigger_text'];

if ($icon_config !== null) {
    $trigger_base_class .= ' list-none [&::-webkit-details-marker]:hidden';
}

$content_class = trim(
    'overflow-hidden pb-5 text-base leading-relaxed ' .
        $color_classes[$color]['content_text'] .
        ($icon_config !== null ? ' pr-11' : ''),
);

$items_markup = '';

foreach ($items as $item) {
    $item_value =
        $item['value'] !== ''
            ? $item['value']
            : 'hengegroup-theme-accordion-item-' . wp_unique_id();

    $trigger_markup =
        $heading_tag !== ''
            ? $render_heading($heading_tag, $item['trigger'])
            : esc_html($item['trigger']);

    $details_attributes = [];

    if ($type === 'single') {
        $details_attributes['name'] = $group_name;
    }

    $details_attributes['data-slot'] = 'accordion-item';
    $details_attributes['id'] = $item_value;
    $details_attributes['class'] = trim(
        'group border-b ' . $divider_class . ($item['class'] !== '' ? ' ' . $item['class'] : ''),
    );

    if ($item['default_open']) {
        $details_attributes['open'] = true;
    }

    $items_markup .= sprintf(
        '<details%1$s><summary%2$s>%3$s%4$s</summary><div%5$s>%6$s</div></details>',
        hengegroup_theme_render_attributes($details_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        hengegroup_theme_render_attributes([
            'data-slot' => 'accordion-trigger',
            'class' => $trigger_base_class,
        ]), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $icon_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        hengegroup_theme_render_attributes([
            'data-slot' => 'accordion-content',
            'class' => $content_class,
        ]), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $item['content'], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$wrapper_attributes = $attributes;
$wrapper_attributes['class'] = trim(
    'border-t ' . $divider_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$wrapper_attributes['data-slot'] = 'accordion';
$wrapper_attributes['data-type'] = $type;
$wrapper_attributes['data-color'] = $color;

foreach ($data_attributes as $name => $value) {
    $data_name = trim((string) $name);

    if ($data_name === '') {
        continue;
    }

    $wrapper_attributes['data-' . $data_name] = $value;
}

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $items_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
