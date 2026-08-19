// Progressive-enhancement layer for template-parts/base/data-table.php: intercepts clicks on its
// sort-column links ([data-slot="data-table-sort"]) and pagination links
// ([data-slot="data-table-pagination"] a[href]) and fetches the target URL instead of letting the
// browser follow it as a full page reload -- see that file's header comment on why v1 shipped
// without this: a sorted/paginated page of arbitrary caller-supplied `rows` needs the actual data
// source re-queried server-side, something this generic component cannot invent on a project's
// behalf. This module still can't invent that re-query -- but it CAN avoid a full page reload for
// it: the href already points at the exact same page with just its orderby/order/paged query args
// changed (add_query_arg(), same URL a real click would follow), so fetching that URL, parsing the
// response, and swapping in only the matching [data-slot="data-table"] subtree reuses the real
// server render instead of reimplementing sorting/pagination client-side -- no new PHP endpoint
// needed, same "the href IS the API" idea calendar.php's own nav links rely on before JS touches
// them.
//
// Multiple data tables can exist on one page; correlation between the clicked table and its
// replacement in the fetched response is positional (the Nth [data-slot="data-table"] in the
// current document maps to the Nth one in the fetched document) -- add_query_arg() only changes the
// query string, so page structure/ordering is stable across these navigations. If that assumption
// doesn't hold (fetch fails, response has fewer tables than expected, ...), this falls back to a
// real navigation rather than silently doing nothing.
//
// Non-goals kept deliberately out, same reasoning as data-table.php's own header comment: this does
// not add filtering, row selection or column visibility -- only removes the reload for the two
// navigation affordances the PHP component already renders as real <a href> links.

function isPlainLeftClick(event) {
    return (
        event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey
    );
}

function currentDataTables() {
    return Array.from(document.querySelectorAll('[data-slot="data-table"]'));
}

async function fetchDataTables(url) {
    const response = await fetch(url);

    if (!response.ok) {
        throw new Error(`data-table fetch failed with status ${response.status}`);
    }

    const html = await response.text();
    const doc = new DOMParser().parseFromString(html, "text/html");

    return doc.querySelectorAll('[data-slot="data-table"]');
}

// Replaces every currently-known data table's content with its positional match from a freshly
// fetched document. Shared by both the click handler (a single table changed, but re-fetching once
// and applying to all keeps popstate and click navigation going through the same code path) and the
// popstate handler (the browser already changed the URL for us, every table on the page needs to
// catch up to it).
async function applyFetchedDataTables(url) {
    const wrappers = currentDataTables();
    const replacements = await fetchDataTables(url);

    if (replacements.length < wrappers.length) {
        throw new Error("data-table fetch response has fewer tables than the current page");
    }

    wrappers.forEach((wrapper, index) => {
        wrapper.innerHTML = replacements[index].innerHTML;
        wrapper.removeAttribute("aria-busy");
    });
}

async function navigate(triggeringWrapper, url) {
    triggeringWrapper.setAttribute("aria-busy", "true");

    try {
        await applyFetchedDataTables(url);
    } catch {
        // Real re-queries can fail for reasons outside this module's control (network error, a
        // caller's own query-var handling rejecting an unexpected value, ...) -- falling through to
        // an actual navigation is strictly no worse than the zero-JS baseline this enhances, never
        // a dead end.
        window.location.assign(url);
        return;
    }

    history.pushState({ dataTableUrl: url }, "", url);

    // Move focus back onto the table itself so keyboard/screen-reader users land somewhere sensible
    // after the content underneath them changed -- the clicked link no longer exists once
    // innerHTML was replaced, so focus would otherwise silently fall back to <body>. tabindex="-1"
    // makes a non-interactive container programmatically focusable without adding it to the regular
    // Tab order (same "focusable but not tab-stopped" idea as toggle.js's aria-hidden input).
    triggeringWrapper.setAttribute("tabindex", "-1");
    triggeringWrapper.focus({ preventScroll: true });
}

function setupDataTable(wrapper) {
    wrapper.addEventListener("click", (event) => {
        const link = event.target.closest(
            '[data-slot="data-table-sort"], [data-slot="data-table-pagination"] a[href]'
        );

        if (!link || !isPlainLeftClick(event)) {
            return;
        }

        const url = new URL(link.href, window.location.href);

        if (url.origin !== window.location.origin) {
            return;
        }

        event.preventDefault();
        navigate(wrapper, url.toString());
    });

    wrapper.setAttribute("data-js", "data-table");
}

export function initDataTable() {
    const wrappers = currentDataTables();

    if (wrappers.length === 0) {
        return;
    }

    wrappers.forEach((wrapper) => {
        setupDataTable(wrapper);
    });

    // The forward/back browser buttons change window.location without dispatching a click this
    // module intercepted (pushState() above doesn't fire "popstate" itself) -- without this, the
    // URL bar would go back to an earlier sort/page while the rendered table stayed on the newest
    // one. Re-fetches the now-current URL and re-applies it to every table on the page, same code
    // path as a real link click.
    window.addEventListener("popstate", () => {
        applyFetchedDataTables(window.location.href).catch(() => {
            window.location.reload();
        });
    });
}
