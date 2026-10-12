<?php

declare(strict_types=1);

// Data table built from the table/* family. All rows render server-side; search, category filter,
// sorting, column toggles and paging run client-side (data-table.js) by hiding/reordering <tr>s.
// Without JS all rows are visible and the toolbar is inert.
//
// Config:
//   columns   array   required, each:
//     key          string   cell lookup key (required)
//     label        string   header text (required)
//     sortable     bool     header becomes a sort button
//     align        string   start (default) | center | end
//     toggleable   bool     adds a column-visibility toggle
//   rows      array   full dataset. Either [key => cell_html] or a rich row
//                     { cells, selected?, class?, attributes?, data_attributes?, search?, sort_values? }.
//                     Cells are pre-rendered HTML (caller escapes).
//     search        string   overrides the search text (default: stripped cell text)
//     sort_values   array    [key => value] overrides the sort key, e.g. numeric sorting
//   caption              string
//   empty_text           string   also the client-side "no results" text (default "No results.")
//   orderby / order      string   initial sort state of the pre-sorted rows (asc|desc)
//   per_page             int|null default 10; 0/null disables paging
//   search               bool     default true
//   search_placeholder   string
//   filter_column        string   column key whose distinct values become filter pills
//   filter_all_label     string   default "All"
//   class / attributes / data_attributes   on the outer wrapper

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$columns_config = is_array($config['columns'] ?? null) ? $config['columns'] : [];
$rows_config = is_array($config['rows'] ?? null) ? $config['rows'] : [];
$caption = trim((string) ($config['caption'] ?? ''));
$empty_text = trim((string) ($config['empty_text'] ?? ''));
$orderby = trim((string) ($config['orderby'] ?? ''));
$order = trim((string) ($config['order'] ?? 'asc'));
$per_page =
    array_key_exists('per_page', $config) && $config['per_page'] !== null
        ? max(0, (int) $config['per_page'])
        : 10;
$search_enabled = !array_key_exists('search', $config) || !empty($config['search']);
$search_placeholder = trim((string) ($config['search_placeholder'] ?? ''));
$filter_column = trim((string) ($config['filter_column'] ?? ''));
$filter_all_label = trim((string) ($config['filter_all_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($empty_text === '') {
    $empty_text = esc_html__('No results.', 'hengegroup-theme');
}

if (!in_array($order, ['asc', 'desc'], true)) {
    $order = 'asc';
}

if ($search_placeholder === '') {
    $search_placeholder = __('Search...', 'hengegroup-theme');
}

if ($filter_all_label === '') {
    $filter_all_label = __('All', 'hengegroup-theme');
}

$allowed_aligns = ['start', 'center', 'end'];

$columns = [];

foreach ($columns_config as $column_config) {
    if (!is_array($column_config)) {
        continue;
    }

    $key = trim((string) ($column_config['key'] ?? ''));
    $label = trim((string) ($column_config['label'] ?? ''));

    if ($key === '' || $label === '') {
        continue;
    }

    $align = trim((string) ($column_config['align'] ?? 'start'));

    if (!in_array($align, $allowed_aligns, true)) {
        $align = 'start';
    }

    // Attribute-name-safe key for data-sort-<key>; falls back to the position so keys can't collide.
    $safe_key = preg_replace('/[^a-z0-9-]/', '', strtolower($key));

    if ($safe_key === '') {
        $safe_key = 'col' . count($columns);
    }

    $columns[] = [
        'key' => $key,
        'safe_key' => $safe_key,
        'label' => $label,
        'sortable' => !empty($column_config['sortable']),
        'align' => $align,
        'toggleable' => !empty($column_config['toggleable']),
    ];
}

if ($columns === []) {
    return;
}

$has_filter_column =
    $filter_column !== '' && in_array($filter_column, array_column($columns, 'key'), true);

$header_cells_markup = '';

foreach ($columns as $column) {
    $cell_attributes = [];
    $cell_data_attributes = [];

    if ($column['toggleable']) {
        $cell_data_attributes['column'] = $column['key'];
    }

    if ($column['sortable']) {
        $is_active = $orderby !== '' && $orderby === $column['key'];
        $direction = $is_active ? $order : '';

        if ($is_active) {
            $cell_attributes['aria-sort'] = $order === 'asc' ? 'ascending' : 'descending';
        }

        $sort_icons = sprintf(
            '<span data-sort-icon="asc"%1$s>%2$s</span><span data-sort-icon="desc"%3$s>%4$s</span><span data-sort-icon="none"%5$s>%6$s</span>',
            $direction === 'asc' ? '' : ' hidden',
            hengegroup_theme_render_icon([
                'name' => 'chevron-up',
                'set' => 'lucide',
                'class' => 'size-3.5',
            ]), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $direction === 'desc' ? '' : ' hidden',
            hengegroup_theme_render_icon([
                'name' => 'chevron-down',
                'set' => 'lucide',
                'class' => 'size-3.5',
            ]), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $direction === '' ? '' : ' hidden',
            hengegroup_theme_render_icon([
                'name' => 'chevrons-up-down',
                'set' => 'lucide',
                'class' => 'size-3.5 opacity-35',
            ]), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );

        $header_content = sprintf(
            '<button type="button" data-slot="data-table-sort" data-sort-key="%1$s" data-state="%2$s" class="inline-flex items-center gap-1.5 text-muted-foreground hover:text-henge-green data-[state=active]:text-henge-green">%3$s%4$s</button>',
            // Must match the row-level data-sort-<safe_key> that data-table.js looks up.
            esc_attr($column['safe_key']),
            $is_active ? 'active' : 'inactive',
            esc_html($column['label']),
            $sort_icons, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    } else {
        $header_content = esc_html($column['label']);
    }

    ob_start();
    get_template_part('template-parts/base/table/table-head', null, [
        'config' => [
            'content' => $header_content,
            'align' => $column['align'],
            'attributes' => $cell_attributes,
            'data_attributes' => $cell_data_attributes,
        ],
    ]);
    $header_cells_markup .= (string) ob_get_clean();
}

ob_start();
get_template_part('template-parts/base/table/table-row', null, [
    'config' => ['content' => $header_cells_markup],
]);
$header_row_markup = (string) ob_get_clean();

ob_start();
get_template_part('template-parts/base/table/table-header', null, [
    'config' => ['content' => $header_row_markup],
]);
$header_markup = (string) ob_get_clean();

$body_rows_markup = '';

if ($rows_config === []) {
    ob_start();
    get_template_part('template-parts/base/table/table-cell', null, [
        'config' => [
            'content' => esc_html($empty_text),
            'align' => 'center',
            'class' => 'text-muted-foreground',
            'attributes' => ['colspan' => (string) count($columns)],
        ],
    ]);
    $empty_cell_markup = (string) ob_get_clean();

    ob_start();
    get_template_part('template-parts/base/table/table-row', null, [
        'config' => ['content' => $empty_cell_markup],
    ]);
    $body_rows_markup = (string) ob_get_clean();
} else {
    foreach ($rows_config as $row_config) {
        if (!is_array($row_config)) {
            continue;
        }

        $row_has_metadata = array_key_exists('cells', $row_config);
        $cells_data =
            $row_has_metadata && is_array($row_config['cells'])
                ? $row_config['cells']
                : $row_config;
        $sort_value_overrides =
            $row_has_metadata && is_array($row_config['sort_values'] ?? null)
                ? $row_config['sort_values']
                : [];

        $row_cells_markup = '';
        $search_parts = [];
        $filter_value = '';
        $row_data_attributes = [];

        foreach ($columns as $column) {
            $cell_content = (string) ($cells_data[$column['key']] ?? '');
            $plain_text = trim(wp_strip_all_tags($cell_content));
            $search_parts[] = $plain_text;

            if ($has_filter_column && $column['key'] === $filter_column) {
                $filter_value = $plain_text;
            }

            if ($column['sortable']) {
                $sort_value = $sort_value_overrides[$column['key']] ?? $plain_text;
                $row_data_attributes['sort-' . $column['safe_key']] = (string) $sort_value;
            }

            $cell_data_attributes = $column['toggleable'] ? ['column' => $column['key']] : [];

            ob_start();
            get_template_part('template-parts/base/table/table-cell', null, [
                'config' => [
                    'content' => $cell_content,
                    'align' => $column['align'],
                    'data_attributes' => $cell_data_attributes,
                ],
            ]);
            $row_cells_markup .= (string) ob_get_clean();
        }

        $row_data_attributes['search'] = mb_strtolower(
            $row_has_metadata && isset($row_config['search'])
                ? trim((string) $row_config['search'])
                : trim(implode(' ', $search_parts)),
        );

        if ($has_filter_column) {
            $row_data_attributes['filter'] = $filter_value;
        }

        if ($row_has_metadata && is_array($row_config['data_attributes'] ?? null)) {
            $row_data_attributes = array_merge(
                $row_data_attributes,
                $row_config['data_attributes'],
            );
        }

        ob_start();
        get_template_part('template-parts/base/table/table-row', null, [
            'config' => [
                'content' => $row_cells_markup,
                'selected' => $row_has_metadata && !empty($row_config['selected']),
                'class' => $row_has_metadata ? trim((string) ($row_config['class'] ?? '')) : '',
                'attributes' =>
                    $row_has_metadata && is_array($row_config['attributes'] ?? null)
                        ? $row_config['attributes']
                        : [],
                'data_attributes' => $row_data_attributes,
            ],
        ]);
        $body_rows_markup .= (string) ob_get_clean();
    }
}

ob_start();
get_template_part('template-parts/base/table/table-body', null, [
    'config' => ['content' => $body_rows_markup],
]);
$body_markup = (string) ob_get_clean();

$caption_markup = '';

if ($caption !== '') {
    ob_start();
    get_template_part('template-parts/base/table/table-caption', null, [
        'config' => ['text' => $caption],
    ]);
    $caption_markup = (string) ob_get_clean();
}

ob_start();
get_template_part('template-parts/base/table/table', null, [
    'config' => ['content' => $caption_markup . $header_markup . $body_markup],
]);
$table_markup = (string) ob_get_clean();

$row_count = count($rows_config);
$show_toggles =
    $row_count > 0 &&
    array_filter($columns, static fn(array $column): bool => $column['toggleable']) !== [];
$show_filter = $row_count > 0 && $has_filter_column;
$show_search = $row_count > 0 && $search_enabled;
$toolbar_markup = '';

if ($show_search || $show_filter || $show_toggles) {
    $search_markup = '';

    if ($show_search) {
        ob_start();
        get_template_part('template-parts/base/input-group/input-group-addon', null, [
            'config' => [
                'content' => hengegroup_theme_render_icon([
                    'name' => 'search',
                    'set' => 'lucide',
                    'class' => 'size-4 text-muted-foreground',
                ]),
            ],
        ]);
        get_template_part('template-parts/base/input', null, [
            'config' => [
                'type' => 'search',
                'placeholder' => $search_placeholder,
                'data_slot' => 'input-group-control',
            ],
        ]);
        $search_control_markup = (string) ob_get_clean();

        ob_start();
        get_template_part('template-parts/base/input-group/input-group', null, [
            'config' => [
                'content' => $search_control_markup,
                'class' => 'w-full min-w-0 sm:max-w-xs',
            ],
        ]);
        $search_markup = (string) ob_get_clean();
    }

    $filter_markup = '';

    if ($show_filter) {
        $filter_values = [];

        foreach ($rows_config as $row_config) {
            $cells_data = is_array($row_config['cells'] ?? null)
                ? $row_config['cells']
                : $row_config;
            $value = trim(wp_strip_all_tags((string) ($cells_data[$filter_column] ?? '')));

            if ($value !== '' && !in_array($value, $filter_values, true)) {
                $filter_values[] = $value;
            }
        }

        if ($filter_values !== []) {
            $pill_class =
                'rounded-md px-3 py-1.5 text-sm font-medium text-muted-foreground transition-colors ' .
                'hover:text-foreground data-[state=active]:bg-card data-[state=active]:text-foreground ' .
                'data-[state=active]:shadow-xs';

            $pills_markup = sprintf(
                '<button type="button" data-slot="data-table-filter-option" data-filter-value="" data-state="active" class="%1$s">%2$s</button>',
                esc_attr($pill_class),
                esc_html($filter_all_label),
            );

            foreach ($filter_values as $value) {
                $pills_markup .= sprintf(
                    '<button type="button" data-slot="data-table-filter-option" data-filter-value="%1$s" data-state="inactive" class="%2$s">%3$s</button>',
                    esc_attr($value),
                    esc_attr($pill_class),
                    esc_html($value),
                );
            }

            $filter_markup = sprintf(
                '<div data-slot="data-table-filter" class="inline-flex flex-wrap items-center gap-0.5 rounded-lg bg-muted p-1">%s</div>',
                $pills_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            );
        }
    }

    $toggles_markup = '';

    if ($show_toggles) {
        $toggle_pill_class =
            'inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium ' .
            'transition-colors border-border text-muted-foreground hover:text-foreground ' .
            'data-[state=active]:border-henge-green/30 data-[state=active]:bg-henge-green/5 ' .
            'data-[state=active]:text-henge-green';

        $toggle_buttons_markup = '';

        foreach ($columns as $column) {
            if (!$column['toggleable']) {
                continue;
            }

            $toggle_buttons_markup .= sprintf(
                '<button type="button" data-slot="data-table-column-toggle" data-column="%1$s" data-state="active" aria-pressed="true" class="%2$s">%3$s</button>',
                esc_attr($column['key']),
                esc_attr($toggle_pill_class),
                esc_html($column['label']),
            );
        }

        ob_start();
        get_template_part('template-parts/base/typography', null, [
            'config' => [
                'variant' => 'body-xs',
                'color' => 'neutral',
                'text' => __('Columns', 'hengegroup-theme'),
            ],
        ]);
        $toggles_label_markup = (string) ob_get_clean();

        $toggles_markup = sprintf(
            '<div data-slot="data-table-columns" class="flex flex-wrap items-center gap-2 sm:ml-auto">%1$s%2$s</div>',
            $toggles_label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $toggle_buttons_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    }

    $toolbar_markup = sprintf(
        '<div data-slot="data-table-toolbar" class="flex flex-wrap items-center gap-3">%1$s%2$s%3$s</div>',
        $search_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $filter_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $toggles_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

// Shown by data-table.js when search/filter leave no row visible.
$empty_state_markup = '';

if ($row_count > 0) {
    $empty_state_markup = sprintf(
        '<p data-slot="data-table-empty-state" class="py-10 text-center text-sm text-muted-foreground" hidden>%s</p>',
        esc_html($empty_text),
    );
}

$pagination_markup = '';

if ($per_page > 0 && $row_count > 0) {
    ob_start();
    get_template_part('template-parts/base/pagination/pagination-compact', null, [
        'config' => [
            'current_page' => 1,
            'total_pages' => max(1, (int) ceil($row_count / $per_page)),
            'aria_label' => __('Data table pagination', 'hengegroup-theme'),
        ],
    ]);
    $pagination_markup = (string) ob_get_clean();
}

$element_attributes = $attributes;
$element_attributes['class'] = trim(
    'flex flex-col gap-4' . ($class_name !== '' ? ' ' . $class_name : ''),
);
$element_attributes['data-slot'] = 'data-table';

if ($per_page > 0) {
    $element_attributes['data-per-page'] = (string) $per_page;
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

printf(
    '<div%1$s>%2$s%3$s%4$s%5$s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $toolbar_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $table_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $empty_state_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $pagination_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
