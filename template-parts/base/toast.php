<?php

declare(strict_types=1);

// Toast viewport (once per page, e.g. footer.php) following the sonner API. toast.js exports
// initToast() and toast(message, options) with .success/.error/.warning/.info/.loading/.message/
// .custom(html)/.dismiss(id)/.promise(). Pre-rendered `toasts` (flash messages) are static without
// JS; only action.href works then. Only `error` tints the whole card. The countdown bar reads its
// duration from an inline custom property because Tailwind cannot generate per-toast classes.
// expand/rich_colors/visible_toasts are data hooks without a visual effect yet.
//
// Config:
//   position         string        top-left | top-center | top-right | bottom-left | bottom-center
//                                  | bottom-right (default)
//   expand           bool
//   rich_colors      bool
//   visible_toasts   int           default 3
//   close_button     bool          default false
//   duration         int           default 4000 ms
//   icons            array|false   per-type icon.php overrides; false disables all
//   toasts           array         each { message or description (required), description, type
//                                  (default|success|error|warning|info|loading), duration,
//                                  icon (array|false), action { label, href }, cancel { label }, id }
//   class / attributes / data_attributes   on the <ol data-slot="toaster">

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

// Icon and countdown bar take the type color via currentColor; only error tints the text as well.
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

// 'spinner' = spinner.php instead of icon.php; can be overridden via `icons.loading`.
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

// Also used by toast.js for imperatively created toasts; keep both in sync.
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
        'border px-4 py-4 shadow-overlay animate-[hg-toast-in_0.28s_cubic-bezier(0.2,0.9,0.3,1)] ' .
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

// Templates for toast.js so that JS toasts look identical to server-rendered ones.
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

$viewport_attributes = hengegroup_theme_merge_data_attributes(
    $viewport_attributes,
    $data_attributes,
);

printf(
    '<ol%1$s>%2$s%3$s</ol>',
    hengegroup_theme_render_attributes($viewport_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $toasts_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $templates_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
