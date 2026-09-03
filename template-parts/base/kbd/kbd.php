<?php

declare(strict_types=1);

// Direct translation of shadcn/ui's Kbd: <kbd> is already a real semantic HTML element ("Defines
// text representing user input, typically keyboard input" per the HTML spec) with correct
// built-in meaning -- no ARIA/JS needed at all, just a data-slot for the project's own CSS (see
// CLAUDE.md #1). For a multi-key combo (e.g. "Ctrl" + "K"), compose several kbd.php calls inside
// template-parts/base/kbd/kbd-group.php instead of adding a "keys" array here.
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind as a "keycap" -- light raised surface, a
// slightly darker/thicker bottom edge standing in for a physical key's bottom bevel, per the
// Claude-Design reference "Hengegroup" (same .dc.html-reference workflow as button.php's/
// typography.php's entries in docs/entscheidungen.md, see that file for this component's entry).
// shadcn's own Kbd (registry/new-york-v4/ui/kbd.tsx, live-checked 2026-09-03) is a single fixed
// look with no size/state prop at all ("pointer-events-none inline-flex h-5 w-fit min-w-5 ...
// rounded-sm bg-muted ... text-muted-foreground"); this project deviates on the explicit strength
// of that reference, same category of deviation as button.php's/badge.php's brand-color variant
// vocabulary:
//   - a `size` scale (sm | default | lg) was added -- the reference dedicates a whole "Größen"
//     section to exactly these 3 named steps (24 / 30 / 38px), see docs/entscheidungen.md for the
//     px -> Tailwind-scale mapping (reuses button.php's own h-6/h-8/h-10 height steps).
//   - a `pressed` state was added -- the reference's "Gedrückter Zustand" section shows the key
//     sinking 1px with an accent-colored fill. Exposed the same render-time-only way
//     progress.php/avatar.php/toggle.php's data-state already work in this theme (reflects the
//     value passed at render time, not a live keydown listener -- kbd.php stays the "no ARIA/JS
//     needed" static element its own original header already established, nothing added here
//     changes that).
//   - `bg-muted`/`text-muted-foreground` swapped for `bg-background`/`text-foreground` -- this
//     project's `--color-muted` is a dimmed placeholder-text role (see assets/css/tokens.css),
//     visually too flat for the reference's crisp near-white keycap fill; `bg-background` is
//     already this project's established "raised bordered surface" fill (button.php's `outline`
//     variant, input.php/textarea.php/select.php/combobox.php/native-select.php/
//     input-group.php's fields).
//   - border color computed from `--color-foreground` at low opacity (`border-foreground/15`,
//     bottom edge `border-b-foreground/25`) instead of a flat border token -- reproduces the
//     reference's semi-transparent dark border (rgba(...,0.16) / rgba(...,0.26)) without a new
//     token, same "Tailwind opacity modifier on an existing role" trick as button.php's
//     `hover:bg-henge-green/90`.
//   - the reference's "Auf dunklem Grund" section (kbd on a dark-background card) was deliberately
//     NOT carried over -- this theme has no dark-mode/dark-surface strategy yet, same reasoning
//     button.php/badge.php already documented for dropping shadcn's own `dark:` classes, see
//     docs/entscheidungen.md.
// font-family uses Tailwind's built-in `font-mono` utility -- its default stack
// (ui-monospace/SFMono-Regular/Menlo/Consolas/...) already matches the reference's chosen
// monospace stack, no arbitrary value needed.
//
// Supported config:
//   text     string   required. The key label (e.g. "Ctrl", "K", "Enter", "⌘"), escaped
//   size     string   sm | default | lg (default: default) -- see file header above
//   pressed  bool     default false. Purely visual, render-time-only "sunken key" state (see file
//                     header above); sets data-state="on"|"off", same vocabulary as toggle.php's
//                     own pressed data-state
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ''));
$size = trim((string) ($config['size'] ?? 'default'));
$pressed = !empty($config['pressed']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($text === '') {
    return;
}

$allowed_sizes = ['sm', 'default', 'lg'];

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'default';
}

// Base classes taken from shadcn's own Kbd cva-less className string (see file header for
// source/deviations): inline-flex/w-fit/pointer-events-none/select-none/leading-none kept 1:1,
// bg-muted/text-muted-foreground swapped for bg-background/text-foreground, border + bottom-edge
// bevel and the `pressed` data-state added on top.
$base_classes =
    'pointer-events-none inline-flex w-fit shrink-0 items-center justify-center ' .
    'whitespace-nowrap font-mono leading-none text-foreground select-none ' .
    'bg-background border border-foreground/15 border-b-2 border-b-foreground/25 ' .
    'transition-[transform,background-color,color,border-color] duration-100 ease-out ' .
    'data-[state=on]:translate-y-px data-[state=on]:border-b data-[state=on]:border-henge-green ' .
    'data-[state=on]:border-b-henge-green data-[state=on]:bg-henge-green ' .
    'data-[state=on]:text-henge-green-foreground';

// Height/min-width/padding/font-size/radius per size, mapped from the reference's px values onto
// the nearest real Tailwind scale steps (no arbitrary px values, same rule as button.php's/
// typography.php's entries) -- see docs/entscheidungen.md for the full px -> scale table.
$size_classes = [
    'sm' => 'h-6 min-w-6 rounded-sm px-2 text-xs',
    'default' => 'h-8 min-w-8 rounded-md px-2.5 text-sm',
    'lg' => 'h-10 min-w-10 rounded-lg px-3 text-base',
];

$computed_class = "{$base_classes} {$size_classes[$size]}";

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['data-slot'] = 'kbd';
$element_attributes['data-size'] = $size;
$element_attributes['data-state'] = $pressed ? 'on' : 'off';

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<kbd%1$s>%2$s</kbd>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);
