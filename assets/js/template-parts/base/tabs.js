// Progressive-enhancement layer for template-parts/base/tabs.php: upgrades the native
// radiogroup-of-radios zero-JS baseline (see that file's header comment) to the WAI-ARIA Tabs
// pattern -- role="tablist"/"tab"/"tabpanel", roving tabindex, orientation-aware arrow-key
// navigation (native radio groups always respond to all four arrow keys regardless of visual
// layout; this makes Left/Right vs Up/Down match `orientation` precisely, like shadcn's own Tabs
// does). Panel visibility itself stays untouched: CSS still switches panels purely off the real
// <input type="radio">s' native :checked state (see tabs.php's CSS contract) -- this module only
// ever manages ARIA/focus, never display.
//
// The radio inputs are hidden from the accessibility tree once JS is active (aria-hidden +
// tabindex="-1") and the visible <label>s become the real focusable, role="tab" elements instead --
// there is no valid ARIA role transformation from `radio` to `tab` on the input itself (see
// tabs.php's header comment), so the interactive element for AT purposes has to change, not just
// its role attribute. Clicking a label still natively forwards to its paired radio input (native
// for/id behaviour, untouched by aria-hidden/tabindex), so no click listener is needed here --
// only keyboard activation, since a plain <label> isn't natively interactive on its own.

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

    // Defensive fallback if the checked item happens to be disabled -- tabs.php's own active-value
    // fallback already avoids this in practice, but keeps the tablist tabbable either way.
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
