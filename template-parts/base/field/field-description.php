<?php

declare(strict_types=1);

// shadcn/ui's FieldDescription: plain helper text below/beside a field's control (e.g. "We will
// never share your email."). A <p>, no ARIA role beyond what plain text already provides -- the
// long-line-balancing behaviour shadcn's own docs mention is `text-wrap: balance`, a project-CSS
// concern (CLAUDE.md #1), not something this file renders.
//
// Supported config:
//   text   string   required. Visible content (plain text, escaped)
//   for    string   the paired control's id -- when given (and `id` is omitted), the id rendered
//                    here is derived from it via base_theme_field_description_id() instead of a
//                    fresh wp_unique_id(), so the control's own `aria-describedby` can compute the
//                    identical string independently (base_theme_field_describedby($control_id)) --
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
            ? base_theme_field_description_id($for)
            : 'base-theme-field-description-' . wp_unique_id();
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

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
    base_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($text),
);
