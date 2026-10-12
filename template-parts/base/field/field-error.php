<?php

declare(strict_types=1);

// Error message(s) for a form field. role="alert" so later client-side updates are announced.
//
// Config:
//   text     string   single message
//   errors   array    several messages as <ul> (wins over `text`)
//   for      string   control id; derives the id via hengegroup_theme_field_error_id()
//   id       string   explicit id (wins over `for`), else wp_unique_id()
//   class / attributes / data_attributes

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
