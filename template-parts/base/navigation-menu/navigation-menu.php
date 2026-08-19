<?php

declare(strict_types=1);

// shadcn/ui's NavigationMenu wraps a headless UI primitive for a horizontal, top-level site nav:
// a row of items that are either plain links or hover/click-triggered dropdown ("mega menu")
// panels holding richer content. Live-checked against shadcn's current docs:
// shadcn's NavigationMenu page itself now just points at Base UI's own Navigation Menu docs for
// the API reference -- like toast.php's own header comment already notes for Toast, this is
// another component shadcn has moved onto a Base UI primitive rather than Radix UI. Root's real,
// current props are `delay`/`closeDelay` (both default 50ms) and `orientation`
// ('horizontal' | 'vertical', default 'horizontal'); Link's real `active` prop (default false)
// is unchanged from what navigation-menu-link.php already implements.
//
// Zero-JS baseline, same core technique as dropdown-menu.php: native <details>/<summary> per
// trigger item gives a real, working disclosure -- click (or Enter/Space on) a trigger to
// show/hide its panel, no JS required (CLAUDE.md #1). What this file adds on top, which
// dropdown-menu.php never needed (a single, standalone <details>, no siblings to coordinate):
// every trigger item's <details> shares one `name` attribute (derived from this instance's own
// `id`) -- the exact same native-only exclusivity trick accordion.php's `type: 'single'` already
// uses, applied here because a nav bar with two panels open at once would be a broken menu, not a
// stylistic choice. "Only one mega-menu panel open at a time" is therefore a genuinely native,
// zero-JS guarantee, not something JS enforces.
//
// Two honest, documented gaps in that zero-JS baseline, closed by
// assets/js/template-parts/base/navigation-menu.js: it doesn't auto-close on outside click or
// Escape (same gap dropdown-menu.php's own header comment documents), and it only opens on click
// -- a real top nav bar is expected to also open on hover, which no plain <details> can do on its
// own. navigation-menu.js adds hover-intent opening/closing, timed by `delay`/`close_delay`
// below (this theme's own snake_case of the real `delay`/`closeDelay` props above), plus
// horizontal Left/Right-arrow roving navigation across the top-level items (links and triggers
// alike, Home/End to jump to the first/last item) -- a reasonable, documented approximation of
// the underlying primitive's own keyboard behaviour, not a byte-for-byte reimplementation of its
// internal focus machinery.
//
// Deliberately out of scope for v1, not silently dropped:
//   - NavigationMenuIndicator (Base UI: Arrow): the small arrow/pointer that tracks the
//     currently active trigger. Purely decorative, has no function without Phase-2 CSS
//     transitions driving its movement (CLAUDE.md #1) -- nothing to build server-side yet.
//   - NavigationMenuViewport (Base UI: Positioner/Popup/Viewport): the underlying primitive's own
//     shared floating-panel plumbing that a content panel's markup gets teleported into,
//     collision-aware positioned, size-animated between panels. This theme has no standalone
//     popover.php to route through either (see date-picker.php's own header comment, same
//     reasoning) -- each trigger's content stays inline directly under its own <details>, the
//     same local-panel architecture dropdown-menu.php/date-picker.php already use, not a shared
//     abstraction.
//
// Composition: plain top-level items nest navigation-menu-link.php (`items`
// config below). A trigger item's mega-menu panel is caller-provided, pre-rendered HTML
// (content-agnostic wrapper, same convention as dropdown-menu.php's own `content`) -- typically
// itself built from several navigation-menu-link.php calls plus e.g. typography.php headings, see
// the recipe below.
//
// Supported config:
//   items       array   required. Each entry:
//     text / label   string   required. Visible label
//     href           string   presence makes this a plain link item, rendered via
//                              navigation-menu-link.php
//     content        string   presence makes this a trigger+panel item instead (pre-rendered
//                              HTML for the mega-menu panel); wins over `href` if both are given
//                              -- a trigger opens a panel, it doesn't also navigate
//     active         bool     forwarded to navigation-menu-link.php for link items
//                              (data-active/aria-current, see that file)
//     id             string   native id for this item's <details> (trigger items only);
//                              auto-generated via wp_unique_id() when omitted
//                       Entries with neither `href` nor `content`, or no `text`, are silently
//                       skipped, same "invalid entry -> skip" convention as toast.php's `toasts`
//   orientation  string   horizontal (default) | vertical -- sets data-orientation only
//                            (matches the underlying primitive's real `orientation` prop, see
//                            above); Phase 1 has no visual difference between the two, same
//                            "hook now, style later" split as card.php's own `size`
//   delay        int      hover-intent delay in ms before a trigger's panel opens (default: 50,
//                            the underlying primitive's own current default), read by the JS via
//                            data-delay
//   close_delay  int      grace period in ms before an open panel closes once the pointer leaves
//                            both trigger and content (default: 50, same source default), read
//                            by the JS via data-close-delay
//   aria_label   string   accessible name for the <nav> landmark -- recommended whenever more
//                            than one <nav> exists on a page (e.g. distinguishing a header nav
//                            from a footer nav), same reasoning as toast.php's viewport
//                            `aria-label`
//   id           string   native id on the outer <nav>; also seeds the shared
//                            <details name="..."> exclusivity group for this instance (see
//                            above) -- auto-generated via wp_unique_id() when omitted
//   class / attributes / data_attributes   passthrough onto the outer
//                            <nav data-slot="navigation-menu">
//
// Recipe for a trigger item's mega-menu panel:
//
//   ob_start();
//   get_template_part('template-parts/base/navigation-menu/navigation-menu-link', null, [
//       'config' => ['text' => 'Introduction', 'href' => '/docs'],
//   ]);
//   get_template_part('template-parts/base/navigation-menu/navigation-menu-link', null, [
//       'config' => ['text' => 'Installation', 'href' => '/docs/install'],
//   ]);
//   $panel_content = ob_get_clean();
//
//   get_template_part('template-parts/base/navigation-menu/navigation-menu', null, [
//       'config' => [
//           'aria_label' => 'Main',
//           'items' => [
//               ['text' => 'Docs', 'content' => $panel_content],
//               ['text' => 'Pricing', 'href' => '/pricing'],
//           ],
//       ],
//   ]);

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$items_config = is_array($config['items'] ?? null) ? $config['items'] : [];
$orientation = trim((string) ($config['orientation'] ?? 'horizontal'));
$delay = trim((string) ($config['delay'] ?? '50'));
$close_delay = trim((string) ($config['close_delay'] ?? '50'));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$id = trim((string) ($config['id'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_orientations = ['horizontal', 'vertical'];

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'horizontal';
}

if (!is_numeric($delay)) {
    $delay = '50';
}

if (!is_numeric($close_delay)) {
    $close_delay = '50';
}

if ($id === '') {
    $id = 'hengegroup-theme-navigation-menu-' . wp_unique_id();
}

$group_name = $id . '-group';
$items_markup = '';

foreach ($items_config as $item) {
    if (!is_array($item)) {
        continue;
    }

    $item_text = trim((string) ($item['text'] ?? ($item['label'] ?? '')));
    $item_href = trim((string) ($item['href'] ?? ''));
    $item_content = (string) ($item['content'] ?? '');
    $item_active = !empty($item['active']);
    $item_id = trim((string) ($item['id'] ?? ''));

    if ($item_text === '') {
        continue;
    }

    if (trim($item_content) !== '') {
        if ($item_id === '') {
            $item_id = $id . '-item-' . wp_unique_id();
        }

        $trigger_markup = sprintf(
            '<summary data-slot="navigation-menu-trigger" aria-haspopup="true">%s</summary>',
            esc_html($item_text),
        );

        $content_markup = sprintf(
            '<div data-slot="navigation-menu-content">%s</div>',
            $item_content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );

        $items_markup .= sprintf(
            '<li data-slot="navigation-menu-item">' .
                '<details data-slot="navigation-menu-trigger-item" name="%1$s" id="%2$s">%3$s%4$s</details>' .
                '</li>',
            esc_attr($group_name),
            esc_attr($item_id),
            $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );

        continue;
    }

    if ($item_href === '') {
        continue;
    }

    ob_start();
    get_template_part('template-parts/base/navigation-menu/navigation-menu-link', null, [
        'config' => [
            'text' => $item_text,
            'href' => $item_href,
            'active' => $item_active,
        ],
    ]);
    $link_markup = (string) ob_get_clean();

    if (trim($link_markup) === '') {
        continue;
    }

    $items_markup .= sprintf(
        '<li data-slot="navigation-menu-item">%s</li>',
        $link_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

if (trim($items_markup) === '') {
    return;
}

$list_markup = sprintf(
    '<ul data-slot="navigation-menu-list">%s</ul>',
    $items_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'navigation-menu';
$element_attributes['data-orientation'] = $orientation;
$element_attributes['data-delay'] = $delay;
$element_attributes['data-close-delay'] = $close_delay;
$element_attributes['id'] = $id;

if ($aria_label !== '') {
    $element_attributes['aria-label'] = $aria_label;
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<nav%1$s>%2$s</nav>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $list_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
