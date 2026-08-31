<?php

declare(strict_types=1);

// shadcn/ui's FieldDescription: plain helper text below/beside a field's control (e.g. "We will
// never share your email."). A <p>, no ARIA role beyond what plain text already provides -- the
// long-line-balancing behaviour shadcn's own docs mention is `text-wrap: balance`, a project-CSS
// concern (CLAUDE.md #1), not something this file renders.
//
// Phase 2 (CLAUDE.md Regel 1): base class taken 1:1 from shadcn's own FieldDescription (registry/
// new-york-v4/ui/field.tsx, live-checked 2026-08-30). `nth-last-2:-mt-1`/
// `[[data-variant=legend]+&]:-mt-1.5` are spacing nudges for two specific adjacency cases (this is
// the second-to-last child in its field/field-set, or it directly follows a field-legend.php) --
// kept 1:1 even without a demonstrated caller yet, same forward-compatible-selector reasoning as
// button-group.php's `[data-slot=select-trigger]` clause.
//
// Supported config:
//   text   string   required. Visible content (plain text, escaped)
//   for    string   the paired control's id -- when given (and `id` is omitted), the id rendered
//                    here is derived from it via hengegroup_theme_field_description_id() instead of a
//                    fresh wp_unique_id(), so the control's own `aria-describedby` can compute the
//                    identical string independently (hengegroup_theme_field_describedby($control_id)) --
//                    no id invented/copied by hand in two places. See field.php's header comment
//                    for the full wiring example
//   id     string   native `id`, takes priority over `for` when both are given; auto-generated via
//                    wp_unique_id() when neither is given -- pass this into the field's control via
//                    `attributes: ['aria-describedby' => $id]` (see field.php's header comment on
//                    why that wiring is the caller's job)
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ''));
$id = trim((string) ($config['id'] ?? ''));
$for = trim((string) ($config['for'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($text === '') {
    return;
}

if ($id === '') {
    $id =
        $for !== ''
            ? hengegroup_theme_field_description_id($for)
            : 'hengegroup-theme-field-description-' . wp_unique_id();
}

$base_classes =
    'text-muted-foreground text-sm leading-normal font-normal last:mt-0 nth-last-2:-mt-1 ' .
    'group-has-[[data-orientation=horizontal]]/field:text-balance ' .
    '[[data-variant=legend]+&]:-mt-1.5 [&>a]:underline [&>a]:underline-offset-4 ' .
    '[&>a:hover]:text-henge-green';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'field-description';
$element_attributes['id'] = $id;

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<p%1$s>%2$s</p>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);
