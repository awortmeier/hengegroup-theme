// Progressive-enhancement layer for template-parts/base/toggle/toggle.php: upgrades the native
// checkbox/radio-based zero-JS baseline (see that file's header comment) to shadcn's own
// role="button" aria-pressed="true|false" announcement. The distinguishing factor for whether an
// input qualifies at the time this runs is NOT its native `type` -- it's whether it's still part of
// a `role="radiogroup"` composition. `type: 'radio'` toggles inside a `role="radiogroup"` exist
// purely for toggle-group.php's `single` mode; that composition's own JS module,
// toggle-group.js, is what promotes the wrapper's role from "radiogroup" to "group" (its
// documented, previously-open ARIA gap, see that file's header comment) and calls enhanceToggle()
// below directly once that promotion has happened -- so by the time an input's guard here would be
// checked, it's either already been promoted+enhanced (idempotency guard below makes that a no-op)
// or genuinely still belongs to an as-yet-unhandled radiogroup and gets correctly skipped. Every
// other checkbox/radio-typed `[data-slot="toggle-input"]` -- standalone toggle.php
// (`type: 'checkbox'`), toggle-group.php's own `multiple` mode (`type: 'checkbox'`, wrapped in
// plain `role="group"`, no radiogroup constraint), and calendar.php's per-day toggle.php nesting in
// EITHER mode (no radiogroup wrapper around a calendar grid at all, see that file's header comment)
// -- gets upgraded directly by the sweep at the bottom of this file, matching shadcn's own
// Calendar/ToggleGroup day-button/item announcements too, not just standalone Toggle.
//
// The input is hidden from the accessibility tree once JS is active (aria-hidden +
// tabindex="-1") and the visible <label> becomes the real focusable, role="button" element instead
// -- there is no valid ARIA role transformation from `checkbox`/`radio` to `button` on the input
// itself (see toggle.php's header comment), so the interactive element for AT purposes has to
// change, not just its role attribute. Clicking the label still natively forwards to its paired
// input (native for/id behaviour, untouched by aria-hidden/tabindex), so no click listener is
// needed here -- only keyboard activation, since a plain <label> isn't natively interactive on its
// own.
//
// Exported (not just used via initToggle()) so calendar.js can call it directly on the day cells
// it creates client-side during month navigation -- those never go through the DOMContentLoaded
// sweep below, so calendar.js re-applies this same enhancement to each cell right after creating
// it, same "real public JS API" reasoning as toast.js's exported toast(). Also
// called directly by toggle-group.js for `single`-mode items, once it has promoted their wrapper
// from role="radiogroup" to role="group" (see that module) -- the radiogroup-skip guard below no
// longer applies to those once promoted, and the "already enhanced" guard makes calling this twice
// on the same input (regardless of which module runs first) a safe no-op either way.
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

        // A plain <label> doesn't natively activate its control on Enter/Space (unlike a real
        // <button>) -- label.click() re-triggers the same native for/id click-forwarding a mouse
        // click already relies on, so the input toggles and its own "change" listener below keeps
        // aria-pressed in sync.
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
