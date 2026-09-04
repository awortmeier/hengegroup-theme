<?php

declare(strict_types=1);

// Direct translation of shadcn/ui's ButtonGroup: a content-agnostic wrapper. shadcn's own
// implementation is just `role="group"` + adjacent-sibling CSS selectors
// (`:not(:first-child)`/`:not(:last-child)`) that strip border-radius/border off touching
// children so multiple buttons visually merge into one connected bar ("segmented control"/
// split-button look) -- no JS, entirely CSS-driven (Phase 2 below).
//
// Same nesting pattern as aspect-ratio.php/kbd/kbd-group.php: buffer the inner
// button.php/separator.php/button-group-text.php output(s) and pass the combined HTML string as
// `content`. shadcn's own ButtonGroupSeparator is nothing but their Separator with
// orientation="vertical" plus a couple of extra override classes (no margin/--color-input instead
// of --color-border, so it fills the group's full height and doesn't add its own gap; the
// full-height fill itself is separator.php's own `orientation: 'vertical'` default now, see that
// file's Bugfix note) -- no new component for that here either: reuse
// template-parts/base/separator/separator.php unchanged and pass those two remaining overrides
// through its own `class` config at the call site, e.g.
// `['orientation' => 'vertical', 'class' => 'bg-input m-0!']`.
//
//   ob_start();
//   get_template_part('template-parts/base/button', null, ['config' => ['text' => 'Left']]);
//   get_template_part('template-parts/base/separator/separator', null, [
//       'config' => [
//           'orientation' => 'vertical',
//           'class' => 'bg-input m-0!',
//       ],
//   ]);
//   get_template_part('template-parts/base/button', null, ['config' => ['text' => 'Right']]);
//   $group_markup = (string) ob_get_clean();
//
//   get_template_part('template-parts/base/button-group/button-group', null, [
//       'config' => ['content' => $group_markup],
//   ]);
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind, classes taken 1:1 from shadcn's own
// buttonGroupVariants() cva() definition (registry/new-york-v4/ui/button-group.tsx, live-checked
// 2026-08-30), with one deviation: the `has-[select[aria-hidden=true]:last-child]:
// [&>[data-slot=select-trigger]:last-of-type]:rounded-r-md` edge case (a trailing native <select>
// fallback keeping a rounded outer corner) uses `rounded-r-lg` instead -- this project's
// established field-surface radius (input.php/input-group.php/native-select.php's own
// rounded-md -> rounded-lg swap, see those files' headers) rather than shadcn's stock rounded-md.
// Kept even though no component in this theme emits `data-slot="select-trigger"` yet -- that's the
// data-slot the planned JS-driven select.php (see native-select.php's header) will use, so this
// selector is forward-compatible with it rather than dead weight.
//
// Supported config:
//   content       string   required. Pre-rendered HTML to wrap (caller's responsibility to
//                          escape/build, e.g. via template-parts/base/button.php)
//   orientation   string   horizontal (default) | vertical -- sets data-orientation and switches
//                          the merge direction (rounds/borders stripped left/right vs. top/bottom)
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$orientation = trim((string) ($config['orientation'] ?? 'horizontal'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

$allowed_orientations = ['horizontal', 'vertical'];

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'horizontal';
}

$base_classes =
    'flex w-fit items-stretch has-[>[data-slot=button-group]]:gap-2 ' .
    '[&>*]:focus-visible:relative [&>*]:focus-visible:z-10 ' .
    'has-[select[aria-hidden=true]:last-child]:[&>[data-slot=select-trigger]:last-of-type]:rounded-r-lg ' .
    "[&>[data-slot=select-trigger]:not([class*='w-'])]:w-fit [&>input]:flex-1";

$orientation_classes = [
    'horizontal' =>
        '[&>*:not(:first-child)]:rounded-l-none [&>*:not(:first-child)]:border-l-0 ' .
        '[&>*:not(:last-child)]:rounded-r-none',
    'vertical' =>
        'flex-col [&>*:not(:first-child)]:rounded-t-none [&>*:not(:first-child)]:border-t-0 ' .
        '[&>*:not(:last-child)]:rounded-b-none',
];

$computed_class = "{$base_classes} {$orientation_classes[$orientation]}";

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['role'] = 'group';
$element_attributes['data-slot'] = 'button-group';
$element_attributes['data-orientation'] = $orientation;

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
