import { enhanceToggle } from "./toggle.js";

// Closes toggle-group.php's single-mode ARIA gap: role="radiogroup" becomes "group" and each item
// gets enhanceToggle() (role="button" aria-pressed). Native radios don't fire `change` on the item
// that gets unchecked, so all siblings' aria-pressed are re-synced on every change. Exclusivity
// itself stays native.
export function initToggleGroup() {
    document.querySelectorAll('[data-slot="toggle-group"][role="radiogroup"]').forEach((group) => {
        enhanceSingleModeGroup(group);
    });
}

function enhanceSingleModeGroup(group) {
    const inputs = Array.from(
        group.querySelectorAll('input[type="radio"][data-slot="toggle-input"]')
    );

    if (inputs.length === 0) {
        return;
    }

    // Must happen before enhanceToggle(), whose guard checks for role="radiogroup".
    group.setAttribute("role", "group");

    inputs.forEach((input) => {
        enhanceToggle(input);

        input.addEventListener("change", () => {
            resyncPressedState(inputs);
        });
    });
}

function resyncPressedState(inputs) {
    inputs.forEach((input) => {
        const label = input.labels && input.labels[0];

        if (label) {
            label.setAttribute("aria-pressed", input.checked ? "true" : "false");
        }
    });
}
