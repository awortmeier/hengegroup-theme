<?php

declare(strict_types=1);

// API modeled on shadcn/ui's classic Typography scale: headings and body text share ONE
// continuous variant scale instead of two separate size systems, each stop has a fixed default
// HTML tag. Intentionally unstyled like button.php: no Tailwind/utility classes are generated
// here, only data-attributes — the actual CSS per data-variant/data-color combination is added
// separately in the project stylesheet (not part of this file).
//
// Note (re-checked live as of an audit of this theme's components against shadcn's docs): both
// /docs/components/typography AND /docs/components/base/typography now show shadcn's newer,
// philosophically different "Typeset" system (CSS custom properties --
// --typeset-size/--typeset-leading/--typeset-flow -- instead of a fixed data-variant enum) -- the
// classic h1/h2/h3/h4/p/lead/large/small/muted scale this file mirrors no longer has a live,
// verifiable shadcn reference page as of this check. This file remains a faithful implementation
// of that last-known scale, not a bug -- but there is currently no shadcn URL to re-verify it
// against; Typeset is worth being aware of as a possible, deliberately different future
// direction, not something to silently retrofit here.
//
// Supported config:
//   text / content   string   visible content (required, nothing renders without it)
//   variant          string   h1 | h2 | h3 | h4 | p | lead | large | small | muted
//                              (default: p) — shadcn's typography scale
//   tag              string   optional tag override, validated against an allow-list; falls back
//                              to the variant's own default tag (not hard-coded to <p>) when
//                              invalid or omitted
//   color            string   default | light | neutral (default: default) — a project addition on
//                              top of shadcn's typography vocabulary (shadcn's own scale has no
//                              separate color axis), for
//                              cases like light text on a dark hero section
//   accent_words     array    words to highlight, rendered via base_theme_render_accent_text()
//   data_slot        string   overrides the root `data-slot` value (default: 'typography') --
//                              same composing-parent escape hatch as input.php's/textarea.php's/
//                              label.php's `data_slot`; card.php requests 'card-title'/
//                              'card-description' here instead of duplicating this file's
//                              attribute-building logic. Leave unset for standalone
//                              use.
//   class / attributes / data_attributes   passthrough, as in the other base parts
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

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

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
    base_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    base_theme_render_accent_text($text, $accent_words), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
