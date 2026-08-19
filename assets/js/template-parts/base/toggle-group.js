import { enhanceToggle } from "./toggle.js";

// Progressive-enhancement layer that closes template-parts/base/toggle/toggle-group.php's
// documented, deliberately-still-open `single`-mode ARIA gap (see that file's header comment for
// the full reasoning this module implements): shadcn's real ToggleGroup announces every item as
// "button, pressed"/"not pressed" regardless of selection mode, with JS -- not native radiogroup
// grouping -- managing the "choose one" exclusivity. The zero-JS baseline instead announces
// `single`-mode items as "radio button" inside a "radio group" (real <input type="radio"> sharing
// one `name`) -- a defensible fallback per that file's reasoning, but not what shadcn itself
// announces. This module is the "real, larger undertaking, closer in scope to select.php's full
// rebuild" that header comment flags as not yet built.
//
// What actually changes: the wrapper's role goes from "radiogroup" to "group", and each item goes
// from the native role="radio" (via toggle.php's own `<label>`, still un-upgraded at this point) to
// role="button" aria-pressed -- reusing toggle.js's own enhanceToggle() rather than duplicating its
// hide-native/relabel-paired-label logic. That reuse is why this has
// to do one thing enhanceToggle() alone can't: native radio-button exclusivity does NOT fire a
// "change" event on the sibling that silently becomes unchecked when another one in the same
// `name` group is checked, so a naive per-item enhanceToggle() call would leave the
// previously-selected item's aria-pressed stuck at "true" once another item is picked. This module
// additionally re-syncs every sibling's aria-pressed whenever any single one of them changes,
// restoring the "only one pressed at a time" announcement a toggle-button group needs.
//
// The underlying native radio inputs are untouched otherwise (same shared `name`, only hidden from
// the accessibility tree by enhanceToggle() itself) -- actual "choose one" enforcement keeps coming
// from the browser's native radio-exclusivity behaviour, not reimplemented here; only the
// *announced* ARIA role/state changes, same "swap the accessible surface, keep the working native
// state underneath" idea as toggle.js's own upgrade and calendar.js's reuse of it.
//
// Registered before initToggle() in assets/js/app.js so that, by the time that sweep reaches these
// same inputs, role="group" is already in place and its own radiogroup-skip guard no longer applies
// to them -- but enhanceToggle()'s "already enhanced" guard (see toggle.js) makes this safe
// regardless of actual call order, not dependent on it.
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

    // Drop radiogroup semantics first -- enhanceToggle()'s own guard checks
    // input.closest('[role="radiogroup"]') at call time, so this has to happen before the loop
    // below, not after.
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
