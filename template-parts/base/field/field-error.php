<?php

declare(strict_types=1);

// shadcn/ui's FieldError: an accessible error container, accepting either a single message or an
// array of messages (shadcn's own docs mention react-hook-form/Zod/Valibot error arrays -- this
// file takes plain strings, translating a validation library's own error shape into that is the
// caller's job, not something baked in here). `role="alert"` is a harmless default even for
// purely server-rendered content (alerts only announce on a later DOM mutation, not on initial
// page parse) and matters once anything -- future client-side validation JS, a form library --
// starts inserting/updating this element after load.
//
// Phase 2 (CLAUDE.md Regel 1): classes taken 1:1 from shadcn's own FieldError (registry/
// new-york-v4/ui/field.tsx) -- it renders two shapes depending on message count (a plain div for
// one message, a div wrapping a `<ul>` for several), each with its own base class, same branching
// this file already has for `$errors`/`$text`.
//
// Supported config:
//   text     string   a single error message (plain text, escaped)
//   errors   array    multiple error messages, rendered as a <ul>; takes priority over `text` when
//                      both are given
//   for      string   the paired control's id -- when given (and `id` is omitted), the id rendered
//                      here is derived from it via hengegroup_theme_field_error_id() instead of a fresh
//                      wp_unique_id(), so the control's own `aria-describedby` can compute the
//                      identical string independently (hengegroup_theme_field_describedby($control_id)) --
//                      no id invented/copied by hand in two places. See field.php's header comment
//                      for the full wiring example, same convention as field-description.php's `for`
//   id       string   native `id`, takes priority over `for` when both are given; auto-generated via
//                      wp_unique_id() when neither is given -- pass this into the field's control via
//                      `attributes: ['aria-describedby' => $id]` (see field.php's header comment on
//                      why that wiring is the caller's job)
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ''));
$errors_config = is_array($config['errors'] ?? null) ? $config['errors'] : [];
$id = trim((string) ($config['id'] ?? ''));
$for = trim((string) ($config['for'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$errors = [];

foreach ($errors_config as $error) {
    $error_text = trim((string) $error);

    if ($error_text !== '') {
        $errors[] = $error_text;
    }
}

if ($errors === [] && $text === '') {
    return;
}

if ($id === '') {
    $id =
        $for !== ''
            ? hengegroup_theme_field_error_id($for)
            : 'hengegroup-theme-field-error-' . wp_unique_id();
}

$element_attributes = $attributes;
$element_attributes['data-slot'] = 'field-error';
$element_attributes['id'] = $id;
$element_attributes['role'] = 'alert';

if (count($errors) > 1) {
    $base_classes = 'text-destructive flex flex-col gap-1 text-sm font-normal';
    $element_attributes['class'] = trim(
        $base_classes . ($class_name !== '' ? ' ' . $class_name : ''),
    );

    $items_markup = '';

    foreach ($errors as $error_text) {
        $items_markup .= sprintf('<li>%s</li>', esc_html($error_text));
    }

    printf(
        '<div%1$s><ul class="ml-4 flex list-disc flex-col gap-1">%2$s</ul></div>',
        hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $items_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );

    return;
}

$base_classes = 'text-destructive text-sm font-normal';
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$message = $errors !== [] ? $errors[0] : $text;

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($message),
);
