<?php

declare(strict_types=1);

// File/attachment card. `href` renders a stretched-link overlay (<a> behind the actions) instead
// of wrapping the card, so action buttons are not nested inside a link.
//
// Config:
//   title         string   required unless `media` is given
//   description   string   meta line, e.g. type + size
//   media         array    { variant: icon (default) | image, icon, image, class }
//   actions       string   pre-rendered button.php markup
//   progress      array    progress.php config, shown below the title
//   state         string   idle | uploading | processing | error | done (default)
//   size          string   default | sm | xs
//   orientation   string   horizontal (default) | vertical
//   href          string   full-card link
//   aria_label    string   link name (falls back to title)
//   id            string   default wp_unique_id()
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$title = trim((string) ($config['title'] ?? ''));
$description = trim((string) ($config['description'] ?? ''));
$media_config = is_array($config['media'] ?? null) ? $config['media'] : null;
$actions = (string) ($config['actions'] ?? '');
$progress_config = is_array($config['progress'] ?? null) ? $config['progress'] : null;
$state = trim((string) ($config['state'] ?? 'done'));
$size = trim((string) ($config['size'] ?? 'default'));
$orientation = trim((string) ($config['orientation'] ?? 'horizontal'));
$href = trim((string) ($config['href'] ?? ''));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$id = trim((string) ($config['id'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($title === '' && $media_config === null) {
    return;
}

$allowed_states = ['idle', 'uploading', 'processing', 'error', 'done'];
$allowed_sizes = ['default', 'sm', 'xs'];
$allowed_orientations = ['horizontal', 'vertical'];
$allowed_media_variants = ['icon', 'image'];

if (!in_array($state, $allowed_states, true)) {
    $state = 'done';
}

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'default';
}

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'horizontal';
}

if ($id === '') {
    $id = 'hengegroup-theme-attachment-' . wp_unique_id();
}

$trigger_markup = '';

if ($href !== '') {
    $trigger_attributes = [
        'data-slot' => 'attachment-trigger',
        'class' => 'absolute inset-0 z-10 outline-none',
        'href' => $href,
    ];

    $trigger_aria_label = $aria_label !== '' ? $aria_label : $title;

    if ($trigger_aria_label !== '') {
        $trigger_attributes['aria-label'] = $trigger_aria_label;
    }

    $trigger_markup = '<a' . hengegroup_theme_render_attributes($trigger_attributes) . '></a>';
}

$media_markup = '';

if ($media_config !== null) {
    $media_variant = trim((string) ($media_config['variant'] ?? 'icon'));

    if (!in_array($media_variant, $allowed_media_variants, true)) {
        $media_variant = 'icon';
    }

    $media_content = '';

    if ($media_variant === 'image' && is_array($media_config['image'] ?? null)) {
        $media_content = hengegroup_theme_render_image($media_config['image']);
    } elseif ($media_variant === 'icon' && is_array($media_config['icon'] ?? null)) {
        $media_content = hengegroup_theme_render_icon($media_config['icon']);
    }

    if ($media_content !== '') {
        $media_class =
            'relative flex aspect-square w-10 shrink-0 items-center justify-center ' .
            'overflow-hidden rounded-xl bg-muted text-muted-foreground ' .
            'group-data-[orientation=vertical]/attachment:w-full ' .
            'group-data-[size=sm]/attachment:w-8 group-data-[size=xs]/attachment:w-7 ' .
            'group-data-[size=xs]/attachment:rounded-lg ' .
            'group-data-[state=error]/attachment:bg-destructive/10 ' .
            'group-data-[state=error]/attachment:text-destructive ' .
            "[&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 " .
            "group-data-[size=xs]/attachment:[&_svg:not([class*='size-'])]:size-3.5";

        if ($media_variant === 'image') {
            $media_class .=
                ' opacity-60 group-data-[state=done]/attachment:opacity-100 ' .
                'group-data-[state=idle]/attachment:opacity-100 [&_img]:size-full [&_img]:object-cover';
        }

        if (isset($media_config['class'])) {
            $media_class .= ' ' . trim((string) $media_config['class']);
        }

        $media_markup = sprintf(
            '<div data-slot="attachment-media" data-variant="%1$s" class="%2$s">%3$s</div>',
            esc_attr($media_variant),
            esc_attr($media_class),
            $media_content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    }
}

$progress_markup = '';

if ($progress_config !== null) {
    $progress_config['class'] = trim('mt-1.5 ' . trim((string) ($progress_config['class'] ?? '')));

    ob_start();
    get_template_part('template-parts/base/progress/progress', null, [
        'config' => $progress_config,
    ]);
    $progress_markup = (string) ob_get_clean();
}

$content_markup = '';

if ($title !== '' || $description !== '' || $progress_markup !== '') {
    $title_markup =
        $title !== ''
            ? sprintf(
                '<div data-slot="attachment-title" class="%1$s">%2$s</div>',
                'block max-w-full min-w-0 truncate text-base font-semibold text-foreground ' .
                    'group-data-[size=sm]/attachment:text-sm group-data-[size=xs]/attachment:text-sm ' .
                    'group-data-[state=error]/attachment:text-destructive ' .
                    'group-data-[state=uploading]/attachment:animate-pulse ' .
                    'group-data-[state=processing]/attachment:animate-pulse',
                esc_html($title),
            )
            : '';
    $description_markup =
        $description !== ''
            ? sprintf(
                '<div data-slot="attachment-description" class="%1$s">%2$s</div>',
                'mt-0.5 block max-w-full min-w-0 truncate text-sm text-muted-foreground ' .
                    'group-data-[size=xs]/attachment:text-xs ' .
                    'group-data-[state=error]/attachment:text-destructive/80 ' .
                    'group-data-[state=uploading]/attachment:animate-pulse ' .
                    'group-data-[state=processing]/attachment:animate-pulse',
                esc_html($description),
            )
            : '';

    $content_markup = sprintf(
        '<div data-slot="attachment-content" class="%1$s">%2$s%3$s%4$s</div>',
        'min-w-0 max-w-full flex-1 leading-tight group-data-[orientation=vertical]/attachment:px-1',
        $title_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $description_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $progress_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$actions_markup =
    trim($actions) !== ''
        ? sprintf(
            '<div data-slot="attachment-actions" class="%1$s">%2$s</div>',
            'relative z-20 flex shrink-0 items-center gap-1 ' .
                'group-data-[orientation=vertical]/attachment:absolute ' .
                'group-data-[orientation=vertical]/attachment:top-2.5 ' .
                'group-data-[orientation=vertical]/attachment:right-2.5',
            $actions, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        )
        : '';

$base_classes =
    'group/attachment relative flex min-w-0 items-center gap-3 rounded-2xl ' .
    'border border-border bg-card text-card-foreground shadow-xs ' .
    'transition-[color,box-shadow,border-color] has-[>a]:hover:shadow-md has-[>a]:hover:bg-muted/50 ' .
    'has-[>a:focus-visible]:ring-[3px] has-[>a:focus-visible]:ring-ring/50 ' .
    'data-[state=idle]:border-dashed data-[state=error]:border-destructive/40 ' .
    'data-[state=error]:bg-destructive/5 ' .
    'data-[orientation=vertical]:w-24 data-[orientation=vertical]:flex-col ' .
    'has-data-[slot=attachment-content]:px-3.5 has-data-[slot=attachment-content]:py-3 ' .
    'has-data-[slot=attachment-media]:p-2.5 ' .
    'data-[size=sm]:rounded-xl data-[size=sm]:has-data-[slot=attachment-content]:px-3 ' .
    'data-[size=sm]:has-data-[slot=attachment-content]:py-2.5 ' .
    'data-[size=sm]:has-data-[slot=attachment-media]:p-2 ' .
    'data-[size=xs]:gap-2 data-[size=xs]:rounded-lg ' .
    'data-[size=xs]:has-data-[slot=attachment-content]:px-2 ' .
    'data-[size=xs]:has-data-[slot=attachment-content]:py-1.5 ' .
    'data-[size=xs]:has-data-[slot=attachment-media]:p-1.5';

$wrapper_attributes = $attributes;
$wrapper_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$wrapper_attributes['data-slot'] = 'attachment';
$wrapper_attributes['data-state'] = $state;
$wrapper_attributes['data-size'] = $size;
$wrapper_attributes['data-orientation'] = $orientation;
$wrapper_attributes['id'] = $id;

$wrapper_attributes = hengegroup_theme_merge_data_attributes($wrapper_attributes, $data_attributes);

printf(
    '<div%1$s>%2$s%3$s%4$s%5$s</div>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $media_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $actions_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
