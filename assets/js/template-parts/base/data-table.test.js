// Unit tests for initDataTable() (see data-table.js's own header comment: it purely
// searches/sorts/filters/paginates already-server-rendered `<tr>` elements client-side --
// no fetch, no navigation). Builds the wrapper/toolbar/table/pagination markup directly in jsdom,
// matching the shape template-parts/base/table/data-table.php actually renders, instead of
// importing the PHP template -- rendering real PHP output isn't available in this JS-only test
// runner, see docs/to-do.md Abschnitt 1 for where a WP-backed integration suite would close that
// gap.
import { beforeEach, describe, expect, it } from "vitest";
import { initDataTable } from "./data-table.js";

function click(el) {
    el.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true, button: 0 }));
}

function sortIcons(direction) {
    return `<span data-sort-icon="asc" ${direction === "asc" ? "" : "hidden"}>asc</span><span data-sort-icon="desc" ${direction === "desc" ? "" : "hidden"}>desc</span><span data-sort-icon="none" ${direction === "" ? "" : "hidden"}>none</span>`;
}

function row({ name, category, stock, selected = false }) {
    const selectedAttr = selected ? ' data-state="selected"' : "";

    return `
        <tr${selectedAttr} data-search="${name.toLowerCase()} ${category.toLowerCase()}" data-filter="${category}" data-sort-name="${name}" data-sort-stock="${stock}">
            <td data-column="category">${category}</td>
            <td>${name}</td>
            <td>${stock}</td>
        </tr>
    `;
}

function renderDataTable({ perPage = 2, rows: rowConfigs, includePagination = true } = {}) {
    const defaultRows = [
        { name: "Alpha", category: "A", stock: 5 },
        { name: "Bravo", category: "B", stock: 20 },
        { name: "Charlie", category: "A", stock: 1 },
        { name: "Delta", category: "B", stock: 12 },
    ];
    const rows = (rowConfigs ?? defaultRows).map(row).join("");
    const totalPages = Math.max(1, Math.ceil((rowConfigs ?? defaultRows).length / perPage));

    const pagination = includePagination
        ? `
        <nav data-slot="pagination-compact">
            <div data-slot="pagination-compact-bar">
                <a href="?paged=0" data-action="previous" aria-disabled="true">Previous</a>
                <span data-slot="pagination-compact-status">Page 1 of ${totalPages}</span>
                <a href="?paged=2" data-action="next">Next</a>
            </div>
        </nav>
    `
        : "";

    document.body.innerHTML = `
        <div data-slot="data-table" data-per-page="${perPage}">
            <div data-slot="data-table-toolbar">
                <input type="search" placeholder="Search..." />
                <div data-slot="data-table-filter">
                    <button type="button" data-slot="data-table-filter-option" data-filter-value="" data-state="active">All</button>
                    <button type="button" data-slot="data-table-filter-option" data-filter-value="A" data-state="inactive">A</button>
                    <button type="button" data-slot="data-table-filter-option" data-filter-value="B" data-state="inactive">B</button>
                </div>
                <div data-slot="data-table-columns">
                    <button type="button" data-slot="data-table-column-toggle" data-column="category" data-state="active" aria-pressed="true">Category</button>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th data-column="category">Category</th>
                        <th><button type="button" data-slot="data-table-sort" data-sort-key="name" data-state="inactive">Name${sortIcons("")}</button></th>
                        <th><button type="button" data-slot="data-table-sort" data-sort-key="stock" data-state="inactive">Stock${sortIcons("")}</button></th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
            <p data-slot="data-table-empty-state" hidden>No results.</p>
            ${pagination}
        </div>
    `;

    return {
        wrapper: document.querySelector('[data-slot="data-table"]'),
        rows: () => Array.from(document.querySelectorAll("tbody > tr")),
        searchInput: document.querySelector('input[type="search"]'),
    };
}

beforeEach(() => {
    document.body.innerHTML = "";
});

describe("initDataTable", () => {
    it("does nothing when the page has no data tables", () => {
        expect(() => initDataTable()).not.toThrow();
    });

    it("does nothing for a table with no body rows (the server-rendered empty state)", () => {
        document.body.innerHTML = `
            <div data-slot="data-table">
                <table><tbody></tbody></table>
            </div>
        `;

        expect(() => initDataTable()).not.toThrow();
    });

    it("shows only the first page of rows on init", () => {
        const { rows } = renderDataTable({ perPage: 2 });

        initDataTable();

        const visible = rows().filter((tr) => !tr.hidden);
        expect(visible.map((tr) => tr.dataset.search)).toEqual(["alpha a", "bravo b"]);
    });

    it("marks the last VISIBLE row (not the true last DOM row) for table-body.php's border hook", () => {
        const { rows } = renderDataTable({ perPage: 2 });
        initDataTable();

        const marked = rows().filter((tr) => tr.hasAttribute("data-last-visible"));
        expect(marked).toHaveLength(1);
        expect(marked[0].dataset.search).toBe("bravo b");
        expect(rows()[rows().length - 1].hasAttribute("data-last-visible")).toBe(false);

        click(document.querySelector('[data-action="next"]'));

        const markedAfterPaging = rows().filter((tr) => tr.hasAttribute("data-last-visible"));
        expect(markedAfterPaging).toHaveLength(1);
        expect(markedAfterPaging[0].dataset.search).toBe("delta b");
    });

    it("filters rows by search text and resets to page 1", () => {
        const { rows, searchInput } = renderDataTable({ perPage: 2 });
        initDataTable();

        searchInput.value = "charlie";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));

        const visible = rows().filter((tr) => !tr.hidden);
        expect(visible).toHaveLength(1);
        expect(visible[0].dataset.search).toBe("charlie a");
    });

    it("shows the empty-state message when the search matches nothing", () => {
        const { searchInput } = renderDataTable({ perPage: 2 });
        initDataTable();

        searchInput.value = "no such row";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));

        expect(document.querySelector('[data-slot="data-table-empty-state"]').hidden).toBe(false);
    });

    it("filters rows by category pill and marks it active", () => {
        const { rows } = renderDataTable({ perPage: 10 });
        initDataTable();

        click(document.querySelector('[data-filter-value="A"]'));

        const visible = rows().filter((tr) => !tr.hidden);
        expect(visible.map((tr) => tr.dataset.filter)).toEqual(["A", "A"]);
        expect(document.querySelector('[data-filter-value="A"]').dataset.state).toBe("active");
        expect(document.querySelector('[data-filter-value=""]').dataset.state).toBe("inactive");
    });

    it("clears the category filter via the All pill", () => {
        const { rows } = renderDataTable({ perPage: 10 });
        initDataTable();

        click(document.querySelector('[data-filter-value="A"]'));
        click(document.querySelector('[data-filter-value=""]'));

        expect(rows().filter((tr) => !tr.hidden)).toHaveLength(4);
    });

    it("sorts rows numerically by a sortable column and updates aria-sort/icon state", () => {
        const { rows } = renderDataTable({ perPage: 10 });
        initDataTable();

        const stockButton = Array.from(
            document.querySelectorAll('[data-slot="data-table-sort"]')
        ).find((button) => button.dataset.sortKey === "stock");
        click(stockButton);

        const order = rows().map(
            (tr) => tr.dataset.sortStock ?? tr.getAttribute("data-sort-stock")
        );
        expect(order).toEqual(["1", "5", "12", "20"]);
        expect(stockButton.dataset.state).toBe("active");
        expect(stockButton.closest("th").getAttribute("aria-sort")).toBe("ascending");
        expect(stockButton.querySelector('[data-sort-icon="asc"]').hidden).toBe(false);
        expect(stockButton.querySelector('[data-sort-icon="none"]').hidden).toBe(true);
    });

    it("reverses sort direction on a second click of the same column", () => {
        const { rows } = renderDataTable({ perPage: 10 });
        initDataTable();

        const stockButton = Array.from(
            document.querySelectorAll('[data-slot="data-table-sort"]')
        ).find((button) => button.dataset.sortKey === "stock");
        click(stockButton);
        click(stockButton);

        const order = rows().map((tr) => tr.getAttribute("data-sort-stock"));
        expect(order).toEqual(["20", "12", "5", "1"]);
        expect(stockButton.closest("th").getAttribute("aria-sort")).toBe("descending");
    });

    it("deactivates the previously active sort column when a different one is clicked", () => {
        renderDataTable({ perPage: 10 });
        initDataTable();

        const buttons = Array.from(document.querySelectorAll('[data-slot="data-table-sort"]'));
        const nameButton = buttons.find((button) => button.dataset.sortKey === "name");
        const stockButton = buttons.find((button) => button.dataset.sortKey === "stock");

        click(nameButton);
        click(stockButton);

        expect(nameButton.dataset.state).toBe("inactive");
        expect(nameButton.closest("th").hasAttribute("aria-sort")).toBe(false);
        expect(stockButton.dataset.state).toBe("active");
    });

    it("toggles column visibility on every cell sharing that column key", () => {
        renderDataTable({ perPage: 10 });
        initDataTable();

        const toggle = document.querySelector('[data-slot="data-table-column-toggle"]');
        click(toggle);

        expect(toggle.dataset.state).toBe("inactive");
        expect(toggle.getAttribute("aria-pressed")).toBe("false");
        document.querySelectorAll('[data-column="category"]').forEach((cell) => {
            expect(cell.hidden).toBe(true);
        });

        click(toggle);
        document.querySelectorAll('[data-column="category"]').forEach((cell) => {
            expect(cell.hidden).toBe(false);
        });
    });

    it("advances to the next page via the reused pagination-compact bar and updates its state", () => {
        const { rows } = renderDataTable({ perPage: 2 });
        initDataTable();

        const nextButton = document.querySelector('[data-action="next"]');
        click(nextButton);

        const visible = rows().filter((tr) => !tr.hidden);
        expect(visible.map((tr) => tr.dataset.search)).toEqual(["charlie a", "delta b"]);
        expect(document.querySelector('[data-slot="pagination-compact-status"]').textContent).toBe(
            "Page 2 of 2"
        );
        expect(nextButton.hasAttribute("href")).toBe(false);
        expect(nextButton.getAttribute("aria-disabled")).toBe("true");

        const previousButton = document.querySelector('[data-action="previous"]');
        expect(previousButton.hasAttribute("aria-disabled")).toBe(false);
        expect(previousButton.getAttribute("href")).toBe("?paged=0");
    });

    it("does not advance past the last page", () => {
        renderDataTable({ perPage: 2 });
        initDataTable();

        const nextButton = document.querySelector('[data-action="next"]');
        click(nextButton);
        click(nextButton);

        expect(document.querySelector('[data-slot="pagination-compact-status"]').textContent).toBe(
            "Page 2 of 2"
        );
    });

    it("resets to page 1 when a search narrows the result set", () => {
        const { rows, searchInput } = renderDataTable({ perPage: 2 });
        initDataTable();

        click(document.querySelector('[data-action="next"]'));
        searchInput.value = "a";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));

        expect(document.querySelector('[data-slot="pagination-compact-status"]').textContent).toBe(
            "Page 1 of 2"
        );
        expect(rows().filter((tr) => !tr.hidden)).toHaveLength(2);
    });

    it("works without a pagination bar (per_page disabled)", () => {
        const { rows } = renderDataTable({ perPage: 0, includePagination: false });
        document.querySelector('[data-slot="data-table"]').removeAttribute("data-per-page");

        initDataTable();

        expect(rows().filter((tr) => !tr.hidden)).toHaveLength(4);
    });

    it("preserves the selected-row data-state through sorting/filtering", () => {
        renderDataTable({
            perPage: 10,
            rows: [
                { name: "Alpha", category: "A", stock: 5, selected: true },
                { name: "Bravo", category: "B", stock: 20 },
            ],
        });
        initDataTable();

        const stockButton = Array.from(
            document.querySelectorAll('[data-slot="data-table-sort"]')
        ).find((button) => button.dataset.sortKey === "stock");
        click(stockButton);

        const selectedRow = document.querySelector('tr[data-state="selected"]');
        expect(selectedRow.dataset.search).toBe("alpha a");
    });
});
