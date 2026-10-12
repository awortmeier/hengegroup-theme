<?php

declare(strict_types=1);

// Divider with a label between two separator.php lines, or a green dot without label.
//
// Config:
//   label      string   omit for the dot variant
//   position   string   start (default, eyebrow label + line) | center (line + label + line)
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$label = trim((string) ($config['label'] ?? ''));
$position = trim((string) ($config['position'] ?? 'start'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_positions = ['start', 'center'];

if (!in_array($position, $allowed_positions, true)) {
    $position = 'start';
}

if ($label === '') {
    $position = 'center';
}

$render_line = static function (): string {
    ob_start();
    get_template_part('template-parts/base/separator/separator', null, [
        'config' => ['class' => 'flex-1'],
    ]);

    return (string) ob_get_clean();
};

if ($label === '') {
    $middle_markup =
        '<span class="size-1.5 shrink-0 rounded-full bg-henge-green" aria-hidden="true"></span>';
} elseif ($position === 'start') {
    $middle_markup = sprintf(
        '<span class="shrink-0 text-xs font-semibold tracking-widest text-muted-foreground uppercase">%s</span>',
        esc_html($label),
    );
} else {
    $middle_markup = sprintf(
        '<span class="shrink-0 text-sm text-muted-foreground">%s</span>',
        esc_html($label),
    );
}

$content_markup =
    $position === 'start'
        ? $middle_markup . $render_line()
        : $render_line() . $middle_markup . $render_line();

$wrapper_attributes = $attributes;
$wrapper_attributes['class'] = trim(
    'flex items-center gap-3.5' . ($class_name !== '' ? ' ' . $class_name : ''),
);
$wrapper_attributes['data-slot'] = 'separator-label';
$wrapper_attributes['data-position'] = $position;

$wrapper_attributes = hengegroup_theme_merge_data_attributes($wrapper_attributes, $data_attributes);

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
