<?php

declare(strict_types=1);

// shadcn/ui's InputGroup has no headless-library primitive behind it (Radix UI/Base UI/React Aria
// alike) -- like Input/Textarea itself, it's a plain styled wrapper, no JS state at all (see
// CLAUDE.md #1). InputGroup itself is just a `<div>` that visually owns the border/background/
// focus-ring that would normally sit on the input, so its nested InputGroupInput/InputGroupTextarea
// can render "bare" inside it -- pure CSS composition, not a component with behaviour.
//
// Content-agnostic wrapper, same pattern as button-group.php/aspect-ratio.php/kbd-group.php:
// buffer the nested control plus its addon(s) and pass the combined HTML string as
// `content`, instead of this file re-implementing input.php/textarea.php's attribute-building logic
// or button.php's icon/variant handling for the addons.
//
//   ob_start();
//   get_template_part('template-parts/base/input-group/input-group-addon', null, [
//       'config' => ['content' => hengegroup_theme_render_icon(['name' => 'search', 'set' => 'lucide'])],
//   ]);
//   get_template_part('template-parts/base/input', null, [
//       'config' => ['type' => 'search', 'placeholder' => 'Search...', 'data_slot' => 'input-group-control'],
//   ]);
//   $group_markup = (string) ob_get_clean();
//
//   get_template_part('template-parts/base/input-group/input-group', null, [
//       'config' => ['content' => $group_markup],
//   ]);
//
// The nested control MUST be given `data_slot: 'input-group-control'` (input.php's/textarea.php's
// own escape hatch for exactly this composition, see those files' header comments) -- without it,
// the control keeps its standalone `data-slot="input"`/`"textarea"` and project CSS scoped to
// `[data-slot="input-group-control"]` won't match. Any `label` pairing happens OUTSIDE this
// component (label.php + input-group.php as siblings) -- don't pass input.php's own `label` config
// here, it would nest an extra `<div data-slot="input-field">` wrapper inside the addon row.
//
// Deliberately NOT mirrored onto this wrapper: `disabled`/`aria_invalid`. shadcn's own InputGroup
// CSS reacts to the STATE OF ITS NESTED CONTROL via a relational selector
// (`[data-slot="input-group"]:has([data-slot="input-group-control"]:disabled)` /
// `:has([data-slot="input-group-control"][aria-invalid])`), not a duplicated prop on the group
// itself -- input.php/textarea.php already set `data-disabled`/`aria-invalid`/`data-invalid` on the
// control, `:has()` is all project CSS needs (no vocabulary shadcn doesn't have, see CLAUDE.md #1).
//
// Phase 2 (CLAUDE.md Regel 1): classes taken 1:1 from shadcn's own InputGroup (registry/
// new-york-v4/ui/input-group.tsx), same `dark:`-prefixed-classes-dropped/`rounded-md` ->
// `rounded-lg` deviations as input.php -- this wrapper now owns the border/background/focus-ring
// that input.php's own `data_slot === 'input-group-control'` branch deliberately omits, so nesting
// both doesn't double up a border. The fixed `h-9` is dropped for the same padding-driven-height
// reason as input.php: the nested control/addon carry the actual `py-3` rhythm instead.
//
// Supported config:
//   content       string   required. Pre-rendered HTML to wrap (caller's responsibility to
//                          escape/build via input.php/textarea.php + input-group-addon.php)
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

$base_classes =
    'border-input bg-background relative flex w-full items-center rounded-lg border ' .
    'shadow-xs transition-[color,box-shadow] outline-none has-[>textarea]:items-start ' .
    'focus-within:border-ring focus-within:ring-ring/50 focus-within:ring-[3px] ' .
    'has-[[data-slot=input-group-control]:disabled]:pointer-events-none ' .
    'has-[[data-slot=input-group-control]:disabled]:opacity-50 ' .
    'has-[[data-slot=input-group-control][aria-invalid=true]]:ring-destructive/20 ' .
    'has-[[data-slot=input-group-control][aria-invalid=true]]:border-destructive';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'input-group';

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
