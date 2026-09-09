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
// Phase 2 (CLAUDE.md Regel 1): deliberately NOT one fixed look, because this file's own two real
// usages (see above) need visually different presentations -- a top-level nav-bar item (padded
// button, brand-accent hover) vs. a panel-internal "list item" (tight title+description row,
// subtle hover tint). shadcn's own real docs hit the same fork and resolve it the same way: a
// shared `navigationMenuTriggerStyle()` helper styles top-level NavigationMenuLink usage, but
// panel-internal links go through a caller-defined local `ListItem` wrapper, NOT
// NavigationMenuLink's own styling. This file follows that split: it only ever renders a minimal,
// context-agnostic base (focus ring + color transition, no background/padding/text color of its
// own) and leaves the actual look to whichever caller composes it --
// navigation-menu.php computes and passes the full top-level trigger-button recipe via `class` for
// plain link items (see that file's header comment); a panel-internal list-item look is the
// caller's own `class`, see the recipe in navigation-menu.php's header comment. This avoids the
// class-ordering pitfall button.php's own header comment documents (a caller-passed `class` is
// appended, not merged -- a conflicting `bg-*`/`text-*` utility doesn't reliably win): with no
// competing background/color classes baked in here, there is nothing for a caller's own classes to
// lose to.
// `active` still recolors unconditionally (`text-henge-green`, brand accent, reads on both light
// and dark surfaces) -- a "current page" indicator is meaningful regardless of which of the two
// contexts above this link is used in, unlike the base look.
//
// Supported config:
//   text / label   string   visible link text (used when `content` is omitted)
//   content        string   optional. Pre-rendered HTML for a richer link body (e.g. a title +
//                             description pair, built by the caller, see the note above); takes
//                             priority over `text` when both are given
//   href           string   required. Native `href`
//   active         bool     marks this as the current page's link: sets `data-active="true"` and
//                             `aria-current="page"` (shadcn's own NavigationMenuLink `active` prop),
//                             plus recolors it `text-henge-green font-semibold` (Phase 2, see above)
//   class / attributes / data_attributes   passthrough, as in the other base parts -- appended
//                             after this file's own minimal base classes (see Phase 2 note above)

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

// Minimal, context-agnostic base -- no background/padding/text color, see Phase 2 note above.
// `active` recolors on top, unconditionally (reads on both this file's own two usages).
$base_class =
    'outline-none rounded-lg transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50';

if ($active) {
    $base_class .= ' text-henge-green font-semibold';
}

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_class . ($class_name !== '' ? ' ' . $class_name : ''));
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
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
