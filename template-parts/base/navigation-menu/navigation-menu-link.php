<?php

declare(strict_types=1);

// shadcn/ui's NavigationMenuLink -- a plain anchor, no headless-primitive runtime behaviour of
// its own beyond an `active` state (real earned semantics, not invented: sets `data-active` PLUS
// `aria-current="page"`, matching the underlying primitive's own NavigationMenuLink `active` prop
// -- Base UI's Navigation Menu as of a live check against shadcn's current docs, see
// navigation-menu.php's own header comment). Used two ways, both real
// shadcn usage patterns: as a top-level item with no dropdown (nested automatically by
// navigation-menu.php's own `items` config, see that file), and inside a trigger's
// NavigationMenuContent panel for the "list of links" mega-menu pattern -- shadcn's own docs
// examples nest several of these inside a content panel's custom grid markup, so this file is a
// standalone, freely reusable atom, not something navigation-menu.php privately
// builds only for itself.
//
// shadcn's own real examples pass arbitrary children, not a formalized icon/title/description
// prop set (a rich mega-menu entry is typically `<div>Title</div><p>Description</p>` as custom
// children) -- so this file offers `content` for that same freedom (pre-rendered HTML, same
// content-agnostic convention as aspect-ratio.php's `content`), with a plain `text` shorthand for
// the common simple-link case (a bare top-level "Home"/"About" item has no need for that).
//
// Supported config:
//   text / label   string   visible link text (used when `content` is omitted)
//   content        string   optional. Pre-rendered HTML for a richer link body (e.g. a title +
//                             description pair, built by the caller, see the note above); takes
//                             priority over `text` when both are given
//   href           string   required. Native `href`
//   active         bool     marks this as the current page's link: sets `data-active="true"` and
//                             `aria-current="page"` (shadcn's own NavigationMenuLink `active` prop)
//   class / attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$text = trim((string) ($config['text'] ?? ($config['label'] ?? '')));
$content = (string) ($config['content'] ?? '');
$href = trim((string) ($config['href'] ?? ''));
$active = !empty($config['active']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($href === '' || ($text === '' && trim($content) === '')) {
    return;
}

$inner_html = trim($content) !== '' ? $content : esc_html($text);

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'navigation-menu-link';
$element_attributes['href'] = $href;

if ($active) {
    $element_attributes['data-active'] = 'true';
    $element_attributes['aria-current'] = 'page';
}

foreach ($data_attributes as $name => $value) {
    $data_name = trim((string) $name);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $value;
}

printf(
    '<a%1$s>%2$s</a>',
    base_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
