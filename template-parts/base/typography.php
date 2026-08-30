<?php

declare(strict_types=1);

// API modeled on shadcn/ui's classic Typography scale: headings and body text share ONE
// continuous variant scale instead of two separate size systems, each stop has a fixed default
// HTML tag. Now with real Tailwind styling (Phase 2, CLAUDE.md Regel 1) -- font-size/-weight/
// -leading per `data-variant` taken from a consolidated read of this project's own Startseite/
// Karriere/Karrieredetail/Produkte/Produktdetail/Anwendungen/Downloads reference design (analyzed
// live 2026-08-30), not shadcn's stock scale 1:1 (unlike button.php/badge.php, shadcn's own
// Typography sizes/weights don't appear anywhere in that reference) -- see
// docs/entscheidungen.md for the full per-variant size derivation and the deliberate
// consolidation calls (the reference isn't a strict token system; several observed sizes had to
// be folded into one variant's responsive range).
//
// IMPORTANT: `variant` (visual appearance) and `tag` (semantic HTML element) are independent axes
// on purpose, on explicit request -- an `h2`-sized heading can render as a `<h4>` (or any allowed
// tag) when the semantic outline requires a different level than the desired visual weight, and
// vice versa. Never assume `variant` implies a fixed tag beyond the DEFAULT `tag` shown below when
// none is given; always pass `tag` explicitly when the semantic level and the visual size differ.
//
// No `clamp()`/viewport-fluid sizing here on explicit request, even though the reference design
// itself uses real CSS `clamp()` for its hero headings -- plain Tailwind breakpoints
// (`sm:`/`md:`/`lg:`) instead, so H1/H2 step at fixed breakpoints rather than scaling continuously
// with the viewport. Add a fluid variant later if actually needed, not speculatively now.
//
// Note (re-checked live as of an audit of this theme's components against shadcn's docs): both
// /docs/components/typography AND /docs/components/base/typography now show shadcn's newer,
// philosophically different "Typeset" system (CSS custom properties --
// --typeset-size/--typeset-leading/--typeset-flow -- instead of a fixed data-variant enum) -- the
// classic h1/h2/h3/h4/p/lead/large/small/muted scale this file mirrors no longer has a live,
// verifiable shadcn reference page as of this check. This file remains a faithful implementation
// of that last-known scale (now with this project's own sizing, see above), not a bug -- but
// there is currently no shadcn URL to re-verify it against; Typeset is worth being aware of as a
// possible, deliberately different future direction, not something to silently retrofit here.
//
// Supported config:
//   text / content   string   visible content (required, nothing renders without it)
//   variant          string   h1 | h2 | h3 | h4 | p | lead | large | small | muted
//                              (default: p) — shadcn's typography scale, this project's own sizing
//                              (see file header)
//   tag              string   optional tag override, validated against an allow-list; falls back
//                              to the variant's own default tag (not hard-coded to <p>) when
//                              invalid or omitted -- see the variant/tag independence note above
//   color            string   default | light | neutral (default: default) — a project addition on
//                              top of shadcn's typography vocabulary (shadcn's own scale has no
//                              separate color axis). `default` adds no class (inherited from the
//                              site-wide `body` rule's `text-foreground`, except `muted`, which
//                              bakes in its own muted color like shadcn's stock variant does);
//                              `light` adds `text-grey-light` (this project's "weisser Text auf
//                              dunklem Grund" brand convention, see tokens.css); `neutral` adds
//                              `text-muted-foreground`. Appended AFTER the variant's own classes
//                              (plain string concat, no tailwind-merge/cn() equivalent available in
//                              PHP, same caveat as button.php's/badge.php's `class`) -- reliable
//                              for `muted` + `light`/`neutral` (only one of the two ever bakes in a
//                              color), not guaranteed to win a real conflict otherwise
//   accent_words     array    words to highlight, rendered via hengegroup_theme_render_accent_text()
//   data_slot        string   overrides the root `data-slot` value (default: 'typography') --
//                              same composing-parent escape hatch as input.php's/textarea.php's/
//                              label.php's `data_slot`; card.php requests 'card-title'/
//                              'card-description' here instead of duplicating this file's
//                              attribute-building logic. Leave unset for standalone
//                              use.
//   class            string   appended AFTER the computed variant/color classes (plain string
//                              concat, no tailwind-merge/cn() equivalent available in PHP) -- same
//                              caveat as button.php's/badge.php's `class`
//   attributes / data_attributes   passthrough, as in the other base parts
//
// Note: `weight_variant` from the old headline.php/text.php is intentionally dropped — shadcn's
// typography scale bakes weight into each variant rather than exposing it as a separate axis, and
// no template consumed the old prop yet, so this is a simplification, not a breaking change.

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ($config['content'] ?? '')));
$variant = trim((string) ($config['variant'] ?? 'p'));
$tag = strtolower(trim((string) ($config['tag'] ?? '')));
$color = trim((string) ($config['color'] ?? 'default'));
$data_slot = trim((string) ($config['data_slot'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$accent_words = is_array($config['accent_words'] ?? null) ? $config['accent_words'] : [];
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($text === '') {
    return;
}

if ($data_slot === '') {
    $data_slot = 'typography';
}

$variant_default_tags = [
    'h1' => 'h1',
    'h2' => 'h2',
    'h3' => 'h3',
    'h4' => 'h4',
    'p' => 'p',
    'lead' => 'p',
    'large' => 'div',
    'small' => 'small',
    'muted' => 'p',
];

if (!array_key_exists($variant, $variant_default_tags)) {
    $variant = 'p';
}

$allowed_tags = ['p', 'span', 'div', 'strong', 'em', 'small', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

if (!in_array($tag, $allowed_tags, true)) {
    $tag = $variant_default_tags[$variant];
}

$allowed_colors = ['default', 'light', 'neutral'];

if (!in_array($color, $allowed_colors, true)) {
    $color = 'default';
}

// Sizes/weights/leadings below are this project's own consolidated reading of the reference
// design (see file header) -- NOT shadcn's stock Typography scale. Two deliberate consolidations,
// both on explicit request:
//   - h1/h2 use plain Tailwind breakpoints instead of the reference's own CSS clamp() (see file
//     header) -- h1 covers the reference's observed 38-64px hero-title range across sm:/lg:, h2
//     covers its observed 26-42px range (both "Ansprechpartner"-style box titles AND full
//     "Produkte"/"Kontakt" section titles turned out to be the same variant at different
//     breakpoints, not two separate variants -- see docs/entscheidungen.md).
//   - h3/h4 stay fixed-size (no responsive step) -- the reference uses them consistently at one
//     size regardless of viewport (h3: 19px/800, e.g. card.php's card-title; h4: 17px/700).
$variant_classes = [
    'h1' => 'text-4xl font-semibold leading-[1.1] sm:text-5xl lg:text-6xl',
    'h2' => 'text-3xl font-semibold leading-[1.15] md:text-4xl',
    'h3' => 'text-[19px] font-extrabold leading-tight',
    'h4' => 'text-[17px] font-bold leading-tight',
    'p' => 'text-base leading-7',
    // Deliberately NOT shadcn's stock `text-xl text-muted-foreground` -- this project's `color`
    // axis (see above) already owns text color independently, the reference's own lead paragraphs
    // are full-opacity text (dark or light depending on section), never dimmed/muted.
    'lead' => 'text-lg leading-relaxed md:text-xl',
    'large' => 'text-lg font-semibold',
    'small' => 'text-sm leading-none font-medium',
    // Unlike the other variants, `muted` DOES bake in its own color (shadcn's own convention for
    // this specific variant) -- it's the one variant whose whole point is a dimmed color, not just
    // a size/weight. Replaces the reference's own opacity:0.6/0.7 tricks (e.g. job-location text)
    // with this project's actual --color-muted-foreground token instead.
    'muted' => 'text-sm text-muted-foreground',
];

$color_classes = [
    'default' => '',
    'light' => 'text-grey-light',
    'neutral' => 'text-muted-foreground',
];

$computed_class = $variant_classes[$variant];

if ($color_classes[$color] !== '') {
    $computed_class .= ' ' . $color_classes[$color];
}

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['data-slot'] = $data_slot;
$element_attributes['data-variant'] = $variant;
$element_attributes['data-color'] = $color;

foreach ($data_attributes as $name => $value) {
    $data_name = trim((string) $name);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $value;
}

printf(
    '<%1$s%2$s>%3$s</%1$s>',
    esc_html($tag),
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    hengegroup_theme_render_accent_text($text, $accent_words), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
