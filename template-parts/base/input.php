<?php

declare(strict_types=1);

// Text input. With `data_slot: 'input-group-control'` it renders bare (input-group.php owns the
// border/background).
//
// Config:
//   type             string   native type (default text)
//   name / value / placeholder / autocomplete / min / max / step / minlength / maxlength / pattern
//   disabled / required / readonly   bool   disabled also sets data-disabled
//   aria_invalid     bool     aria-invalid + data-invalid
//   id               string   default wp_unique_id()
//   data_slot        string   default 'input'
//   label            string   adds label.php, both wrapped in <div data-slot="input-field">
//   aria_label       string
//   class / attributes / data_attributes   on the <input>

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$type = trim((string) ($config['type'] ?? 'text'));
$name = trim((string) ($config['name'] ?? ''));
$value = trim((string) ($config['value'] ?? ''));
$placeholder = trim((string) ($config['placeholder'] ?? ''));
$autocomplete = trim((string) ($config['autocomplete'] ?? ''));
$min = trim((string) ($config['min'] ?? ''));
$max = trim((string) ($config['max'] ?? ''));
$step = trim((string) ($config['step'] ?? ''));
$minlength = trim((string) ($config['minlength'] ?? ''));
$maxlength = trim((string) ($config['maxlength'] ?? ''));
$pattern = trim((string) ($config['pattern'] ?? ''));
$disabled = !empty($config['disabled']);
$required = !empty($config['required']);
$readonly = !empty($config['readonly']);
$aria_invalid = !empty($config['aria_invalid']);
$id = trim((string) ($config['id'] ?? ''));
$data_slot = trim((string) ($config['data_slot'] ?? ''));
$label_text = trim((string) ($config['label'] ?? ''));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($data_slot === '') {
    $data_slot = 'input';
}

$allowed_types = [
    'text',
    'email',
    'password',
    'number',
    'tel',
    'url',
    'search',
    'date',
    'datetime-local',
    'month',
    'week',
    'time',
    'color',
    'file',
    'range',
    'hidden',
];

if (!in_array($type, $allowed_types, true)) {
    $type = 'text';
}

if ($id === '') {
    $id = 'hengegroup-theme-input-' . wp_unique_id();
}

$is_group_control = $data_slot === 'input-group-control';

$computed_class = $is_group_control
    ? 'flex-1 min-w-0 rounded-none border-0 bg-transparent px-3.5 py-3 text-base shadow-none ' .
        'outline-none focus-visible:ring-0 placeholder:text-muted-foreground ' .
        'file:text-foreground file:inline-flex file:h-7 file:border-0 file:bg-transparent ' .
        'file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed ' .
        'disabled:opacity-50 md:text-sm'
    : 'file:text-foreground placeholder:text-muted-foreground selection:bg-henge-green ' .
        'selection:text-henge-green-foreground border-input flex w-full min-w-0 rounded-lg ' .
        'border bg-background px-3.5 py-3 text-base shadow-xs transition-[color,box-shadow] ' .
        'outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent ' .
        'file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed ' .
        'disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 ' .
        'focus-visible:ring-[3px] aria-invalid:ring-destructive/20 aria-invalid:border-destructive';

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['type'] = $type;
$element_attributes['data-slot'] = $data_slot;
$element_attributes['id'] = $id;

if ($name !== '') {
    $element_attributes['name'] = $name;
}

if ($value !== '') {
    $element_attributes['value'] = $value;
}

if ($placeholder !== '') {
    $element_attributes['placeholder'] = $placeholder;
}

if ($autocomplete !== '') {
    $element_attributes['autocomplete'] = $autocomplete;
}

if ($min !== '') {
    $element_attributes['min'] = $min;
}

if ($max !== '') {
    $element_attributes['max'] = $max;
}

if ($step !== '') {
    $element_attributes['step'] = $step;
}

if ($minlength !== '') {
    $element_attributes['minlength'] = $minlength;
}

if ($maxlength !== '') {
    $element_attributes['maxlength'] = $maxlength;
}

if ($pattern !== '') {
    $element_attributes['pattern'] = $pattern;
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

if ($label_text === '') {
    echo $input_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    return;
}

ob_start();
get_template_part('template-parts/base/label', null, [
    'config' => ['text' => $label_text, 'for' => $id],
]);
$label_markup = (string) ob_get_clean();

printf(
    '<div class="flex flex-col gap-2" data-slot="input-field">%1$s%2$s</div>',
    $label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $input_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
