<?php

declare(strict_types=1);

// Produkt-Anfrageformular, verarbeitet von inc/setup/theme-product-inquiries.php.
//
// Args:
//   product_id      int      0 = allgemeine Anfrage
//   with_location   bool     PLZ/Ort-Pflichtfelder
//   tone            string   light | dark (Beschriftungsfarbe)
//   accent          string   siehe request-form.php
//   message_label   string   default "Nachricht"

$product_id = (int) ($args['product_id'] ?? 0);
$with_location = !empty($args['with_location']);
$is_dark = ($args['tone'] ?? 'light') === 'dark';
$message_label = trim((string) ($args['message_label'] ?? ''));
$message_label = $message_label !== '' ? $message_label : __('Nachricht', 'hengegroup-theme');

$state = hengegroup_theme_get_inquiry_state();
$values = $state['values'];
$errors = $state['errors'];
$form_id = wp_unique_id('hengegroup-theme-inquiry-');
$value = static fn(string $field): string => (string) ($values[$field] ?? '');
$label_class = 'text-sm font-semibold ' . ($is_dark ? 'text-grey-light' : 'text-grey-dark');
$error_class = $is_dark ? '!text-red-300' : '';

$print_field = static function (array $field): void {
    printf(
        '%s',
        hengegroup_theme_render_form_field($field), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered by base components.
    );
};

$input = static function (
    string $field,
    string $label_text,
    string $type = 'text',
    bool $required = false,
    string $autocomplete = '',
) use ($form_id, $value, $errors, $label_class, $error_class, $print_field): void {
    $control_id = $form_id . '-' . $field;

    ob_start();
    get_template_part('template-parts/base/input', null, [
        'config' => [
            'id' => $control_id,
            'type' => $type,
            'name' => $field,
            'value' => $value($field),
            'autocomplete' => $autocomplete,
            'required' => $required,
            'maxlength' => '200',
            'aria_invalid' => isset($errors[$field]),
            'attributes' => isset($errors[$field])
                ? ['aria-describedby' => hengegroup_theme_field_error_id($control_id)]
                : [],
        ],
    ]);

    $print_field([
        'control_id' => $control_id,
        'control' => (string) ob_get_clean(),
        'label' => $required ? $label_text . '*' : $label_text,
        'error' => (string) ($errors[$field] ?? ''),
        'label_class' => $label_class,
        'error_class' => $error_class,
    ]);
};

$field_rows = [
    [
        ['company', __('Firma', 'hengegroup-theme'), 'text', true, 'organization'],
        ['name', __('Name, Vorname', 'hengegroup-theme'), 'text', true, 'name'],
    ],
];

if ($with_location) {
    $field_rows[] = [
        ['postal_code', __('Postleitzahl', 'hengegroup-theme'), 'text', true, 'postal-code'],
        ['city', __('Ort', 'hengegroup-theme'), 'text', true, 'address-level2'],
    ];
}

$field_rows[] = [
    ['email', __('E-Mail-Adresse', 'hengegroup-theme'), 'email', true, 'email'],
    ['phone', __('Telefon', 'hengegroup-theme'), 'tel', false, 'tel'],
];

ob_start();

foreach ($field_rows as $field_row) {
    echo '<div class="grid gap-5 sm:grid-cols-2">';

    foreach ($field_row as $field_config) {
        $input(...$field_config);
    }

    echo '</div>';
}

ob_start();
get_template_part('template-parts/base/textarea', null, [
    'config' => [
        'id' => $form_id . '-message',
        'name' => 'message',
        'value' => $value('message'),
        'rows' => 4,
        'maxlength' => '5000',
    ],
]);
$print_field([
    'control_id' => $form_id . '-message',
    'control' => (string) ob_get_clean(),
    'label' => $message_label,
    'label_class' => $label_class,
]);

get_template_part('template-parts/components/request-form', null, [
    'config' => [
        'id' => $form_id,
        'action' => HENGEGROUP_THEME_INQUIRY_ACTION,
        'nonce_name' => '_hengegroup_theme_inquiry_nonce',
        'state' => $state,
        'content' => (string) ob_get_clean(),
        'hidden' => [
            'product_id' => (string) $product_id,
            'return_id' => (string) get_queried_object_id(),
            'with_location' => $with_location ? '1' : '',
        ],
        'tone' => $is_dark ? 'dark' : 'light',
        'accent' => (string) ($args['accent'] ?? ''),
        'data_attributes' => ['form' => 'inquiry'],
    ],
]);
