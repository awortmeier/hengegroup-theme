<?php

declare(strict_types=1);

// API modeled on shadcn/ui's Button (variant/size vocabulary, icon slot, loading state,
// polymorphic root element). Phase 2 (CLAUDE.md Regel 1): styled via Tailwind, classes taken
// 1:1 from shadcn's own buttonVariants() cva() definition (registry/new-york-v4/ui/button.tsx,
// live-checked 2026-08-28) with two deliberate deviations:
//   - variant vocabulary renamed to this project's own brand-color names (see `variant` below)
//     instead of shadcn's default/secondary -- see docs/entscheidungen.md for why.
//   - all `dark:`-prefixed classes dropped -- this theme has no dark-mode strategy yet (see
//     docs/to-do.md), shipping half a dark-mode path (shadcn's literal dark: utilities without
//     this project's own tokens following suit) would be worse than shipping none.
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
//   size           string   default | xs | sm | lg | icon | icon-xs | icon-sm | icon-lg (matches
//                           shadcn's current Button size scale as of its Base UI/React
//                           Aria/Radix UI multi-backend rewrite -- `xs` and the combined
//                           `icon-*` sizes used to not exist in shadcn's stock vocabulary, they
//                           do now, so nothing here is a project addition anymore)
//   type           string   button | submit | reset (ignored when href is set)
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
$size = trim((string) ($config['size'] ?? 'default'));
$type = trim((string) ($config['type'] ?? 'button'));
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
$allowed_sizes = ['default', 'xs', 'sm', 'lg', 'icon', 'icon-xs', 'icon-sm', 'icon-lg'];

if (!in_array($variant, $allowed_variants, true)) {
    $variant = 'henge-green';
}

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'default';
}

// Base/variant/size classes taken 1:1 from shadcn's buttonVariants() cva() call (see file header
// for source/deviations). font-family/text-color are NOT repeated here -- inherited from the
// site-wide `body` rule (assets/css/app.css), same "only declare what deviates from the global
// default" pattern shadcn itself uses.
$base_classes =
    'inline-flex shrink-0 items-center justify-center gap-2 rounded-md text-sm font-medium ' .
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
        'border border-input bg-background shadow-xs hover:bg-accent hover:text-accent-foreground',
    'ghost' => 'hover:bg-accent hover:text-accent-foreground',
    'link' => 'text-henge-green underline-offset-4 hover:underline',
];

$size_classes = [
    'default' => 'h-9 px-4 py-2 has-[>svg]:px-3',
    'xs' =>
        "h-6 gap-1 rounded-md px-2 text-xs has-[>svg]:px-1.5 [&_svg:not([class*='size-'])]:size-3",
    'sm' => 'h-8 gap-1.5 rounded-md px-3 has-[>svg]:px-2.5',
    'lg' => 'h-10 rounded-md px-6 has-[>svg]:px-4',
    'icon' => 'size-9',
    'icon-xs' => "size-6 rounded-md [&_svg:not([class*='size-'])]:size-3",
    'icon-sm' => 'size-8',
    'icon-lg' => 'size-10',
];

$computed_class = "{$base_classes} {$variant_classes[$variant]} {$size_classes[$size]}";

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
