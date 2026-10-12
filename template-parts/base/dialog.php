<?php

declare(strict_types=1);

// Native <dialog> (focus trap, ::backdrop, Escape, focus return). Opened via the Invoker Commands
// API: the trigger is a caller button.php with
// `attributes: ['command' => 'show-modal', 'commandfor' => $id]` (close: 'close'). dialog.js
// polyfills that, closes on backdrop click, honors `dismissible` and upgrades `open` to showModal().
//
// Config:
//   title                   string   recommended (or aria_label); aria-labelledby
//   title_tag               string   h2 (default) - h6
//   title_visually_hidden   bool     sr-only
//   description             string   aria-describedby
//   content                 string   pre-rendered body
//   footer                  string   pre-rendered footer
//   show_close_button       bool     default true
//   modal                   bool     default true; false uses show()
//   open                    bool     initially open (non-modal without JS)
//   dismissible             bool     default true; false blocks Escape/backdrop (with JS only)
//   id                      string   pass it explicitly for commandfor; else wp_unique_id()
//   aria_label              string
//   class / attributes / data_attributes   on the <dialog>

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$title = trim((string) ($config['title'] ?? ''));
$title_tag = strtolower(trim((string) ($config['title_tag'] ?? 'h2')));
$title_visually_hidden = !empty($config['title_visually_hidden']);
$description = trim((string) ($config['description'] ?? ''));
$content = (string) ($config['content'] ?? '');
$footer = (string) ($config['footer'] ?? '');
$show_close_button =
    !array_key_exists('show_close_button', $config) || !empty($config['show_close_button']);
$modal = !array_key_exists('modal', $config) || !empty($config['modal']);
$open = !empty($config['open']);
$dismissible = !array_key_exists('dismissible', $config) || !empty($config['dismissible']);
$id = trim((string) ($config['id'] ?? ''));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_title_tags = ['h2', 'h3', 'h4', 'h5', 'h6'];

if (!in_array($title_tag, $allowed_title_tags, true)) {
    $title_tag = 'h2';
}

if ($id === '') {
    $id = 'hengegroup-theme-dialog-' . wp_unique_id();
}

$title_markup = '';

if ($title !== '') {
    ob_start();
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => 'body-lg',
            'tag' => $title_tag,
            'text' => $title,
            'data_slot' => 'dialog-title',
            'class' => trim('font-semibold ' . ($title_visually_hidden ? 'sr-only' : '')),
            'attributes' => ['id' => $id . '-title'],
        ],
    ]);
    $title_markup = (string) ob_get_clean();
}

$description_markup = '';

if ($description !== '') {
    ob_start();
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => 'body-sm',
            'text' => $description,
            'data_slot' => 'dialog-description',
            'color' => 'neutral',
            'class' => 'text-pretty',
            'attributes' => ['id' => $id . '-description'],
        ],
    ]);
    $description_markup = (string) ob_get_clean();
}

$header_markup = '';

if ($title_markup !== '' || $description_markup !== '') {
    $header_markup = sprintf(
        '<div class="flex flex-col gap-2 pr-8" data-slot="dialog-header">%1$s%2$s</div>',
        $title_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $description_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$footer_markup =
    trim($footer) !== ''
        ? sprintf(
            '<div class="flex flex-wrap items-center justify-end gap-3" data-slot="dialog-footer">%s</div>',
            $footer, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        )
        : '';

$close_button_markup = '';

if ($show_close_button) {
    $close_button_classes =
        'absolute top-8 right-8 flex size-9 items-center justify-center rounded-lg border ' .
        'border-border text-muted-foreground outline-none transition-colors hover:bg-accent ' .
        'hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-[3px] ' .
        "focus-visible:ring-ring/50 [&_svg:not([class*='size-'])]:size-4";

    $close_button_markup = sprintf(
        '<button type="button" class="%1$s" data-slot="dialog-close" command="close" ' .
            'commandfor="%2$s" aria-label="%3$s">%4$s</button>',
        esc_attr($close_button_classes),
        esc_attr($id),
        esc_attr__('Close', 'hengegroup-theme'),
        hengegroup_theme_render_icon(['name' => 'x', 'set' => 'lucide']), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$inner_html = $header_markup . $content . $footer_markup . $close_button_markup;

if (trim($inner_html) === '') {
    return;
}

// m-auto: Preflight removes the UA margin:auto centering. `open:flex` instead of `flex`: an author
// `display` would override the UA's dialog:not([open]) { display: none }.
$content_classes =
    'open:flex m-auto w-full max-w-[calc(100%-2rem)] flex-col gap-6 rounded-2xl bg-background p-8 ' .
    'text-foreground shadow-[0_12px_32px_rgba(0,0,0,0.14)] outline-hidden sm:max-w-lg ' .
    'animate-[hg-dialog-in_180ms_ease-out] ' .
    '[&::backdrop]:bg-black/50 [&::backdrop]:animate-[hg-dialog-overlay-in_180ms_ease-out]';

$element_attributes = $attributes;

$element_attributes['class'] = trim(
    $content_classes . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['data-slot'] = 'dialog-content';

if (!$modal) {
    $element_attributes['data-modal'] = 'false';
}

if (!$dismissible) {
    $element_attributes['data-dismissible'] = 'false';
}

if ($open) {
    $element_attributes['data-open'] = 'true';
    $element_attributes['open'] = true;
}

if ($title_markup !== '') {
    $element_attributes['aria-labelledby'] = $id . '-title';
} elseif ($aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
}

if ($description_markup !== '') {
    $element_attributes['aria-describedby'] = $id . '-description';
}

$element_attributes['id'] = $id;

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<dialog%1$s>%2$s</dialog>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
