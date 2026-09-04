<?php

declare(strict_types=1);

// Rebuilt against the actual "sonner" npm package (sonner.emilkowal.ski, Emil Kowalski's
// framework-agnostic toast library -- the real thing "Sonner" refers to). shadcn's current Toast
// page (ui.shadcn.com/docs/components/toast) has since moved on itself -- it now wraps Base UI's
// Toast primitive under its own `toast.add()`/`toast.close()` API, which has drifted further from
// sonner's shape than the older "Sonner" page had. This file deliberately keeps rebuilding the
// real sonner API (see git history), not shadcn's current Base UI wrapper. Toaster props/defaults
// below are taken from sonner's live API reference, not guessed:
//   position: bottom-right | expand: false | visibleToasts: 3 | closeButton: false |
//   richColors: false | toastOptions.duration: 4000
// -- this theme's previous bottom-right/4000ms defaults already happened to match; the newly added
// props (expand/visibleToasts/closeButton/richColors) did not exist here before.
//
// Same "page-level singleton container + imperative JS API" shape as before, no
// native-HTML-only equivalent exists for stacking/auto-dismissing notifications. What changed vs.
// the previous version:
//   - `variant` is now `type` (sonner's own vocabulary: default | success | error | warning |
//     info | loading), matching what a live re-check of shadcn's current Toast page also uses
//   - each toast gets a stable `id` (auto-generated via wp_unique_id() when omitted) so
//     assets/js/template-parts/base/toast.js's `toast.dismiss(id)`/`toast.promise()` can target/
//     update a specific toast later, not just remove the DOM element toast() happened to return
//   - `success`/`error`/`warning`/`info`/`loading` each get a default icon (sonner's own visual
//     signature), overridable per-toast or per-type, or disabled entirely via `icons: false`
//   - `action`/`cancel` buttons: sonner's own options. A pre-rendered flash toast can only offer a
//     REAL zero-JS action via `action.href` (rendered as a plain `<a>`) -- an `onClick`-style
//     callback has no server-side equivalent, so that half only exists on the JS side (toast.js's
//     `toast()` API, see below). `cancel` has no href equivalent (it only ever dismisses), so it
//     renders as an inert button without JS, same honest limitation the close button already has
//   - Toaster-level `expand`/`rich_colors`/`visible_toasts`/`close_button`/`duration` (sonner's
//     own toastOptions.duration) are new; `theme`/`invert`/`dir`/`hotkey`/`offset`/`gap` are
//     deliberately NOT included -- `dir` already inherits natively from `<html dir>`, `theme`/
//     `invert` belong to whatever global dark-mode mechanism the project already has (no other
//     base component takes its own `theme` prop either), `offset`/`gap` are plain CSS spacing
//     values (project-CSS concern, CLAUDE.md #1, e.g. custom properties on
//     `[data-slot="toaster"]`), and a global `hotkey` listener is a bigger, separate feature with
//     no precedent elsewhere in this theme -- not silently dropped, just out of v1 scope
//   - `toast.promise()` (sonner's own promise-chaining) is now implemented JS-side (see toast.js) --
//     it has no PHP-side equivalent, a Promise cannot exist at render time
//
// Render this ONCE per page (e.g. in footer.php). assets/js/template-parts/base/toast.js exports:
//   - initToast()   finds the viewport, wires up any pre-rendered `toasts` (close/action/cancel
//                    buttons, auto-dismiss timer, pause-on-hover)
//   - toast(message, options)   sonner's own calling convention: a positional message string, not
//                    an options object -- plus `toast.success()`/`.error()`/`.warning()`/`.info()`/
//                    `.loading()`/`.message()` (one method per `type`, sonner's own API shape),
//                    `toast.custom(html, options)`, `toast.dismiss(id?)`, `toast.promise(promise,
//                    { loading, success, error })`
//
// Progressive enhancement: `toasts` lets PHP pre-render one or more toasts immediately on page
// load (the classic server-rendered "flash message after a redirect" pattern). Without JS these
// are static, always-visible notices -- auto-dismiss timers, the close button, and any `cancel`
// button are all inert (no working close) until JS is active; an `action.href` link still works,
// since that's a real `<a>`, not a JS click handler. Honest, still largely functional degradation,
// not a broken one.
//
// Not included -- deferred extension points, not added speculatively:
//   - swipe-to-dismiss (touch gesture handling)
//   - `toast.custom()`'s sonner original is a JSX render function; there's no PHP/vanilla-JS
//     equivalent to "a function that returns markup", so this theme's `toast.custom()` instead
//     takes a pre-rendered HTML string (same convention as tooltip.php's `trigger`)
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind on the strength of the Claude-Design reference
// "Hengegroup" (same `.dc.html` reference workflow as separator.php's/pagination.php's own entries,
// see docs/entscheidungen.md for this component's entry). No per-`type` file split was made even
// though the reference organizes its "Varianten" section by type (success/info/error/neutral) --
// every other multi-state base component in this theme (button.php's `variant`, badge.php's
// `variant`, separator.php's `weight`/`style`, progress.php's `variant`) is one config-driven file
// with a small type-to-classes map, not one file per state; a `type`-per-file split here would be
// the only component in the theme to break that convention for a difference that IS just a couple
// of classes, not divergent markup/behaviour. Because it stayed one file, it also did NOT move into
// a `template-parts/base/toast/` folder (Regel 4 only applies once a component is more than one
// PHP file).
//
// What carried over from the reference, and what didn't:
//   - "Nur Fehler bekommt eine getönte Karte" (only the error state tints the whole card) is the
//     reference's own explicit rule, applied literally below: every OTHER type only colors its
//     icon (and, when `duration` renders a life bar, that bar) via a `text-{accent}` class on the
//     `<li>` itself, read by the icon's inherited `currentColor` and the life bar's `bg-current` --
//     title/description/close stay neutral. `error` additionally tints title/description/close via
//     `--color-destructive` (this project's existing error token, already used by every form
//     component's `aria-invalid` state) instead of the reference's own bespoke rust-red hex --
//     introducing a second, differently-sourced "error color" alongside the theme's established
//     `destructive` role would fragment that vocabulary for a difference few users would ever
//     A/B side-by-side.
//   - reference RGB values for `info`/`neutral` (`rgb(7,95,143)`/`rgb(100,106,108)`) turned out to
//     be EXACT matches for this theme's existing `--color-henge-blue`/`--color-henge-grey` tokens
//     (verified byte-for-byte) -- used directly rather than re-declared. `success`/the reference's
//     default demo accent maps to `--color-henge-green` (this project's primary brand accent,
//     the reference's own themeable `accentColor` prop default). `warning` has no reference example
//     at all -- given Tailwind's own `amber-600` (tokens.css's own documented convention: reference
//     Tailwind's stock scales directly when no project token exists yet), not invented from scratch.
//   - `loading`'s default icon composes spinner.php (this component's own Phase-2 ring, see that
//     file's header) instead of the old static `loader-circle` lucide icon -- same
//     already-established substitution button.php's own `loading` state made
//     (`docs/entscheidungen.md`), reused here rather than re-solving "what does a spinner look like"
//     a third time. A caller-supplied `icons.loading`/per-toast `icon` override still renders via
//     icon.php as before (see `$loading_icon_is_default` below) -- only the THEME's own default
//     changed, the override contract didn't.
//   - the reference's "Verhalten" section's auto-dismiss "Laufleiste" (a countdown bar along the
//     card's bottom edge) is reproduced as `[data-slot="toast-life"]`, only rendered when this
//     toast's resolved `duration` is > 0 (an infinite/pending toast, e.g. `toast.promise()`'s
//     "loading" state, correctly gets none, same as the reference's own always-on countdown only
//     applying to toasts that actually auto-dismiss). Its animation duration is the one genuinely
//     PER-TOAST-DYNAMIC value here -- Tailwind's build-time class scanner can only generate CSS for
//     class strings that appear literally in source (same gap find-lucide-icons.php's header
//     documents for icon names assembled from a variable), so a literal `duration`-derived class
//     name is a dead end. Solved the same way progress-circle.php's own truly-dynamic `--pc-value`
//     is: an inline `style="--toast-duration: …ms"` custom property (Regel-1 raw-CSS exception,
//     scoped to exactly this one value) feeding a STATIC `animate-[hg-toast-life_var(--toast-duration)_linear_forwards]`
//     utility -- the class string itself never changes per toast, only the custom property it reads
//     does, so Tailwind's scanner sees one literal class regardless of how many different durations
//     actually render. `hg-toast-life`'s `@keyframes` (plus `hg-toast-in`/`hg-toast-in-top` for the
//     entrance animation) live in assets/css/app.css next to this theme's other documented
//     Regel-1 keyframe exceptions (progress.php's `hg-progress-stripes`, same reasoning: no stock
//     Tailwind utility composes a named, reusable keyframe set). toast.js mirrors this same life-bar
//     markup/custom-property for imperatively created toasts, plus pausing its
//     `animation-play-state` in step with the existing hover-pause timer logic.
//   - fixed positioning (all six `position` values) is now real, `data-[position=...]:` Tailwind
//     variants on `[data-slot="toaster"]` -- Phase 1 deliberately left this as "a project concern"
//     (see the `position` config doc below), this IS that follow-up. `pointer-events-none` on the
//     viewport + `pointer-events-auto` on each toast keeps the empty part of the fixed stack from
//     blocking clicks on whatever page content sits underneath it.
//   - `expand`/`rich_colors`/`visible_toasts` stay exactly what Phase 1 already made them: real
//     bootstrap data (`data-expand`/`data-rich-colors`/`data-visible-toasts`) with no visual effect
//     yet. The reference has no "collapsed stack that fans out" or "solid rich-colors background"
//     example to style against (its own "Verhalten" section already shows every visible toast at
//     full size, uncollapsed) -- inventing that look from nothing would be exactly the kind of
//     un-requested, un-validated vocabulary docs/neue-komponente-erstellen.md #2 warns against for
//     shadcn's OWN vocabulary; left as a config hook for a future pass that has a reference to build
//     against, same "not silently dropped, just out of v1 scope" framing already used above for
//     `hotkey`/`offset`/`gap`.
//   - the reference's "Auf dunklem Grund" section was NOT ported, same reason as every other
//     component's Phase-2 entry so far (separator.php/kbd.php/pagination.php/table/*.php) -- this
//     theme has no dark-mode/dark-surface strategy yet, see docs/entscheidungen.md.
//
// Supported config:
//   position         string   top-left | top-center | top-right | bottom-left | bottom-center |
//                              bottom-right (default: bottom-right, sonner's own default). Sets
//                              data-position AND drives the viewport's real fixed positioning
//                              (Tailwind `data-[position=...]:` variants, see file header above)
//   expand           bool     default false. Sets data-expand="true" when true -- sonner's own
//                              default keeps a collapsed stack that expands on hover; the fan/
//                              collapse visual itself is not implemented yet, see file header above
//   rich_colors      bool     default false. Sets data-rich-colors="true" when true -- sonner's own
//                              styling hook for stronger per-type background colors; not
//                              implemented yet, see file header above
//   visible_toasts   int      default 3 (sonner's own default). Always rendered as
//                              data-visible-toasts on the viewport -- bootstrap data for toast.js
//                              to decide when older toasts collapse into the stack
//   close_button     bool     default false (sonner's own default -- no close button unless asked
//                              for). Controls whether pre-rendered `toasts` get a close button AND
//                              is mirrored as data-close-button on the viewport so toast.js applies
//                              the same default to imperatively created toasts
//   duration         int      default 4000ms (sonner's own toastOptions.duration default) --
//                              toaster-wide default auto-dismiss delay once JS is active; a
//                              per-toast `duration` below overrides this. Also drives whether a
//                              toast renders a `[data-slot="toast-life"]` countdown bar (only when
//                              the resolved duration is > 0, see file header above)
//   icons            array|false   default null (built-in icons for success/error/warning/info/
//                              loading, none for default -- sonner's own visual signature). Array:
//                              per-type icon.php config overrides, e.g. ['success' => [...]] or
//                              ['success' => false] to disable just that one type; overriding
//                              `loading` this way also opts it out of the spinner.php default (see
//                              file header above). `false`: disable all built-in type icons
//                              theme-wide (a per-toast `icon` config can still add one back
//                              explicitly, see below)
//   toasts           array    optional, pre-rendered flash toasts shown immediately on page load,
//                              each:
//     message          string   required (or description required if message omitted) -- sonner's
//                                own positional first argument to toast(), here as a named key
//                                since PHP config is always an associative array
//     description      string   optional secondary text
//     type             string   default | success | error | warning | info | loading
//                                (default: default) -- sonner's own type vocabulary
//     duration         int      per-toast override of the toaster-level `duration` above
//     icon             array|false   per-toast override of this type's default icon (see `icons`
//                                above); false removes the icon for just this one toast
//     action           array    { label, href } -- real, zero-JS-functional link (see note above)
//     cancel           array    { label } (default label: "Dismiss") -- always just dismisses;
//                                inert without JS, same limitation as the close button
//     id               string   this toast's own id (used as its DOM `id`, see below);
//                                auto-generated via wp_unique_id() when omitted
//   class / attributes / data_attributes   passthrough onto the outer
//                        <ol data-slot="toaster"> viewport (no separate `id` config here -- a
//                        page-level singleton is always found via `[data-slot="toaster"]`, not by
//                        id; pass `attributes: ['id' => '...']` if one is genuinely needed)

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$position = trim((string) ($config['position'] ?? 'bottom-right'));
$expand = !empty($config['expand']);
$rich_colors = !empty($config['rich_colors']);
$visible_toasts = (int) ($config['visible_toasts'] ?? 3);
$close_button = !empty($config['close_button']);
$default_duration = (int) ($config['duration'] ?? 4000);
$toasts_config = is_array($config['toasts'] ?? null) ? $config['toasts'] : [];
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_positions = [
    'top-left',
    'top-center',
    'top-right',
    'bottom-left',
    'bottom-center',
    'bottom-right',
];

if (!in_array($position, $allowed_positions, true)) {
    $position = 'bottom-right';
}

if ($visible_toasts < 1) {
    $visible_toasts = 3;
}

if ($default_duration < 0) {
    $default_duration = 4000;
}

$allowed_types = ['default', 'success', 'error', 'warning', 'info', 'loading'];

// Read by the icon (inherited `currentColor`) and, when a toast renders a life bar, that bar's
// `bg-current` -- title/description/close stay neutral for every type except `error`, which tints
// them separately below ("nur Fehler bekommt eine getönte Karte", see file header above).
$type_accent_classes = [
    'default' => 'text-muted-foreground',
    'success' => 'text-henge-green',
    'error' => 'text-destructive',
    'warning' => 'text-amber-600',
    'info' => 'text-henge-blue',
    'loading' => 'text-muted-foreground',
];

$icons_disabled = array_key_exists('icons', $config) && $config['icons'] === false;
$icons_override = !$icons_disabled && is_array($config['icons'] ?? null) ? $config['icons'] : [];

// 'spinner' is a sentinel, not an icon.php config -- resolved to spinner.php's own markup below
// instead of icon.php's, see file header above. array_merge lets a caller's own `icons.loading`
// override replace it with a real icon.php config (or `false` to disable) same as every other type.
$default_type_icons = $icons_disabled
    ? []
    : array_merge(
        [
            'success' => ['name' => 'circle-check', 'set' => 'lucide'],
            'error' => ['name' => 'circle-x', 'set' => 'lucide'],
            'warning' => ['name' => 'triangle-alert', 'set' => 'lucide'],
            'info' => ['name' => 'info', 'set' => 'lucide'],
            'loading' => 'spinner',
        ],
        $icons_override,
    );

$close_icon_markup = hengegroup_theme_render_icon(['name' => 'x', 'set' => 'lucide']);

// Phase 2 Tailwind classes, shared between the pre-rendered `toasts` loop below and toast.js's own
// imperative show() -- keep both in sync when changing either, same "className duplicated between
// PHP and its JS-enhancement layer" idiom select.js/combobox.js/calendar.js already use for the
// options/cells they build client-side.
$icon_wrap_classes = "mt-0.5 shrink-0 [&_svg:not([class*='size-'])]:size-5";
$content_classes = 'flex min-w-0 flex-1 flex-col gap-1';
$title_classes = 'text-base leading-[1.3] font-semibold';
$description_classes = 'text-sm leading-[1.45] text-pretty';
$actions_classes = 'flex shrink-0 items-center gap-2 self-center';
$action_classes =
    'inline-flex items-center justify-center rounded-lg border border-foreground/16 px-3.5 py-1.5 ' .
    'text-sm font-semibold text-foreground transition-colors hover:border-henge-green hover:text-henge-green';
$cancel_classes =
    'inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-sm font-medium ' .
    'text-muted-foreground transition-colors hover:bg-foreground/6 hover:text-foreground';
$close_classes =
    '-mt-1 -mr-1.5 ml-1 flex size-7 shrink-0 self-center items-center justify-center rounded-lg ' .
    "transition-colors [&_svg:not([class*='size-'])]:size-4";
$life_classes =
    'absolute inset-x-0 bottom-0 h-0.5 origin-left bg-current opacity-50 ' .
    'animate-[hg-toast-life_var(--toast-duration)_linear_forwards]';

$toasts_markup = '';

foreach ($toasts_config as $toast_config) {
    if (!is_array($toast_config)) {
        continue;
    }

    $message = trim((string) ($toast_config['message'] ?? ''));
    $description = trim((string) ($toast_config['description'] ?? ''));

    if ($message === '' && $description === '') {
        continue;
    }

    $type = trim((string) ($toast_config['type'] ?? 'default'));

    if (!in_array($type, $allowed_types, true)) {
        $type = 'default';
    }

    $is_error = $type === 'error';

    $duration = array_key_exists('duration', $toast_config)
        ? (int) $toast_config['duration']
        : $default_duration;

    if ($duration < 0) {
        $duration = $default_duration;
    }

    $toast_id = trim((string) ($toast_config['id'] ?? ''));

    if ($toast_id === '') {
        $toast_id = 'hengegroup-theme-toast-' . wp_unique_id();
    }

    $icon_config = array_key_exists('icon', $toast_config)
        ? $toast_config['icon']
        : $default_type_icons[$type] ?? null;

    if ($icon_config === 'spinner') {
        ob_start();
        get_template_part('template-parts/base/spinner', null, [
            'config' => ['size' => 'sm', 'color' => 'inherit', 'decorative' => true],
        ]);
        $spinner_markup = (string) ob_get_clean();

        $icon_markup = sprintf(
            '<div data-slot="toast-icon" class="%s">%s</div>',
            esc_attr($icon_wrap_classes),
            $spinner_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    } elseif (is_array($icon_config)) {
        $icon_markup = sprintf(
            '<div data-slot="toast-icon" class="%s">%s</div>',
            esc_attr($icon_wrap_classes),
            hengegroup_theme_render_icon($icon_config), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    } else {
        $icon_markup = '';
    }

    $text_markup = '';

    if ($message !== '') {
        $text_markup .= sprintf(
            '<div data-slot="toast-title" class="%1$s %2$s">%3$s</div>',
            esc_attr($title_classes),
            esc_attr($is_error ? 'text-destructive' : 'text-foreground'),
            esc_html($message),
        );
    }

    if ($description !== '') {
        $text_markup .= sprintf(
            '<div data-slot="toast-description" class="%1$s %2$s">%3$s</div>',
            esc_attr($description_classes),
            esc_attr($is_error ? 'text-destructive/80' : 'text-muted-foreground'),
            esc_html($description),
        );
    }

    $content_markup = sprintf(
        '<div data-slot="toast-content" class="%1$s">%2$s</div>',
        esc_attr($content_classes),
        $text_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );

    $action_markup = '';

    if (is_array($toast_config['action'] ?? null)) {
        $action_label = trim((string) ($toast_config['action']['label'] ?? ''));
        $action_href = trim((string) ($toast_config['action']['href'] ?? ''));

        if ($action_label !== '') {
            $action_attributes = ['data-slot' => 'toast-action', 'class' => $action_classes];

            if ($action_href !== '') {
                $action_attributes['href'] = $action_href;
            }

            $action_markup = sprintf(
                '<a%1$s>%2$s</a>',
                hengegroup_theme_render_attributes($action_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                esc_html($action_label),
            );
        }
    }

    $cancel_markup = '';

    if (is_array($toast_config['cancel'] ?? null)) {
        $cancel_label = trim((string) ($toast_config['cancel']['label'] ?? ''));

        if ($cancel_label === '') {
            $cancel_label = esc_html__('Dismiss', 'hengegroup-theme');
        }

        $cancel_markup = sprintf(
            '<button type="button" data-slot="toast-cancel" class="%1$s">%2$s</button>',
            esc_attr($cancel_classes),
            esc_html($cancel_label),
        );
    }

    $actions_markup = '';

    if ($action_markup !== '' || $cancel_markup !== '') {
        $actions_markup = sprintf(
            '<div data-slot="toast-actions" class="%1$s">%2$s%3$s</div>',
            esc_attr($actions_classes),
            $action_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $cancel_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    }

    $close_markup = '';

    if ($close_button) {
        $close_color_classes = $is_error
            ? 'text-destructive/60 hover:bg-destructive/10 hover:text-destructive'
            : 'text-foreground/45 hover:bg-foreground/6 hover:text-foreground';

        $close_markup = sprintf(
            '<button type="button" data-slot="toast-close" class="%1$s %2$s" aria-label="%3$s">%4$s</button>',
            esc_attr($close_classes),
            esc_attr($close_color_classes),
            esc_attr__('Close', 'hengegroup-theme'),
            $close_icon_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    }

    $life_markup = '';

    if ($duration > 0) {
        $life_markup = sprintf(
            '<span data-slot="toast-life" class="%s"></span>',
            esc_attr($life_classes),
        );
    }

    $card_classes =
        'pointer-events-auto relative flex w-full items-start gap-3 overflow-hidden rounded-2xl ' .
        'border px-4 py-4 shadow-lg animate-[hg-toast-in_0.28s_cubic-bezier(0.2,0.9,0.3,1)] ' .
        ($is_error ? 'border-destructive/25 bg-destructive/6' : 'border-foreground/8 bg-card') .
        ' ' .
        $type_accent_classes[$type];

    $toasts_markup .= sprintf(
        '<li data-slot="toast" data-type="%1$s" id="%2$s" data-duration="%3$s" class="%4$s" style="--toast-duration: %3$sms">' .
            '%5$s%6$s%7$s%8$s%9$s' .
            '</li>',
        esc_attr($type),
        esc_attr($toast_id),
        esc_attr((string) $duration),
        esc_attr($card_classes),
        $icon_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $actions_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $close_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $life_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

// Cloned by toast.js: the close icon (for any imperatively created toast's close button) plus one
// icon per built-in type, so JS-created toasts stay visually identical to server-rendered ones
// instead of JS falling back to a second, duplicated icon implementation. `loading`'s template
// carries spinner.php's markup whenever it's still the theme default (see file header above); a
// caller override renders through icon.php like every other type.
$templates_markup = sprintf(
    '<template data-slot="toast-close-icon-template">%s</template>',
    $close_icon_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

foreach ($default_type_icons as $icon_type => $icon_config) {
    if ($icon_config === 'spinner') {
        ob_start();
        get_template_part('template-parts/base/spinner', null, [
            'config' => ['size' => 'sm', 'color' => 'inherit', 'decorative' => true],
        ]);
        $spinner_markup = (string) ob_get_clean();

        $templates_markup .= sprintf(
            '<template data-slot="toast-icon-template" data-type="%1$s">%2$s</template>',
            esc_attr($icon_type),
            $spinner_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );

        continue;
    }

    if (!is_array($icon_config)) {
        continue;
    }

    $templates_markup .= sprintf(
        '<template data-slot="toast-icon-template" data-type="%1$s">%2$s</template>',
        esc_attr($icon_type),
        hengegroup_theme_render_icon($icon_config), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$viewport_base_classes =
    'pointer-events-none fixed z-50 m-0 flex w-[calc(100%-2rem)] max-w-[380px] list-none flex-col gap-3 p-0 ' .
    'data-[position=bottom-right]:right-4 data-[position=bottom-right]:bottom-4 ' .
    'data-[position=bottom-right]:items-end data-[position=bottom-left]:bottom-4 ' .
    'data-[position=bottom-left]:left-4 data-[position=bottom-left]:items-start ' .
    'data-[position=bottom-center]:bottom-4 data-[position=bottom-center]:left-1/2 ' .
    'data-[position=bottom-center]:-translate-x-1/2 data-[position=bottom-center]:items-center ' .
    'data-[position=top-right]:top-4 data-[position=top-right]:right-4 ' .
    'data-[position=top-right]:flex-col-reverse data-[position=top-right]:items-end ' .
    'data-[position=top-left]:top-4 data-[position=top-left]:left-4 ' .
    'data-[position=top-left]:flex-col-reverse data-[position=top-left]:items-start ' .
    'data-[position=top-center]:top-4 data-[position=top-center]:left-1/2 ' .
    'data-[position=top-center]:-translate-x-1/2 data-[position=top-center]:flex-col-reverse ' .
    'data-[position=top-center]:items-center';

$viewport_attributes = $attributes;

$viewport_attributes['class'] = trim(
    $viewport_base_classes . ($class_name !== '' ? ' ' . $class_name : ''),
);

$viewport_attributes['data-slot'] = 'toaster';
$viewport_attributes['data-position'] = $position;
$viewport_attributes['data-visible-toasts'] = (string) $visible_toasts;
$viewport_attributes['data-duration'] = (string) $default_duration;
$viewport_attributes['role'] = 'region';
$viewport_attributes['aria-live'] = 'polite';
$viewport_attributes['aria-label'] = esc_attr__('Notifications', 'hengegroup-theme');

if ($expand) {
    $viewport_attributes['data-expand'] = 'true';
}

if ($rich_colors) {
    $viewport_attributes['data-rich-colors'] = 'true';
}

if ($close_button) {
    $viewport_attributes['data-close-button'] = 'true';
}

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $viewport_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<ol%1$s>%2$s%3$s</ol>',
    hengegroup_theme_render_attributes($viewport_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $toasts_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $templates_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
