<?php

declare(strict_types=1);

// shadcn/ui's Attachment is one of its newer AI-chat-building-block components (alongside Bubble,
// Message, Message Scroller, Marker -- not yet built here): a card showing a file/image attachment
// with media, metadata and actions, for "files and images in chat composers, message threads, and
// upload lists". Unlike most components in this theme, there is no native-vs-headless-primitive
// question to work through at all -- shadcn's own Attachment is already "styled native React/HTML
// rather than wrapping headless UI primitives" (no interaction pattern to replicate), so this file
// is, like badge.php/avatar.php, a direct presentational translation: pure data-attributed markup,
// zero JS (see CLAUDE.md #1).
//
// Composition: `media` nests template-parts/base/icon.php or
// template-parts/base/image.php depending on `media.variant` -- shadcn's own AttachmentMedia is
// just an icon-or-image switch, nothing more. `actions` is caller-provided, pre-rendered HTML (same
// convention as tooltip.php's `trigger`) -- shadcn's own AttachmentAction "inherits all Button
// props, defaults to size=icon-xs", both already valid button.php values (`size: 'icon-xs'`,
// `variant: 'ghost'`), so no separate attachment-action.php is needed, same reasoning as
// input-group.php not needing an input-group-button.php. Several attachment.php calls are grouped
// via template-parts/base/attachment/attachment-group.php, which nests scroll-area.php rather than
// reimplementing horizontal scrolling (see that file's header comment).
//
// `href` (shadcn's AttachmentTrigger, "a full-card overlay for links/dialogs that doesn't interfere
// with clickable actions") is rendered as its own `<a data-slot="attachment-trigger">` -- a
// sibling of the media/content/actions, NOT a wrapper around them. Wrapping the whole card
// (including its own `actions` buttons) in one `<a>` would nest interactive elements inside an
// interactive element, invalid/broken markup. The standard "stretched link" CSS technique covers
// this instead, a project-CSS concern (CLAUDE.md #1):
//   [data-slot="attachment"] { position: relative; }
//   [data-slot="attachment-trigger"] { position: absolute; inset: 0; z-index: 0; }
//   [data-slot="attachment-actions"] { position: relative; z-index: 1; }
// (the trigger visually covers the whole card but sits BEHIND the actions in stacking order, so
// clicking a real action button still reaches that button first, not the full-card link underneath)
//
// Supported config:
//   title           string   file/attachment name (required unless `media` is given)
//   description     string   metadata line (e.g. file type + size, or an upload-progress message)
//   media           array    { variant: 'icon' (default) | 'image', icon: icon.php config,
//                              image: image.php config } -- renders the matching nested component
//   actions         string   pre-rendered HTML for one or more button.php calls (see composition
//                             note above)
//   state           string   idle | uploading | processing | error | done (default: done) --
//                             shadcn's own Attachment state vocabulary (verified live: shadcn's
//                             documented default is "done", not "idle" -- a bare attachment.php
//                             call is assumed to represent an already-attached file, not one mid-
//                             upload), sets data-state only; the shimmer animation during
//                             uploading/processing and destructive styling for error are
//                             project-CSS driven off of it, not baked in here
//   size            string   default | sm | xs
//   orientation     string   horizontal (default) | vertical
//   href            string   renders the full-card `<a data-slot="attachment-trigger">` overlay
//                             (see the note above); requires `aria_label` or `title` for its
//                             accessible name
//   aria_label      string   accessible name for the `href` trigger; falls back to `title` when
//                             omitted
//   id              string   native `id` on the outer wrapper; auto-generated via wp_unique_id()
//                             when omitted
//   class / attributes / data_attributes   passthrough onto the outer
//                             <div data-slot="attachment"> wrapper

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$title = trim((string) ($config['title'] ?? ''));
$description = trim((string) ($config['description'] ?? ''));
$media_config = is_array($config['media'] ?? null) ? $config['media'] : null;
$actions = (string) ($config['actions'] ?? '');
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
        $media_markup = sprintf(
            '<div data-slot="attachment-media" data-variant="%1$s">%2$s</div>',
            esc_attr($media_variant),
            $media_content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    }
}

$content_markup = '';

if ($title !== '' || $description !== '') {
    $title_markup =
        $title !== ''
            ? sprintf('<div data-slot="attachment-title">%s</div>', esc_html($title))
            : '';
    $description_markup =
        $description !== ''
            ? sprintf('<div data-slot="attachment-description">%s</div>', esc_html($description))
            : '';

    $content_markup = sprintf(
        '<div data-slot="attachment-content">%1$s%2$s</div>',
        $title_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $description_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$actions_markup =
    trim($actions) !== ''
        ? sprintf('<div data-slot="attachment-actions">%s</div>', $actions) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        : '';

$wrapper_attributes = $attributes;

if ($class_name !== '') {
    $wrapper_attributes['class'] = $class_name;
}

$wrapper_attributes['data-slot'] = 'attachment';
$wrapper_attributes['data-state'] = $state;
$wrapper_attributes['data-size'] = $size;
$wrapper_attributes['data-orientation'] = $orientation;
$wrapper_attributes['id'] = $id;

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $wrapper_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<div%1$s>%2$s%3$s%4$s%5$s</div>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $media_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $actions_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
