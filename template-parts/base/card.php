<?php

declare(strict_types=1);

// API modeled on shadcn/ui's Card family (Card / CardHeader / CardTitle / CardDescription /
// CardAction / CardContent / CardFooter) -- no headless UI primitive underneath at all, just plain
// <div> wrappers with no interactive state, so unlike e.g. accordion.php/select.php there is no
// native-HTML-vs-JS deliberation to make here (CLAUDE.md #1). shadcn's six sub-components
// collapse into ONE config-driven file here instead of a six-file subfolder (the "more
// than one file -> own subfolder" rule doesn't apply) -- same spirit as badge.php/accordion.php
// folding their own sub-parts into config keys/arrays instead of separate files: title/
// description/image are genuinely single-purpose slots this component renders itself by nesting
// other base components, while action/content/footer stay content-agnostic
// pre-rendered-HTML slots (same convention as aspect-ratio.php's `content`) because shadcn's own
// CardAction/CardContent/CardFooter accept arbitrary children, not just text.
//
// Nests typography.php for the title (variant 'h3') and description (variant 'p') slots via its
// `data_slot` escape hatch (same trick as input.php's/textarea.php's/label.php's own `data_slot`,
// see those files' own header comments) so the rendered elements carry data-slot="card-title"/"card-description"
// instead of typography.php's own default data-slot="typography" -- lets project CSS target
// [data-slot="card-title"] directly without an extra wrapper element. Nests image.php for the
// optional cover-media slot the same way avatar.php nests image.php: buffer the output and check
// for emptiness (image.php renders nothing for a missing/invalid file), skip the media wrapper
// entirely rather than emitting an empty <div>.
//
// `size` (live-rechecked against shadcn's current Card docs): Card has since gained
// a `size: "default" | "sm"` prop ("set the size of the card to small ... uses smaller spacing").
// Phase 1 only wires the `data-size` hook onto the outer element -- same "hook now, style later"
// split as avatar.php's own `size` (data-size only, no Phase-2 spacing baked in here yet).
//
// Supported config:
//   title          string   optional. Visible title, rendered via typography.php (variant 'h3',
//                             data_slot 'card-title')
//   title_tag      string   h2 | h3 | h4 | h5 | h6 (default: h3) -- overrides the rendered heading
//                             tag while keeping the 'h3' visual variant, same
//                             config-vs-visual-style split as accordion.php's `heading_tag`
//   description    string   optional. Secondary text below the title, rendered via typography.php
//                             (variant 'p', data_slot 'card-description')
//   image          array    optional. image.php config for a cover-media slot, rendered above the
//                             header, wrapped in <div data-slot="card-media"> -- omitted entirely
//                             when image.php itself renders nothing (missing/invalid file)
//   action         string   optional. Pre-rendered HTML for the header's top-right slot (shadcn's
//                             CardAction), e.g. a buffered button.php icon-only call -- caller's
//                             responsibility to build/escape (content-agnostic, same convention as
//                             aspect-ratio.php's `content`)
//   content        string   optional. Pre-rendered HTML for the card body (shadcn's CardContent) --
//                             arbitrary markup, not limited to text
//   footer         string   optional. Pre-rendered HTML for the footer (shadcn's CardFooter), e.g.
//                             buffered button.php calls (same convention as button-group.php's
//                             `content`)
//   tag            string   div (default) | article | section -- semantic root element override,
//                             e.g. `article` for an independent card within a list/grid of cards
//   size           string   default | sm -- sets data-size on the outer element only (matches
//                             shadcn's Card `size`, see note above); Phase 1 has no visual
//                             difference between the two, this is purely the Phase-2 CSS hook
//   class / attributes / data_attributes   passthrough onto the outer <div data-slot="card">
//
// Header (title/description/action) is only rendered when at least one of the three is given --
// same conditional-region convention as shadcn's own CardHeader (its grid layout already changes
// based on whether a CardAction is present). Nothing is printed at all when the whole config
// resolves empty (no image, no header, no content, no footer).

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$title = trim((string) ($config['title'] ?? ''));
$title_tag = strtolower(trim((string) ($config['title_tag'] ?? 'h3')));
$description = trim((string) ($config['description'] ?? ''));
$image_config = is_array($config['image'] ?? null) ? $config['image'] : null;
$action = (string) ($config['action'] ?? '');
$content = (string) ($config['content'] ?? '');
$footer = (string) ($config['footer'] ?? '');
$tag = strtolower(trim((string) ($config['tag'] ?? 'div')));
$size = trim((string) ($config['size'] ?? 'default'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_title_tags = ['h2', 'h3', 'h4', 'h5', 'h6'];

if (!in_array($title_tag, $allowed_title_tags, true)) {
    $title_tag = 'h3';
}

$allowed_tags = ['div', 'article', 'section'];

if (!in_array($tag, $allowed_tags, true)) {
    $tag = 'div';
}

$allowed_sizes = ['default', 'sm'];

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'default';
}

$media_markup = '';

if ($image_config !== null) {
    $image_markup = base_theme_render_image($image_config);

    if (trim($image_markup) !== '') {
        $media_markup = sprintf(
            '<div data-slot="card-media">%s</div>',
            $image_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    }
}

$title_markup = '';

if ($title !== '') {
    ob_start();
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => 'h3',
            'tag' => $title_tag,
            'text' => $title,
            'data_slot' => 'card-title',
        ],
    ]);
    $title_markup = (string) ob_get_clean();
}

$description_markup = '';

if ($description !== '') {
    ob_start();
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => 'p',
            'text' => $description,
            'data_slot' => 'card-description',
        ],
    ]);
    $description_markup = (string) ob_get_clean();
}

$action_markup =
    trim($action) !== ''
        ? sprintf('<div data-slot="card-action">%s</div>', $action) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        : '';

$header_markup = '';

if ($title_markup !== '' || $description_markup !== '' || $action_markup !== '') {
    $header_markup = sprintf(
        '<div data-slot="card-header">%s%s%s</div>',
        $title_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $description_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $action_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$content_markup =
    trim($content) !== ''
        ? sprintf('<div data-slot="card-content">%s</div>', $content) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        : '';
$footer_markup =
    trim($footer) !== ''
        ? sprintf('<div data-slot="card-footer">%s</div>', $footer) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        : '';

$inner_html = $media_markup . $header_markup . $content_markup . $footer_markup;

if (trim($inner_html) === '') {
    return;
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'card';
$element_attributes['data-size'] = $size;

foreach ($data_attributes as $name => $value) {
    $data_name = trim((string) $name);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $value;
}

printf(
    '<%1$s%2$s>%3$s</%1$s>',
    esc_html($tag),
    base_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
