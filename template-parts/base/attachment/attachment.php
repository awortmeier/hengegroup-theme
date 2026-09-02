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
// props, defaults to size=icon-xs" (this project's nearest equivalent is `size: 'icon-sm'`, see
// docs/entscheidungen.md for button.php's sm/base/lg size vocabulary), `variant: 'ghost'` for a
// button, so no separate attachment-action.php is needed, same reasoning as
// input-group.php not needing an input-group-button.php. Several attachment.php calls are grouped
// via template-parts/base/attachment/attachment-group.php, which nests scroll-area.php rather than
// reimplementing horizontal scrolling (see that file's header comment).
//
// `href` (shadcn's AttachmentTrigger, "a full-card overlay for links/dialogs that doesn't interfere
// with clickable actions") is rendered as its own `<a data-slot="attachment-trigger">` -- a
// sibling of the media/content/actions, NOT a wrapper around them. Wrapping the whole card
// (including its own `actions` buttons) in one `<a>` would nest interactive elements inside an
// interactive element, invalid/broken markup. The standard "stretched link" CSS technique covers
// this instead (`absolute inset-0` on the trigger, `relative` on the card, the trigger BEHIND the
// actions in stacking order so a real action button still reaches that button first) -- see the
// Tailwind classes below, `data-slot="attachment-trigger"`/`-actions"` are the same hooks either
// way, just authored as Tailwind utilities now that Phase 2 puts classes directly in this file
// (same convention as every other styled base component).
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind, classes adapted from shadcn's own
// Attachment/AttachmentMedia/-Content/-Title/-Description/-Actions/-Trigger (registry/new-york-v4/
// ui/attachment.tsx, live-checked 2026-09-02) with the deviations the design reference below calls
// for:
//   - shadcn's own `w-fit` is dropped entirely rather than replaced with `w-full` -- an explicit
//     width would have to be right for BOTH consumers (a full-width row in attachment-group.php's
//     `flex-col` vertical list, a content-sized item in its own horizontal-scroll `flex-row` group,
//     see that file's header comment), and a bare block-level `<div>` already IS full-width in the
//     first context (normal flex `align-items: stretch` in a column) and content-sized in the
//     second (default `flex: 0 1 auto`, no `align-items: stretch` on the row's cross axis) with NO
//     explicit width utility needed either way -- letting the two callers' own flex directions do
//     this for free beats hand-tuning a `w-*` value that would have to be undone in one of them.
//     `flex-wrap` also dropped so the title truncates instead of the actions wrapping onto a
//     second line.
//   - `focus-within:ring-[3px]` instead of shadcn's `ring-1` -- this project's own focus-ring width
//     convention everywhere else (accordion.php/select.php/calendar.php/...), not a
//     component-specific choice.
//   - card gets `shadow-xs`/`hover:shadow-md` (shadcn's own has no shadow at all) and a graduated
//     `rounded-2xl`/`rounded-xl`/`rounded-lg` per `size` (shadcn: flat `rounded-xl`, only `xs` steps
//     down to `rounded-lg`) -- the reference's cards visibly lift/shadow, real Tailwind steps used
//     throughout instead of its literal `14px`/`12px`/`10px` (see button.php's own size-class
//     comment for the "real steps only" convention this repeats).
//   - error state additionally gets `bg-destructive/5` (shadcn's own only tints the border) --
//     matches the reference's own pale-red error card wash; `uploading`/`processing`/`done` get NO
//     extra border/background tint of their own even though the reference gives each a distinct
//     one -- that reads as a side-by-side demo-grid flourish (states shown all at once need more
//     visual separation than any one of them needs on its own), not a resting-state look worth
//     baking in permanently, especially for `done` (the default -- see below), matching shadcn's
//     own real spec instead, which doesn't tint any of the three either.
//   - all remaining structure -- the `group/attachment` + `group-data-[...]/attachment:`/
//     `has-data-[slot=...]:` mechanism driving size/orientation/state entirely from CSS off the
//     `data-size`/`data-orientation`/`data-state` attributes already set below, `data-[state=idle]:
//     border-dashed`, the image media's opacity fade-in on `done`/`idle`, `AttachmentTitle`'s
//     `animate-pulse` while `uploading`/`processing` (this project's stock-Tailwind stand-in for
//     shadcn's own custom `shimmer` utility -- same visual intent, zero custom CSS/keyframes needed,
//     proportionate for what's otherwise a one-line effect) -- is shadcn's own, kept close to 1:1.
//   - NOT reproduced from the reference: its per-file-type icon accent colors (each demo card picks
//     its own icon-box background) aren't a shadcn prop or a formal "variant" this component should
//     own -- that's caller content, covered by `media.class` below (same escape hatch as
//     `class`/`attributes` elsewhere), not a bespoke `accent` config duplicating Tailwind's own
//     `bg-*` utilities; its per-card diagonal shimmer-sweep overlay (an extra absolutely-positioned
//     div + custom keyframe) is simplified to the `animate-pulse` treatment above, one fewer
//     one-off animation for the design system to carry (same simplification spirit as accordion.
//     php's own dark-variant chevron-badge note); its "Zustände" trailing icons (retry/check/
//     spinner) are ordinary `actions` content -- shadcn's real AttachmentAction is just a Button,
//     so there is no per-state icon for this component to own either (see the showcase page for
//     how to compose these, e.g. nesting spinner.php for `processing`).
//
// `progress` (design request 2026-09-02): nests template-parts/base/progress/progress.php INSIDE
// `attachment-content`, below title/description -- not a shadcn prop (its real Attachment has no
// progress bar at all, see above), but the design reference's own "Zustände" section nests its own
// progress bar the same way (inside the card's content column, not as a separate element below the
// card), so this is closer to that reference than the plain external-composition approach tried
// first. Still just a thin config-to-progress.php passthrough, not a reimplementation -- the
// component doesn't know or care whether the value is real or a demo.
//
// Design reference: https://claude.ai/code/artifact/79e4e9d6-7cc2-43f6-a58a-b54c4184b194
// ("Basis"/"Zustände"/"Größen" sections; "Bild" render an unrelated placeholder in the artifact
// itself and isn't a usable reference -- `media.variant: 'image'` is styled from shadcn's own
// AttachmentMedia image treatment instead).
//
// File organization: kept as the existing two-file template-parts/base/attachment/ folder
// (attachment.php + attachment-group.php) rather than splitting further into one file per
// shadcn sub-component (Media/Content/Title/Description/Actions/Trigger). Unlike e.g.
// input-group.php's addon or field.php's sub-parts, none of these are ever invoked on their own by
// a caller -- they only ever exist as config keys (`media`/`actions`) or literal markup
// (title/description/trigger) inside one attachment.php call, so a separate file per part would add
// files without adding any real composability, the same "config keys instead of a subfolder"
// reasoning card.php/badge.php already document for their own multi-part shadcn origin. The
// per-state/-size look itself is CSS-attribute-driven (see above), not a PHP-computed class map per
// value either, so there's no per-variant branch that would want its own file.
//
// Supported config:
//   title           string   file/attachment name (required unless `media` is given)
//   description     string   metadata line (e.g. file type + size, or an upload-progress message)
//   media           array    { variant: 'icon' (default) | 'image', icon: icon.php config,
//                              image: image.php config, class: string appended onto the media
//                              wrapper AFTER its computed classes (e.g. an icon-box accent color --
//                              plain string concat, no tailwind-merge/cn() equivalent available in
//                              PHP, same caveat as button.php's/badge.php's own `class`) }
//                             -- renders the matching nested component
//   actions         string   pre-rendered HTML for one or more button.php calls (see composition
//                             note above)
//   progress        array    optional. template-parts/base/progress/progress.php config (value/max/
//                             aria_label/aria_valuetext/class/...), nested inside the content
//                             column below title/description (see the `progress` note above) --
//                             typically paired with `state: 'uploading'`, not enforced
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
    'transition-[color,box-shadow,border-color] hover:shadow-md has-[>a]:hover:bg-muted/50 ' .
    'focus-within:ring-[3px] focus-within:ring-ring/50 ' .
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
