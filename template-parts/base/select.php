<?php

declare(strict_types=1);

// Styled select: nests native-select.php as the data source (works without JS) plus a hidden
// trigger and empty listbox. select.js builds the items from the native <option>s (select-only
// combobox pattern), syncs the value back via `change` and sets data-js; the native select then
// stays visually hidden but focusable for its label.
//
// Config:
//   options / value / placeholder / name / disabled / required / aria_invalid   as native-select.php
//   size         string   default | sm
//   id           string   id of the native select, default wp_unique_id()
//   label        string   adds label.php
//   aria_label   string   required without a visible label (the label points at the native select)
//   class / attributes / data_attributes   on the trigger

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$options_config = is_array($config['options'] ?? null) ? $config['options'] : [];
$placeholder = trim((string) ($config['placeholder'] ?? ''));
$name = trim((string) ($config['name'] ?? ''));
$disabled = !empty($config['disabled']);
$required = !empty($config['required']);
$aria_invalid = !empty($config['aria_invalid']);
$size = trim((string) ($config['size'] ?? 'default'));
$id = trim((string) ($config['id'] ?? ''));
$label_text = trim((string) ($config['label'] ?? ''));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($options_config === []) {
    return;
}

$allowed_sizes = ['default', 'sm'];

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'default';
}

if ($id === '') {
    $id = 'hengegroup-theme-select-' . wp_unique_id();
}

// Flattened only to find the initial trigger text.
$flat_options = [];

foreach ($options_config as $option_config) {
    if (!is_array($option_config)) {
        continue;
    }

    $nested_options = is_array($option_config['options'] ?? null)
        ? $option_config['options']
        : null;

    if ($nested_options !== null) {
        foreach ($nested_options as $nested_option_config) {
            if (is_array($nested_option_config)) {
                $flat_options[] = $nested_option_config;
            }
        }

        continue;
    }

    $flat_options[] = $option_config;
}

$target_value = array_key_exists('value', $config) ? (string) $config['value'] : null;
$trigger_text = '';

if ($target_value !== null) {
    foreach ($flat_options as $option_config) {
        if ((string) ($option_config['value'] ?? '') === $target_value) {
            $trigger_text = trim((string) ($option_config['text'] ?? ''));
            break;
        }
    }
} else {
    foreach ($flat_options as $option_config) {
        if (!empty($option_config['selected'])) {
            $trigger_text = trim((string) ($option_config['text'] ?? ''));
            break;
        }
    }

    if ($trigger_text === '' && $placeholder === '' && $flat_options !== []) {
        $trigger_text = trim((string) ($flat_options[0]['text'] ?? ''));
    }
}

if ($trigger_text === '') {
    $trigger_text = $placeholder;
}

ob_start();
get_template_part('template-parts/base/native-select', null, [
    'config' => [
        'options' => $options_config,
        'value' => $config['value'] ?? null,
        'placeholder' => $placeholder,
        'name' => $name,
        'disabled' => $disabled,
        'required' => $required,
        'aria_invalid' => $aria_invalid,
        'id' => $id,
    ],
]);
$native_select_markup = (string) ob_get_clean();

if ($native_select_markup === '') {
    return;
}

$base_classes =
    'border-input data-[placeholder]:text-muted-foreground flex w-full items-center ' .
    'justify-between gap-2 rounded-lg border bg-background text-base shadow-xs ' .
    'transition-[color,box-shadow] outline-none focus-visible:border-ring ' .
    'focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive ' .
    'aria-invalid:ring-destructive/20 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm ' .
    "[&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4";

$size_classes = [
    'default' => 'px-3.5 py-3',
    'sm' => 'px-3 py-2 text-sm',
];

$computed_class = "{$base_classes} {$size_classes[$size]}";

$trigger_attributes = $attributes;
$trigger_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$trigger_attributes['data-slot'] = 'select-trigger';
$trigger_attributes['data-size'] = $size;
$trigger_attributes['type'] = 'button';
$trigger_attributes['role'] = 'combobox';
$trigger_attributes['aria-haspopup'] = 'listbox';
$trigger_attributes['aria-expanded'] = 'false';
$trigger_attributes['aria-controls'] = $id . '-listbox';

if ($disabled) {
    $trigger_attributes['disabled'] = true;
    $trigger_attributes['data-disabled'] = 'true';
}

if ($aria_invalid) {
    $trigger_attributes['aria-invalid'] = 'true';
    $trigger_attributes['data-invalid'] = 'true';
}

if ($required) {
    // The trigger is a <button>, so `required` must be announced via ARIA.
    $trigger_attributes['aria-required'] = 'true';
}

if ($label_text === '' && $aria_label !== '') {
    $trigger_attributes['aria-label'] = $aria_label;
}

$trigger_attributes = hengegroup_theme_merge_data_attributes($trigger_attributes, $data_attributes);

$chevron_markup = hengegroup_theme_render_icon(['name' => 'chevron-down', 'set' => 'lucide']);
$check_markup = hengegroup_theme_render_icon(['name' => 'check', 'set' => 'lucide']);

$trigger_markup = sprintf(
    '<button%1$s><span data-slot="select-value">%2$s</span>%3$s</button>',
    hengegroup_theme_render_attributes($trigger_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($trigger_text),
    $chevron_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

// Absolute so the panel floats over the following content. Not scroll-area.php: it renders
// nothing for empty content, but this shell is filled by JS.
$content_markup = sprintf(
    '<div class="absolute top-full left-0 z-50 mt-2 max-h-64 w-full overflow-y-auto ' .
        '[scrollbar-width:thin] [scrollbar-color:var(--color-border)_transparent] ' .
        '[&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar]:h-2 ' .
        '[&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:rounded-full ' .
        '[&::-webkit-scrollbar-thumb]:bg-border ' .
        'rounded-lg border border-input bg-background p-1 shadow-overlay" ' .
        'data-slot="select-content" id="%1$s" role="listbox" hidden></div>',
    esc_attr($id . '-listbox'),
);

$indicator_template_markup = sprintf(
    '<template data-slot="select-item-indicator-template">%s</template>',
    $check_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

$select_markup = sprintf(
    '<div class="relative" data-slot="select">%1$s%2$s%3$s%4$s</div>',
    $native_select_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $indicator_template_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
    '<div class="flex flex-col gap-2" data-slot="select-field">%1$s%2$s</div>',
    $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $select_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
