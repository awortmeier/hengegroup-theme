<?php

declare(strict_types=1);

// Compact "Page X of Y" bar with optional per-page switcher. Links via add_query_arg(); the
// prev/next buttons carry data-action for data-table.js.
//
// Config:
//   current_page       int        default 1
//   total_pages        int        default 1
//   page_var           string     default 'paged'
//   status_text        string     default "Page %1$d of %2$d"
//   total_items        int|null   with per_page: adds a "1-12 of 40 results" line
//   per_page           int|null   current page size
//   per_page_options   array      e.g. [12, 24, 48]; renders a button-group, resets to page 1
//   per_page_var       string     default 'per_page'
//   per_page_label     string     default "Per page"
//   aria_label         string     default "pagination"
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$current_page = max(1, (int) ($config['current_page'] ?? 1));
$total_pages = max(1, (int) ($config['total_pages'] ?? 1));
$page_var = trim((string) ($config['page_var'] ?? 'paged'));
$status_text = trim((string) ($config['status_text'] ?? ''));
$total_items =
    array_key_exists('total_items', $config) && $config['total_items'] !== null
        ? max(0, (int) $config['total_items'])
        : null;
$per_page =
    array_key_exists('per_page', $config) && $config['per_page'] !== null
        ? max(1, (int) $config['per_page'])
        : null;
$per_page_options = is_array($config['per_page_options'] ?? null)
    ? $config['per_page_options']
    : [];
$per_page_var = trim((string) ($config['per_page_var'] ?? 'per_page'));
$per_page_label = trim((string) ($config['per_page_label'] ?? ''));
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($page_var === '') {
    $page_var = 'paged';
}

if ($per_page_var === '') {
    $per_page_var = 'per_page';
}

if ($aria_label === '') {
    $aria_label = __('pagination', 'hengegroup-theme');
}

if ($per_page_label === '') {
    $per_page_label = __('Per page', 'hengegroup-theme');
}

$current_page = min($current_page, $total_pages);
$has_previous = $current_page > 1;
$has_next = $current_page < $total_pages;

$nav_button = static function (
    string $target_page,
    string $icon_name,
    string $text,
    string $icon_position,
    bool $disabled,
    string $action,
) use ($page_var): string {
    ob_start();
    get_template_part('template-parts/base/button', null, [
        'config' => [
            'href' => esc_url(add_query_arg($page_var, $target_page)),
            'variant' => 'ghost',
            'size' => 'base',
            'text' => $text,
            'icon' => ['name' => $icon_name, 'set' => 'lucide'],
            'icon_position' => $icon_position,
            'disabled' => $disabled,
            'data_attributes' => ['action' => $action],
        ],
    ]);

    return (string) ob_get_clean();
};

$previous_markup = $nav_button(
    (string) max(1, $current_page - 1),
    'chevron-left',
    __('Previous', 'hengegroup-theme'),
    'start',
    !$has_previous,
    'previous',
);

$next_markup = $nav_button(
    (string) min($total_pages, $current_page + 1),
    'chevron-right',
    __('Next', 'hengegroup-theme'),
    'end',
    !$has_next,
    'next',
);

if ($status_text === '') {
    $status_text = sprintf(
        /* translators: 1: current page number, 2: total number of pages */
        __('Page %1$d of %2$d', 'hengegroup-theme'),
        $current_page,
        $total_pages,
    );
}

ob_start();
get_template_part('template-parts/base/typography', null, [
    'config' => [
        'variant' => 'body-xs',
        'color' => 'neutral',
        'data_slot' => 'pagination-compact-status',
        'text' => $status_text,
    ],
]);
$status_markup = (string) ob_get_clean();

$bar_markup = sprintf(
    '<div data-slot="pagination-compact-bar" class="flex items-center justify-between gap-5 rounded-xl border border-border bg-card px-3.5 py-3 shadow-xs">%1$s%2$s%3$s</div>',
    $previous_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $status_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $next_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

$range_markup = '';

if ($total_items !== null && $per_page !== null) {
    $from = $total_items === 0 ? 0 : ($current_page - 1) * $per_page + 1;
    $to = min($current_page * $per_page, $total_items);

    ob_start();
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => 'body-xs',
            'color' => 'neutral',
            'data_slot' => 'pagination-compact-range',
            'text' => sprintf(
                /* translators: 1: first result number on this page, 2: last result number on this
                 page, 3: total number of results */
                __('%1$d-%2$d of %3$d results', 'hengegroup-theme'),
                $from,
                $to,
                $total_items,
            ),
        ],
    ]);
    $range_markup = (string) ob_get_clean();
}

$per_page_markup = '';

if ($per_page_options !== [] && $per_page !== null) {
    ob_start();

    foreach ($per_page_options as $option_value) {
        $option_value = (int) $option_value;

        if ($option_value < 1) {
            continue;
        }

        $is_active = $option_value === $per_page;

        get_template_part('template-parts/base/button', null, [
            'config' => [
                'href' => esc_url(
                    add_query_arg([$page_var => '1', $per_page_var => (string) $option_value]),
                ),
                'variant' => $is_active ? 'grey-light' : 'ghost',
                'size' => 'sm',
                'text' => (string) $option_value,
                'attributes' => $is_active ? ['aria-current' => 'true'] : [],
            ],
        ]);
    }

    $options_markup = (string) ob_get_clean();

    if (trim($options_markup) !== '') {
        ob_start();
        get_template_part('template-parts/base/button-group/button-group', null, [
            'config' => ['content' => $options_markup],
        ]);
        $group_markup = (string) ob_get_clean();

        $per_page_markup = sprintf(
            '<div data-slot="pagination-compact-per-page" class="flex items-center gap-2">' .
                '<span class="text-sm text-muted-foreground">%1$s</span>%2$s</div>',
            esc_html($per_page_label),
            $group_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    }
}

$footer_markup = '';

if ($range_markup !== '' || $per_page_markup !== '') {
    $footer_markup = sprintf(
        '<div data-slot="pagination-compact-footer" class="flex items-center justify-between gap-5">%1$s%2$s</div>',
        $range_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $per_page_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$wrapper_attributes = $attributes;
$wrapper_attributes['class'] = trim(
    'flex flex-col gap-4' . ($class_name !== '' ? ' ' . $class_name : ''),
);

$wrapper_attributes['data-slot'] = 'pagination-compact';
$wrapper_attributes['aria-label'] = $aria_label;

$wrapper_attributes = hengegroup_theme_merge_data_attributes($wrapper_attributes, $data_attributes);

printf(
    '<nav%1$s>%2$s%3$s</nav>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $bar_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $footer_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
