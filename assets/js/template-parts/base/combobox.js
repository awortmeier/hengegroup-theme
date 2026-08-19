// Progressive-enhancement layer for template-parts/base/combobox.php: builds a custom-styled,
// filter-as-you-type listbox from a native <datalist>'s own <option> elements (single source of
// truth, see that file's header comment) and wires up the WAI-ARIA "combobox with listbox popup"
// pattern (role="combobox" + aria-expanded/aria-controls/aria-activedescendant, focus stays on the
// text input itself). combobox.php never renders those states itself -- the popup they describe
// only exists once this module builds it, same "PHP stays honest, JS applies the ARIA correction"
// precedent as toggle.js/tabs.js -- so setupCombobox() applies role/aria-
// autocomplete/aria-controls once here, before wiring up open()/close()/setActive(), which keep
// aria-expanded/aria-activedescendant in sync afterwards.

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
                groupLabelEl.textContent = groupLabel;

                group.appendChild(groupLabelEl);
                content.appendChild(group);
                currentGroupContainer = group;
            }
        }

        const item = document.createElement("div");
        item.id = `${input.id}-option-${index}`;
        item.setAttribute("role", "option");
        item.setAttribute("data-slot", "combobox-item");
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

        if (indicatorTemplate) {
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

    // These four ARIA states describe the popup this module is about to build -- combobox.php
    // deliberately leaves them off its zero-JS markup (see that file's header comment), so they're
    // applied here, once, before any open()/close() call touches aria-expanded.
    input.setAttribute("role", "combobox");
    input.setAttribute("aria-autocomplete", "list");
    input.setAttribute("aria-controls", content.id);

    // Resolve the initial canonical value from the input's server-rendered display text, then
    // detach the input from the datalist so the browser's own native suggestion popup doesn't
    // show a second, redundant UI next to the custom one (the <datalist> itself stays in the DOM
    // as this module's own data source, see this file's header comment).
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
        // A free-typed value no longer matches whatever was previously selected -- see this
        // file's header comment on the v1 value===text scope decision.
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
