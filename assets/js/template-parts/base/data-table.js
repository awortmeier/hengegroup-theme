// JS-required enhancement layer for template-parts/base/data-table.php (design request
// 2026-09-04, see that file's own ARCHITECTURE header comment for why this replaced the previous
// fetch-and-swap-a-server-rendered-page approach entirely): the PHP component now renders every
// row up front, and this module does ALL sorting/searching/filtering/paging purely by showing and
// hiding those already-rendered `<tr>` elements (`hidden` attribute) and reordering them in the
// DOM for sorting -- no fetch, no navigation, no new PHP endpoint. Without this module, every row
// stays visible/unsorted/unfiltered and the toolbar controls are inert -- see data-table.php's own
// header comment for why that degraded-without-JS state is an accepted trade-off here, not a bug.
//
// State per table lives entirely in the DOM (row `hidden` attributes, each control's own
// `data-state`/`aria-*`) plus a handful of small closures below (current page, active sort column/
// direction, active filter value) -- not one shared object. `updateView()` is the single function
// that reconciles "which rows currently match the search text + the active filter pill" against
// "which page is active" and applies the resulting hidden/visible split; every other handler below
// (search input, filter pill click, sort click, page nav click) only changes one piece of state
// and then calls it.
//
// Not self-registering: like every other template-parts/base/*.js module, `initDataTable()` is
// exported and called from assets/js/app.js's own `onDomReady()` sweep, not wired up here.

const SEARCH_INPUT_SELECTOR = '[data-slot="data-table-toolbar"] input[type="search"]';
const FILTER_OPTION_SELECTOR = '[data-slot="data-table-filter-option"]';
const COLUMN_TOGGLE_SELECTOR = '[data-slot="data-table-column-toggle"]';
const SORT_BUTTON_SELECTOR = '[data-slot="data-table-sort"]';

// Numeric-aware compare, same "number if both sides parse as one, otherwise locale string compare"
// rule the Claude-Design reference "Hengegroup" itself uses for its own client-sorted table --
// avoids "118 t" sorting before "42 t" as plain strings would. `sort_values` (data-table.php's own
// row-level override, see its header comment) is what usually makes both sides numeric; the
// auto-computed fallback (stripped cell text) still benefits here for naturally-numeric columns.
function compareSortValues(a, b) {
    const numberA = Number(a);
    const numberB = Number(b);
    const bothNumeric = a !== "" && b !== "" && !Number.isNaN(numberA) && !Number.isNaN(numberB);

    return bothNumeric ? numberA - numberB : a.localeCompare(b, "de");
}

// Sortable headers: `<button data-slot="data-table-sort" data-sort-key="...">`, `data-sort-key`
// holding the SAME sanitized key data-table.php used to build each row's `data-sort-<key>`
// attribute (see that file's `safe_key` note) -- plain string concatenation
// (`data-sort-${sortKey}`) is enough to find a row's value for this column, no dataset camelCasing
// needed on either side.
function setupSort(wrapper, rows, onSortChange) {
    const buttons = Array.from(wrapper.querySelectorAll(SORT_BUTTON_SELECTOR));

    if (buttons.length === 0) {
        return;
    }

    function setButtonState(button, state, direction) {
        button.dataset.state = state;

        const th = button.closest("th");

        if (th) {
            if (state === "active") {
                th.setAttribute("aria-sort", direction === "desc" ? "descending" : "ascending");
            } else {
                th.removeAttribute("aria-sort");
            }
        }

        const activeIcon = state === "active" ? direction : "none";
        button.querySelectorAll("[data-sort-icon]").forEach((icon) => {
            icon.hidden = icon.dataset.sortIcon !== activeIcon;
        });
    }

    buttons.forEach((button) => {
        const sortKey = button.dataset.sortKey ?? "";
        const attributeName = `data-sort-${sortKey}`;

        button.addEventListener("click", () => {
            const wasActive = button.dataset.state === "active";
            const nextDirection = wasActive && button.dataset.direction === "asc" ? "desc" : "asc";

            buttons.forEach((otherButton) => {
                if (otherButton !== button) {
                    setButtonState(otherButton, "inactive", "");
                }
            });

            button.dataset.direction = nextDirection;
            setButtonState(button, "active", nextDirection);

            rows.sort((rowA, rowB) => {
                const result = compareSortValues(
                    rowA.getAttribute(attributeName) ?? "",
                    rowB.getAttribute(attributeName) ?? ""
                );

                return nextDirection === "desc" ? -result : result;
            });

            const tbody = wrapper.querySelector(":scope table tbody");
            rows.forEach((row) => tbody.appendChild(row));

            onSortChange();
        });
    });
}

// Category filter pills: `[data-slot="data-table-filter-option"][data-filter-value]`, one of them
// (the reset "All" pill) carrying an empty `data-filter-value`. Returns a getter for the currently
// active value so updateView() (in setupDataTable() below) can read it without this module needing
// a shared mutable state object.
function setupFilter(wrapper, onFilterChange) {
    const options = Array.from(wrapper.querySelectorAll(FILTER_OPTION_SELECTOR));
    let activeValue = "";

    options.forEach((option) => {
        option.addEventListener("click", () => {
            activeValue = option.dataset.filterValue ?? "";

            options.forEach((otherOption) => {
                otherOption.dataset.state = otherOption === option ? "active" : "inactive";
            });

            onFilterChange();
        });
    });

    return () => activeValue;
}

// Column-visibility toggles: `[data-slot="data-table-column-toggle"][data-column="key"]` toggles
// `hidden` on every `[data-column="key"]` cell (both the `<th>` and every `<td>` in that column,
// see data-table.php's own header comment) -- purely visual, does not affect search/sort/paging.
function setupColumnToggles(wrapper) {
    const buttons = Array.from(wrapper.querySelectorAll(COLUMN_TOGGLE_SELECTOR));

    buttons.forEach((button) => {
        button.addEventListener("click", () => {
            const nextState = button.dataset.state === "inactive" ? "active" : "inactive";

            button.dataset.state = nextState;
            button.setAttribute("aria-pressed", nextState === "active" ? "true" : "false");

            const columnKey = button.dataset.column ?? "";
            wrapper.querySelectorAll(`[data-column="${CSS.escape(columnKey)}"]`).forEach((cell) => {
                cell.hidden = nextState === "inactive";
            });
        });
    });
}

// Manages the nested, unmodified pagination-compact.php bar (see data-table.php's own header
// comment on why this component is reused rather than a hand-rolled prev/next pair): reads its
// Previous/Next buttons via the `data-action="previous"|"next"` hook that component renders for
// exactly this purpose (see pagination-compact.php's own header comment), and its status text via
// the `data-slot="pagination-compact-status"` element it already renders. Returns null when there
// is no pagination bar to manage (e.g. `per_page: null` on data-table.php).
function setupPagination(wrapper, perPage, onPageChange) {
    const bar = wrapper.querySelector('[data-slot="pagination-compact"]');

    if (!bar || !perPage) {
        return null;
    }

    const previousButton = bar.querySelector('[data-action="previous"]');
    const nextButton = bar.querySelector('[data-action="next"]');
    const status = bar.querySelector('[data-slot="pagination-compact-status"]');

    [previousButton, nextButton].forEach((button) => {
        if (button) {
            button.dataset.href = button.getAttribute("href") ?? "";
        }
    });

    function applyDisabledState(button, disabled) {
        if (!button) {
            return;
        }

        if (disabled) {
            button.setAttribute("aria-disabled", "true");
            button.removeAttribute("href");
        } else {
            button.removeAttribute("aria-disabled");

            if (button.dataset.href) {
                button.setAttribute("href", button.dataset.href);
            }
        }
    }

    let currentPage = 1;
    let totalPages = 1;

    // Captures the server-rendered "Page %1 of %2" copy as a re-usable template on first run
    // (translated server-side by data-table.php via pagination-compact.php, this module must not
    // hardcode English) -- the two numbers are located generically (first/second run of digits) so
    // render() below can restring it on every page/filter change without re-fetching or
    // duplicating that translation.
    let statusTemplate = null;

    if (status) {
        const match = status.textContent.match(/^(\D*)(\d+)(\D+)(\d+)(\D*)$/);
        statusTemplate = match ? [match[1], match[3], match[5]] : null;
    }

    // The row count from the most recent getCurrentPage() call -- prev/next only ever change
    // WHICH page is current, never how many rows currently match search/filter, so they can just
    // re-render against whatever count updateView() last reported instead of needing it passed in.
    // updateView() always runs once during setup before any click is possible, so this initial 0
    // never actually reaches render() unrefreshed.
    let lastVisibleRowCount = 0;

    function render() {
        totalPages = Math.max(1, Math.ceil(lastVisibleRowCount / perPage));
        currentPage = Math.min(Math.max(1, currentPage), totalPages);

        if (status && statusTemplate) {
            status.textContent = `${statusTemplate[0]}${currentPage}${statusTemplate[1]}${totalPages}${statusTemplate[2]}`;
        }

        applyDisabledState(previousButton, currentPage <= 1);
        applyDisabledState(nextButton, currentPage >= totalPages);

        return currentPage;
    }

    // Prev/Next only change WHICH page is current -- they hand back off to updateView() (via
    // onPageChange) for the actual row hidden/visible re-slice instead of re-rendering the bar
    // itself here, same single-source-of-truth-for-the-visible-slice reasoning as sort/filter/
    // search below (setupDataTable() always drives row visibility, this module never does).
    previousButton?.addEventListener("click", (event) => {
        event.preventDefault();

        if (currentPage > 1) {
            currentPage -= 1;
            onPageChange();
        }
    });

    nextButton?.addEventListener("click", (event) => {
        event.preventDefault();

        if (currentPage < totalPages) {
            currentPage += 1;
            onPageChange();
        }
    });

    return {
        resetToFirstPage() {
            currentPage = 1;
        },
        getCurrentPage(visibleRowCount) {
            lastVisibleRowCount = visibleRowCount;

            return render();
        },
    };
}

function setupDataTable(wrapper) {
    const rows = Array.from(wrapper.querySelectorAll(":scope table tbody > tr"));

    if (rows.length === 0) {
        return;
    }

    const searchInput = wrapper.querySelector(SEARCH_INPUT_SELECTOR);
    const emptyState = wrapper.querySelector('[data-slot="data-table-empty-state"]');
    const perPage = parseInt(wrapper.dataset.perPage ?? "", 10) || 0;
    // updateView() is declared further down as a function statement (hoisted), so referencing it
    // here -- before its literal declaration -- is safe: nothing above actually CALLS it until
    // after setup finishes.
    const pagination = setupPagination(wrapper, perPage, () => updateView());
    const getActiveFilterValue = setupFilter(wrapper, () => {
        pagination?.resetToFirstPage();
        updateView();
    });

    setupColumnToggles(wrapper);

    function updateView() {
        const query = (searchInput?.value ?? "").trim().toLowerCase();
        const filterValue = getActiveFilterValue();

        const matches = rows.filter((row) => {
            const matchesSearch = query === "" || (row.dataset.search ?? "").includes(query);
            const matchesFilter = filterValue === "" || row.dataset.filter === filterValue;

            return matchesSearch && matchesFilter;
        });

        let visibleMatches = matches;

        if (pagination) {
            const currentPage = pagination.getCurrentPage(matches.length);
            const start = (currentPage - 1) * perPage;
            visibleMatches = matches.slice(start, start + perPage);
        }

        const visibleSet = new Set(visibleMatches);
        rows.forEach((row) => {
            row.hidden = !visibleSet.has(row);
            row.removeAttribute("data-last-visible");
        });

        // The true last `<tr>` (`:last-child`, table-body.php's own default border-drop hook) is
        // frequently NOT the one actually rendered last once pagination hides everything after it
        // -- mark whichever row IS currently the last one on screen instead, see table-body.php's
        // own header comment for the matching CSS hook.
        if (visibleMatches.length > 0) {
            visibleMatches[visibleMatches.length - 1].setAttribute("data-last-visible", "true");
        }

        if (emptyState) {
            emptyState.hidden = matches.length > 0;
        }
    }

    setupSort(wrapper, rows, () => {
        pagination?.resetToFirstPage();
        updateView();
    });

    searchInput?.addEventListener("input", () => {
        pagination?.resetToFirstPage();
        updateView();
    });

    updateView();
    wrapper.setAttribute("data-js", "data-table");
}

export function initDataTable() {
    const wrappers = Array.from(document.querySelectorAll('[data-slot="data-table"]'));

    wrappers.forEach((wrapper) => {
        setupDataTable(wrapper);
    });
}
