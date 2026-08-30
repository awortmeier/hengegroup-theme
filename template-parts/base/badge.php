<?php

declare(strict_types=1);

// API modeled on shadcn/ui's Badge (variant vocabulary, polymorphic root element via `href`, same
// asChild/Slot analog as button.php), now with real Tailwind styling (Phase 2, CLAUDE.md Regel 1)
// -- classes taken 1:1 from shadcn's own badgeVariants() cva() definition
// (registry/new-york-v4/ui/badge.tsx, live-checked 2026-08-30), with deliberate deviations:
//   - variant vocabulary narrowed AND renamed to this project's brand-color names, on explicit
//     request: henge-green/henge-blue/henge-grey/grey-dark/grey-light (same five full-color names
//     as button.php's variant vocabulary, see its file header) plus `outline`. Unlike button.php,
//     badge.php deliberately drops destructive/ghost/link -- a static label has no
//     destructive/call-to-action/inline-text-link use case, see docs/entscheidungen.md.
//   - `outline`'s border uses this project's grey-light brand token instead of shadcn's
//     `--color-border` role, on the same explicit request.
//   - `dark:`-prefixed classes dropped, same reasoning as button.php (no dark-mode strategy yet,
//     see docs/to-do.md).
// Unlike button.php, shadcn's Badge has no `size`/`loading`/`disabled` -- it's a static label, not
// a form control, so none of those are added here (see CLAUDE.md #1: adopt shadcn's vocabulary,
// don't invent beyond it).
//
// Supported config:
//   text / label   string   visible label (omit for an icon-only badge)
//   href           string   renders <a> instead of <span> (shadcn's asChild/Slot analog, same
//                            idiom as button.php's `href`)
//   variant        string   grey-dark | grey-light | henge-blue | henge-green | henge-grey |
//                           outline -- project-specific brand-color vocabulary (see file header),
//                           narrower than button.php's variant scale (no destructive/ghost/link)
//   icon           array    icon.php config, e.g. ['name' => 'check', 'set' => 'lucide'].
//                           Rendered with a data-icon="inline-start"|"inline-end" attribute
//                           (matching shadcn's own icon-spacing convention) reflecting
//                           `icon_position`, unless the badge is icon-only
//   icon_position  string   start | end (ignored for icon-only badges)
//   font           string   primary | accent (default: primary) -- this project's two theme fonts
//                           (--font-primary/Outfit, --font-accent/Crillee, see
//                           assets/css/tokens.css). `primary` is already inherited from the
//                           site-wide `body` rule, so it adds no class (same "only declare what
//                           deviates" pattern as everywhere else); `accent` adds the `font-accent`
//                           utility, the same one hengegroup_theme_render_accent_text() uses for
//                           highlighted words in typography.php -- here applied to the whole label
//                           instead of individual words, e.g. for a Crillee-set brand tag like the
//                           Karriere job-listing badges in the Startseite design.
//   aria_label     string   required for icon-only badges (no visible text to name them)
//   class          string   appended AFTER the computed base/variant classes (plain string concat,
//                           no tailwind-merge/cn() equivalent available in PHP) -- same caveat as
//                           button.php's `class`: fine for additive classes, not a reliable
//                           bg-*/text-* override
//   attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ($config['label'] ?? '')));
$href = trim((string) ($config['href'] ?? ''));
$variant = trim((string) ($config['variant'] ?? 'henge-green'));
$icon_config = is_array($config['icon'] ?? null) ? $config['icon'] : null;
$icon_position = trim((string) ($config['icon_position'] ?? 'start'));
$font = trim((string) ($config['font'] ?? 'primary'));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$has_icon = $icon_config !== null;

if ($text === '' && !$has_icon) {
    return;
}

$allowed_variants = [
    'grey-dark',
    'grey-light',
    'henge-blue',
    'henge-green',
    'henge-grey',
    'outline',
];

if (!in_array($variant, $allowed_variants, true)) {
    $variant = 'henge-green';
}

$allowed_fonts = ['primary', 'accent'];

if (!in_array($font, $allowed_fonts, true)) {
    $font = 'primary';
}

// Classes taken 1:1 from shadcn's badgeVariants() cva() call (see file header for source/
// deviations), except padding/text-size (design request 2026-08-30, per the Produkte/Karriere
// section pill labels in the Startseite reference design -- category badge/job tag there use
// ~6px/12px padding and 12-14px text, noticeably roomier than shadcn's stock px-2 py-0.5 text-xs):
// px-2 py-0.5 text-xs -> px-3 py-1.5 text-sm. rounded-full (shape) already matched that reference,
// left as-is. font-family/text-color are NOT repeated here for the solid variants beyond what
// deviates from the global default -- same "only declare what deviates" pattern as button.php.
$base_classes =
    'inline-flex w-fit shrink-0 items-center justify-center gap-1 overflow-hidden rounded-full ' .
    'border border-transparent px-2 py-0.75 text-sm font-medium whitespace-nowrap ' .
    'transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-[3px] ' .
    'focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 ' .
    '[&>svg]:pointer-events-none [&>svg]:size-3';

$variant_classes = [
    'henge-green' => 'bg-henge-green text-henge-green-foreground [a&]:hover:bg-henge-green/90',
    'henge-blue' => 'bg-henge-blue text-henge-blue-foreground [a&]:hover:bg-henge-blue/90',
    'henge-grey' => 'bg-henge-grey text-henge-grey-foreground [a&]:hover:bg-henge-grey/90',
    'grey-dark' => 'bg-grey-dark text-grey-dark-foreground [a&]:hover:bg-grey-dark/90',
    'grey-light' => 'bg-grey-light text-grey-light-foreground [a&]:hover:bg-grey-light/80',
    'outline' =>
        'border-grey-light text-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground',
];

$computed_class = "{$base_classes} {$variant_classes[$variant]}";

if ($font === 'accent') {
    $computed_class .= ' font-accent';
}

if ($icon_position !== 'end') {
    $icon_position = 'start';
}

$is_icon_only = $text === '';

if ($has_icon && !$is_icon_only) {
    $icon_config['data_attributes'] = array_merge(
        is_array($icon_config['data_attributes'] ?? null) ? $icon_config['data_attributes'] : [],
        ['icon' => $icon_position === 'end' ? 'inline-end' : 'inline-start'],
    );
}

$icon_markup = $has_icon ? hengegroup_theme_render_icon($icon_config) : '';

if ($is_icon_only) {
    $inner_html = $icon_markup;
} elseif ($icon_markup !== '') {
    $inner_html =
        $icon_position === 'end' ? esc_html($text) . $icon_markup : $icon_markup . esc_html($text);
} else {
    $inner_html = esc_html($text);
}

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['data-slot'] = 'badge';
$element_attributes['data-variant'] = $variant;
$element_attributes['data-font'] = $font;

if ($is_icon_only && $aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
}

foreach ($data_attributes as $name => $value) {
    $data_name = trim((string) $name);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $value;
}

$tag = 'span';

if ($href !== '') {
    $tag = 'a';
    $element_attributes['href'] = $href;
}

printf(
    '<%1$s%2$s>%3$s</%1$s>',
    esc_html($tag),
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
