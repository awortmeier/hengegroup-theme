<?php

declare(strict_types=1);

// Translation of shadcn/ui's Spinner. shadcn's own source is deliberately tiny -- just an icon
// with baked-in loading semantics, no variant/size prop of its own:
//
//   function Spinner({ className, ...props }: React.ComponentProps<"svg">) {
//     return (
//       <LoaderIcon role="status" aria-label="Loading" className={cn("size-4 animate-spin", className)} {...props} />
//     )
//   }
//
// Phase 1 followed that literally (icon.php's `loader-circle` + `animate-spin`, no size/color of
// its own, see docs/entscheidungen.md history). Phase 2 (this file) replaces that with a dedicated
// two-circle ring instead of delegating to icon.php -- the design reference's "Basis" section
// describes the shape explicitly ("Ein Ring in Hairline-Staerke, offene Viertelkante, gleichmaessige
// Drehung"): a full, static, low-opacity TRACK ring plus a short ~90 deg ACCENT arc rotating on top
// of it. No single lucide icon draws that (lucide's own `loader-circle` is one 270 deg-ish arc path,
// no separate track at all -- see assets/images/icons/lucide/loader-circle.svg) -- same "the
// reference needs a shape shadcn/lucide doesn't ship" situation progress-circle.php's own header
// documents for its ring, not invented here. `icon`/`set` config removed as a result (nothing to
// point at an icon file anymore); no real caller used anything but the default, see
// docs/entscheidungen.md.
//
// Markup: `<svg viewBox="0 0 24 24">` with two concentric `<circle>`s, r="10", both inheriting
// `stroke="currentColor" stroke-width="2" fill="none"` from the `<svg>` (SVG presentation
// attributes inherit like CSS). No `width`/`height` attribute on the `<svg>` itself -- actual
// rendered size comes entirely from the `size` config's Tailwind class below, same "CSS box wins
// over baked-in SVG dimensions" idiom icon.php's own lucide sources already rely on. Deliberate
// side effect: because every size shares the SAME 24-unit viewBox and the SAME stroke-width="2" in
// that coordinate space, the effective on-screen stroke thickness scales automatically with the
// rendered box size (2px at a 24px render, ~1.2px at 14px, ~3.3px at 40px) -- exactly the
// reference's own "Groessen" note ("Die Strichstaerke waechst mit dem Durchmesser, damit der Ring
// auf jeder Groesse gleich dicht wirkt"), for free, no per-size stroke-width override needed.
//
// - Track circle: no `stroke-dasharray` (full 360 deg ring), `opacity-20` -- same
//   currentColor-at-reduced-opacity technique progress.php's own track already uses
//   (`bg-{variant}/20`), not a second hardcoded grey token.
// - Arc circle: `stroke-linecap="round"` + `stroke-dasharray="15.71 62.83"` -- circumference of
//   r="10" is 2*pi*10 = 62.83, a quarter of that (90 deg, the reference's "offene Viertelkante") is
//   15.71; the second dasharray number only needs to exceed the remaining 47.12 so the pattern
//   doesn't repeat a second time around the same circle, reusing the full circumference is the
//   simplest number that guarantees that.
// - Rotation: `animate-spin` (Tailwind's built-in `spin 1s linear infinite` utility) on the `<svg>`
//   itself -- matches "gleichmaessige Drehung" (even/constant rotation) exactly, no raw-CSS
//   `@keyframes` needed (Regel 1).
//
// `color` (new, Phase 2): default (henge-green) | muted (text-muted-foreground) | inherit (no
// color class at all, stays whatever `color`/currentColor an ancestor already set). Three, not the
// simpler `default`/`light` two-value "which surface is this on" vocabulary
// accordion.php/typography.php/progress-steps.php share -- the reference shows three genuinely
// different EMPHASIS levels for the arc, not a light/dark-surface switch:
//   - standalone spinners, the list-footer/card-placeholder examples, AND next to plain body text
//     all default to the brand accent (green) regardless of that surrounding text's own color --
//     not `currentColor`-driven, an explicit brand color like progress.php's/progress-circle.php's
//     own `henge-green` default.
//   - the reference's secondary/outline-button example dims the arc to a muted grey matching that
//     button's own lower-emphasis text -- `muted`, matching this project's existing
//     `text-muted-foreground` token (already spinner.php's real predecessor here, see
//     page-component-showcase-attachment.php's "processing" example this file's rewrite also
//     migrates from a raw `class` override to this named value).
//   - `inherit` exists specifically for composing inside an ALREADY-colored ancestor whose text
//     color varies by variant (button.php's `henge-green`/`henge-blue`/.../`outline`/`ghost` -- no
//     single fixed value works for a spinner nested in every one of those), see button.php's own
//     header for how it uses this.
// The reference's "Auf dunklem Grund" section (a fourth, lighter-tint appearance for dark surfaces)
// was deliberately NOT built -- same reasoning as kbd.php's/pagination.php's/table/*.php's/
// separator.php's own Phase 2 passes (see docs/entscheidungen.md): this theme has no project-wide
// dark-surface strategy yet, a spinner-only dark mode would be a lone-wolf addition ahead of that.
//
// `size` (new, Phase 2): sm (14px) | base (20px, default) | lg (28px) | xl (40px) -- the
// reference's own four named steps ("Klein"/"Standard"/"Groß"/"Sehr groß"), real Tailwind spacing
// steps (`size-3.5`/`size-5`/`size-7`/`size-10`), no arbitrary values. A 4th step beyond this
// project's usual `sm`/`base`/`lg` trio (button.php, progress.php, progress-circle.php, kbd.php)
// because the reference itself shows four, not three -- `xl` appended rather than renaming/
// compressing the existing trio, same "extend the vocabulary the reference actually asks for"
// approach button.php's own icon-* sizes already set.
//
// No file split / no folder move: `color`/`size` are plain class-map variants within this ONE
// file, the exact same shape as button.php's/badge.php's/kbd.php's own `variant`/`size` -- none of
// those ever got a file-per-value split (see kbd.php's own header, "keine Datei-pro-Variante"), and
// nothing in the reference calls for a structurally different spinner composition (unlike e.g.
// separator.php + separator-label.php). `template-parts/base/spinner.php` stays a single flat file
// (Regel 4: a folder happens once a component is MORE than one file).
//
// Design reference: https://claude.ai/code/artifact/795f39d7-99e9-4211-9b9a-c15dabacc6ab
//
// Supported config:
//   size         string   sm | base (default) | lg | xl -- see note above
//   color        string   default (henge-green, default) | muted | inherit -- see note above
//   aria_label   string   accessible name announced via role="status". Default: 'Loading'
//                         (matches shadcn's own hardcoded default), localized because -- like
//                         calendar.php -- this component renders its own static UI text rather
//                         than only caller-supplied text. Ignored when `decorative` is true.
//   decorative   bool     default false. false (default) -> role="status" + aria-label, matching
//                         shadcn's own Spinner out of the box (an accessible status that's announced
//                         on appearance). true -> aria-hidden="true" instead, for a spinner placed
//                         right next to its own visible status text (e.g. a "Speichert ..."
//                         label) where a second, redundant announcement of the spinner itself
//                         isn't wanted -- same decorative/non-decorative vocabulary as icon.php's
//                         own `decorative` flag (sibling consistency).
//   class / attributes / data_attributes   passthrough, merged onto the rendered <svg>

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$size = trim((string) ($config['size'] ?? 'base'));
$color = trim((string) ($config['color'] ?? 'default'));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$decorative = !empty($config['decorative']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_sizes = ['sm', 'base', 'lg', 'xl'];

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'base';
}

$allowed_colors = ['default', 'muted', 'inherit'];

if (!in_array($color, $allowed_colors, true)) {
    $color = 'default';
}

if ($aria_label === '') {
    // Translate only, don't escape here -- like every other component in this theme, escaping
    // happens once, at render time, via hengegroup_theme_render_attributes().
    $aria_label = __('Loading', 'hengegroup-theme');
}

$size_classes = [
    'sm' => 'size-3.5',
    'base' => 'size-5',
    'lg' => 'size-7',
    'xl' => 'size-10',
];

$color_classes = [
    'default' => 'text-henge-green',
    'muted' => 'text-muted-foreground',
    'inherit' => '',
];

$base_classes = 'inline-block shrink-0 animate-spin';

$computed_class = trim("{$base_classes} {$size_classes[$size]} {$color_classes[$color]}");

$svg_attributes = $attributes;
$svg_attributes['class'] = trim($computed_class . ($class_name !== '' ? ' ' . $class_name : ''));
$svg_attributes['viewBox'] = '0 0 24 24';
$svg_attributes['fill'] = 'none';
$svg_attributes['stroke'] = 'currentColor';
$svg_attributes['stroke-width'] = '2';
$svg_attributes['data-slot'] = 'spinner';
$svg_attributes['data-size'] = $size;
$svg_attributes['data-color'] = $color;

if ($decorative) {
    $svg_attributes['aria-hidden'] = 'true';
} else {
    $svg_attributes['role'] = 'status';
    $svg_attributes['aria-label'] = $aria_label;
}

foreach ($data_attributes as $name => $value) {
    $data_name = trim((string) $name);

    if ($data_name === '') {
        continue;
    }

    $svg_attributes['data-' . $data_name] = $value;
}

printf(
    '<svg%1$s><circle cx="12" cy="12" r="10" class="opacity-20"></circle>' .
        '<circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-dasharray="15.71 62.83"></circle></svg>',
    hengegroup_theme_render_attributes($svg_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
