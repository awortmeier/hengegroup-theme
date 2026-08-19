<?php

declare(strict_types=1);

// shadcn/ui's ToggleGroup wraps a headless UI primitive (historically Radix UI; shadcn now also
// ships Base UI/React Aria variants of many components): a context provider (shares `variant`/
// `size` with its items) around several ToggleGroupItem, JS-driven single/multiple selection state.
// Composition instead of duplication: this file nests several
// template-parts/base/toggle/toggle.php calls -- the exact same pattern radio-group.php already
// uses for radio.php -- rather than re-implementing toggle.php's icon/text/variant/size rendering
// here.
//
// `type` picks which native input toggle.php renders per item, matching each selection mode to its
// closest native primitive:
//   - `single` (mutually exclusive, one item selected) -> `type: 'radio'` toggles sharing one
//     `name`. Native radio grouping enforces the exclusivity with zero JS -- clicking one item
//     natively unchecks the others, nothing to wire up (same trick as radio-group.php).
//   - `multiple` (independent booleans, any number selected) -> `type: 'checkbox'` toggles (each
//     toggle.php's own default) -- a direct, unmodified nesting, no extra wiring needed at all.
//
// Same semantic trade-off as toggle.php itself, just restated at the group level: shadcn announces
// every item as "button, pressed/not pressed" regardless of `type`, selection exclusivity in
// `single` mode is JS-managed state, not a native role. This component instead announces `single`
// items as "radio button, selected, N of M" (arguably an even closer semantic fit for "choose one
// from a set" than a generic pressed button, if a different one than shadcn's own) and `multiple`
// items as "checkbox, checked/not checked" -- see toggle.php's header comment for the full
// reasoning. The wrapper role follows the same native-first logic: `role="radiogroup"` for `single`
// (the correct ARIA container for a set of same-named radios, matching radio-group.php), plain
// `role="group"` for `multiple` (a set of independent checkboxes, no radiogroup semantics apply).
//
// `multiple` mode's gap is closed: its items are plain `type: 'checkbox'` toggle.php instances
// under a plain `role="group"`, so assets/js/template-parts/base/toggle.js's enhanceToggle()
// upgrades each one to `role="button" aria-pressed` exactly like standalone toggle.php (see that
// file's header comment) -- nothing group-specific stood in the way.
//
// `single` mode's gap -- previously a known, deliberately still-open exception to the established
// ARIA-gap-closing rule -- is closed by assets/js/template-parts/base/toggle-group.js: unlike
// `multiple` mode, toggle.js's own enhanceToggle() alone can't do this (it explicitly skips
// radio-typed toggles inside a `role="radiogroup"`, exactly this component's `single` mode), because
// the small "hide native + relabel the paired <label>" trick that closes every other case in this
// theme doesn't apply on its own here -- shadcn's real ToggleGroup (`single`) is a
// `role="group"`/toolbar-pattern set of real `aria-pressed` buttons where JS itself manages the
// mutual-exclusivity that native radiogroup grouping gives this component for free; relabeling
// individual items to `role="button"` without also replacing that native exclusivity with
// JS-managed state would leave a broken half-upgrade (buttons that don't actually enforce "choose
// one"). toggle-group.js is that larger undertaking: it promotes the wrapper from
// `role="radiogroup"` to `role="group"`, then calls toggle.js's enhanceToggle() on each item (now
// unblocked, since the guard above no longer matches once the wrapper's role has changed) --
// reusing that logic rather than duplicating it -- and additionally
// re-syncs every sibling's `aria-pressed` on each item's `change` event, because native radio
// exclusivity does NOT fire `change` on the item that silently becomes unchecked, which
// enhanceToggle()'s own per-item listener alone would miss. The underlying native radio inputs
// (same shared `name`) are untouched otherwise -- actual "choose one" enforcement still comes from
// the browser's native radio-exclusivity behaviour, not reimplemented in JS; only the *announced*
// ARIA role/state changes. Without JS, `single` mode still falls back to announcing "radio button,
// selected, N of M", which -- as argued above -- is itself a defensible, WCAG-valid fit for this
// exact composition, not merely a broken fallback.
//
// `orientation` is exposed for CSS/ARIA parity with shadcn's vocabulary (data-orientation,
// aria-orientation) but, like radio-group.php, doesn't change actual arrow-key direction -- native
// Tab/Space (and, for `single`, native radio arrow-key) behaviour is DOM-order-based regardless of
// visual layout; not a functional gap worth solving with JS for the common case (see CLAUDE.md #1).
//
// Supported config:
//   type          string   single | multiple (default: single) -- selection mode, see above
//   items         array    required, ordered list of:
//     value          string   required. This item's native `value`
//     text / label    string   visible label text (omit for an icon-only item)
//     icon            array    icon.php config, e.g. ['name' => 'bold', 'set' => 'lucide']
//     icon_position   string   start | end (ignored for icon-only items)
//     aria_label      string   accessible name, required for icon-only items
//     disabled        bool     per-item disabled (in addition to the group-wide `disabled`)
//     variant         string   per-item override of the group's `variant`
//     size            string   per-item override of the group's `size`
//     id              string   this item's id; auto-generated via wp_unique_id() when omitted
//   value         string|array   the currently selected value(s) -- a string for `single` (compared
//                          against each item's `value`), an array of strings for `multiple` (each
//                          item whose `value` is in the array gets `pressed`). Matches shadcn's own
//                          `value` prop, which is likewise a string for `single` and a string[] for
//                          `multiple`.
//   variant       string   default | outline (default: default) -- shared default for every item,
//                          same vocabulary as toggle.php, propagated like shadcn's context provider
//   size          string   default | sm | lg (default: default) -- same propagation as `variant`
//   disabled      bool     disables every item in the group; also sets `data-disabled="true"` on
//                          the group wrapper itself (there's no native `disabled` attribute for a
//                          <div>), matching radio-group.php's convention
//   required      bool     applied to every item's underlying checkbox/radio input -- same
//                          cross-family-consistency reasoning as toggle.php's own `required`
//                          (matches radio-group.php's own `required`, which does the same per item)
//   aria_invalid  bool     sets aria-invalid="true" plus a mirrored data-invalid="true" on the
//                          group wrapper -- same error-state hooks as radio-group.php's own
//                          `aria_invalid`, kept on the wrapper rather than per item for the same
//                          reason
//   name          string   base form field name. `single`: the literal shared radio `name` (what
//                          makes the items mutually exclusive at all), auto-generated via
//                          wp_unique_id() when omitted, exactly like radio-group.php. `multiple`:
//                          optional; when given, each item gets `{name}[]` (standard HTML
//                          checkbox-array submission convention) -- omit for no form participation.
//   orientation   string   horizontal (default, matches shadcn's own default row layout) | vertical
//                          -- sets data-orientation/aria-orientation only, see note above
//   aria_label    string   accessible name for the group wrapper
//   class / attributes / data_attributes   passthrough onto the outer
//                          <div role="radiogroup"|"group" data-slot="toggle-group"> wrapper

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$type = trim((string) ($config['type'] ?? 'single'));
$items_config = is_array($config['items'] ?? null) ? $config['items'] : [];
$group_variant = trim((string) ($config['variant'] ?? 'default'));
$group_size = trim((string) ($config['size'] ?? 'default'));
$group_disabled = !empty($config['disabled']);
$required = !empty($config['required']);
$aria_invalid = !empty($config['aria_invalid']);
$name = trim((string) ($config['name'] ?? ''));
$orientation = trim((string) ($config['orientation'] ?? 'horizontal'));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_types = ['single', 'multiple'];
$allowed_variants = ['default', 'outline'];
$allowed_sizes = ['default', 'sm', 'lg'];
$allowed_orientations = ['horizontal', 'vertical'];

if (!in_array($type, $allowed_types, true)) {
    $type = 'single';
}

if (!in_array($group_variant, $allowed_variants, true)) {
    $group_variant = 'default';
}

if (!in_array($group_size, $allowed_sizes, true)) {
    $group_size = 'default';
}

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'horizontal';
}

$is_single = $type === 'single';

if ($is_single) {
    $selected_values = array_key_exists('value', $config) ? [(string) $config['value']] : [];

    if ($name === '') {
        $name = 'hengegroup-theme-toggle-group-' . wp_unique_id();
    }
} else {
    $raw_values = $config['value'] ?? [];
    $selected_values = array_map(
        'strval',
        is_array($raw_values) ? $raw_values : [(string) $raw_values],
    );
}

$items_markup = '';

foreach ($items_config as $item_config) {
    if (!is_array($item_config)) {
        continue;
    }

    $value = trim((string) ($item_config['value'] ?? ''));

    if ($value === '') {
        continue;
    }

    $item_variant = trim((string) ($item_config['variant'] ?? $group_variant));

    if (!in_array($item_variant, $allowed_variants, true)) {
        $item_variant = $group_variant;
    }

    $item_size = trim((string) ($item_config['size'] ?? $group_size));

    if (!in_array($item_size, $allowed_sizes, true)) {
        $item_size = $group_size;
    }

    ob_start();
    get_template_part('template-parts/base/toggle/toggle', null, [
        'config' => [
            'type' => $is_single ? 'radio' : 'checkbox',
            'name' => $is_single ? $name : ($name !== '' ? $name . '[]' : ''),
            'value' => $value,
            'pressed' => in_array($value, $selected_values, true),
            'disabled' => $group_disabled || !empty($item_config['disabled']),
            'required' => $required,
            'variant' => $item_variant,
            'size' => $item_size,
            'text' => trim((string) ($item_config['text'] ?? ($item_config['label'] ?? ''))),
            'icon' => is_array($item_config['icon'] ?? null) ? $item_config['icon'] : null,
            'icon_position' => trim((string) ($item_config['icon_position'] ?? 'start')),
            'aria_label' => trim((string) ($item_config['aria_label'] ?? '')),
            'id' => trim((string) ($item_config['id'] ?? '')),
        ],
    ]);
    $items_markup .= (string) ob_get_clean();
}

if ($items_markup === '') {
    return;
}

$wrapper_attributes = $attributes;

if ($class_name !== '') {
    $wrapper_attributes['class'] = $class_name;
}

$wrapper_attributes['role'] = $is_single ? 'radiogroup' : 'group';
$wrapper_attributes['data-slot'] = 'toggle-group';
$wrapper_attributes['data-type'] = $type;
$wrapper_attributes['data-variant'] = $group_variant;
$wrapper_attributes['data-size'] = $group_size;
$wrapper_attributes['data-orientation'] = $orientation;
$wrapper_attributes['aria-orientation'] = $orientation;

if ($group_disabled) {
    $wrapper_attributes['data-disabled'] = 'true';
}

if ($aria_invalid) {
    $wrapper_attributes['aria-invalid'] = 'true';
    $wrapper_attributes['data-invalid'] = 'true';
}

if ($aria_label !== '') {
    $wrapper_attributes['aria-label'] = $aria_label;
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $wrapper_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $items_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
