// Progressive-enhancement layer for template-parts/base/select.php: builds a custom-styled
// listbox from a native <select>'s own <option>/<optgroup> elements (single source of truth, see
// that file's header comment) and wires up the WAI-ARIA "select-only combobox" pattern
// (role="combobox" + aria-activedescendant, focus always stays on the trigger button).

function getFlatOptions(nativeSelect) {
    const flat = [];

    Array.from(nativeSelect.children).forEach((child) => {
        if (child.tagName === "OPTGROUP") {
            Array.from(child.children).forEach((option) => {
                if (option.tagName === "OPTION") {
                    flat.push({ option, groupLabel: child.label });
                }
            });
            return;
        }

        if (child.tagName === "OPTION") {
            flat.push({ option: child, groupLabel: null });
        }
    });

    return flat;
}

function buildListbox(nativeSelect, content, indicatorTemplate) {
    const items = [];
    let currentGroup;
    let currentGroupContainer = content;

    content.innerHTML = "";

    getFlatOptions(nativeSelect).forEach(({ option, groupLabel }, index) => {
        if (groupLabel !== currentGroup) {
            currentGroup = groupLabel;

            if (groupLabel === null) {
                currentGroupContainer = content;
            } else {
                const group = document.createElement("div");
                group.setAttribute("role", "group");
                group.setAttribute("data-slot", "select-group");

                const groupLabelEl = document.createElement("div");
                groupLabelEl.setAttribute("data-slot", "select-label");
                groupLabelEl.className = "text-muted-foreground px-2 py-1.5 text-xs";
                groupLabelEl.textContent = groupLabel;

                group.appendChild(groupLabelEl);
                content.appendChild(group);
                currentGroupContainer = group;
            }
        }

        // Classes taken from shadcn's own SelectItem (registry/new-york-v4/ui/select.tsx) --
        // `relative`/`pr-8` + the indicator's own `absolute right-2` (below) is what keeps the
        // checkmark from taking its own row: it's overlaid on the item, not laid out inline next
        // to the text. `data-active` (this module's own keyboard/hover-highlight state, see
        // setActive()) mirrors shadcn's `data-highlighted` via the same `bg-accent` treatment.
        const item = document.createElement("div");
        item.id = `${nativeSelect.id}-option-${index}`;
        item.setAttribute("role", "option");
        item.setAttribute("data-slot", "select-item");
        item.className =
            "relative flex w-full cursor-default items-center gap-2 rounded-sm py-1.5 pr-8 " +
            "pl-2 text-sm outline-hidden select-none data-[active]:bg-accent " +
            "data-[active]:text-accent-foreground data-[disabled]:pointer-events-none " +
            "data-[disabled]:opacity-50";
        item.dataset.value = option.value;

        if (option.disabled) {
            item.setAttribute("aria-disabled", "true");
            item.setAttribute("data-disabled", "true");
        }

        item.setAttribute("aria-selected", option.selected ? "true" : "false");
        item.toggleAttribute("data-selected", option.selected);

        const indicator = document.createElement("span");
        indicator.setAttribute("data-slot", "select-item-indicator");
        indicator.className = "absolute right-2 flex size-3.5 items-center justify-center";

        // Only populate the indicator for the actually-selected item -- shadcn's own
        // SelectItemIndicator (Radix) only renders its children when selected; cloning the
        // checkmark template unconditionally here (the previous bug) made it show on every item.
        if (indicatorTemplate && option.selected) {
            indicator.appendChild(indicatorTemplate.content.cloneNode(true));
        }

        const text = document.createElement("span");
        text.setAttribute("data-slot", "select-item-text");
        text.textContent = option.textContent;

        item.appendChild(indicator);
        item.appendChild(text);
        currentGroupContainer.appendChild(item);
        items.push(item);
    });

    return items;
}

function setupSelect(wrapper) {
    const nativeSelect = wrapper.querySelector('[data-slot="native-select"]');
    const trigger = wrapper.querySelector('[data-slot="select-trigger"]');
    const content = wrapper.querySelector('[data-slot="select-content"]');
    const valueEl = trigger ? trigger.querySelector('[data-slot="select-value"]') : null;
    const indicatorTemplate = wrapper.querySelector('[data-slot="select-item-indicator-template"]');

    if (!nativeSelect || !trigger || !content || !valueEl) {
        return;
    }

    let items = buildListbox(nativeSelect, content, indicatorTemplate);
    let activeIndex = items.findIndex((item) => item.getAttribute("aria-selected") === "true");

    const isOpen = () => trigger.getAttribute("aria-expanded") === "true";

    const close = () => {
        trigger.setAttribute("aria-expanded", "false");
        trigger.removeAttribute("aria-activedescendant");
        content.hidden = true;
    };

    const setActive = (index) => {
        if (index < 0 || index >= items.length) {
            return;
        }

        items.forEach((item) => item.removeAttribute("data-active"));
        items[index].setAttribute("data-active", "true");
        trigger.setAttribute("aria-activedescendant", items[index].id);
        items[index].scrollIntoView({ block: "nearest" });
        activeIndex = index;
    };

    const open = () => {
        if (trigger.disabled || isOpen()) {
            return;
        }

        trigger.setAttribute("aria-expanded", "true");
        content.hidden = false;

        if (activeIndex < 0) {
            activeIndex = items.findIndex((item) => item.getAttribute("aria-disabled") !== "true");
        }

        setActive(activeIndex);
    };

    const moveActive = (delta) => {
        if (items.length === 0) {
            return;
        }

        let index = activeIndex;

        for (let step = 0; step < items.length; step += 1) {
            index = (index + delta + items.length) % items.length;

            if (items[index].getAttribute("aria-disabled") !== "true") {
                setActive(index);
                return;
            }
        }
    };

    const selectItem = (index) => {
        const item = items[index];

        if (!item || item.getAttribute("aria-disabled") === "true") {
            return;
        }

        nativeSelect.value = item.dataset.value;
        nativeSelect.dispatchEvent(new Event("change", { bubbles: true }));
        close();
        trigger.focus();
    };

    trigger.addEventListener("click", () => {
        if (isOpen()) {
            close();
        } else {
            open();
        }
    });

    trigger.addEventListener("keydown", (event) => {
        if (event.key === "ArrowDown") {
            event.preventDefault();
            isOpen() ? moveActive(1) : open();
        } else if (event.key === "ArrowUp") {
            event.preventDefault();
            isOpen() ? moveActive(-1) : open();
        } else if (event.key === "Home" && isOpen()) {
            event.preventDefault();
            setActive(0);
        } else if (event.key === "End" && isOpen()) {
            event.preventDefault();
            setActive(items.length - 1);
        } else if ((event.key === "Enter" || event.key === " ") && isOpen()) {
            event.preventDefault();
            selectItem(activeIndex);
        } else if (event.key === "Escape" && isOpen()) {
            event.preventDefault();
            close();
        }
    });

    content.addEventListener("click", (event) => {
        const item = event.target.closest('[role="option"]');

        if (item) {
            selectItem(items.indexOf(item));
        }
    });

    document.addEventListener("click", (event) => {
        if (!wrapper.contains(event.target)) {
            close();
        }
    });

    // A click on the paired <label for="..."> focuses the (visually-hidden-once-JS-ready) native
    // select natively -- redirect that focus to the visible trigger instead.
    nativeSelect.addEventListener("focus", () => {
        trigger.focus();
    });

    // Keeps the custom UI in sync if the native select's value changes from outside this module
    // (a form reset, another script, ...), not just from selectItem() above.
    nativeSelect.addEventListener("change", () => {
        items = buildListbox(nativeSelect, content, indicatorTemplate);
        activeIndex = items.findIndex((item) => item.getAttribute("aria-selected") === "true");

        const selected = items[activeIndex];

        if (selected) {
            selected.setAttribute("data-active", "true");
        }

        valueEl.textContent = selected
            ? selected.querySelector('[data-slot="select-item-text"]').textContent
            : "";
    });

    close();
    wrapper.setAttribute("data-js", "select");
}

export function initSelect() {
    document.querySelectorAll('[data-slot="select"]').forEach((wrapper) => {
        setupSelect(wrapper);
    });
}
