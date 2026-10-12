// WAI-ARIA menu pattern for dropdown-menu.php (roving tabindex, arrow keys, Home/End, type-ahead,
// outside click/Escape) and the only place checkbox/radio items can toggle.

function getItems(content) {
    return Array.from(
        content.querySelectorAll(
            '[role="menuitem"], [role="menuitemcheckbox"], [role="menuitemradio"]'
        )
    );
}

function isDisabled(item) {
    return item.disabled || item.getAttribute("aria-disabled") === "true";
}

function setupDropdownMenu(wrapper) {
    const trigger = wrapper.querySelector('[data-slot="dropdown-menu-trigger"]');
    const content = wrapper.querySelector('[data-slot="dropdown-menu-content"]');

    if (!trigger || !content) {
        return;
    }

    let items = getItems(content);
    let typeAheadBuffer = "";
    let typeAheadTimer;

    const focusableItems = () => items.filter((item) => !isDisabled(item));

    const setRovingTabindex = (activeItem) => {
        items.forEach((item) => {
            item.setAttribute("tabindex", item === activeItem ? "0" : "-1");
        });
    };

    const close = ({ focusTrigger = false } = {}) => {
        wrapper.removeAttribute("open");

        if (focusTrigger) {
            trigger.focus();
        }
    };

    const focusItem = (item) => {
        if (!item) {
            return;
        }

        setRovingTabindex(item);
        item.focus();
    };

    const moveFocus = (delta) => {
        const enabled = focusableItems();

        if (enabled.length === 0) {
            return;
        }

        const current = document.activeElement;
        const currentIndex = enabled.indexOf(current);
        const nextIndex = (currentIndex + delta + enabled.length) % enabled.length;

        focusItem(enabled[nextIndex]);
    };

    const toggleCheckState = (item) => {
        const role = item.getAttribute("role");

        if (role !== "menuitemcheckbox" && role !== "menuitemradio") {
            return;
        }

        const checked = item.getAttribute("aria-checked") === "true";

        if (role === "menuitemradio") {
            const group = item.closest('[data-slot="dropdown-menu-radio-group"]') || content;

            group.querySelectorAll('[role="menuitemradio"]').forEach((sibling) => {
                sibling.setAttribute("aria-checked", "false");
                sibling.setAttribute("data-state", "unchecked");
            });

            item.setAttribute("aria-checked", "true");
            item.setAttribute("data-state", "checked");
        } else {
            item.setAttribute("aria-checked", checked ? "false" : "true");
            item.setAttribute("data-state", checked ? "unchecked" : "checked");
        }

        item.dispatchEvent(new Event("change", { bubbles: true }));
    };

    const activateItem = (item) => {
        if (!item || isDisabled(item)) {
            return;
        }

        toggleCheckState(item);

        // Plain menu items act natively; only close the menu.
        if (item.getAttribute("role") === "menuitem") {
            close();
            return;
        }

        // Checkbox/radio items keep the menu open for several changes.
    };

    trigger.addEventListener("keydown", (event) => {
        if (event.key === "ArrowDown" || event.key === "ArrowUp") {
            event.preventDefault();

            if (!wrapper.open) {
                wrapper.setAttribute("open", "");
                items = getItems(content);
            }

            focusItem(
                event.key === "ArrowDown"
                    ? focusableItems()[0]
                    : focusableItems()[focusableItems().length - 1]
            );
        }
    });

    content.addEventListener("keydown", (event) => {
        if (event.key === "ArrowDown") {
            event.preventDefault();
            moveFocus(1);
        } else if (event.key === "ArrowUp") {
            event.preventDefault();
            moveFocus(-1);
        } else if (event.key === "Home") {
            event.preventDefault();
            focusItem(focusableItems()[0]);
        } else if (event.key === "End") {
            event.preventDefault();
            focusItem(focusableItems()[focusableItems().length - 1]);
        } else if (event.key === "Escape") {
            event.preventDefault();
            close({ focusTrigger: true });
        } else if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            activateItem(document.activeElement);
        } else if (event.key === "Tab") {
            close();
        } else if (event.key.length === 1 && event.key !== " ") {
            window.clearTimeout(typeAheadTimer);
            typeAheadBuffer += event.key.toLowerCase();
            typeAheadTimer = window.setTimeout(() => {
                typeAheadBuffer = "";
            }, 500);

            const enabled = focusableItems();
            const startIndex = Math.max(enabled.indexOf(document.activeElement) + 1, 0);
            const ordered = enabled.slice(startIndex).concat(enabled.slice(0, startIndex));
            const match = ordered.find((item) => {
                const textEl = item.querySelector('[data-slot="dropdown-menu-item-text"]');
                return (
                    textEl && textEl.textContent.trim().toLowerCase().startsWith(typeAheadBuffer)
                );
            });

            focusItem(match);
        }
    });

    content.addEventListener("click", (event) => {
        const item = event.target.closest(
            '[role="menuitem"], [role="menuitemcheckbox"], [role="menuitemradio"]'
        );

        if (item) {
            activateItem(item);
        }
    });

    document.addEventListener("click", (event) => {
        if (wrapper.open && !wrapper.contains(event.target)) {
            close();
        }
    });

    wrapper.addEventListener("toggle", () => {
        if (wrapper.open) {
            items = getItems(content);
            const enabled = focusableItems();
            setRovingTabindex(enabled[0]);
        }
    });

    setRovingTabindex(focusableItems()[0]);
    wrapper.setAttribute("data-js", "dropdown-menu");
}

export function initDropdownMenu() {
    document.querySelectorAll('[data-slot="dropdown-menu"]').forEach((wrapper) => {
        setupDropdownMenu(wrapper);
    });
}
