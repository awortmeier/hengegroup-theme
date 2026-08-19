<?php

declare(strict_types=1);

// shadcn/ui's Dialog wraps a headless UI primitive (historically Radix UI; shadcn now also ships
// Base UI/React Aria variants) for its modal behaviour: focus trap, an overlay/backdrop, Escape-
// to-close, and focus returning to the trigger on close. Unlike almost every other component in
// this theme, HTML has a purpose-built native element for exactly this job: <dialog>. Calling
// dialogEl.showModal() natively gives a real focus trap, a ::backdrop pseudo-element (no separate
// DialogOverlay <div> needed -- project CSS targets `[data-slot="dialog-content"]::backdrop`
// directly), native Escape-to-close (fires a cancelable `cancel` event, then `close`), and focus
// automatically returns to whatever was focused before showModal() was called. None of that needs
// reimplementing in JS the way e.g. select.php's listbox does.
//
// The one thing native HTML can't do declaratively on its own -- until very recently -- is
// invoke showModal()/close() from a plain <button> without JS. The HTML Invoker Commands API
// (`command`/`commandfor` attributes on <button>, shipping first in Chromium, other engines
// catching up) closes exactly that gap natively: `<button command="show-modal"
// commandfor="my-dialog">` opens `<dialog id="my-dialog">` with ZERO JavaScript in browsers that
// support it -- the same "real, working, native zero-JS baseline" role accordion.php's
// `<details name>` or dropdown-menu.php's `<details>`/`<summary>` play, just via a newer platform
// feature instead of a decades-old one. This file renders `command`/`commandfor` on the one
// button it builds itself (the built-in close button below); the external trigger that opens
// this dialog in the first place is the CALLER's own button.php call, given the same
// `command`/`commandfor` attributes via its `attributes` passthrough -- see the recipe below
// (reuse button.php, don't reinvent a trigger element here).
//
// Honest, documented gap: in browsers WITHOUT Invoker Commands support, a `command`/`commandfor`
// button does nothing at all without JS -- unlike <details>-based components, there is no
// degraded-but-working native fallback in between "full native support" and "needs JS".
// assets/js/template-parts/base/dialog.js closes that gap with a small, feature-detected
// polyfill (wires up any `[commandfor]` button pointing at a <dialog>) so `show-modal`/`close`
// keep working end-to-end regardless of native support -- it does not reimplement anything
// showModal() already gives for free, it only fills in the invocation mechanism. The same file
// also closes two gaps native `command`/`commandfor` support does NOT cover on its own: a click
// on the backdrop itself closing the dialog (see `dismissible` below), and upgrading an
// `open: true`-rendered dialog (see below) from its native non-modal `open` baseline into a real
// showModal() on page load.
//
// `open: true`'s own zero-JS baseline: the plain `open` boolean attribute makes a <dialog>
// visible immediately, but per its native meaning that is always the NON-modal state (in normal
// document flow, no ::backdrop, no focus trap, background stays interactive) -- there is no HTML
// attribute that means "start open AND modal". dialog.js upgrades this into a real showModal()
// on init when `modal` is not false, the same "real if degraded baseline, JS upgrades it" shape
// as tooltip.php's native `title` -> styled panel.
//
// `dismissible: false`: dialog.js prevents the native `cancel` event (Escape) and ignores
// backdrop clicks. Without JS, Escape always closes an open <dialog> -- honest, documented gap,
// there is no way to prevent that natively.
//
// Composition: `title`/`description` are nested via typography.php (same
// `data_slot` escape hatch as card.php's `card-title`/`card-description`); the built-in close
// button nests icon.php (Lucide `x`, same choice as toast.php's own close icon). `content`/
// `footer` are caller-provided, pre-rendered HTML (same convention as card.php/aspect-ratio.php)
// -- unlike card.php, `content` is placed directly with no extra wrapper element, because stock
// shadcn's own DialogContent has no separate "body" sub-component the way Card has CardContent;
// header/content/footer are all just direct children of DialogContent there too.
//
// Trigger recipe (this file renders no trigger of its own -- see the header comment above):
//
//   $dialog_id = 'edit-profile-dialog';
//
//   ob_start();
//   get_template_part('template-parts/base/button', null, [
//       'config' => [
//           'text' => 'Edit profile',
//           'attributes' => ['command' => 'show-modal', 'commandfor' => $dialog_id],
//       ],
//   ]);
//   $trigger_markup = ob_get_clean();
//
//   echo $trigger_markup;
//   get_template_part('template-parts/base/dialog', null, [
//       'config' => [
//           'id' => $dialog_id,
//           'title' => 'Edit profile',
//           'description' => 'Make changes to your profile here.',
//           'content' => '<p>Form fields would go here.</p>',
//       ],
//   ]);
//
// A footer button that should also close the dialog uses the same recipe: give it
// `attributes: ['command' => 'close', 'commandfor' => $dialog_id]` before buffering it into
// `footer`.
//
// Supported config:
//   title                     string   recommended (required for accessibility unless
//                                       `aria_label` is given -- shadcn's own DialogTitle is
//                                       mandatory for the same reason). Rendered via
//                                       typography.php (variant 'h2', data_slot 'dialog-title'),
//                                       wired to the dialog via aria-labelledby
//   title_tag                 string   h2 | h3 | h4 | h5 | h6 (default: h2) -- overrides the
//                                       rendered heading tag, same config-vs-visual-style split
//                                       as card.php's `title_tag`
//   title_visually_hidden     bool     default false. Adds a functional `sr-only` class (kept in
//                                       the accessibility tree, hidden visually) -- shadcn's own
//                                       documented escape hatch (a VisuallyHidden-wrapped Title)
//                                       for a dialog that needs an accessible name but no visible
//                                       heading. A functional class, not an optical one, allowed
//                                       in Phase 1 (CLAUDE.md #1, same reasoning as tooltip.php's
//                                       default-hidden content)
//   description               string   optional. Rendered via typography.php (variant 'p',
//                                       data_slot 'dialog-description'), wired via
//                                       aria-describedby
//   content                   string   optional. Pre-rendered HTML body, placed directly (see the
//                                       composition note above)
//   footer                    string   optional. Pre-rendered HTML, wrapped in
//                                       <div data-slot="dialog-footer"> (e.g. buffered
//                                       button.php calls, same convention as card.php's `footer`)
//   show_close_button         bool     default true. Built-in top-right close button
//                                       (icon.php 'x'/'lucide', `command="close"`)
//   modal                     bool     default true. true: dialog.js calls showModal() (focus
//                                       trap, ::backdrop, native Escape-to-close). false: matches
//                                       shadcn's own Dialog `modal={false}` -- dialog.js calls the
//                                       non-modal show() instead (no focus trap, no ::backdrop)
//   open                      bool     default false. Initial visible state -- see the zero-JS
//                                       baseline note above
//   dismissible               bool     default true. false blocks Escape/backdrop-close once JS
//                                       is active -- see the gap note above
//   id                        string   native id; pass one explicitly when composing an external
//                                       trigger/footer close button that needs to `commandfor` it
//                                       (see the recipe above) -- auto-generated via
//                                       wp_unique_id() otherwise
//   aria_label                string   fallback accessible name when `title` is omitted
//   class / attributes / data_attributes   passthrough onto the
//                                       <dialog data-slot="dialog-content"> element

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
            'variant' => 'h2',
            'tag' => $title_tag,
            'text' => $title,
            'data_slot' => 'dialog-title',
            'class' => $title_visually_hidden ? 'sr-only' : '',
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
            'variant' => 'p',
            'text' => $description,
            'data_slot' => 'dialog-description',
            'attributes' => ['id' => $id . '-description'],
        ],
    ]);
    $description_markup = (string) ob_get_clean();
}

$header_markup = '';

if ($title_markup !== '' || $description_markup !== '') {
    $header_markup = sprintf(
        '<div data-slot="dialog-header">%1$s%2$s</div>',
        $title_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $description_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$footer_markup =
    trim($footer) !== ''
        ? sprintf('<div data-slot="dialog-footer">%s</div>', $footer) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        : '';

$close_button_markup = '';

if ($show_close_button) {
    $close_button_markup = sprintf(
        '<button type="button" data-slot="dialog-close" command="close" commandfor="%1$s" aria-label="%2$s">%3$s</button>',
        esc_attr($id),
        esc_attr__('Close', 'hengegroup-theme'),
        hengegroup_theme_render_icon(['name' => 'x', 'set' => 'lucide']), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$inner_html = $header_markup . $content . $footer_markup . $close_button_markup;

if (trim($inner_html) === '') {
    return;
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

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

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<dialog%1$s>%2$s</dialog>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
