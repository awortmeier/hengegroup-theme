// Builds a styled listbox from combobox.php's <datalist> and applies the combobox ARIA pattern
// (focus stays on the input). The ARIA states are only set here because the popup only exists with JS.

function getOptions(datalist) {
    return Array.from(datalist.querySelectorAll("option"));
}

function buildListbox(input, content, options, indicatorTemplate, emptyTemplate, filterText) {
    const items = [];
    const needle = filterText.trim().toLowerCase();
    const matches = options.filter((option) => option.value.toLowerCase().includes(needle));

    content.innerHTML = "";

    if (matches.length === 0) {
        if (emptyTemplate) {
            const empty = document.createElement("div");
            empty.setAttribute("data-slot", "combobox-empty");
            empty.className = "text-muted-foreground py-6 text-center text-sm";
            empty.appendChild(emptyTemplate.content.cloneNode(true));
            content.appendChild(empty);
        }

        return items;
    }

    let currentGroup;
    let currentGroupContainer = content;

    matches.forEach((option, index) => {
        const groupLabel = option.dataset.group || null;

        if (groupLabel !== currentGroup) {
            currentGroup = groupLabel;

            if (groupLabel === null) {
                currentGroupContainer = content;
            } else {
                const group = document.createElement("div");
                group.setAttribute("role", "group");
                group.setAttribute("data-slot", "combobox-group");

                const groupLabelEl = document.createElement("div");
                groupLabelEl.setAttribute("data-slot", "combobox-label");
                groupLabelEl.className = "text-muted-foreground px-2 py-1.5 text-xs";
                groupLabelEl.textContent = groupLabel;

                group.appendChild(groupLabelEl);
                content.appendChild(group);
                currentGroupContainer = group;
            }
        }

        // relative/pr-8 + absolute indicator keeps the check mark on the same line.
        const item = document.createElement("div");
        item.id = `${input.id}-option-${index}`;
        item.setAttribute("role", "option");
        item.setAttribute("data-slot", "combobox-item");
        item.className =
            "relative flex w-full cursor-default items-center gap-2 rounded-sm py-1.5 pr-8 " +
            "pl-2 text-sm outline-hidden select-none data-[active]:bg-accent " +
            "data-[active]:text-accent-foreground data-[disabled]:pointer-events-none " +
            "data-[disabled]:opacity-50";
        item.dataset.value = option.dataset.value || option.value;
        item.dataset.text = option.value;

        const isDisabled = option.dataset.disabled === "true";
        const isSelected = item.dataset.value === input.dataset.selectedValue;

        if (isDisabled) {
            item.setAttribute("aria-disabled", "true");
            item.setAttribute("data-disabled", "true");
        }

        item.setAttribute("aria-selected", isSelected ? "true" : "false");
        item.toggleAttribute("data-selected", isSelected);

        const indicator = document.createElement("span");
        indicator.setAttribute("data-slot", "combobox-item-indicator");
        indicator.className = "absolute right-2 flex size-3.5 items-center justify-center";

        if (indicatorTemplate && isSelected) {
            indicator.appendChild(indicatorTemplate.content.cloneNode(true));
        }

        const text = document.createElement("span");
        text.setAttribute("data-slot", "combobox-item-text");
        text.textContent = option.value;

        item.appendChild(indicator);
        item.appendChild(text);
        currentGroupContainer.appendChild(item);
        items.push(item);
    });

    return items;
}

function setupCombobox(wrapper) {
    const input = wrapper.querySelector('[data-slot="combobox-input"]');
    const content = wrapper.querySelector('[data-slot="combobox-content"]');
    const datalist = input ? document.getElementById(input.getAttribute("list")) : null;
    const indicatorTemplate = wrapper.querySelector(
        '[data-slot="combobox-item-indicator-template"]'
    );
    const emptyTemplate = wrapper.querySelector('[data-slot="combobox-empty-template"]');

    if (!input || !content || !datalist) {
        return;
    }

    const options = getOptions(datalist);

    input.setAttribute("role", "combobox");
    input.setAttribute("aria-autocomplete", "list");
    input.setAttribute("aria-controls", content.id);

    // Detach the input from the datalist so the native suggestions don't show next to the custom
    // list (the datalist stays as data source).
    const initialMatch = options.find((option) => option.value === input.value);
    input.dataset.selectedValue = initialMatch
        ? initialMatch.dataset.value || initialMatch.value
        : "";
    input.removeAttribute("list");

    let items = [];

    const isOpen = () => input.getAttribute("aria-expanded") === "true";

    const close = () => {
        input.setAttribute("aria-expanded", "false");
        input.removeAttribute("aria-activedescendant");
        content.hidden = true;
    };

    const setActive = (index) => {
        if (index < 0 || index >= items.length) {
            input.removeAttribute("aria-activedescendant");
            return;
        }

        items.forEach((item) => item.removeAttribute("data-active"));
        items[index].setAttribute("data-active", "true");
        input.setAttribute("aria-activedescendant", items[index].id);
        items[index].scrollIntoView({ block: "nearest" });
    };

    const firstEnabledIndex = () =>
        items.findIndex((item) => item.getAttribute("aria-disabled") !== "true");

    const render = () => {
        items = buildListbox(
            input,
            content,
            options,
            indicatorTemplate,
            emptyTemplate,
            input.value
        );
        setActive(firstEnabledIndex());
    };

    const open = () => {
        if (input.disabled || isOpen()) {
            return;
        }

        input.setAttribute("aria-expanded", "true");
        content.hidden = false;
        render();
    };

    const moveActive = (delta) => {
        if (items.length === 0) {
            return;
        }

        const current = items.findIndex((item) => item.hasAttribute("data-active"));
        let index = current;

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

        input.value = item.dataset.text;
        input.dataset.selectedValue = item.dataset.value;
        input.dispatchEvent(new Event("change", { bubbles: true }));
        close();
        input.focus();
    };

    input.addEventListener("focus", () => {
        open();
    });

    input.addEventListener("input", () => {
        // Free text no longer matches the previous selection.
        input.dataset.selectedValue = "";
        open();
        render();
    });

    input.addEventListener("keydown", (event) => {
        if (event.key === "ArrowDown") {
            event.preventDefault();
            isOpen() ? moveActive(1) : open();
        } else if (event.key === "ArrowUp") {
            event.preventDefault();
            isOpen() ? moveActive(-1) : open();
        } else if (event.key === "Enter") {
            if (isOpen()) {
                event.preventDefault();
                const active = items.findIndex((item) => item.hasAttribute("data-active"));
                selectItem(active);
            }
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

    close();
    wrapper.setAttribute("data-js", "combobox");
}

export function initCombobox() {
    document.querySelectorAll('[data-slot="combobox"]').forEach((wrapper) => {
        setupCombobox(wrapper);
    });
}
