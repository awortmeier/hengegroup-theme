// Unit tests for enhanceToggle()/initToggle() (see toggle.js's header comment for the ARIA gap
// this closes). Builds the native checkbox/radio + <label> markup toggle.php renders directly in
// jsdom instead of importing template-parts/base/toggle/toggle.php -- rendering real PHP output
// isn't available in this JS-only test runner, see docs/to-do.md Abschnitt 1 for
// where a WP-backed integration suite would close that gap.
import { beforeEach, describe, expect, it, vi } from "vitest";
import { enhanceToggle, initToggle } from "./toggle.js";

function renderToggle({
    id = "toggle-1",
    type = "checkbox",
    checked = false,
    disabled = false,
} = {}) {
    document.body.innerHTML = `
        <input type="${type}" id="${id}" data-slot="toggle-input" ${checked ? "checked" : ""} ${disabled ? "disabled" : ""} />
        <label for="${id}" data-slot="toggle">Bold</label>
    `;

    return {
        input: document.getElementById(id),
        label: document.querySelector(`label[for="${id}"]`),
    };
}

beforeEach(() => {
    document.body.innerHTML = "";
});

describe("enhanceToggle", () => {
    it("hides the input from the accessibility tree and upgrades the label to role=button", () => {
        const { input, label } = renderToggle();

        enhanceToggle(input);

        expect(input.getAttribute("aria-hidden")).toBe("true");
        expect(input.tabIndex).toBe(-1);
        expect(label.getAttribute("role")).toBe("button");
        expect(label.getAttribute("aria-pressed")).toBe("false");
        expect(label.tabIndex).toBe(0);
        expect(label.dataset.js).toBe("toggle");
    });

    it("announces aria-pressed=true for an already-checked input", () => {
        const { input, label } = renderToggle({ checked: true });

        enhanceToggle(input);

        expect(label.getAttribute("aria-pressed")).toBe("true");
    });

    it("marks a disabled toggle as aria-disabled and removes it from tab order", () => {
        const { input, label } = renderToggle({ disabled: true });

        enhanceToggle(input);

        expect(label.getAttribute("aria-disabled")).toBe("true");
        expect(label.tabIndex).toBe(-1);
    });

    it("keeps aria-pressed in sync when the input's checked state changes", () => {
        const { input, label } = renderToggle();

        enhanceToggle(input);
        input.checked = true;
        input.dispatchEvent(new Event("change"));

        expect(label.getAttribute("aria-pressed")).toBe("true");
    });

    it("forwards Enter/Space keydowns on the label to a native click, but ignores other keys", () => {
        const { input, label } = renderToggle();

        enhanceToggle(input);
        const clickSpy = vi.spyOn(label, "click");

        label.dispatchEvent(new KeyboardEvent("keydown", { key: "a", bubbles: true }));
        expect(clickSpy).not.toHaveBeenCalled();

        label.dispatchEvent(
            new KeyboardEvent("keydown", { key: " ", bubbles: true, cancelable: true })
        );
        expect(clickSpy).toHaveBeenCalledTimes(1);

        label.dispatchEvent(
            new KeyboardEvent("keydown", { key: "Enter", bubbles: true, cancelable: true })
        );
        expect(clickSpy).toHaveBeenCalledTimes(2);
    });

    it("is idempotent: calling it twice on the same input registers listeners only once", () => {
        const { input, label } = renderToggle();

        enhanceToggle(input);
        const addEventListenerSpy = vi.spyOn(label, "addEventListener");
        enhanceToggle(input);

        expect(addEventListenerSpy).not.toHaveBeenCalled();
        expect(label.dataset.js).toBe("toggle");
    });

    it("skips a radio input that still sits inside a role=radiogroup wrapper", () => {
        document.body.innerHTML = `
            <div role="radiogroup">
                <input type="radio" id="toggle-radio" data-slot="toggle-input" />
                <label for="toggle-radio" data-slot="toggle">Bold</label>
            </div>
        `;
        const input = document.getElementById("toggle-radio");
        const label = document.querySelector('label[for="toggle-radio"]');

        enhanceToggle(input);

        expect(input.hasAttribute("aria-hidden")).toBe(false);
        expect(label.hasAttribute("role")).toBe(false);
    });

    it("does enhance a radio input once its radiogroup wrapper has been promoted to role=group", () => {
        // Mirrors what toggle-group.js does before calling enhanceToggle() itself for single-mode
        // items (see that module's header comment): promote the wrapper's role first, then enhance.
        document.body.innerHTML = `
            <div role="group">
                <input type="radio" id="toggle-radio" data-slot="toggle-input" />
                <label for="toggle-radio" data-slot="toggle">Bold</label>
            </div>
        `;
        const input = document.getElementById("toggle-radio");
        const label = document.querySelector('label[for="toggle-radio"]');

        enhanceToggle(input);

        expect(input.getAttribute("aria-hidden")).toBe("true");
        expect(label.getAttribute("role")).toBe("button");
    });

    it("ignores inputs that are neither checkbox nor radio", () => {
        document.body.innerHTML = `
            <input type="text" id="toggle-text" data-slot="toggle-input" />
            <label for="toggle-text" data-slot="toggle">Bold</label>
        `;
        const input = document.getElementById("toggle-text");
        const label = document.querySelector('label[for="toggle-text"]');

        enhanceToggle(input);

        expect(input.hasAttribute("aria-hidden")).toBe(false);
        expect(label.hasAttribute("role")).toBe(false);
    });
});

describe("initToggle", () => {
    it("enhances every [data-slot=toggle-input] present in the document", () => {
        document.body.innerHTML = `
            <input type="checkbox" id="a" data-slot="toggle-input" />
            <label for="a" data-slot="toggle">A</label>
            <input type="checkbox" id="b" data-slot="toggle-input" />
            <label for="b" data-slot="toggle">B</label>
        `;

        initToggle();

        expect(document.getElementById("a").getAttribute("aria-hidden")).toBe("true");
        expect(document.getElementById("b").getAttribute("aria-hidden")).toBe("true");
    });
});
