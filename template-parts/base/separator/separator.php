<?php

declare(strict_types=1);

// Direct translation of shadcn/ui's Separator (wraps a headless UI primitive -- historically
// Radix UI, shadcn now also ships Base UI/React Aria variants of many components). That primitive
// has no interactive/JS behaviour at all -- it's just a <div> whose role toggles
// between "separator" (semantic, announced by screen readers) and "none" (purely decorative,
// removed from the accessibility tree) depending on the `decorative` prop, plus a
// `data-orientation` attribute for horizontal/vertical CSS. Native HTML's own <hr> only covers
// the horizontal + always-semantic case, so a plain <div> (matching that primitive's own choice)
// is used here to support both orientations and the decorative toggle faithfully (see CLAUDE.md #1).
//
// Moved into its own template-parts/base/separator/ folder (Regel 4: more than one file) now that
// separator-label.php joins it -- see that file's own header.
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind on the strength of the Claude-Design reference
// "Hengegroup"'s "Basis"/"Stärken"/"Stile" sections (same `.dc.html`-reference workflow as
// kbd.php's/pagination.php's own entries, see docs/entscheidungen.md for this component's entry).
// shadcn's own Separator (registry/new-york-v4/ui/separator.tsx, live-checked 2026-08-30) is a
// single fixed look with no `weight`/`style` prop at all (`bg-border shrink-0 ...`); this project
// deviates on the explicit strength of that reference, same category of deviation as kbd.php's
// `size`/`pressed` additions:
//   - `bg-border` (this project's generic --color-border/neutral-200 role, always 100% opaque)
//     dropped in favour of `bg-foreground` at a low opacity per `weight` step -- the reference's
//     own hairlines are semi-transparent dark (`rgba(30,29,28,0.08|0.12|0.24)` over both light AND
//     dark surfaces, see the dropped "Auf dunklem Grund" section below), not a flat opaque token.
//     Same "Tailwind opacity modifier on an existing role" trick as kbd.php's
//     `border-foreground/15`.
//   - a `weight` scale (thin | default | thick | section) was added -- the reference dedicates a
//     whole "Stärken" section to exactly these 4 named steps ("Fein"/"Standard"/"Kräftig"/
//     "Abschnitt": 1px at 8/12/24% opacity, then 3px at 16% opacity with a full radius). `section`
//     reuses the exact 3px-rounded shape the reference's "Stile" section also uses for its flat
//     "Akzent" bar -- one shared shape, not two.
//   - a `style` scale (solid | dashed | accent | gradient) was added, covering the reference's
//     "Stile" examples that need either a genuinely different CSS technique or a dedicated colour
//     (not just an opacity step):
//       - `dashed` needs `border-*-dashed` instead of a `background-color` fill -- CSS can't dash a
//         solid background. Always renders as a hairline (`weight` ignored, only sensible value the
//         reference itself shows), `border-foreground/28` matching the reference's own dashed
//         opacity.
//       - `accent` is a flat `bg-henge-green` fill -- this project's fixed brand accent colour (see
//         tokens.css), not a configurable colour prop, same fixed-colour choice as pagination.php's
//         own active-page henge-green fill. Appending a colour override via the `class` config
//         instead (e.g. `class: 'bg-henge-green'`) is NOT reliable here (two same-specificity
//         `bg-*` utilities racing for source order, exact caveat kbd-group.php's own header already
//         documents for `class`) -- `style: 'accent'` exists specifically so this one common case
//         doesn't depend on that race.
//       - `gradient` is a fixed three-stop `henge-blue -> henge-green -> henge-grey` brand gradient
//         (on explicit request, 2026-09-04) -- direction follows `orientation`
//         (`bg-gradient-to-r` horizontal, `bg-gradient-to-b` vertical), reusing `weight`'s own
//         thickness/rounded steps the same way `accent` does. A `background-image` gradient paints
//         OVER `background-color`, so this one doesn't hit the `class`-override race the two colour
//         styles above do -- it's a first-class `style` value anyway (rather than left to the
//         `class` passthrough, like the reference's own single-colour "Verlauf"/"Farbverlauf"
//         examples still are, see below) because it was requested as a reusable, nameable look, not
//         a one-off colour tweak.
//   - The reference's OWN "Verlauf"/two "Farbverlauf" examples (single-colour fade, and a
//     green/blue/grey-light 3-stop mix in a different order than the `gradient` style above)
//     deliberately still have NO dedicated config of their own -- reachable through the existing
//     `class` passthrough (e.g. `class: 'bg-gradient-to-r from-henge-green to-transparent'`),
//     same "lean on `class` for one-off colour work" idiom button-group.php's own header documents
//     for its vertical divider override. No broader multi-stop-gradient vocabulary exists anywhere
//     else in this theme to generalise a second dedicated config from (same "no speculative
//     extension" reasoning as data-table.php's single `filter_column`) -- `gradient` above covers
//     the one 3-stop combination actually requested, not every combination the reference happens to
//     show.
//   - The reference's "Auf dunklem Grund" section was NOT ported, same reason as every other
//     component's Phase-2 entry so far (kbd.php/pagination.php/table/*.php) -- this theme has no
//     dark-mode/dark-surface strategy yet, see docs/entscheidungen.md.
//
// Bugfix (2026-09-04): `orientation: 'vertical'` rendered as an invisible 1px-wide, 0px-tall line
// -- `data-[orientation=vertical]:h-full` (shadcn's own upstream technique, unchanged from before
// Phase 2) is a PERCENTAGE height, which only resolves against a definite parent height. A flex row
// sized by its own content (e.g. `flex items-center`, this component's own most common host layout)
// never gives its children a definite height, so `h-full` computed to 0 even inside
// `items-stretch` containers (verified via a real Chrome headless render, not just spec-reading --
// `align-items: stretch`'s "treat auto cross size as stretch" carve-out does NOT extend to an
// explicit percentage height that merely resolves to auto). Fixed by swapping the vertical branch
// to `self-stretch` (an `align-self` override that fills the flex/grid line's cross size directly,
// independent of the parent's own `align-items` and of percentage-height resolution) plus
// `h-auto` instead of `h-full` -- the exact technique button-group.php's own vertical-divider
// override already worked around this with by hand (`self-stretch
// data-[orientation=vertical]:h-auto` in its own `class` config, now redundant/removed there since
// it is the new default here). Vertical separators still need a flex/grid host to have any sibling
// to stretch against (a bare standalone vertical line with no layout context is not a real use
// case shadcn's own reference shows either) -- but no longer need that host to declare an explicit
// height, which was the actual gap.
//
// Supported config:
//   orientation   string   horizontal | vertical (default: horizontal) -- vertical needs a
//                          flex/grid host, see the Bugfix note above
//   decorative    bool     default true. true -> role="none" (purely visual, hidden from
//                          assistive tech); false -> role="separator" (+ aria-orientation for
//                          vertical, since horizontal is the ARIA default and doesn't need it)
//   weight        string   thin | default | thick | section (default: default) -- see file header
//                          above for the px/opacity mapping per step
//   style         string   solid | dashed | accent | gradient (default: solid) -- see file header
//                          above; `dashed` ignores `weight` (always a hairline)
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$orientation = trim((string) ($config['orientation'] ?? 'horizontal'));
$decorative = !array_key_exists('decorative', $config) || !empty($config['decorative']);
$weight = trim((string) ($config['weight'] ?? 'default'));
$style = trim((string) ($config['style'] ?? 'solid'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_orientations = ['horizontal', 'vertical'];

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'horizontal';
}

// weight => [full literal `bg-foreground/<opacity>` class for `style: solid`, thickness step
// (Tailwind spacing scale, no arbitrary px), rounded]. `section`'s 0.75 step is 3px (0.75 * 4px)
// -- same "real scale step, no arbitrary value" rule as button.php's/typography.php's own entries
// in docs/entscheidungen.md. `fill` is written out as a full literal class string per step rather
// than assembled via string concatenation (`'bg-foreground/' . $opacity`) -- Tailwind's build-time
// content scanner only finds class names that appear as complete literal strings somewhere in the
// source, the exact same "dynamically composed name" gap find-lucide-icons.php's own header
// documents for icon names built from a PHP variable.
$weight_map = [
    'thin' => ['fill' => 'bg-foreground/8', 'thickness' => 'px', 'rounded' => false],
    'default' => ['fill' => 'bg-foreground/12', 'thickness' => 'px', 'rounded' => false],
    'thick' => ['fill' => 'bg-foreground/24', 'thickness' => 'px', 'rounded' => false],
    'section' => ['fill' => 'bg-foreground/16', 'thickness' => '0.75', 'rounded' => true],
];

if (!array_key_exists($weight, $weight_map)) {
    $weight = 'default';
}

$allowed_styles = ['solid', 'dashed', 'accent', 'gradient'];

if (!in_array($style, $allowed_styles, true)) {
    $style = 'solid';
}

// Vertical cross-size: `self-stretch` + `h-auto`, NOT `h-full` -- see the Bugfix note in the file
// header above for why the percentage-height version renders invisible.
if ($style === 'dashed') {
    // Always a hairline -- `border-*-dashed` (not a `background-color` fill), `weight` doesn't
    // apply, see file header above.
    $variant_classes =
        'data-[orientation=horizontal]:h-0 data-[orientation=horizontal]:w-full ' .
        'data-[orientation=horizontal]:border-t data-[orientation=vertical]:h-auto ' .
        'data-[orientation=vertical]:self-stretch data-[orientation=vertical]:w-0 ' .
        'data-[orientation=vertical]:border-l border-dashed border-foreground/28';
} else {
    $weight_config = $weight_map[$weight];
    $thickness_classes =
        $weight_config['thickness'] === 'px'
            ? 'data-[orientation=horizontal]:h-px data-[orientation=vertical]:w-px'
            : 'data-[orientation=horizontal]:h-0.75 data-[orientation=vertical]:w-0.75';

    if ($style === 'accent') {
        $fill_class = 'bg-henge-green';
    } elseif ($style === 'gradient') {
        // Direction follows orientation -- literal per-orientation class pair (not assembled via
        // concatenation), same static-scanner reasoning as `$weight_map`'s `fill` above.
        $fill_class =
            'data-[orientation=horizontal]:bg-gradient-to-r ' .
            'data-[orientation=vertical]:bg-gradient-to-b ' .
            'from-henge-blue via-henge-green to-henge-grey';
    } else {
        $fill_class = $weight_config['fill'];
    }

    $variant_classes = trim(
        $thickness_classes .
            ' data-[orientation=horizontal]:w-full data-[orientation=vertical]:h-auto ' .
            'data-[orientation=vertical]:self-stretch ' .
            $fill_class .
            ($weight_config['rounded'] ? ' rounded-full' : ''),
    );
}

$base_classes = "shrink-0 {$variant_classes}";

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'separator';
$element_attributes['data-orientation'] = $orientation;
$element_attributes['data-weight'] = $weight;
$element_attributes['data-style'] = $style;

if ($decorative) {
    $element_attributes['role'] = 'none';
} else {
    $element_attributes['role'] = 'separator';

    if ($orientation === 'vertical') {
        $element_attributes['aria-orientation'] = 'vertical';
    }
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<div%s></div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
