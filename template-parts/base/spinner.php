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
// Sizing/color in shadcn's docs is done entirely via arbitrary Tailwind utility classes on
// `className` (`size-6`, `text-blue-500`, ...) -- there is no fixed size enum to translate into a
// `data-size` attribute here (unlike button.php/badge.php), so this component follows icon.php's
// own precedent instead (its closest sibling: this is structurally just an
// icon.php instance with loading semantics baked on): no size prop, `class` passthrough only,
// actual sizing/animation is a project-CSS concern keyed off `[data-slot="spinner"]` (equivalent
// to shadcn's `animate-spin` utility).
//
// Icon note: shadcn's current source uses lucide's plain `LoaderIcon` (dashes), not the
// `Loader2Icon`/`LoaderCircleIcon` ("loader-circle") this theme's button.php already defaults its
// own `loading` state to -- but only `loader-circle` is synced under assets/images/icons/lucide
// right now, so this component's default mirrors button.php's existing `spinner_icon` default
// instead of shadcn's, for consistency within the theme. Sync `loader` (see README "Icons") and
// change the default here if the dashes variant is preferred later.
//
// Supported config:
//   icon         array   icon.php config for the underlying icon. Default:
//                         ['name' => 'loader-circle', 'set' => 'lucide']
//   aria_label   string   accessible name announced via role="status". Default: 'Loading'
//                         (matches shadcn's own hardcoded default), localized because -- like
//                         calendar.php -- this component renders its own static UI text rather
//                         than only caller-supplied text. Ignored when `decorative` is true.
//   decorative   bool     default false. false (default) -> role="status" + aria-label, matching
//                         shadcn's Spinner out of the box (an accessible status that's announced
//                         on appearance). true -> aria-hidden="true" instead, for a spinner placed
//                         right next to its own visible status text (e.g. a "Speichert ..."
//                         label) where a second, redundant announcement of the spinner itself
//                         isn't wanted -- same decorative/non-decorative vocabulary as icon.php's
//                         own `decorative` flag (sibling consistency).
//   class / attributes / data_attributes   passthrough, merged onto the rendered <svg>, same
//                         idiom as icon.php

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$icon_config = is_array($config['icon'] ?? null)
    ? $config['icon']
    : ['name' => 'loader-circle', 'set' => 'lucide'];
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$decorative = !empty($config['decorative']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($aria_label === '') {
    // Translate only, don't escape here -- like every other component in this theme, escaping
    // happens once, at render time, via base_theme_render_attributes(). Escaping twice (here via
    // esc_attr__(), then again when base_theme_render_icon() -> icon.php merges this into its
    // <svg> attributes) double-encodes entities in the translated string (e.g. `&` -> `&amp;amp;`).
    $aria_label = __('Loading', 'base-theme');
}

$svg_attributes = $attributes;
$svg_attributes['data-slot'] = 'spinner';

if ($decorative) {
    $svg_attributes['aria-hidden'] = 'true';
} else {
    // icon.php defaults to aria-hidden="true" (its own `decorative` default) -- explicitly
    // cancel that out (false is dropped by base_theme_render_attributes()) in favor of the
    // role="status" + aria-label pairing shadcn's Spinner ships out of the box.
    $svg_attributes['aria-hidden'] = false;
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

$icon_config['class'] = trim((string) ($icon_config['class'] ?? '') . ' ' . $class_name);
$icon_config['attributes'] = array_merge(
    is_array($icon_config['attributes'] ?? null) ? $icon_config['attributes'] : [],
    $svg_attributes,
);

echo base_theme_render_icon($icon_config); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
