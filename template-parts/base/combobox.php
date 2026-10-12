<?php

declare(strict_types=1);

// Combobox on <input list> + <datalist> (works without JS, submits the display text).
// combobox.js builds a styled listbox from the datalist, sets the combobox ARIA and removes the
// `list` attribute; only then does `value` get submitted separately from the text.
//
// Config:
//   options                        array    required, each { value (required), text, group, disabled }
//   value                          string   preselected option
//   placeholder                    string
//   empty_text                     string   default "No results found."
//   name                           string
//   disabled / required / readonly bool     disabled also sets data-disabled
//   aria_invalid                   bool     aria-invalid + data-invalid
//   id                             string   default wp_unique_id()
//   label                          string   adds label.php
//   aria_label                     string
//   class / attributes / data_attributes   on the <input>

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$options_config = is_array($config['options'] ?? null) ? $config['options'] : [];
$selected_value = array_key_exists('value', $config) ? (string) $config['value'] : null;
$placeholder = trim((string) ($config['placeholder'] ?? ''));
$empty_text = trim((string) ($config['empty_text'] ?? ''));
$name = trim((string) ($config['name'] ?? ''));
$disabled = !empty($config['disabled']);
$required = !empty($config['required']);
$readonly = !empty($config['readonly']);
$aria_invalid = !empty($config['aria_invalid']);
$id = trim((string) ($config['id'] ?? ''));
$label_text = trim((string) ($config['label'] ?? ''));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($empty_text === '') {
    $empty_text = esc_html__('No results found.', 'hengegroup-theme');
}

$options_markup = '';
$initial_text = '';

foreach ($options_config as $option_config) {
    if (!is_array($option_config)) {
        continue;
    }

    $value = trim((string) ($option_config['value'] ?? ''));

    if ($value === '') {
        continue;
    }

    $text = trim((string) ($option_config['text'] ?? ''));

    if ($text === '') {
        $text = $value;
    }

    $group = trim((string) ($option_config['group'] ?? ''));
    $option_disabled = !empty($option_config['disabled']);

    if ($selected_value !== null && $selected_value === $value) {
        $initial_text = $text;
    }

    $option_attributes = ['value' => $text, 'data-value' => $value];

    if ($group !== '') {
        $option_attributes['data-group'] = $group;
    }

    if ($option_disabled) {
        $option_attributes['data-disabled'] = 'true';
    }

    $options_markup .= sprintf(
        '<option%1$s>%2$s</option>',
        hengegroup_theme_render_attributes($option_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        esc_html($text),
    );
}

if ($options_markup === '') {
    return;
}

if ($id === '') {
    $id = 'hengegroup-theme-combobox-' . wp_unique_id();
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

$element_attributes['type'] = 'text';
$element_attributes['data-slot'] = 'combobox-input';
$element_attributes['id'] = $id;
$element_attributes['list'] = $id . '-datalist';
$element_attributes['autocomplete'] = 'off';

if ($name !== '') {
    $element_attributes['name'] = $name;
}

if ($placeholder !== '') {
    $element_attributes['placeholder'] = $placeholder;
}

if ($initial_text !== '') {
    $element_attributes['value'] = $initial_text;
}

if ($disabled) {
    $element_attributes['disabled'] = true;
    $element_attributes['data-disabled'] = 'true';
}

if ($required) {
    $element_attributes['required'] = true;
}

if ($readonly) {
    $element_attributes['readonly'] = true;
}

if ($aria_invalid) {
    $element_attributes['aria-invalid'] = 'true';
    $element_attributes['data-invalid'] = 'true';
}

if ($label_text === '' && $aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

$input_markup = '<input' . hengegroup_theme_render_attributes($element_attributes) . '>';

$datalist_markup = sprintf(
    '<datalist id="%1$s">%2$s</datalist>',
    esc_attr($id . '-datalist'),
    $options_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

// Absolute so the panel floats over the following content. Not scroll-area.php: it renders
// nothing for empty content, but this shell is filled by JS.
$content_markup = sprintf(
    '<div class="absolute top-full left-0 z-50 mt-2 max-h-64 w-full overflow-y-auto ' .
        '[scrollbar-width:thin] [scrollbar-color:var(--color-border)_transparent] ' .
        '[&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar]:h-2 ' .
        '[&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:rounded-full ' .
        '[&::-webkit-scrollbar-thumb]:bg-border ' .
        'rounded-lg border border-input bg-background p-1 shadow-md" ' .
        'data-slot="combobox-content" id="%1$s" role="listbox" hidden></div>',
    esc_attr($id . '-listbox'),
);

$indicator_markup = hengegroup_theme_render_icon(['name' => 'check', 'set' => 'lucide']);
$indicator_template_markup = sprintf(
    '<template data-slot="combobox-item-indicator-template">%s</template>',
    $indicator_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
$empty_template_markup = sprintf(
    '<template data-slot="combobox-empty-template">%s</template>',
    esc_html($empty_text),
);

$combobox_markup = sprintf(
    '<div class="relative" data-slot="combobox">%1$s%2$s%3$s%4$s%5$s</div>',
    $input_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $datalist_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $indicator_template_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $empty_template_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

if ($label_text === '') {
    echo $combobox_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    return;
}

ob_start();
get_template_part('template-parts/base/label', null, [
    'config' => ['text' => $label_text, 'for' => $id],
]);
$label_markup = (string) ob_get_clean();

printf(
    '<div class="flex flex-col gap-2" data-slot="combobox-field">%1$s%2$s</div>',
    $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $combobox_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
