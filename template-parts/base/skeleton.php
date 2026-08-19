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
// Every single Tailwind class shadcn ships here (`animate-pulse`, `rounded-md`, `bg-accent`) is a
// purely visual/gestalterische decision -- pulse animation, corner radius, background color -- not
// one of them is load-bearing for the component to function (unlike e.g. dropdown-menu.php's
// `hidden`-by-default panel, which really would render wrong without its functional class). Per
// CLAUDE.md Regel 1 this makes Skeleton the starkest possible Phase-1 example: this file renders
// NO Tailwind classes of its own at all -- same `[data-slot="skeleton"]`-keyed project-CSS
// deferral spinner.php already established for its own `animate-spin`. Phase 2 supplies the
// pulse/color/radius later; `class` passthrough lets a caller size an individual instance in the
// meantime (shadcn's own docs example: `<Skeleton className="h-[20px] w-[100px] rounded-full" />`).
//
// Accessibility (not part of shadcn's own source, which has none -- but every visual component in
// this theme gets a11y config from the start, CLAUDE.md Regel 5): a lone skeleton shape carries no
// real content, so it defaults to `decorative: true` (aria-hidden="true"), same vocabulary/default
// as icon.php/separator.php. Real skeleton screens render many shapes per loading region --
// announcing each one individually would be repetitive screen-reader noise, so this default is
// deliberate, not an oversight. `decorative: false` switches to `role="status"` + `aria-label`
// instead, same pairing as spinner.php (Skeleton and Spinner are both "content is loading"
// indicators, just different visual shapes) -- for the rarer case of a single standalone skeleton
// block used without any other status text nearby to announce the loading state.
//
// Supported config:
//   decorative     bool     default true. true -> aria-hidden="true" (silent, the common case for
//                           multi-shape skeleton screens, see above); false -> role="status" +
//                           aria-label
//   aria_label     string   accessible name announced via role="status" when `decorative` is
//                           false. Default: 'Loading' (matches spinner.php's own default),
//                           localized. Ignored when `decorative` is true
//   class / attributes / data_attributes   passthrough onto the rendered <div>, as in the other
//                           base parts -- this is how a caller sizes/shapes an individual instance
//                           (see the shadcn example above), since this file has no size/variant
//                           config of its own (shadcn's Skeleton doesn't either, CLAUDE.md Regel 2)

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$decorative = !array_key_exists('decorative', $config) || !empty($config['decorative']);
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($aria_label === '') {
    // Translate only, don't escape here -- like spinner.php, escaping happens once, at render
    // time, via base_theme_render_attributes().
    $aria_label = __('Loading', 'base-theme');
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'skeleton';

if ($decorative) {
    $element_attributes['aria-hidden'] = 'true';
} else {
    $element_attributes['role'] = 'status';
    $element_attributes['aria-label'] = $aria_label;
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
    base_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
