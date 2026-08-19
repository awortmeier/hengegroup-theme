<?php

declare(strict_types=1);

// shadcn/ui's Select wraps a headless UI primitive (historically Radix UI; shadcn now also ships
// Base UI/React Aria variants of many components): a custom, JS-driven popover-based dropdown
// (SelectTrigger/SelectContent/SelectItem) with its own keyboard/ARIA handling. Unlike every other
// base component so far, there is no native HTML equivalent for a fully custom-styled option list
// (see CLAUDE.md #1) -- this is the case that rule anticipates as "State wird zwingend gebraucht",
// and building it is the whole point of this file (native-select.php already covers the
// zero-JS case, see that file's header comment).
//
// Architecture (progressive enhancement, composition):
//   - This component nests template-parts/base/native-select.php (`data-slot="native-select"`)
//     as the ONE authoritative data source: real <option>/<optgroup> elements, real form
//     participation, works perfectly with zero JS on its own.
//   - It additionally renders an (initially hidden) custom trigger <button> +
//     <div role="listbox"> shell. assets/js/template-parts/base/select.js (mirrors this file's
//     own path under assets/js/template-parts/) finds these on page load,
//     builds the listbox items FROM the native select's own <option>/<optgroup> DOM (single
//     source of truth -- no separate PHP/JS option list to keep in sync), and wires up
//     open/close + keyboard navigation via the WAI-ARIA "select-only combobox" pattern
//     (role="combobox" + aria-activedescendant, focus stays on the trigger). Selecting a custom
//     item sets the native select's value and dispatches a `change` event on it, so the native
//     select stays the source of truth for forms/any other code observing it.
//   - CSS contract: the JS sets a `data-js` attribute on the outer <div data-slot="select"> once
//     it has initialized. Project CSS must gate visibility on that attribute:
//       [data-slot="select"]:not([data-js]) [data-slot="select-trigger"],
//       [data-slot="select"]:not([data-js]) [data-slot="select-content"] { display: none; }
//       [data-slot="select"][data-js] [data-slot="native-select"] { /* visually-hidden rules,
//         NOT display:none/visibility:hidden -- it must stay focusable so the paired `label`
//         (for/id) still works; JS also redirects a focus event on it back to the trigger */ }
//     Without JS (or before it runs), the real native select stays visible/usable -- this is
//     deliberate graceful degradation, not a bug.
//
// Scope: single-select only, matching shadcn's own stock Select (no `multiple` -- that's a
// genuinely different, more complex component, not a variant of this one, see native-select.php).
// No search/type-ahead/virtualized lists in v1 -- deferred extension points, not added
// speculatively without a concrete need.
//
// Supported config:
//   options / value / placeholder / name / disabled / required / aria_invalid
//                    same meaning as native-select.php (passed straight through to the nested
//                    native-select.php call) -- see that file's config docs. `disabled`/
//                    `aria_invalid` are also mirrored onto the trigger button itself as
//                    data-disabled="true"/data-invalid="true" (shadcn's own CSS-hook convention);
//                    `required` is additionally mirrored as aria-required="true" on the trigger --
//                    unlike the other two, `required` has no data-* mirror to stay consistent with
//                    it, since none of the sibling form components mirror `required` that way
//                    either (see input.php)
//   size             string   default | sm -- shadcn's SelectTrigger size (default: default)
//   id               string   native id on the underlying native select; auto-generated via
//                    wp_unique_id() when omitted (needed to pair with `label` via its `for`
//                    attribute)
//   label            string   optional visible label text; when given, nests
//                    template-parts/base/label.php before the select, both wrapped in a plain
//                    <div data-slot="select-field">. Omit for full manual control.
//   aria_label       string   accessible name for the trigger, used only when no visible `label`
//                    is given (same visible-label-wins gating as every sibling form component)
//                    -- required once `label` is omitted, since label.php's `for`
//                    pairs with the native select, not the trigger button that actually receives
//                    focus after JS init
//   class / attributes / data_attributes   passthrough onto the trigger <button> (shadcn's own
//                    SelectTrigger receives className the same way, not the outer wrapper)

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

// Flatten groups once, just to resolve the trigger's initial label text -- native-select.php does
// its own (separate) options handling for the actual <option>/<optgroup> markup; duplicating that
// full logic here isn't worth it for what's ultimately a single text lookup.
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

$trigger_attributes = $attributes;

if ($class_name !== '') {
    $trigger_attributes['class'] = $class_name;
}

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
    // native-select.php's underlying <select required> already announces this natively, but after
    // JS init the trigger <button role="combobox"> is the actually focused/interactive element --
    // a plain <button> has no native `required` semantics, so aria-required is the only way a
    // screen reader user learns this field is required from the trigger itself.
    $trigger_attributes['aria-required'] = 'true';
}

// Same visible-label-wins gating as every sibling form component (input.php, textarea.php,
// checkbox.php, radio.php, switch.php, input-otp.php, combobox.php, native-select.php): setting
// aria-label unconditionally would be harmless on its own, but label.php's `for="$id"` here points
// at the native select (hidden after JS init, not the trigger), so the trigger has no native label
// association either way -- aria_label is this component's only way to give the trigger an
// accessible name, and must stay required whenever a visible `label` isn't given.
if ($label_text === '' && $aria_label !== '') {
    $trigger_attributes['aria-label'] = $aria_label;
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $trigger_attributes['data-' . $data_name] = $attribute_value;
}

$chevron_markup = hengegroup_theme_render_icon(['name' => 'chevron-down', 'set' => 'lucide']);
$check_markup = hengegroup_theme_render_icon(['name' => 'check', 'set' => 'lucide']);

$trigger_markup = sprintf(
    '<button%1$s><span data-slot="select-value">%2$s</span>%3$s</button>',
    hengegroup_theme_render_attributes($trigger_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    esc_html($trigger_text),
    $chevron_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

$content_markup = sprintf(
    '<div data-slot="select-content" id="%1$s" role="listbox" hidden></div>',
    esc_attr($id . '-listbox'),
);

$indicator_template_markup = sprintf(
    '<template data-slot="select-item-indicator-template">%s</template>',
    $check_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

$select_markup = sprintf(
    '<div data-slot="select">%1$s%2$s%3$s%4$s</div>',
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
    '<div data-slot="select-field">%1$s%2$s</div>',
    $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $select_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
