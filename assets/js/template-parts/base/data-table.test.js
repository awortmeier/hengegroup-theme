// Unit tests for initDataTable() (see data-table.js's header comment: it fetches the real
// add_query_arg() sort/pagination hrefs template-parts/base/data-table.php already renders and
// swaps in only the matching [data-slot="data-table"] subtree, falling back to a real navigation
// whenever that fetch/swap can't be trusted). Builds that wrapper + sort-link + pagination markup
// directly in jsdom instead of importing the PHP template -- rendering real PHP output isn't
// available in this JS-only test runner, see docs/to-do.md Abschnitt 1 for where
// a WP-backed integration suite would close that gap.
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { initDataTable } from "./data-table.js";

function click(el, options = {}) {
    el.dispatchEvent(
        new MouseEvent("click", { bubbles: true, cancelable: true, button: 0, ...options })
    );
}

function renderDataTable({ id = "table-1" } = {}) {
    document.body.innerHTML = `
        <div data-slot="data-table" id="${id}">
            <table>
                <tbody><tr><td>Old</td></tr></tbody>
            </table>
            <a href="/products/?orderby=name&order=asc" data-slot="data-table-sort">Name</a>
            <nav data-slot="data-table-pagination">
                <a href="/products/?paged=2">2</a>
            </nav>
        </div>
    `;

    return document.querySelector('[data-slot="data-table"]');
}

function mockFetchResolving(html) {
    global.fetch = vi.fn().mockResolvedValue({
        ok: true,
        text: () => Promise.resolve(html),
    });
}

beforeEach(() => {
    document.body.innerHTML = "";
});

afterEach(() => {
    vi.restoreAllMocks();
    delete global.fetch;
});

describe("initDataTable", () => {
    it("does nothing when the page has no data tables", () => {
        global.fetch = vi.fn();

        initDataTable();
        window.dispatchEvent(new Event("popstate"));

        expect(fetch).not.toHaveBeenCalled();
    });

    it("intercepts a plain left click on a sort link and swaps in the fetched table", async () => {
        const wrapper = renderDataTable();
        const link = wrapper.querySelector('[data-slot="data-table-sort"]');
        mockFetchResolving(
            '<div data-slot="data-table" id="table-1"><table><tbody><tr><td>New</td></tr></tbody></table></div>'
        );
        const pushStateSpy = vi.spyOn(history, "pushState").mockImplementation(() => {});

        initDataTable();
        click(link);

        // navigate() sets aria-busy synchronously, before the fetch promise settles
        expect(wrapper.getAttribute("aria-busy")).toBe("true");

        await vi.waitFor(() => {
            expect(wrapper.textContent).toContain("New");
        });

        expect(fetch).toHaveBeenCalledWith(expect.stringContaining("orderby=name"));
        expect(wrapper.hasAttribute("aria-busy")).toBe(false);
        expect(pushStateSpy).toHaveBeenCalled();
        expect(document.activeElement).toBe(wrapper);
    });

    // window.location.assign/.reload are non-configurable, non-writable own properties on jsdom's
    // Location object (unlike a plain method), so vi.spyOn() can't intercept them here -- the
    // fallback tests below instead assert the *absence* of the success path (no history entry, no
    // content swap) as the observable proof that navigate()'s catch branch ran.
    it("falls back to a real navigation when the fetch fails", async () => {
        const wrapper = renderDataTable();
        const link = wrapper.querySelector('[data-slot="data-table-sort"]');
        global.fetch = vi.fn().mockRejectedValue(new Error("network error"));
        const pushStateSpy = vi.spyOn(history, "pushState").mockImplementation(() => {});

        initDataTable();
        click(link);

        await vi.waitFor(() => {
            expect(fetch).toHaveBeenCalledWith(expect.stringContaining("orderby=name"));
        });
        expect(pushStateSpy).not.toHaveBeenCalled();
        expect(wrapper.textContent).toContain("Old");
    });

    it("falls back to a real navigation when the response has fewer tables than expected", async () => {
        const wrapper = renderDataTable();
        const link = wrapper.querySelector('[data-slot="data-table-sort"]');
        mockFetchResolving("<p>no tables in this response</p>");
        const pushStateSpy = vi.spyOn(history, "pushState").mockImplementation(() => {});

        initDataTable();
        click(link);

        await vi.waitFor(() => {
            expect(fetch).toHaveBeenCalledWith(expect.stringContaining("orderby=name"));
        });
        expect(pushStateSpy).not.toHaveBeenCalled();
        expect(wrapper.textContent).toContain("Old");
    });

    it("ignores clicks with a modifier key", () => {
        const wrapper = renderDataTable();
        const link = wrapper.querySelector('[data-slot="data-table-sort"]');
        global.fetch = vi.fn();

        initDataTable();
        const event = new MouseEvent("click", {
            bubbles: true,
            cancelable: true,
            button: 0,
            ctrlKey: true,
        });
        link.dispatchEvent(event);

        expect(event.defaultPrevented).toBe(false);
        expect(fetch).not.toHaveBeenCalled();
    });

    it("ignores cross-origin links", () => {
        const wrapper = renderDataTable();
        const link = wrapper.querySelector('[data-slot="data-table-pagination"] a');
        link.href = "https://example.com/other";
        global.fetch = vi.fn();

        initDataTable();
        const event = new MouseEvent("click", { bubbles: true, cancelable: true, button: 0 });
        link.dispatchEvent(event);

        expect(event.defaultPrevented).toBe(false);
        expect(fetch).not.toHaveBeenCalled();
    });

    it("re-fetches the current URL and re-applies every table on the page on popstate", async () => {
        const wrapper = renderDataTable();
        mockFetchResolving(
            '<div data-slot="data-table" id="table-1"><table><tbody><tr><td>Back</td></tr></tbody></table></div>'
        );

        initDataTable();
        window.dispatchEvent(new PopStateEvent("popstate"));

        await vi.waitFor(() => {
            expect(wrapper.textContent).toContain("Back");
        });
        expect(fetch).toHaveBeenCalledWith(window.location.href);
    });

    it("falls back to reloading the page if the popstate re-fetch itself fails", async () => {
        const wrapper = renderDataTable();
        global.fetch = vi.fn().mockRejectedValue(new Error("offline"));

        initDataTable();
        window.dispatchEvent(new PopStateEvent("popstate"));

        // window.location.reload() is called unconditionally in the .catch() handler here -- see
        // the comment above on why it can't be spied on directly in jsdom. Fetch having run against
        // the current URL, with the table content left untouched, is the observable proof this
        // branch (rather than a silent no-op) executed.
        await vi.waitFor(() => {
            expect(fetch).toHaveBeenCalledWith(window.location.href);
        });
        expect(wrapper.textContent).toContain("Old");
    });
});
