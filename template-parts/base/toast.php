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
// Supported config:
//   position         string   top-left | top-center | top-right | bottom-left | bottom-center |
//                              bottom-right (default: bottom-right, sonner's own default) -- sets
//                              data-position, actual fixed-positioning CSS is a project concern
//   expand           bool     default false. Sets data-expand="true" when true -- sonner's own
//                              default keeps a collapsed stack that expands on hover; whether that
//                              actually happens is project-CSS/JS, this is only the config hook
//   rich_colors      bool     default false. Sets data-rich-colors="true" when true -- sonner's own
//                              styling hook for stronger per-type background colors (project-CSS)
//   visible_toasts   int      default 3 (sonner's own default). Always rendered as
//                              data-visible-toasts on the viewport -- bootstrap data for toast.js
//                              to decide when older toasts collapse into the stack
//   close_button     bool     default false (sonner's own default -- no close button unless asked
//                              for). Controls whether pre-rendered `toasts` get a close button AND
//                              is mirrored as data-close-button on the viewport so toast.js applies
//                              the same default to imperatively created toasts
//   duration         int      default 4000ms (sonner's own toastOptions.duration default) --
//                              toaster-wide default auto-dismiss delay once JS is active; a
//                              per-toast `duration` below overrides this
//   icons            array|false   default null (built-in icons for success/error/warning/info/
//                              loading, none for default -- sonner's own visual signature). Array:
//                              per-type icon.php config overrides, e.g. ['success' => [...]] or
//                              ['success' => false] to disable just that one type. `false`: disable
//                              all built-in type icons theme-wide (a per-toast `icon` config can
//                              still add one back explicitly, see below)
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

$icons_disabled = array_key_exists('icons', $config) && $config['icons'] === false;
$icons_override = !$icons_disabled && is_array($config['icons'] ?? null) ? $config['icons'] : [];

$default_type_icons = $icons_disabled
    ? []
    : array_merge(
        [
            'success' => ['name' => 'circle-check', 'set' => 'lucide'],
            'error' => ['name' => 'circle-x', 'set' => 'lucide'],
            'warning' => ['name' => 'triangle-alert', 'set' => 'lucide'],
            'info' => ['name' => 'info', 'set' => 'lucide'],
            'loading' => ['name' => 'loader-circle', 'set' => 'lucide'],
        ],
        $icons_override,
    );

$close_icon_markup = base_theme_render_icon(['name' => 'x', 'set' => 'lucide']);

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

    $duration = array_key_exists('duration', $toast_config)
        ? (int) $toast_config['duration']
        : $default_duration;

    if ($duration < 0) {
        $duration = $default_duration;
    }

    $toast_id = trim((string) ($toast_config['id'] ?? ''));

    if ($toast_id === '') {
        $toast_id = 'base-theme-toast-' . wp_unique_id();
    }

    $icon_config = array_key_exists('icon', $toast_config)
        ? $toast_config['icon']
        : $default_type_icons[$type] ?? null;

    $icon_markup = is_array($icon_config)
        ? sprintf(
            '<div data-slot="toast-icon">%s</div>',
            base_theme_render_icon($icon_config), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        )
        : '';

    $text_markup = '';

    if ($message !== '') {
        $text_markup .= sprintf('<div data-slot="toast-title">%s</div>', esc_html($message));
    }

    if ($description !== '') {
        $text_markup .= sprintf(
            '<div data-slot="toast-description">%s</div>',
            esc_html($description),
        );
    }

    $content_markup = sprintf(
        '<div data-slot="toast-content">%s</div>',
        $text_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );

    $action_markup = '';

    if (is_array($toast_config['action'] ?? null)) {
        $action_label = trim((string) ($toast_config['action']['label'] ?? ''));
        $action_href = trim((string) ($toast_config['action']['href'] ?? ''));

        if ($action_label !== '') {
            $action_attributes = ['data-slot' => 'toast-action'];

            if ($action_href !== '') {
                $action_attributes['href'] = $action_href;
            }

            $action_markup = sprintf(
                '<a%1$s>%2$s</a>',
                base_theme_render_attributes($action_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                esc_html($action_label),
            );
        }
    }

    $cancel_markup = '';

    if (is_array($toast_config['cancel'] ?? null)) {
        $cancel_label = trim((string) ($toast_config['cancel']['label'] ?? ''));

        if ($cancel_label === '') {
            $cancel_label = esc_html__('Dismiss', 'base-theme');
        }

        $cancel_markup = sprintf(
            '<button type="button" data-slot="toast-cancel">%s</button>',
            esc_html($cancel_label),
        );
    }

    $actions_markup = '';

    if ($action_markup !== '' || $cancel_markup !== '') {
        $actions_markup = sprintf(
            '<div data-slot="toast-actions">%1$s%2$s</div>',
            $action_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $cancel_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    }

    $close_markup = '';

    if ($close_button) {
        $close_markup = sprintf(
            '<button type="button" data-slot="toast-close" aria-label="%1$s">%2$s</button>',
            esc_attr__('Close', 'base-theme'),
            $close_icon_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    }

    $toasts_markup .= sprintf(
        '<li data-slot="toast" data-type="%1$s" id="%2$s" data-duration="%3$s">' .
            '%4$s%5$s%6$s%7$s' .
            '</li>',
        esc_attr($type),
        esc_attr($toast_id),
        esc_attr((string) $duration),
        $icon_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $actions_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $close_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

// Cloned by toast.js: the close icon (for any imperatively created toast's close button) plus one
// icon per built-in type, so JS-created toasts stay visually identical to server-rendered ones
// instead of JS falling back to a second, duplicated icon implementation.
$templates_markup = sprintf(
    '<template data-slot="toast-close-icon-template">%s</template>',
    $close_icon_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

foreach ($default_type_icons as $icon_type => $icon_config) {
    if (!is_array($icon_config)) {
        continue;
    }

    $templates_markup .= sprintf(
        '<template data-slot="toast-icon-template" data-type="%1$s">%2$s</template>',
        esc_attr($icon_type),
        base_theme_render_icon($icon_config), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$viewport_attributes = $attributes;

if ($class_name !== '') {
    $viewport_attributes['class'] = $class_name;
}

$viewport_attributes['data-slot'] = 'toaster';
$viewport_attributes['data-position'] = $position;
$viewport_attributes['data-visible-toasts'] = (string) $visible_toasts;
$viewport_attributes['data-duration'] = (string) $default_duration;
$viewport_attributes['role'] = 'region';
$viewport_attributes['aria-live'] = 'polite';
$viewport_attributes['aria-label'] = esc_attr__('Notifications', 'base-theme');

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
    base_theme_render_attributes($viewport_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $toasts_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $templates_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
