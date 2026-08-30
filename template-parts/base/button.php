<?php

declare(strict_types=1);

// API modeled on shadcn/ui's Button (variant/size vocabulary, icon slot, loading state,
// polymorphic root element). Phase 2 (CLAUDE.md Regel 1): styled via Tailwind, classes taken
// 1:1 from shadcn's own buttonVariants() cva() definition (registry/new-york-v4/ui/button.tsx,
// live-checked 2026-08-28) with three deliberate deviations:
//   - variant vocabulary renamed to this project's own brand-color names (see `variant` below)
//     instead of shadcn's default/secondary -- see docs/entscheidungen.md for why.
//   - all `dark:`-prefixed classes dropped -- this theme has no dark-mode strategy yet (see
//     docs/to-do.md), shipping half a dark-mode path (shadcn's literal dark: utilities without
//     this project's own tokens following suit) would be worse than shipping none.
//   - size vocabulary renamed/reduced to sm | base | lg (+ icon-sm | icon-base | icon-lg) instead
//     of shadcn's default | xs | sm | lg (+ icon | icon-xs | icon-sm | icon-lg) -- shadcn's
//     `default`/`icon` dropped entirely rather than kept as a 4th step, `sm` renamed `base`
//     (it's the new middle/most-used step) and `xs` renamed `sm` (now the smallest step) -- see
//     docs/entscheidungen.md for why.
// variant/size still double as data-attributes (data-variant/data-size), same hooks as before,
// now additionally driving the actual Tailwind classes instead of being purely a future hook.
//
// Supported config:
//   text / label   string   visible label (omit for an icon-only button)
//   href           string   renders <a> instead of <button> (shadcn's asChild/Slot analog)
//   variant        string   henge-green | henge-blue | henge-grey | grey-dark | grey-light |
//                           destructive | outline | ghost | link -- project-specific brand-color
//                           vocabulary instead of shadcn's default/secondary (henge-green replaces
//                           default, grey-light replaces secondary; henge-blue/henge-grey/grey-dark
//                           are new solid-fill options), destructive/outline/ghost/link unchanged
//   size           string   sm | base | lg | icon-sm | icon-base | icon-lg -- project-specific
//                           rename/reduction of shadcn's current Button size scale (default | xs |
//                           sm | lg | icon | icon-xs | icon-sm | icon-lg), see file header above
//   type           string   button | submit | reset (ignored when href is set)
//   full_width     bool     stretches the button to 100% of its parent's width (adds `w-full`) --
//                           combinable with every variant/size, on explicit request
//   disabled       bool
//   loading        bool     implies disabled; swaps the icon slot for a spinner and sets aria-busy
//   icon           array    icon.php config, e.g. ['name' => 'arrow-right', 'set' => 'lucide'].
//                           Rendered with a data-icon="inline-start"|"inline-end" attribute
//                           (matching shadcn's own icon-spacing convention) reflecting
//                           `icon_position`, unless the button is icon-only (nothing to be
//                           "inline" relative to there)
//   icon_position  string   start | end (ignored for icon-only buttons)
//   spinner_icon   array    icon.php config override for the loading spinner
//                           (default: ['name' => 'loader-circle', 'set' => 'lucide'])
//   aria_label     string   required for icon-only buttons (no visible text to name them) -- a
//                           missing value doesn't hard-fail the render, but triggers
//                           a WP_DEBUG-only _doing_it_wrong() hint, see
//                           hengegroup_theme_warn_missing_aria_label()
//   class          string   appended AFTER the computed base/variant/size classes (plain string
//                           concat, no tailwind-merge/cn() equivalent available in PHP) -- a
//                           conflicting utility here is not guaranteed to win over the computed
//                           ones, unlike shadcn's own className prop. Fine for additive classes
//                           (margins, layout), not for overriding e.g. bg-*/text-* from `variant`
//   attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ($config['label'] ?? '')));
$href = trim((string) ($config['href'] ?? ''));
$variant = trim((string) ($config['variant'] ?? 'henge-green'));
$size = trim((string) ($config['size'] ?? 'base'));
$type = trim((string) ($config['type'] ?? 'button'));
$full_width = !empty($config['full_width']);
$disabled = !empty($config['disabled']);
$loading = !empty($config['loading']);
$icon_config = is_array($config['icon'] ?? null) ? $config['icon'] : null;
$icon_position = trim((string) ($config['icon_position'] ?? 'start'));
$spinner_icon_config = is_array($config['spinner_icon'] ?? null)
    ? $config['spinner_icon']
    : ['name' => 'loader-circle', 'set' => 'lucide'];
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$has_icon = $icon_config !== null;

if ($text === '' && !$has_icon && !$loading) {
    return;
}

if ($loading) {
    $disabled = true;
}

$allowed_variants = [
    'henge-green',
    'henge-blue',
    'henge-grey',
    'grey-dark',
    'grey-light',
    'destructive',
    'outline',
    'ghost',
    'link',
];
$allowed_sizes = ['sm', 'base', 'lg', 'icon-sm', 'icon-base', 'icon-lg'];

if (!in_array($variant, $allowed_variants, true)) {
    $variant = 'henge-green';
}

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'base';
}

// Base/variant/size classes taken 1:1 from shadcn's buttonVariants() cva() call (see file header
// for source/deviations), except:
//   - outline/ghost/link's hover-state colors, which use this project's grey-dark/grey-light
//     brand tokens instead of shadcn's neutral accent/-foreground (design request 2026-08-30):
//     outline's border also switched from --color-input to --color-grey-dark for the same reason.
//   - shape/padding (design request 2026-08-30, per-size hengegroup.com pill-button reference):
//     rounded-md swapped for a fully rounded pill (rounded-full) everywhere, horizontal padding
//     widened per size (`base` matches the reference nav pill's 10px/20px exactly, `lg` matches
//     the reference hero CTA's 28px horizontal exactly) -- vertical padding stays governed by each
//     size's fixed h-* like before, only `base` already carried an explicit py-* (bumped
//     proportionally, same as its px-*).
//   - per-size font-size (design request 2026-08-30, same hengegroup.com reference pages): moved
//     out of the shared base text-sm into $size_classes below, one step per size instead of
//     shadcn's single shared size -- sm/icon-sm text-sm (14px), base/icon-base text-base (16px),
//     lg/icon-lg text-lg (18px). Real Tailwind scale steps only, no arbitrary px value (see
//     docs/entscheidungen.md for the source pixel values this was mapped from, and for why the
//     size vocabulary itself was renamed/reduced from shadcn's default | xs | sm | lg).
// font-family/text-color are NOT repeated here -- inherited from the site-wide `body` rule
// (assets/css/app.css), same "only declare what deviates from the global default" pattern shadcn
// itself uses.
$base_classes =
    'inline-flex shrink-0 items-center justify-center gap-2 rounded-full font-medium ' .
    'whitespace-nowrap transition-all outline-none focus-visible:border-ring ' .
    'focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none ' .
    'disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 ' .
    "[&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4";

$variant_classes = [
    'henge-green' => 'bg-henge-green text-henge-green-foreground hover:bg-henge-green/90',
    'henge-blue' => 'bg-henge-blue text-henge-blue-foreground hover:bg-henge-blue/90',
    'henge-grey' => 'bg-henge-grey text-henge-grey-foreground hover:bg-henge-grey/90',
    'grey-dark' => 'bg-grey-dark text-grey-dark-foreground hover:bg-grey-dark/90',
    'grey-light' => 'bg-grey-light text-grey-light-foreground hover:bg-grey-light/80',
    'destructive' =>
        'bg-destructive text-destructive-foreground hover:bg-destructive/90 focus-visible:ring-destructive/20',
    'outline' =>
        'border border-grey-dark bg-background shadow-xs hover:bg-grey-light hover:text-grey-light-foreground',
    'ghost' => 'hover:bg-grey-light hover:text-grey-light-foreground',
    'link' => 'text-grey-dark underline-offset-4 hover:underline',
];

$size_classes = [
    'sm' =>
        "h-6 gap-1 rounded-full px-2.5 text-sm has-[>svg]:px-2 [&_svg:not([class*='size-'])]:size-3",
    'base' => 'h-8 gap-1.5 rounded-full px-4 text-base has-[>svg]:px-3',
    'lg' => 'h-10 rounded-full px-7 text-lg has-[>svg]:px-5',
    'icon-sm' => "size-6 rounded-full text-sm [&_svg:not([class*='size-'])]:size-3",
    'icon-base' => 'size-8 text-base',
    'icon-lg' => 'size-10 text-lg',
];

$computed_class = "{$base_classes} {$variant_classes[$variant]} {$size_classes[$size]}";

if ($full_width) {
    $computed_class .= ' w-full';
}

if ($icon_position !== 'end') {
    $icon_position = 'start';
}

$is_icon_only = $text === '' && ($has_icon || $loading);

if ($loading) {
    $active_icon_config = $spinner_icon_config;
} elseif ($has_icon) {
    $active_icon_config = $icon_config;
} else {
    $active_icon_config = null;
}

if ($active_icon_config !== null) {
    if (!$is_icon_only) {
        $active_icon_config['data_attributes'] = array_merge(
            is_array($active_icon_config['data_attributes'] ?? null)
                ? $active_icon_config['data_attributes']
                : [],
            ['icon' => $icon_position === 'end' ? 'inline-end' : 'inline-start'],
        );
    }

    $icon_markup = hengegroup_theme_render_icon($active_icon_config);
} else {
    $icon_markup = '';
}

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

$element_attributes['data-slot'] = 'button';
$element_attributes['data-variant'] = $variant;
$element_attributes['data-size'] = $size;

if ($loading) {
    $element_attributes['aria-busy'] = 'true';
}

if ($is_icon_only && $aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
}

hengegroup_theme_warn_missing_aria_label('button.php', $is_icon_only, $aria_label);

foreach ($data_attributes as $name => $value) {
    $data_name = trim((string) $name);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $value;
}

if ($href !== '') {
    if ($disabled) {
        // <a> has no native disabled attribute; dropping href removes it from the tab order
        // and from navigation, aria-disabled announces the state to assistive tech.
        $element_attributes['aria-disabled'] = 'true';
    } else {
        $element_attributes['href'] = $href;
    }

    printf(
        '<a%1$s>%2$s</a>',
        hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );

    return;
}

$allowed_types = ['button', 'submit', 'reset'];

if (!in_array($type, $allowed_types, true)) {
    $type = 'button';
}

$element_attributes['type'] = $type;

if ($disabled) {
    $element_attributes['disabled'] = true;
}

printf(
    '<button%1$s>%2$s</button>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
