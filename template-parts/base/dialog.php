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
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind on the strength of the Claude-Design reference
// "Hengegroup" (https://claude.ai/code/artifact/51dedf08-71e3-4deb-9e68-19256e4cfb39), same
// `.dc.html` reference workflow as popover.php's/hover-card.php's own entries, see
// docs/entscheidungen.md for this component's entry). The reference itself is short -- one worked
// example ("Lieferform ändern": header, a caller-built option list, footer with two actions) plus a
// prose anatomy callout ("Kopf mit Titel und Beschreibung, Inhalt, Fußzeile mit bis zu zwei
// Aktionen. 32 px Innenabstand, 18 px Radius") -- not a matrix of structurally different dialogs.
//
// What carried over from the reference, and what didn't:
//   - **No file-per-variant split, no `dialog/` folder move** (the task explicitly asked to check,
//     same phrasing as hover-card.php's/popover.php's own tasks) -- the reference shows exactly one
//     look; `title`/`description`/`content`/`footer`/`show_close_button` already model every part of
//     it via config/composition, same conclusion popover.php's/hover-card.php's/tabs.php's own
//     Phase 2 entries already reached for an almost identical shape.
//   - radius: the reference's own literal 18px was NOT copied -- standardized on `rounded-2xl`
//     (16px), matching this project's other Phase-2 floating/card surfaces (popover.php's/
//     hover-card.php's/toast.php's/calendar.php's own `rounded-2xl`) rather than a one-off number,
//     same reasoning as popover.php's own radius entry.
//   - padding: the reference's own literal 32px WAS kept as `p-8` -- unlike the radius above, this
//     one maps exactly onto Tailwind's real scale (`p-8` = 32px), so there was no "one-off pixel
//     value vs. a systematic step" conflict to resolve.
//   - background/text color: `bg-background`/`text-foreground` -- shadcn's own real stock
//     `DialogContent` classes (checked live against current docs), not `bg-card`/`bg-popover` (this
//     project's other two neutral-surface tokens); all three tokens currently resolve to the same
//     literal color, so this is a "match shadcn's own vocabulary" choice, not a visible difference.
//   - no border: the reference's card has no visible edge against its dark demo frame, only a
//     shadow -- kept borderless (unlike popover.php's/hover-card.php's own `border-border` hairline)
//     rather than adding one shadcn's real stock `DialogContent` does carry, because it would be
//     purely invented against this specific reference's own visual evidence.
//   - shadow (`shadow-[0_12px_32px_rgba(0,0,0,0.14)]`) reuses popover.php's own literal value
//     (extracted from ITS reference) rather than the reference's own value for this component (which
//     was not measurable from a flat demo frame) -- same "this project's own floating-surface shadow
//     convention" reasoning as hover-card.php's own entry, not a fresh number invented from nothing.
//   - width: `w-full max-w-[calc(100%-2rem)] sm:max-w-lg` is shadcn's own real stock
//     `DialogContent` sizing (checked live against current docs) -- close enough to the reference's
//     own ~520px example box that reproducing shadcn's actual vocabulary was preferred over pinning
//     one demo's specific pixel width, same "generalize instead of over-fitting to one demo string"
//     reasoning as popover.php's own width entry. Native `<dialog>` needs no
//     `fixed top-1/2 left-1/2 translate-x-[-50%] translate-y-[-50%]` centering hack the way a plain
//     `<div>`-based Dialog needs (see this file's own Phase 1 header comment) -- the UA stylesheet's
//     own `dialog:modal { position: fixed; inset: 0; }` already does that; only `m-auto` had to be
//     re-added explicitly (Tailwind's Preflight zeroes `margin`/`padding` on every element including
//     `<dialog>`, which would otherwise silently cancel the UA stylesheet's own `margin: auto`
//     centering -- a real, non-obvious gotcha of pairing native `<dialog>` with Tailwind, not a
//     stylistic choice). `max-height`/`overflow: auto` for oversized content are NOT re-added --
//     Preflight never touches either property, so the UA stylesheet's own
//     `dialog:modal { max-height: calc(100% - 6px - 2em); overflow: auto; }` survives untouched,
//     same "native already gives this for free" principle as the rest of this file.
//   - entrance animation: `hg-dialog-in` (opacity + scale, content) and `hg-dialog-overlay-in`
//     (opacity, `::backdrop` via the `[&::backdrop]:` arbitrary variant -- Preflight's own reset
//     explicitly targets `::backdrop` too, see `assets/css/app.css`'s own header comment for that
//     keyframe pair), two new named `@keyframes` in assets/css/app.css (documented Regel-1 raw-CSS
//     exception, same reasoning as that file's other `hg-*-in` entries -- no stock Tailwind utility
//     composes a named keyframe set). Both run unconditionally, same "no JS-toggled state class
//     needed" reasoning as popover.php's own `hg-popover-in` -- see that keyframe's own comment in
//     app.css for why native `<dialog>` gives this the same way native `<details>` does. `::backdrop`
//     only ever exists for a `:modal` dialog (the `modal: false` config path renders no backdrop at
//     all, native platform behaviour, not a v1 limitation), and `bg-black/50` is shadcn's own real
//     stock `DialogOverlay` color.
//   - header (`gap-2` between title/description, shadcn's own real stock `DialogHeader` gap) and
//     footer (`gap-3`, matching card.php's own default `footer_gap` rather than shadcn's stock
//     `gap-2`, for consistency with this project's other header/content/footer composed surfaces)
//     both stay simple `flex flex-col`/`flex flex-wrap items-center justify-end` -- shadcn's own
//     stock `DialogFooter` additionally reverses stacking order on narrow viewports
//     (`flex-col-reverse ... sm:flex-row`) so the primary action stays reachable first on mobile;
//     NOT reproduced here since the reference shows no mobile/narrow layout to validate that against,
//     same "not silently dropped, just out of v1 scope" framing used elsewhere in this codebase.
//     Outer `gap-6` matches card.php's own default `outer_gap`.
//   - the built-in close button (`dialog-close`) is new project CSS, not carried from shadcn's stock
//     borderless ghost icon button -- the reference explicitly draws a bordered, rounded square
//     (`border-border`/`rounded-lg`/`size-9`), closer in spirit to toast.php's own `toast-close`
//     (`rounded-lg`, `size-7`) than to a plain ghost icon; kept the reference's own border rather
//     than toast-close's borderless look since the reference shows one clearly and a dialog's close
//     control has more visual weight to carry (no adjacent auto-dismiss affordance the way a toast
//     has). Positioned `top-8 right-8` -- the same 32px inset as the content box's own `p-8`, so it
//     sits flush with the header text's own padding edge rather than a separate, smaller inset.
//   - `description` gets `color: 'neutral'`/`class: 'text-pretty'` (typography.php), same convention
//     as card.php's own `card-description` -- the reference's description text is visibly muted
//     relative to the title, matching `text-muted-foreground` rather than typography.php's default
//     full-strength text color.
//   - `page-component-showcase-dialog.php` new, analog to the other showcase pages.
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
            'variant' => 'body-lg',
            'tag' => $title_tag,
            'text' => $title,
            'data_slot' => 'dialog-title',
            // body-lg is font-normal by default (see typography.php) -- a dialog title needs
            // to stand out from dialog-description below it, hence the added emphasis here.
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
            // shadcn's own DialogDescription is `text-muted-foreground` -- typography.php's `color`
            // axis already models this exact role (see card.php's own `card-description` for the
            // identical convention).
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

// See the Phase 2 file header for the full derivation of every class below, in particular why
// `m-auto` has to be re-added explicitly (Preflight zeroes the UA stylesheet's own centering
// `margin: auto`) while `max-height`/`overflow` don't (Preflight never touches either). `flex` is
// gated behind the `open:` variant (`&[open]`), NOT applied bare -- a bare `flex` is an author-origin
// declaration, which beats the UA stylesheet's own `dialog:not([open]) { display: none }`
// REGARDLESS of selector specificity (author always wins over user-agent for two normal, i.e.
// non-`!important`, declarations). An unconditional `flex` would therefore force every dialog
// permanently visible -- open or not -- the exact bug this file shipped with initially. Scoping it
// to `open:flex` only ever declares `display` while `[open]` is actually present, leaving the UA
// stylesheet's own `display: none` completely untouched (and therefore still in effect) the rest
// of the time.
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
