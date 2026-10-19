<?php

declare(strict_types=1);

// Head of a section or page: kicker, title, description and meta as one gap column, actions below.
// Class lists come from section-header-classes.json (shared with the block editor). Width and grid
// placement come from the caller (`class`). Renders nothing when everything is empty.
//
// Config:
//   size         string   sm (default) | base
//   color        string   default | light (on dark surfaces)
//   align        string   start (default) | center
//   kicker       string   pre-rendered, above the title (e.g. badge.php)
//   title        string   text or RichText HTML with the "Akzent" format
//   title_tag    string   h1-h6 | p (h2)
//   title_id     string   id of the title, e.g. for aria-labelledby
//   description  string   text or RichText HTML with the "Akzent" format
//   meta         string   pre-rendered line below the description (icon + text)
//   actions      string   pre-rendered buttons
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];
$c = hengegroup_theme_get_section_header_classes();

$size = trim((string) ($config['size'] ?? 'sm'));
$color = trim((string) ($config['color'] ?? 'default'));
$align = trim((string) ($config['align'] ?? 'start'));
$kicker = trim((string) ($config['kicker'] ?? ''));
$title = trim((string) ($config['title'] ?? ''));
$title_tag = strtolower(trim((string) ($config['title_tag'] ?? 'h2')));
$title_id = trim((string) ($config['title_id'] ?? ''));
$description = trim((string) ($config['description'] ?? ''));
$meta = trim((string) ($config['meta'] ?? ''));
$actions = trim((string) ($config['actions'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$has_title = trim(wp_strip_all_tags($title)) !== '';
$has_description = trim(wp_strip_all_tags($description)) !== '';

if (!$has_title && !$has_description && $kicker === '' && $meta === '' && $actions === '') {
    return;
}

if (!in_array($size, $c['sizes'], true)) {
    $size = 'sm';
}

if (!in_array($color, $c['colors'], true)) {
    $color = 'default';
}

if (!in_array($align, $c['aligns'], true)) {
    $align = 'start';
}

if (!in_array($title_tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'], true)) {
    $title_tag = 'h2';
}

$row_class = static fn(string $base): string => trim($base . ' ' . $c['align'][$align]['row']);

$text = '';

if ($kicker !== '') {
    $text .=
        '<div class="' .
        esc_attr($row_class($c['kicker'])) .
        '" data-slot="section-header-kicker">' .
        $kicker .
        '</div>';
}

if ($has_title) {
    $text .= hengegroup_theme_render_typography([
        'text' => $title,
        'accent_markup' => true,
        'variant' => $c['title'][$size]['variant'],
        'tag' => $title_tag,
        'color' => $color,
        'data_slot' => 'section-header-title',
        'class' => $c['title'][$size]['class'],
        'attributes' => $title_id !== '' ? ['id' => $title_id] : [],
    ]);
}

if ($has_description) {
    $text .= hengegroup_theme_render_typography([
        'text' => $description,
        'accent_markup' => true,
        'variant' => $c['description'][$size],
        'color' => $color,
        'data_slot' => 'section-header-description',
    ]);
}

if ($meta !== '') {
    $text .=
        '<div class="' .
        esc_attr($row_class($c['meta'] . ' ' . $c['metaColor'][$color])) .
        '" data-slot="section-header-meta">' .
        $meta .
        '</div>';
}

$attributes = hengegroup_theme_merge_data_attributes($attributes, $data_attributes);
$attributes['class'] = trim($c['root'] . ' ' . $c['align'][$align]['root'] . ' ' . $class_name);
$attributes['data-slot'] = 'section-header';
$attributes['data-size'] = $size;

echo '<div' . hengegroup_theme_render_attributes($attributes) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

if ($text !== '') {
    printf(
        '<div class="%1$s" data-slot="section-header-text">%2$s</div>',
        esc_attr($c['text']),
        $text, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- typography.php escapes, slots are pre-rendered.
    );
}

if ($actions !== '') {
    printf(
        '<div class="%1$s" data-slot="section-header-actions">%2$s</div>',
        esc_attr($row_class($c['actions'])),
        $actions, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-rendered by base components.
    );
}

echo '</div>';
