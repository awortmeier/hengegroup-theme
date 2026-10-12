// Upgrades tabs.php's radios to the WAI-ARIA tabs pattern (tablist/tab/tabpanel, roving tabindex,
// arrow keys per orientation). Radios get aria-hidden + tabindex="-1", the labels become the tabs;
// clicks still reach the radio natively, so only keyboard activation is added. Panel visibility
// stays CSS.

const FOCUSABLE_SELECTOR =
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

function getTriggers(tabs) {
    return Array.from(tabs.querySelectorAll('[data-slot="tabs-trigger-input"]'))
        .map((input) => ({ input, label: input.labels && input.labels[0] }))
        .filter((trigger) => trigger.label);
}

function syncSelection(triggers, activeInput) {
    triggers.forEach(({ input, label }) => {
        const isActive = input === activeInput;
        label.setAttribute("aria-selected", isActive ? "true" : "false");
        label.tabIndex = isActive ? 0 : -1;
    });
}

function activateTrigger(trigger) {
    trigger.input.checked = true;
    trigger.input.dispatchEvent(new Event("change", { bubbles: true }));
    trigger.label.focus();
}

function handleTriggerKeydown(list, triggers, currentLabel, event) {
    const enabled = triggers.filter(({ input }) => !input.disabled);
    const currentIndex = enabled.findIndex(({ label }) => label === currentLabel);

    if (currentIndex === -1) {
        return;
    }

    const isVertical = list.getAttribute("data-orientation") === "vertical";
    const nextKey = isVertical ? "ArrowDown" : "ArrowRight";
    const previousKey = isVertical ? "ArrowUp" : "ArrowLeft";

    let nextIndex;

    if (event.key === nextKey) {
        nextIndex = (currentIndex + 1) % enabled.length;
    } else if (event.key === previousKey) {
        nextIndex = (currentIndex - 1 + enabled.length) % enabled.length;
    } else if (event.key === "Home") {
        nextIndex = 0;
    } else if (event.key === "End") {
        nextIndex = enabled.length - 1;
    } else {
        return;
    }

    event.preventDefault();
    activateTrigger(enabled[nextIndex]);
}

function setupTabs(tabs) {
    const list = tabs.querySelector('[data-slot="tabs-list"]');
    const triggers = getTriggers(tabs);

    if (!list || triggers.length === 0) {
        return;
    }

    list.setAttribute("role", "tablist");

    triggers.forEach(({ input, label }) => {
        input.setAttribute("aria-hidden", "true");
        input.tabIndex = -1;

        label.setAttribute("role", "tab");
        label.setAttribute("aria-selected", input.checked ? "true" : "false");
        label.tabIndex = input.checked && !input.disabled ? 0 : -1;

        if (input.disabled) {
            label.setAttribute("aria-disabled", "true");
        }

        label.addEventListener("keydown", (event) =>
            handleTriggerKeydown(list, triggers, label, event)
        );
    });

    // Keep the tablist reachable even if the checked item is disabled.
    if (!triggers.some(({ label }) => label.tabIndex === 0)) {
        const firstEnabled = triggers.find(({ input }) => !input.disabled);

        if (firstEnabled) {
            firstEnabled.label.tabIndex = 0;
        }
    }

    list.addEventListener("change", (event) => {
        if (event.target.matches('[data-slot="tabs-trigger-input"]')) {
            syncSelection(triggers, event.target);
        }
    });

    tabs.querySelectorAll('[data-slot="tabs-content"]').forEach((panel) => {
        panel.setAttribute("role", "tabpanel");

        if (!panel.querySelector(FOCUSABLE_SELECTOR)) {
            panel.tabIndex = 0;
        }
    });

    tabs.setAttribute("data-js", "tabs");
}

export function initTabs() {
    document.querySelectorAll('[data-slot="tabs"]').forEach((tabs) => {
        setupTabs(tabs);
    });
}
