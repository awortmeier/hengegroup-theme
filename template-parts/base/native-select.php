<?php

declare(strict_types=1);

// Native <select>/<option>/<optgroup> (text-only options). For a styled listbox use select.php.
//
// Config:
//   options        array          required: { value, text, selected, disabled } or
//                                 { label, options } for an <optgroup>
//   value          string|array   preselection (wins over `selected`), array when multiple
//   placeholder    string         disabled empty first option
//   multiple       bool
//   name           string
//   disabled / required   bool    disabled also sets data-disabled
//   aria_invalid   bool           aria-invalid + data-invalid
//   id             string         default wp_unique_id()
//   label          string         adds label.php
//   aria_label     string
//   class / attributes / data_attributes   on the <select>

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$options_config = is_array($config['options'] ?? null) ? $config['options'] : [];
$placeholder = trim((string) ($config['placeholder'] ?? ''));
$multiple = !empty($config['multiple']);
$name = trim((string) ($config['name'] ?? ''));
$disabled = !empty($config['disabled']);
$required = !empty($config['required']);
$aria_invalid = !empty($config['aria_invalid']);
$id = trim((string) ($config['id'] ?? ''));
$label_text = trim((string) ($config['label'] ?? ''));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$selected_values = null;

if (array_key_exists('value', $config)) {
    $raw_value = $config['value'];

    if ($multiple && is_array($raw_value)) {
        $selected_values = array_map(static fn($value): string => (string) $value, $raw_value);
    } else {
        $selected_values = [(string) $raw_value];
    }
}

$render_option = static function (array $option_config) use ($selected_values): string {
    $text = trim((string) ($option_config['text'] ?? ''));

    if ($text === '') {
        return '';
    }

    $value = (string) ($option_config['value'] ?? '');
    $option_attributes = ['value' => $value];

    $is_selected =
        $selected_values !== null
            ? in_array($value, $selected_values, true)
            : !empty($option_config['selected']);

    if ($is_selected) {
        $option_attributes['selected'] = true;
    }

    if (!empty($option_config['disabled'])) {
        $option_attributes['disabled'] = true;
    }

    return sprintf(
        '<option%1$s>%2$s</option>',
        hengegroup_theme_render_attributes($option_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        esc_html($text),
    );
};

$options_markup = '';

if ($placeholder !== '') {
    $placeholder_attributes = ['value' => '', 'disabled' => true, 'hidden' => true];

    if ($selected_values === null) {
        $placeholder_attributes['selected'] = true;
    }

    $options_markup .= sprintf(
        '<option%1$s>%2$s</option>',
        hengegroup_theme_render_attributes($placeholder_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        esc_html($placeholder),
    );
}

foreach ($options_config as $option_config) {
    if (!is_array($option_config)) {
        continue;
    }

    $group_options = is_array($option_config['options'] ?? null) ? $option_config['options'] : null;

    if ($group_options === null) {
        $options_markup .= $render_option($option_config); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        continue;
    }

    $group_markup = '';

    foreach ($group_options as $nested_option_config) {
        if (!is_array($nested_option_config)) {
            continue;
        }

        $group_markup .= $render_option($nested_option_config);
    }

    if ($group_markup === '') {
        continue;
    }

    $options_markup .= sprintf(
        '<optgroup label="%1$s">%2$s</optgroup>',
        esc_attr(trim((string) ($option_config['label'] ?? ''))),
        $group_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

if ($options_markup === '') {
    return;
}

if ($id === '') {
    $id = 'hengegroup-theme-native-select-' . wp_unique_id();
}

$base_classes =
    'placeholder:text-muted-foreground selection:bg-henge-green ' .
    'selection:text-henge-green-foreground border-input flex w-full min-w-0 rounded-lg border ' .
    'bg-background px-3.5 py-3 text-base shadow-xs transition-[color,box-shadow] outline-none ' .
    'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm ' .
    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] ' .
    'aria-invalid:ring-destructive/20 aria-invalid:border-destructive';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'native-select';
$element_attributes['id'] = $id;

if ($name !== '') {
    $element_attributes['name'] = $name;
}

if ($multiple) {
    $element_attributes['multiple'] = true;
}

if ($disabled) {
    $element_attributes['disabled'] = true;
    $element_attributes['data-disabled'] = 'true';
}

if ($required) {
    $element_attributes['required'] = true;
}

if ($aria_invalid) {
    $element_attributes['aria-invalid'] = 'true';
    $element_attributes['data-invalid'] = 'true';
}

if ($label_text === '' && $aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

$select_markup = sprintf(
    '<select%1$s>%2$s</select>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $options_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

if ($label_text === '') {
    echo $select_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    return;
}

ob_start();
get_template_part('template-parts/base/label', null, [
    'config' => ['text' => $label_text, 'for' => $id],
]);
$label_markup = (string) ob_get_clean();

printf(
    '<div class="flex flex-col gap-2" data-slot="native-select-field">%1$s%2$s</div>',
    $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $select_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
