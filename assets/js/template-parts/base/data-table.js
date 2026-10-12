// Client-side search, filter, sorting, column toggles and paging for data-table.php by hiding and
// reordering the rendered <tr>s. updateView() reconciles search/filter/page; every handler only
// changes its piece of state and calls it.

const SEARCH_INPUT_SELECTOR = '[data-slot="data-table-toolbar"] input[type="search"]';
const FILTER_OPTION_SELECTOR = '[data-slot="data-table-filter-option"]';
const COLUMN_TOGGLE_SELECTOR = '[data-slot="data-table-column-toggle"]';
const SORT_BUTTON_SELECTOR = '[data-slot="data-table-sort"]';

// Numeric when both sides parse as numbers ("118 t" after "42 t"), otherwise locale compare.
function compareSortValues(a, b) {
    const numberA = Number(a);
    const numberB = Number(b);
    const bothNumeric = a !== "" && b !== "" && !Number.isNaN(numberA) && !Number.isNaN(numberB);

    return bothNumeric ? numberA - numberB : a.localeCompare(b, "de");
}

// data-sort-key matches the row attribute data-sort-<key> 1:1.
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

// Returns a getter for the active filter value ("" = all).
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

// Hides [data-column] cells; does not affect search/sort/paging.
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

// Drives the nested pagination-compact.php via its data-action buttons and status element.
// null when there is no pagination bar.
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

    // Reuse the server-translated "Page X of Y" text; the two numbers are replaced generically.
    let statusTemplate = null;

    if (status) {
        const match = status.textContent.match(/^(\D*)(\d+)(\D+)(\d+)(\D*)$/);
        statusTemplate = match ? [match[1], match[3], match[5]] : null;
    }

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

        // :last-child is often a hidden row; mark the last visible one for table-body.php's border.
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
