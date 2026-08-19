<?php

declare(strict_types=1);

// Direct translation of shadcn/ui's ButtonGroup: a content-agnostic wrapper, entirely CSS-driven,
// no JS. shadcn's own implementation is just `role="group"` + adjacent-sibling CSS selectors
// (`:not(:first-child)`/`:not(:last-child)`) that strip border-radius/border off touching
// children so multiple buttons visually merge into one connected bar ("segmented control"/
// split-button look) -- a project-CSS concern, not baked in here (see CLAUDE.md #1).
//
// Same nesting pattern as aspect-ratio.php/kbd/kbd-group.php: buffer the inner
// button.php/separator.php/button-group-text.php output(s) and pass the combined HTML string as
// `content`. shadcn's own ButtonGroupSeparator is nothing but their Separator with
// orientation="vertical" -- that's exactly template-parts/base/separator.php, reused unchanged,
// not a new component.
//
//   ob_start();
//   get_template_part('template-parts/base/button', null, ['config' => ['text' => 'Left']]);
//   get_template_part('template-parts/base/separator', null, ['config' => ['orientation' => 'vertical']]);
//   get_template_part('template-parts/base/button', null, ['config' => ['text' => 'Right']]);
//   $group_markup = (string) ob_get_clean();
//
//   get_template_part('template-parts/base/button-group/button-group', null, [
//       'config' => ['content' => $group_markup],
//   ]);
//
// Supported config:
//   content       string   required. Pre-rendered HTML to wrap (caller's responsibility to
//                          escape/build, e.g. via template-parts/base/button.php)
//   orientation   string   horizontal (default) | vertical -- sets data-orientation
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

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

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
    base_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
