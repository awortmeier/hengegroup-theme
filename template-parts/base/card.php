<?php

declare(strict_types=1);

// Card. Renders nothing when everything is empty; the header only when title/description/action
// is given; the media only when image.php actually renders something.
//
// Config:
//   title            string   via typography.php
//   title_tag        string   h2-h6 (default h3)
//   title_variant    string   body-lg (default) | body-base | body-sm
//   description      string
//   image            array    image.php config (cover media)
//   media_badge      string   pre-rendered overlay top left of the image
//   icon             string   pre-rendered icon in a 44px box left of the title
//   icon_class       string   classes of that box (default bg-muted text-foreground)
//   action           string   pre-rendered HTML top right of the header
//   content          string   pre-rendered body
//   footer           string   pre-rendered footer
//   footer_divider   bool     divider above the footer
//   href             string   whole card as <a> (hover + focus ring)
//   tag              string   div (default) | article | section | li
//   size             string   sm (16px) | default (24px) | lg (28px)
//   elevation        string   flat (default) | raised
//   orientation      string   vertical (default) | horizontal (media as left column)
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$title = trim((string) ($config['title'] ?? ''));
$title_tag = strtolower(trim((string) ($config['title_tag'] ?? 'h3')));
$description = trim((string) ($config['description'] ?? ''));
$image_config = is_array($config['image'] ?? null) ? $config['image'] : null;
$media_badge = (string) ($config['media_badge'] ?? '');
$action = (string) ($config['action'] ?? '');
$content = (string) ($config['content'] ?? '');
$footer = (string) ($config['footer'] ?? '');
$footer_divider = !empty($config['footer_divider']);
$href = trim((string) ($config['href'] ?? ''));
$tag = strtolower(trim((string) ($config['tag'] ?? 'div')));
$size = trim((string) ($config['size'] ?? 'default'));
$elevation = trim((string) ($config['elevation'] ?? 'flat'));
$orientation = trim((string) ($config['orientation'] ?? 'vertical'));
$icon = (string) ($config['icon'] ?? '');
$icon_class = trim((string) ($config['icon_class'] ?? ''));
$title_variant = trim((string) ($config['title_variant'] ?? 'body-lg'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_title_tags = ['h2', 'h3', 'h4', 'h5', 'h6'];

if (!in_array($title_tag, $allowed_title_tags, true)) {
    $title_tag = 'h3';
}

$allowed_tags = ['div', 'article', 'section', 'li'];

if (!in_array($tag, $allowed_tags, true)) {
    $tag = 'div';
}

if ($href !== '') {
    $tag = 'a';
}

$allowed_sizes = ['default', 'sm', 'lg'];

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'default';
}

if (!in_array($elevation, ['flat', 'raised'], true)) {
    $elevation = 'flat';
}

if (!in_array($orientation, ['vertical', 'horizontal'], true)) {
    $orientation = 'vertical';
}

if (!in_array($title_variant, ['body-lg', 'body-base', 'body-sm'], true)) {
    $title_variant = 'body-lg';
}

if ($icon_class === '') {
    $icon_class = 'bg-muted text-foreground';
}

$horizontal = $orientation === 'horizontal';

$size_classes = [
    'default' => [
        'outer_gap' => 'gap-6',
        'outer_py' => 'py-6',
        'section_px' => 'px-6',
        'header_gap' => 'gap-2',
        'footer_gap' => 'gap-3',
        'media_mt' => '-mt-6',
        'footer_divider_pt' => 'pt-6',
    ],
    'sm' => [
        'outer_gap' => 'gap-4',
        'outer_py' => 'py-4',
        'section_px' => 'px-4',
        'header_gap' => 'gap-1.5',
        'footer_gap' => 'gap-2',
        'media_mt' => '-mt-4',
        'footer_divider_pt' => 'pt-4',
    ],
    'lg' => [
        'outer_gap' => 'gap-4',
        'outer_py' => 'py-7',
        'section_px' => 'px-7',
        'header_gap' => 'gap-2',
        'footer_gap' => 'gap-3',
        'media_mt' => '-mt-7',
        'footer_divider_pt' => 'pt-6',
    ],
];
$s = $size_classes[$size];

$media_markup = '';

if ($image_config !== null) {
    $image_markup = hengegroup_theme_render_image($image_config);

    if (trim($image_markup) !== '') {
        $media_badge_markup =
            trim($media_badge) !== ''
                ? sprintf(
                    '<div class="absolute top-3 left-3" data-slot="card-media-badge">%s</div>',
                    $media_badge, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                )
                : '';

        // Vertical: bleeds to the card edge. Horizontal: full-height left column.
        $media_class = $horizontal
            ? 'min-h-45'
            : $s['media_mt'] .
                ' overflow-hidden ' .
                ($elevation === 'raised' ? 'rounded-t-card-raised' : 'rounded-t-card-flat');

        $media_markup = sprintf(
            '<div class="relative %1$s" data-slot="card-media">%2$s%3$s</div>',
            $media_class,
            $image_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $media_badge_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    }
}

$title_markup = '';

if ($title !== '') {
    ob_start();
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => $title_variant,
            'tag' => $title_tag,
            'text' => $title,
            'data_slot' => 'card-title',
            // No extra leading-*: it would compete with typography.php's own.
            'class' => 'font-extrabold',
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
            'data_slot' => 'card-description',
            'color' => 'neutral',
            'class' => 'text-pretty',
        ],
    ]);
    $description_markup = (string) ob_get_clean();
}

$action_markup =
    trim($action) !== ''
        ? sprintf(
            '<div class="col-start-2 row-span-2 row-start-1 self-start justify-self-end" data-slot="card-action">%s</div>',
            $action, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        )
        : '';

if ($title_markup !== '' && trim($icon) !== '') {
    $title_markup = sprintf(
        '<div class="flex min-w-0 items-center gap-3.5" data-slot="card-title-row"><span class="%1$s" data-slot="card-icon">%2$s</span>%3$s</div>',
        esc_attr(
            'flex size-11 shrink-0 items-center justify-center rounded-xl ' .
                "[&_svg:not([class*='size-'])]:size-5 " .
                $icon_class,
        ),
        $icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $title_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$header_markup = '';

if ($title_markup !== '' || $description_markup !== '' || $action_markup !== '') {
    $header_markup = sprintf(
        '<div class="@container/card-header grid auto-rows-min grid-rows-[auto_auto] items-start %1$s %2$s has-data-[slot=card-action]:grid-cols-[1fr_auto]" data-slot="card-header">%3$s%4$s%5$s</div>',
        $s['header_gap'],
        $s['section_px'],
        $title_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $description_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $action_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$content_markup =
    trim($content) !== ''
        ? sprintf(
            '<div class="%1$s" data-slot="card-content">%2$s</div>',
            $s['section_px'],
            $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        )
        : '';

$footer_class = "flex items-center {$s['footer_gap']} {$s['section_px']}";

if ($footer_divider) {
    $footer_class .= " border-t border-border {$s['footer_divider_pt']}";
}

$footer_markup =
    trim($footer) !== ''
        ? sprintf(
            '<div class="%1$s" data-slot="card-footer">%2$s</div>',
            trim($footer_class),
            $footer, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        )
        : '';

$body_html = $header_markup . $content_markup . $footer_markup;

if (trim($media_markup . $body_html) === '') {
    return;
}

$inner_html = $horizontal
    ? $media_markup .
        sprintf(
            '<div class="flex min-w-0 flex-col %1$s %2$s" data-slot="card-body">%3$s</div>',
            $s['outer_gap'],
            $s['outer_py'],
            $body_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        )
    : $media_markup . $body_html;

$element_attributes = $attributes;

$elevation_classes = [
    'flat' => 'rounded-card-flat shadow-card-flat [a&]:hover:shadow-card-hover',
    'raised' => 'rounded-card-raised shadow-card-raised [a&]:hover:shadow-lg',
];
$layout_classes = $horizontal
    ? 'grid overflow-hidden sm:grid-cols-[240px_1fr]'
    : "flex flex-col {$s['outer_gap']} {$s['outer_py']}";

$computed_class = trim(
    "bg-card text-card-foreground {$layout_classes} {$elevation_classes[$elevation]} " .
        '[a&]:cursor-pointer [a&]:no-underline [a&]:transition-shadow ' .
        '[a&]:focus-visible:ring-[3px] [a&]:focus-visible:ring-ring/50 [a&]:focus-visible:outline-none',
);

$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['data-slot'] = 'card';
$element_attributes['data-size'] = $size;
$element_attributes['data-elevation'] = $elevation;
$element_attributes['data-orientation'] = $orientation;

if ($href !== '') {
    $element_attributes['href'] = $href;
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<%1$s%2$s>%3$s</%1$s>',
    esc_html($tag),
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
