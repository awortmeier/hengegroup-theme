<?php

declare(strict_types=1);

// Direct translation of shadcn/ui's Skeleton -- checked live against the current source
// (CLAUDE.md Regel 2), which is deliberately tiny, no headless UI primitive involved at all:
//
//   function Skeleton({ className, ...props }: React.ComponentProps<"div">) {
//     return (
//       <div data-slot="skeleton" className={cn("animate-pulse rounded-md bg-accent", className)} {...props} />
//     )
//   }
//
// Phase 1 rendered NO Tailwind classes of its own at all (Regel 1) -- same `[data-slot="skeleton"]`-
// keyed project-CSS deferral spinner.php's own Phase 1 already established. Phase 2 (this file) is
// styled on the strength of the Claude-Design reference "Hengegroup"
// (https://claude.ai/artifact/AgUuYjLPw8bHzEXojvtqyj, "Basis"/"Formen"/"Auf dunklem Grund" sections),
// with these deliberate deviations from the shadcn snippet above:
//   - `shape` (new config, see below) -- shadcn's Skeleton has no shape concept at all, every
//     instance is the same undifferentiated `rounded-md` box sized entirely via a caller-supplied
//     `class` (shadcn's own docs example: `<Skeleton className="h-[20px] w-[100px] rounded-full" />`).
//     The reference's own "Formen" section explicitly names four recurring shapes instead ("Der
//     Radius folgt dem Element, das ersetzt wird" -- the radius follows the element being replaced),
//     each with its own sensible default size/radius a caller can still override via `class` (same
//     passthrough-wins-by-source-order convention as spinner.php's own `class` handling): `line`
//     (a text line, `rounded-full`, thin), `block` (an image/media rectangle, shadcn's own literal
//     `rounded-md`, the closest analog to shadcn's undifferentiated default), `circle` (an avatar,
//     `rounded-full`, square aspect), `pill` (a button, `rounded-full`, `h-8` matching button.php's
//     own `base` height). `line` is this file's default -- the single most common skeleton use
//     (a placeholder text line) and the least visually intrusive fallback if a caller omits `shape`.
//   - `animate-pulse` replaced with a moving-highlight sweep (raw CSS exception, see app.css's own
//     `hg-skeleton-shimmer` header comment for the full rationale) -- the reference is explicit that
//     this is a highlight SWEEPING across the shape ("Ein heller Streifen läuft von rechts nach
//     links"), not shadcn's own opacity pulse; same "the reference needs a technique shadcn/Tailwind
//     doesn't ship" situation spinner.php's own ring replacement and progress.php's own striped fill
//     already document, not invented here. `motion-reduce:animate-none` added alongside it -- this is
//     the first Phase-2 component to introduce a genuinely new animation, and docs/to-do.md's own
//     accessibility section already asks for reduced-motion to be "von Anfang an" considered for any
//     new Phase-2 animation rather than retrofitted later; Tailwind's stock `motion-reduce:` variant
//     covers this without needing the broader reduced-motion CSS-token work that to-do item otherwise
//     tracks (see docs/entscheidungen.md for this file's own entry).
//   - `color` (new config, see below) -- same `default | light` vocabulary/meaning as
//     accordion.php's/typography.php's own `color` config: "light" adapts the shape's own fill/
//     highlight for placement on a dark/anthracite surface (the reference's own "Auf dunklem Grund"
//     section: "Aufgehellte Flächen statt abgedunkelter, der Streifen läuft mit geringerer
//     Deckkraft"). It does NOT paint that dark surface itself -- that stays the caller's job (e.g.
//     wrap in a dark card/section), same component-boundary convention accordion.php's own header
//     comment already documents for its own `color: light`.
//   - the reference's "Karte"/"Liste und Tabelle"/"Übergang" sections are all this file composed by
//     a caller into a card/table/toggle-comparison layout, not a structurally different variant of
//     Skeleton itself (same "the reference's sections are composition, not new component shapes"
//     reading as spinner.php's own "Im Kontext" section) -- demonstrated in
//     page-component-showcase-skeleton.php, nothing new here.
//
// Accessibility (not part of shadcn's own source, which has none -- but every visual component in
// this theme gets a11y config from the start, CLAUDE.md Regel 5): a lone skeleton shape carries no
// real content, so it defaults to `decorative: true` (aria-hidden="true"), same vocabulary/default
// as icon.php/separator.php. Real skeleton screens render many shapes per loading region --
// announcing each one individually would be repetitive screen-reader noise, so this default is
// deliberate, not an oversight. `decorative: false` switches to `role="status"` + `aria-label`
// instead, same pairing as spinner.php -- for the rarer case of a single standalone skeleton
// block used without any other status text nearby to announce the loading state.
//
// Supported config:
//   shape          string   line (default) | block | circle | pill -- see Phase 2 note above; sets
//                           the shape's default size + radius, both overridable via `class`
//   color          string   default (default) | light -- see Phase 2 note above
//   decorative     bool     default true. true -> aria-hidden="true" (silent, the common case for
//                           multi-shape skeleton screens, see above); false -> role="status" +
//                           aria-label
//   aria_label     string   accessible name announced via role="status" when `decorative` is
//                           false. Default: 'Loading' (matches spinner.php's own default),
//                           localized. Ignored when `decorative` is true
//   class / attributes / data_attributes   passthrough onto the rendered <div>, appended after this
//                           file's own computed classes (source-order wins, same convention as
//                           spinner.php's own `class` handling) -- this is how a caller resizes/
//                           reshapes an individual instance beyond its `shape` default (see the
//                           shadcn example above)

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$shape = trim((string) ($config['shape'] ?? 'line'));
$color = trim((string) ($config['color'] ?? 'default'));
$decorative = !array_key_exists('decorative', $config) || !empty($config['decorative']);
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_shapes = ['line', 'block', 'circle', 'pill'];

if (!in_array($shape, $allowed_shapes, true)) {
    $shape = 'line';
}

$allowed_colors = ['default', 'light'];

if (!in_array($color, $allowed_colors, true)) {
    $color = 'default';
}

if ($aria_label === '') {
    // Translate only, don't escape here -- like spinner.php, escaping happens once, at render
    // time, via hengegroup_theme_render_attributes().
    $aria_label = __('Loading', 'hengegroup-theme');
}

// Shape -> size/radius map (Phase 2, see file header): each shape's own sensible default, all
// overridable via `class` (source-order wins, appended below).
$shape_classes = [
    'line' => 'h-3 w-full rounded-full',
    'block' => 'h-32 w-full rounded-md',
    'circle' => 'size-10 rounded-full',
    'pill' => 'h-8 w-20 rounded-full',
];

// Color -> fill map (Phase 2, see file header): `default` is shadcn's own literal `bg-accent`
// role; `light` swaps in this project's near-white brand grey (tokens.css's `--color-grey-light`,
// same token accordion.php's own `color: light` already reuses) for placement on a dark surface.
$color_classes = [
    'default' => 'bg-accent',
    'light' => 'bg-grey-light/15',
];

// `hg-skeleton-shimmer` (app.css): the moving-highlight sweep replacing shadcn's `animate-pulse`,
// see the file header above. `motion-reduce:animate-none` is a stock Tailwind variant, no raw CSS
// needed for it.
$computed_class = trim(
    "{$shape_classes[$shape]} {$color_classes[$color]} " .
        'animate-[hg-skeleton-shimmer_1.8s_ease-in-out_infinite] motion-reduce:animate-none',
);

$element_attributes = $attributes;

$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['data-slot'] = 'skeleton';
$element_attributes['data-shape'] = $shape;
$element_attributes['data-color'] = $color;

if ($decorative) {
    $element_attributes['aria-hidden'] = 'true';
} else {
    $element_attributes['role'] = 'status';
    $element_attributes['aria-label'] = $aria_label;
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<div%s></div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
