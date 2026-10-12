// Upgrades toggle.php's hidden checkbox/radio to role="button" aria-pressed on the label (input gets
// aria-hidden + tabindex="-1"). Radios inside a role="radiogroup" are skipped (toggle-group.js
// handles them). Exported for calendar.js (cells created later) and toggle-group.js; idempotent.
export function enhanceToggle(input) {
    if (input.type !== "checkbox" && input.type !== "radio") {
        return;
    }

    if (input.type === "radio" && input.closest('[role="radiogroup"]')) {
        return;
    }

    const label = input.labels && input.labels[0];

    if (!label || label.dataset.js === "toggle") {
        return;
    }

    input.setAttribute("aria-hidden", "true");
    input.tabIndex = -1;

    label.setAttribute("role", "button");
    label.setAttribute("aria-pressed", input.checked ? "true" : "false");
    label.tabIndex = input.disabled ? -1 : 0;

    if (input.disabled) {
        label.setAttribute("aria-disabled", "true");
    }

    label.addEventListener("keydown", (event) => {
        if (event.key !== "Enter" && event.key !== " ") {
            return;
        }

        // A <label> doesn't activate on Enter/Space; label.click() forwards to the input natively.
        event.preventDefault();
        label.click();
    });

    input.addEventListener("change", () => {
        label.setAttribute("aria-pressed", input.checked ? "true" : "false");
    });

    label.setAttribute("data-js", "toggle");
}

export function initToggle() {
    document.querySelectorAll('[data-slot="toggle-input"]').forEach((input) => {
        enhanceToggle(input);
    });
}
