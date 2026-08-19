// Progressive-enhancement layer for template-parts/base/navigation-menu/navigation-menu.php: adds
// what the native <details>/<summary> zero-JS baseline can't do on its own (see that file's header
// comment) -- outside-click/Escape close (same gap dropdown-menu.js already closes for its own
// single-<details> case), hover-intent open/close per trigger item, and horizontal Left/Right
// (Home/End) roving navigation across the top-level items. Does NOT reimplement the "only one
// panel open at a time" behaviour -- every trigger item's <details> already shares one native
// `name` (see that file's header comment), so opening one via script (`details.open = true`)
// closes any other same-named open one for free, exactly like a user click would.

function getTopItems(list) {
    return Array.from(
        list.querySelectorAll(
            ':scope > li > [data-slot="navigation-menu-link"], :scope > li > details > summary[data-slot="navigation-menu-trigger"]'
        )
    );
}

function getOpenItem(nav) {
    return nav.querySelector('details[data-slot="navigation-menu-trigger-item"][open]');
}

function setupNavigationMenu(nav) {
    const list = nav.querySelector('[data-slot="navigation-menu-list"]');

    if (!list) {
        return;
    }

    const openDelay = Number(nav.dataset.delay) || 50;
    const closeDelay = Number(nav.dataset.closeDelay) || 50;

    nav.querySelectorAll('details[data-slot="navigation-menu-trigger-item"]').forEach((details) => {
        const trigger = details.querySelector(
            ':scope > summary[data-slot="navigation-menu-trigger"]'
        );
        const content = details.querySelector(':scope > [data-slot="navigation-menu-content"]');

        if (!trigger || !content) {
            return;
        }

        let openTimer = null;
        let closeTimer = null;

        const open = () => {
            window.clearTimeout(closeTimer);
            details.open = true;
        };

        const scheduleOpen = () => {
            window.clearTimeout(closeTimer);
            window.clearTimeout(openTimer);
            openTimer = window.setTimeout(open, openDelay);
        };

        const scheduleClose = () => {
            window.clearTimeout(openTimer);
            window.clearTimeout(closeTimer);
            closeTimer = window.setTimeout(() => {
                details.open = false;
            }, closeDelay);
        };

        [trigger, content].forEach((element) => {
            element.addEventListener("mouseenter", scheduleOpen);
            element.addEventListener("mouseleave", scheduleClose);
        });
    });

    nav.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            const openItem = getOpenItem(nav);

            if (openItem) {
                openItem.open = false;
                openItem
                    .querySelector(':scope > summary[data-slot="navigation-menu-trigger"]')
                    ?.focus();
            }

            return;
        }

        if (!["ArrowRight", "ArrowLeft", "Home", "End"].includes(event.key)) {
            return;
        }

        const items = getTopItems(list);
        const currentIndex = items.indexOf(document.activeElement);

        if (currentIndex === -1) {
            return;
        }

        event.preventDefault();

        let nextIndex;

        if (event.key === "ArrowRight") {
            nextIndex = (currentIndex + 1) % items.length;
        } else if (event.key === "ArrowLeft") {
            nextIndex = (currentIndex - 1 + items.length) % items.length;
        } else if (event.key === "Home") {
            nextIndex = 0;
        } else {
            nextIndex = items.length - 1;
        }

        items[nextIndex].focus();
    });

    document.addEventListener("click", (event) => {
        const openItem = getOpenItem(nav);

        if (openItem && !nav.contains(event.target)) {
            openItem.open = false;
        }
    });

    nav.dataset.js = "navigation-menu";
}

export function initNavigationMenu() {
    document.querySelectorAll('[data-slot="navigation-menu"]').forEach((nav) => {
        setupNavigationMenu(nav);
    });
}
