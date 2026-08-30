<?php

declare(strict_types=1);

// Headings and body text share ONE continuous variant scale instead of two separate size systems,
// each stop has a fixed default HTML tag -- same structural idea as shadcn/ui's classic Typography
// scale, but as of 2026-08-30 (on explicit request) this project's OWN size-only vocabulary
// (`headline-lg/base/sm/xs`/`body-lg/base/sm/xs`) instead of shadcn's own h1-h4/p/lead/large/
// small/muted naming -- see docs/entscheidungen.md for why the switch (Tailwind's fixed size
// scale has a gap between `text-4xl`/36px and `text-5xl`/48px that made two of shadcn's five
// heading-ish stops collide once mapped to real Tailwind classes, so the scale itself was
// redesigned around four heading + four body stops instead of patching around that gap).
//
// IMPORTANT: `variant` (visual appearance) and `tag` (semantic HTML element) are independent axes
// on purpose, on explicit request -- a `headline-base`-sized heading can render as a `<h4>` (or any
// allowed tag) when the semantic outline requires a different level than the desired visual
// weight, and vice versa. Never assume `variant` implies a fixed tag beyond the DEFAULT `tag`
// shown below when none is given; always pass `tag` explicitly when the semantic level and the
// visual size differ.
//
// No `clamp()`/viewport-fluid sizing here on explicit request, even though the reference design
// (see docs/entscheidungen.md) itself uses real CSS `clamp()` for its hero headings -- plain
// Tailwind size stops instead (`text-6xl`/`text-5xl`/...), no responsive step per variant. Add a
// fluid variant later if actually needed, not speculatively now.
//
// Supported config:
//   text / content   string   visible content (required, nothing renders without it)
//   variant          string   headline-lg | headline-base | headline-sm | headline-xs | body-lg |
//                              body-base | body-sm | body-xs (default: body-base) --
//                              this project's own size-only vocabulary (see file header), NOT
//                              shadcn's h1-h4/p/lead/large/small/muted naming
//   tag              string   optional tag override, validated against an allow-list; falls back
//                              to the variant's own default tag (not hard-coded to <p>) when
//                              invalid or omitted -- see the variant/tag independence note above
//   color            string   default | light | neutral (default: default) -- `default` adds no
//                              class (inherited from the site-wide `body` rule's
//                              `text-foreground`); `light` adds `text-grey-light` (this project's
//                              "weisser Text auf dunklem Grund" brand convention, see tokens.css);
//                              `neutral` adds `text-muted-foreground` (replaces shadcn's own
//                              baked-in `muted` variant color -- there is no dedicated `muted`
//                              size variant here anymore, use any variant + `color: 'neutral'`
//                              instead). Appended AFTER the variant's own classes (plain string
//                              concat, no tailwind-merge/cn() equivalent available in PHP, same
//                              caveat as button.php's/badge.php's `class`)
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
// Note: `weight_variant` from the old headline.php/text.php is intentionally dropped -- each
// variant bakes in its own weight rather than exposing it as a separate axis, and no template
// consumed the old prop yet, so this is a simplification, not a breaking change.

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ($config['content'] ?? '')));
$variant = trim((string) ($config['variant'] ?? 'body-base'));
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
    'headline-lg' => 'h1',
    'headline-base' => 'h2',
    'headline-sm' => 'h3',
    'headline-xs' => 'h4',
    'body-lg' => 'p',
    'body-base' => 'p',
    'body-sm' => 'p',
    'body-xs' => 'small',
];

if (!array_key_exists($variant, $variant_default_tags)) {
    $variant = 'body-base';
}

$allowed_tags = ['p', 'span', 'div', 'strong', 'em', 'small', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

if (!in_array($tag, $allowed_tags, true)) {
    $tag = $variant_default_tags[$variant];
}

$allowed_colors = ['default', 'light', 'neutral'];

if (!in_array($color, $allowed_colors, true)) {
    $color = 'default';
}

// Fixed size-only scale (no fluid/clamp() step, see file header), explicit request 2026-08-30:
// four heading stops (`headline-lg/base/sm/xs`) at Tailwind's own `text-6xl/5xl/4xl/3xl`, four body stops
// (`body-lg/base/sm/xs`) at `text-2xl/lg/base/sm` -- see docs/entscheidungen.md for the
// full size derivation (incl. why `text-xl`/22px isn't used: Tailwind's scale has no stop there
// either, `text-2xl`/24px was chosen for a smoother step down from `headline-xs`/30px). Headings
// share one weight/leading (`font-semibold leading-tight`); body stops stay at the browser/
// Tailwind default weight (400) with `leading-normal` -- neither was part of the explicit size
// request, kept deliberately uniform rather than guessing a per-stop weight scale that wasn't
// asked for.
$variant_classes = [
    'headline-lg' => 'text-6xl font-semibold leading-tight',
    'headline-base' => 'text-5xl font-semibold leading-tight',
    'headline-sm' => 'text-4xl font-semibold leading-tight',
    'headline-xs' => 'text-3xl font-semibold leading-tight',
    'body-lg' => 'text-2xl leading-normal',
    'body-base' => 'text-lg leading-normal',
    'body-sm' => 'text-base leading-normal',
    'body-xs' => 'text-sm leading-normal',
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
