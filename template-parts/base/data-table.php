<?php

declare(strict_types=1);

// shadcn/ui's Data Table is explicitly NOT a component in the usual sense -- their own docs open
// with "This is not a data-table component. It is a set of instructions for how to build your own
// using @tanstack/react-table." There is no fixed props API to mirror the way there is for
// Button/Card/Carousel: sorting, pagination, filtering, row selection and column visibility are
// all client-side TanStack Table state, wired up per-project in example code, not a shipped
// component (CLAUDE.md #1's "welche Props/Varianten/States gibt es" question has no single stable
// answer to check against here).
//
// Translated to this theme (no client-state framework, PHP renders from data the caller already
// queried/sorted/paginated -- e.g. via WP_Query/wpdb): sorting and pagination become real
// `<a href>` navigation links built with WordPress's own `add_query_arg()`, exactly
// calendar.php's own month-navigation technique (CLAUDE.md #1) -- a full page reload, genuinely
// functional with zero JS, not a fake/inert control. This component does NOT read `$_GET` itself
// to figure out the current sort/page -- like calendar.php, it's a pure config-driven renderer; a
// caller relying on the reload path owns reading `orderby`/`order`/the pagination query var back
// out of the incoming request and re-querying/re-sorting/re-paginating `rows` before the next
// render (same responsibility split as calendar.php's `month`/`year` and WordPress's own `paged`).
//
// Unlike calendar.php (which bakes its own day-grid <table> markup because a calendar grid is a
// genuinely special shape), a data table's body is exactly the generic tabular markup
// template-parts/base/table/*.php already provides -- so this component NESTS that whole family
// (table.php/table-header.php/table-body.php/table-row.php/table-head.php/table-cell.php,
// table-caption.php) instead of duplicating <table>/<thead>/<tbody>/<tr>/<td> markup a second time.
// It stays a single file (no subfolder) because, unlike table.php's own
// deliberately free-form slot composition, "take columns + rows data and render a sortable,
// paginated table" is one coherent, data-driven operation -- same reasoning as accordion.php's
// `items` array collapsing shadcn's separate Accordion/AccordionItem/AccordionTrigger/
// AccordionContent into one file instead of a subfolder.
//
// Sorting is real, working ARIA too, not a documented gap: because PHP already knows the current
// `orderby`/`order` at render time (the caller passes it back in, see above), the active sort
// column's <th> gets a correct `aria-sort="ascending"|"descending"` for free -- no JS needed to
// compute or keep it in sync, unlike e.g. toggle.php's role-transformation gap.
//
// JS enhancement: assets/js/template-parts/base/data-table.js. Unlike calendar.php's month
// navigation (pure, client-computable date arithmetic -- calendar.js can regenerate a whole month
// grid from nothing), a sorted/paginated page of arbitrary caller-supplied `rows` requires
// re-querying the actual data source (WP_Query/wpdb/a REST endpoint) -- something this generic base
// component still cannot invent on a project's behalf, and data-table.js doesn't try to. What it
// does instead: the sort/pagination hrefs above already point at the exact URL a real click would
// navigate to (add_query_arg() on the current page) -- data-table.js fetches that same URL, parses
// the response HTML, and swaps in only the matching `[data-slot="data-table"]` subtree, reusing the
// real server-rendered re-query instead of reimplementing it client-side. No new PHP endpoint is
// needed; without JS, the same links remain genuinely functional full-page navigation, zero-JS
// fallback unchanged.
//
// Deliberately out of scope for v1 (each a separate, larger JS-only feature with no meaningful
// zero-JS story, same "not a variant of this one" reasoning as native-select.php's `multiple`/
// combobox.php's chips mode/dropdown-menu.php's submenus):
//   - Filtering/search toolbar -- shadcn's own demo binds a text Input to live client-side
//     TanStack Table filter state; the equivalent here is an ordinary caller-built <form
//     method="get"> (an input.php + submit button.php) around THIS component, wired to whatever
//     query logic the project actually needs -- not something this component can own, since it
//     never sees unfiltered data to filter, only the `rows` it's given
//   - Row selection (checkboxes + bulk actions) -- WordPress's own admin list tables solve this via
//     a real <form>-based bulk-action submit (checkbox `name="post[]"` + a bulk-action select), a
//     legitimate zero-JS pattern in its own right but a substantial separate feature, not added
//     speculatively without a concrete consumer
//   - Column visibility toggling -- inherently pure client-state (which columns are currently
//     shown), no zero-JS equivalent at all
//
// Supported config:
//   columns        array   required, ordered list of:
//     key              string   required. Looks up each row's cell value by this key (or, for the
//                                 "rich row" shape below, `cells[key]`)
//     label            string   required. Visible column header text
//     sortable         bool     default false. Renders the header as a real `<a href>` that toggles
//                                 asc/desc via `orderby_var`/`order_var` -- see the header comment
//                                 above for why this needs no JS at all
//     align            string   start (default) | center | end -- reflected as `data-align` on both
//                                 the <th> and every <td> in this column, for project CSS
//                                 (`[data-align="end"] { text-align: right; }`)
//   rows           array   required (may be empty -- renders `empty_text` instead). Each row is
//                          EITHER a flat `[column_key => cell_html]` map (the common case) OR, for
//                          per-row metadata, a "rich row" `{ cells: [column_key => cell_html],
//                          selected?, class?, attributes?, data_attributes? }` -- disambiguated by
//                          the presence of a `cells` key, same auto-detect idiom as
//                          native-select.php's flat-option-vs-optgroup `options` shape. Every cell
//                          value is pre-rendered HTML (plain escaped text or nested component
//                          markup, e.g. a badge.php/button.php call) -- caller's responsibility to
//                          escape/build, same convention as table-cell.php's own `content`; a
//                          missing key renders a deliberately blank cell
//   caption        string   optional. table-caption.php text
//   empty_text     string   shown as a single full-width row instead of `rows` when it's empty
//                          (default: translated "No results.", component-owned UI text, same i18n
//                          precedent as calendar.php's own nav labels)
//   orderby        string   the currently active sort column `key` (caller reads this back from
//                          the incoming request, see header comment above); empty means unsorted
//   order          string   asc (default) | desc -- the currently active sort direction
//   orderby_var    string   query var name the sort links write the column key to (default
//                          'orderby')
//   order_var      string   query var name the sort links write the direction to (default 'order')
//   pagination     array|null   optional:
//     current_page     int      required (clamped to >= 1)
//     total_pages      int      required (clamped to >= 1)
//     page_var         string   default 'paged' -- WordPress's own pagination query var convention
//   class / attributes / data_attributes   passthrough onto the outer
//                          <div data-slot="data-table"> wrapper

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
$orderby_var = trim((string) ($config['orderby_var'] ?? 'orderby'));
$order_var = trim((string) ($config['order_var'] ?? 'order'));
$pagination_config = is_array($config['pagination'] ?? null) ? $config['pagination'] : null;
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($empty_text === '') {
    $empty_text = esc_html__('No results.', 'base-theme');
}

if (!in_array($order, ['asc', 'desc'], true)) {
    $order = 'asc';
}

if ($orderby_var === '') {
    $orderby_var = 'orderby';
}

if ($order_var === '') {
    $order_var = 'order';
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

    $columns[] = [
        'key' => $key,
        'label' => $label,
        'sortable' => !empty($column_config['sortable']),
        'align' => $align,
    ];
}

if ($columns === []) {
    return;
}

// Header row: one buffered table-head.php call per column, wrapped in one table-row.php call.
$header_cells_markup = '';

foreach ($columns as $column) {
    $cell_attributes = [];
    $cell_data_attributes = [];

    if ($column['align'] !== 'start') {
        $cell_data_attributes['align'] = $column['align'];
    }

    if ($column['sortable']) {
        $is_active = $orderby !== '' && $orderby === $column['key'];
        $next_order = $is_active && $order === 'asc' ? 'desc' : 'asc';
        $href = esc_url(add_query_arg([$orderby_var => $column['key'], $order_var => $next_order]));

        if ($is_active) {
            $cell_attributes['aria-sort'] = $order === 'asc' ? 'ascending' : 'descending';
            $icon_name = $order === 'asc' ? 'chevron-up' : 'chevron-down';
        } else {
            $icon_name = 'chevrons-up-down';
        }

        $sort_icon = base_theme_render_icon(['name' => $icon_name, 'set' => 'lucide']);

        $header_content = sprintf(
            '<a href="%1$s" data-slot="data-table-sort">%2$s%3$s</a>',
            $href,
            esc_html($column['label']),
            $sort_icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    } else {
        $header_content = esc_html($column['label']);
    }

    ob_start();
    get_template_part('template-parts/base/table/table-head', null, [
        'config' => [
            'content' => $header_content,
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

// Body rows.
$body_rows_markup = '';

if ($rows_config === []) {
    $empty_cell_markup = '';

    ob_start();
    get_template_part('template-parts/base/table/table-cell', null, [
        'config' => [
            'content' => esc_html($empty_text),
            'attributes' => ['colspan' => (string) count($columns)],
            'data_attributes' => ['align' => 'center'],
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

        $cells_data = is_array($row_config['cells'] ?? null) ? $row_config['cells'] : $row_config;

        $row_cells_markup = '';

        foreach ($columns as $column) {
            $cell_content = (string) ($cells_data[$column['key']] ?? '');
            $cell_data_attributes = [];

            if ($column['align'] !== 'start') {
                $cell_data_attributes['align'] = $column['align'];
            }

            ob_start();
            get_template_part('template-parts/base/table/table-cell', null, [
                'config' => [
                    'content' => $cell_content,
                    'data_attributes' => $cell_data_attributes,
                ],
            ]);
            $row_cells_markup .= (string) ob_get_clean();
        }

        $row_has_metadata = array_key_exists('cells', $row_config);

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
                'data_attributes' =>
                    $row_has_metadata && is_array($row_config['data_attributes'] ?? null)
                        ? $row_config['data_attributes']
                        : [],
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

// Pagination footer: real <a href> navigation via add_query_arg(), same technique as calendar.php's
// month nav -- button.php's own `disabled` (removes href, sets aria-disabled) handles the bounds.
$pagination_markup = '';

if ($pagination_config !== null) {
    $current_page = max(1, (int) ($pagination_config['current_page'] ?? 1));
    $total_pages = max(1, (int) ($pagination_config['total_pages'] ?? 1));
    $page_var = trim((string) ($pagination_config['page_var'] ?? 'paged'));

    if ($page_var === '') {
        $page_var = 'paged';
    }

    $has_previous = $current_page > 1;
    $has_next = $current_page < $total_pages;

    $nav_button = static function (
        string $target_page,
        string $icon_name,
        string $aria_label,
        bool $disabled,
    ) use ($page_var): string {
        ob_start();
        get_template_part('template-parts/base/button', null, [
            'config' => [
                'href' => esc_url(add_query_arg($page_var, $target_page)),
                'variant' => 'outline',
                'size' => 'icon',
                'icon' => ['name' => $icon_name, 'set' => 'lucide'],
                'aria_label' => $aria_label,
                'disabled' => $disabled,
            ],
        ]);

        return (string) ob_get_clean();
    };

    ob_start();
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => 'muted',
            'data_slot' => 'data-table-pagination-label',
            'text' => sprintf(
                /* translators: 1: current page number, 2: total number of pages */
                esc_html__('Page %1$d of %2$d', 'base-theme'),
                $current_page,
                $total_pages,
            ),
        ],
    ]);
    $page_label_markup = (string) ob_get_clean();

    // <nav aria-label="Pagination"> landmark around the controls -- shadcn's own Pagination
    // component (the same UI pattern, just without a page-of-pages data source to drive it) renders
    // exactly this: <nav role="navigation" aria-label="pagination">. `role="navigation"` itself is
    // left implicit (native <nav> already carries it, no need to double up).
    $pagination_markup = sprintf(
        '<nav data-slot="data-table-pagination" aria-label="%1$s">%2$s%3$s%4$s%5$s%6$s</nav>',
        esc_attr__('Pagination', 'base-theme'),
        $nav_button('1', 'chevrons-left', esc_html__('First page', 'base-theme'), !$has_previous), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $nav_button(
            (string) max(1, $current_page - 1),
            'chevron-left',
            esc_html__('Previous page', 'base-theme'),
            !$has_previous,
        ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $page_label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $nav_button(
            (string) min($total_pages, $current_page + 1),
            'chevron-right',
            esc_html__('Next page', 'base-theme'),
            !$has_next,
        ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $nav_button(
            (string) $total_pages,
            'chevrons-right',
            esc_html__('Last page', 'base-theme'),
            !$has_next,
        ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

$element_attributes['data-slot'] = 'data-table';

foreach ($data_attributes as $name => $value) {
    $data_name = trim((string) $name);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $value;
}

printf(
    '<div%1$s>%2$s%3$s</div>',
    base_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $table_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $pagination_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
