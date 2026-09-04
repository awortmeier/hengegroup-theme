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
// Unlike calendar.php (which bakes its own day-grid <table> markup because a calendar grid is a
// genuinely special shape), a data table's body is exactly the generic tabular markup
// template-parts/base/table/*.php already provides -- so this component NESTS that whole family
// (table.php/table-header.php/table-body.php/table-row.php/table-head.php/table-cell.php,
// table-caption.php) instead of duplicating <table>/<thead>/<tbody>/<tr>/<td> markup a second time.
// It stays a single file (no subfolder) because "take columns + rows data and render a sortable,
// filterable, paginated table" is one coherent, data-driven operation -- same reasoning as
// accordion.php's `items` array collapsing shadcn's separate Accordion/AccordionItem/
// AccordionTrigger/AccordionContent into one file instead of a subfolder.
//
// ARCHITECTURE (design request 2026-09-04, supersedes this file's original v1 shape): `rows` is now
// the caller's COMPLETE dataset, rendered in full on the server -- sorting, search, the category
// filter and pagination are all real client-side JS state
// (assets/js/template-parts/base/data-table.js), not `add_query_arg()` reload links. This is a
// deliberate reversal of v1's own "genuinely functional with zero JS, not a fake/inert control"
// stance (see git history) -- CLAUDE.md's Kernhaltung has no categorical preference for zero-JS
// solutions, UX/DX decide per case, and the caller explicitly asked for this component to load once
// and page/sort/filter entirely client-side instead of round-tripping the server for every
// interaction. Concretely, this means:
//   - `orderby`/`order` are now only the INITIAL sort state for the first render (which column/
//     direction the caller's own pre-sorted `rows` are already in) -- clicking a sortable header
//     re-sorts the already-rendered `<tr>` elements in place via data-table.js, no navigation, no
//     re-query.
//   - There is no more `pagination` config / real prev/next/first/last `<a href>` footer. Paging is
//     `per_page`-driven and rendered via template-parts/base/pagination/pagination-compact.php
//     (design request: reuse the existing Pagination family instead of hand-rolling a third
//     prev/next bar) -- see the Phase 2 note below for how that nested, unmodified component's
//     Previous/Next buttons get repurposed for client paging.
//   - A new toolbar (`data-slot="data-table-toolbar"`) adds free-text search, an optional
//     single-column category filter and optional per-column visibility toggles -- the reference's
//     own toolbar row, previously the explicitly out-of-scope-for-v1 half of this component.
// Without JS: every row renders, unpaginated and unfiltered (still fully readable/scrollable, not
// broken), the toolbar controls are present but inert (a search input that does nothing when typed
// into, filter/column-toggle buttons that do nothing when clicked), and the pagination-compact bar
// always shows "Page 1 of 1" with both Previous/Next disabled (there is only ever one server-
// rendered page now, see below) -- an accepted, deliberate regression from v1's own genuinely-
// functional-without-JS pagination/sorting, not an oversight; there is no meaningful zero-JS
// re-query story left once ALL rows already sit in the DOM (a "Page 2" link would just reload the
// exact same markup).
//
// JS enhancement: assets/js/template-parts/base/data-table.js. Reads every row's `data-search`/
// `data-filter`/`data-sort-<column_key>` attributes (computed server-side below, see the `search`/
// `sort_values` row-level overrides in the config doc) to search/sort/filter/paginate purely by
// showing/hiding already-rendered `<tr>` elements (native `hidden` attribute, same "hidden as
// default, JS toggles visibility" idiom CLAUDE.md Regel 1 already sanctions) and reordering them
// in the DOM for sorting -- no fetch, no new PHP endpoint, nothing to re-query.
//
// Deliberately still out of scope (each a separate, larger feature with no concrete consumer yet,
// same "not a variant of this one" reasoning as native-select.php's `multiple`/combobox.php's chips
// mode/dropdown-menu.php's submenus):
//   - Row selection (checkboxes + bulk actions) -- WordPress's own admin list tables solve this via
//     a real <form>-based bulk-action submit (checkbox `name="post[]"` + a bulk-action select), a
//     substantial separate feature, not added speculatively without a concrete consumer.
//   - Multi-column/combined filtering -- `filter_column` is intentionally a single column (matching
//     the Claude-Design reference "Hengegroup"'s own one-dimension "Kategorie" filter); a caller
//     needing more can still filter `rows` itself before calling this component (same "the caller
//     owns re-querying" split the old server-driven design used, just applied before render instead
//     of on every navigation).
//
// Moved into template-parts/base/table/ (previously template-parts/base/data-table.php) alongside
// the table/* family it nests -- shadcn's own Data Table docs open by calling it "not a data-table
// component" but a pattern built ON TOP OF Table (see above), so it belongs in the same folder as
// the primitives it composes rather than getting a folder of its own; unlike table.php's own
// siblings it stays a single file with no `table-` prefix (Rule 4 only mandates the shared folder
// once a component is more than one file, not a shared filename prefix for every file inside it).
// get_template_part() callers now need the doubled 'template-parts/base/table/data-table' path,
// same doubled-path convention as every other multi-file component folder.
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind on the strength of the Claude-Design reference
// "Hengegroup" (same `.dc.html` reference workflow as table.php's own entry, see
// docs/entscheidungen.md for this component's entry). The table itself needs no styling of its own
// here -- it already comes from the nested, already-Phase-2-styled table/* family (table.php's own
// card look, table-head.php's sortable-header-ready uppercase treatment, table-row.php's
// henge-green `selected` tint). What this file adds on top:
//   - the sortable header `<button data-slot="data-table-sort">` (a `<button>`, not an `<a>` -- see
//     the ARCHITECTURE note above: there is no longer a URL a click actually navigates to, a
//     `<button>` communicates that honestly instead of implying navigation that never happens):
//     `inline-flex items-center gap-1.5` to lay out label + chevron, a static
//     `data-[state=active]:text-henge-green` class alongside a plain `text-muted-foreground
//     hover:text-henge-green` base -- data-table.js only ever toggles this button's `data-state`
//     attribute, the brand-color swap itself is a plain Tailwind variant already baked into the
//     class string (same `data-[state=selected]:bg-henge-green/5` idiom table-row.php's own header
//     comment documents), matching the reference's own active-column color swap
//     (`color: active ? accent : muted`). The sort indicator renders all three possible icons
//     (ascending/descending/unsorted) up front, two of them `hidden` -- data-table.js swaps which
//     one is `hidden` instead of trying to swap SVG contents (there is no CSS way to morph one
//     glyph into another); the unsorted glyph gets `opacity-35`, the nearest real Tailwind opacity
//     step to the reference's own inactive-arrow `opacity:0.35`.
//   - the toolbar (`data-slot="data-table-toolbar"`): search via input.php + input-group.php's own
//     documented leading-icon-addon composition (see input-group.php's header comment, reused
//     verbatim, not reinvented); the category filter and column-visibility toggles are hand-built
//     (no existing base component covers a segmented single-select pill row or a toggleable-chip
//     row) using the same `data-[state=active]:` static-class-plus-JS-toggles-the-attribute idiom
//     as the sort button above -- kept out of button.php's own variant vocabulary since neither
//     shape is a general-purpose button variant, just this toolbar's own look.
//   - pagination: template-parts/base/pagination/pagination-compact.php, unmodified, nested as-is
//     (design request: reuse Pagination instead of a third hand-rolled prev/next bar) -- its
//     Previous/Next buttons already carry a `data-action="previous"|"next"` hook (added to that
//     file for this exact purpose, see its own header comment) that data-table.js uses to manage
//     `href`/`aria-disabled` client-side once the true page count is known up front.
//   - the outer `<div data-slot="data-table">` wrapper: `flex flex-col gap-4` to space the
//     toolbar/table card/pagination bar apart, matching the reference's own vertical rhythm.
//
// Supported config:
//   columns        array   required, ordered list of:
//     key              string   required. Looks up each row's cell value by this key (or, for the
//                                 "rich row" shape below, `cells[key]`)
//     label            string   required. Visible column header text
//     sortable         bool     default false. Renders the header as a `<button>` that re-sorts the
//                                 already-rendered rows client-side (see ARCHITECTURE note above)
//     align            string   start (default) | center | end -- passed straight through as
//                                 table-head.php's/table-cell.php's own `align` config on both the
//                                 <th> and every <td> in this column
//     toggleable       bool     default false. Adds a column-visibility toggle button to the
//                                 toolbar for this column (see `data-column` below)
//   rows           array   required (may be empty -- renders `empty_text` instead). The FULL
//                          dataset (see ARCHITECTURE note above), not just one page. Each row is
//                          EITHER a flat `[column_key => cell_html]` map (the common case) OR, for
//                          per-row metadata, a "rich row" `{ cells: [column_key => cell_html],
//                          selected?, class?, attributes?, data_attributes?, search?, sort_values?
//                          }` -- disambiguated by the presence of a `cells` key, same auto-detect
//                          idiom as native-select.php's flat-option-vs-optgroup `options` shape.
//                          Every cell value is pre-rendered HTML (plain escaped text or nested
//                          component markup, e.g. a badge.php/button.php call) -- caller's
//                          responsibility to escape/build, same convention as table-cell.php's own
//                          `content`; a missing key renders a deliberately blank cell.
//     search           string   optional (rich row only). Overrides the auto-computed search
//                                haystack for this row (default: every cell's HTML with tags
//                                stripped, lowercased and joined by a space via
//                                `wp_strip_all_tags()`) -- set this when a cell's visible text
//                                doesn't reflect what should be searchable (e.g. an icon-only cell)
//     sort_values      array    optional (rich row only), `[column_key => string|int|float]`.
//                                Overrides the auto-computed sort key for one or more SORTABLE
//                                columns (default: that column's cell HTML with tags stripped) --
//                                set this when the visible cell text sorts wrong as a plain string
//                                (e.g. a "12 t" cell needs `sort_values: ['bestand' => 12]` to sort
//                                numerically instead of lexicographically)
//   caption        string   optional. table-caption.php text
//   empty_text     string   shown as a single full-width row instead of `rows` when it's empty, AND
//                          reused by data-table.js as the client-side "no results" message once
//                          search/filter hide every row (default: translated "No results.",
//                          component-owned UI text, same i18n precedent as calendar.php's own nav
//                          labels)
//   orderby        string   the column `key` `rows` are already sorted by, for the initial render's
//                          `aria-sort`/active-icon state only (see ARCHITECTURE note above); empty
//                          means unsorted
//   order          string   asc (default) | desc -- the initial sort direction, paired with
//                          `orderby`
//   per_page       int|null   default 10. Client-side page size; `null`/`0` disables pagination
//                          entirely (all rows shown, no pagination-compact.php bar rendered) --
//                          search/sort/filter still apply
//   search         bool    default true. Shows/hides the toolbar's search input
//   search_placeholder   string   default: translated "Search..."
//   filter_column  string|null   optional. A column `key` whose distinct cell values (HTML tags
//                          stripped) become a row of category-filter pills in the toolbar, plus an
//                          "All" pill that clears the filter. Omit/empty for no category filter.
//   filter_all_label   string   default: translated "All" -- the reset pill's label
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

    // Attribute-name-safe form of `key`, for `data-sort-<key>` -- `key` itself stays free-form
    // (already used as-is in labels/lookups), only the derived attribute NAME needs sanitizing,
    // not the value. Falls back to the column's ordinal position if `key` has no safe characters
    // at all (e.g. a purely non-ASCII key), so two such columns can't collide on an empty
    // `data-sort-` attribute name.
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

// Header row: one buffered table-head.php call per column, wrapped in one table-row.php call.
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
            // `safe_key`, not `key` -- must match the row-level `data-sort-<safe_key>` attribute
            // suffix built below 1:1 so data-table.js's plain string-concat lookup
            // (`data-sort-${sortKey}`) finds it, see that file's own header comment.
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

// Body rows.
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

// Toolbar: search + category filter + column-visibility toggles. Only rendered when at least one
// is actually enabled/configured, same "no empty wrapper" convention as the pagination footer below.
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

// "No results after filtering" message -- hidden by default, shown by data-table.js once
// search/filter leave zero rows visible (see ARCHITECTURE note above; the `rows_config === []`
// case above already covers "no rows at all" server-side, this covers "no rows MATCH" client-side).
$empty_state_markup = '';

if ($row_count > 0) {
    $empty_state_markup = sprintf(
        '<p data-slot="data-table-empty-state" class="py-10 text-center text-sm text-muted-foreground" hidden>%s</p>',
        esc_html($empty_text),
    );
}

// Pagination: template-parts/base/pagination/pagination-compact.php, unmodified (design request:
// reuse Pagination instead of a hand-rolled bar, see file header). Rendered for the initial
// server-side state (page 1 of ceil(rows / per_page)); data-table.js manages `current_page`/
// `total_pages` client-side from there via that component's `data-action="previous"|"next"` hook
// (see pagination-compact.php's own header comment) plus its `data-slot="pagination-compact-status"`
// label -- no re-render, no new PHP endpoint.
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

foreach ($data_attributes as $name => $value) {
    $data_name = trim((string) $name);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $value;
}

printf(
    '<div%1$s>%2$s%3$s%4$s%5$s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $toolbar_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $table_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $empty_state_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $pagination_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
