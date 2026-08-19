// Unit tests for initCombobox() (see combobox.js's header comment for the WAI-ARIA "combobox with
// listbox popup" pattern it applies on top of the native <input list="..."> + <datalist> baseline
// in template-parts/base/combobox.php). Builds that native-input + datalist + content markup
// directly in jsdom instead of importing the PHP template -- rendering real PHP output isn't
// available in this JS-only test runner, see docs/to-do.md Abschnitt 1 for where
// a WP-backed integration suite would close that gap.
import { beforeEach, describe, expect, it } from "vitest";
import { initCombobox } from "./combobox.js";

function key(el, key) {
    el.dispatchEvent(new KeyboardEvent("keydown", { key, bubbles: true, cancelable: true }));
}

function renderCombobox({ value = "" } = {}) {
    document.body.innerHTML = `
        <div data-slot="combobox">
            <input type="text" id="fruit" data-slot="combobox-input" list="fruit-list" value="${value}" />
            <datalist id="fruit-list">
                <option value="Apple" data-value="apple"></option>
                <option value="Banana" data-value="banana" data-group="Yellow"></option>
                <option value="Lemon" data-value="lemon" data-group="Yellow" data-disabled="true"></option>
                <option value="Cherry" data-value="cherry"></option>
            </datalist>
            <div data-slot="combobox-content" id="fruit-content" role="listbox" hidden></div>
            <template data-slot="combobox-item-indicator-template"><span class="check"></span></template>
            <template data-slot="combobox-empty-template"><span>No results</span></template>
        </div>
    `;

    return {
        input: document.getElementById("fruit"),
        content: document.querySelector('[data-slot="combobox-content"]'),
    };
}

beforeEach(() => {
    document.body.innerHTML = "";
});

describe("initCombobox", () => {
    it("applies the combobox ARIA contract, resolves the initial selected value, and detaches the datalist", () => {
        const { input, content } = renderCombobox({ value: "Apple" });

        initCombobox();

        expect(input.getAttribute("role")).toBe("combobox");
        expect(input.getAttribute("aria-autocomplete")).toBe("list");
        expect(input.getAttribute("aria-controls")).toBe(content.id);
        expect(input.dataset.selectedValue).toBe("apple");
        expect(input.hasAttribute("list")).toBe(false);
    });

    it("opens on focus and groups matches by data-group", () => {
        const { input, content } = renderCombobox();
        initCombobox();

        input.focus();

        expect(input.getAttribute("aria-expanded")).toBe("true");
        expect(content.hidden).toBe(false);
        expect(content.querySelectorAll('[role="option"]')).toHaveLength(4);
        const group = content.querySelector('[role="group"]');
        expect(group.querySelector('[data-slot="combobox-label"]').textContent).toBe("Yellow");
        expect(group.querySelectorAll('[role="option"]')).toHaveLength(2);
    });

    it("filters items as the user types", () => {
        const { input, content } = renderCombobox();
        initCombobox();

        input.focus();
        input.value = "ch";
        input.dispatchEvent(new Event("input"));

        const items = content.querySelectorAll('[role="option"]');
        expect(items).toHaveLength(1);
        expect(items[0].dataset.value).toBe("cherry");
    });

    it("shows the empty template when nothing matches", () => {
        const { input, content } = renderCombobox();
        initCombobox();

        input.focus();
        input.value = "zzz";
        input.dispatchEvent(new Event("input"));

        expect(content.querySelectorAll('[role="option"]')).toHaveLength(0);
        expect(content.querySelector('[data-slot="combobox-empty"]')).not.toBeNull();
    });

    it("clears the previously selected value once the user types a free-form value", () => {
        const { input } = renderCombobox({ value: "Apple" });
        initCombobox();
        expect(input.dataset.selectedValue).toBe("apple");

        input.value = "Apple pie";
        input.dispatchEvent(new Event("input"));

        expect(input.dataset.selectedValue).toBe("");
    });

    it("moves the active item with ArrowDown, skipping a disabled option", () => {
        const { input, content } = renderCombobox();
        initCombobox();

        input.focus();
        expect(content.querySelector('[data-value="apple"]').hasAttribute("data-active")).toBe(
            true
        );

        key(input, "ArrowDown");
        expect(content.querySelector('[data-value="banana"]').hasAttribute("data-active")).toBe(
            true
        );

        // lemon is disabled and gets skipped, landing straight on cherry
        key(input, "ArrowDown");
        expect(content.querySelector('[data-value="cherry"]').hasAttribute("data-active")).toBe(
            true
        );
    });

    it("selects the active item on Enter and syncs the input's value/selected-value", () => {
        const { input } = renderCombobox();
        initCombobox();

        input.focus();
        key(input, "ArrowDown");
        key(input, "Enter");

        expect(input.value).toBe("Banana");
        expect(input.dataset.selectedValue).toBe("banana");
        expect(input.getAttribute("aria-expanded")).toBe("false");
    });

    it("selects an item via click and keeps focus on the input", () => {
        const { input, content } = renderCombobox();
        initCombobox();

        input.focus();
        content
            .querySelector('[data-value="cherry"]')
            .dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true }));

        expect(input.value).toBe("Cherry");
        expect(document.activeElement).toBe(input);
    });

    it("closes on Escape", () => {
        const { input, content } = renderCombobox();
        initCombobox();

        input.focus();
        key(input, "Escape");

        expect(input.getAttribute("aria-expanded")).toBe("false");
        expect(content.hidden).toBe(true);
    });

    it("closes when clicking outside the wrapper", () => {
        const { input, content } = renderCombobox();
        initCombobox();

        input.focus();
        document.body.dispatchEvent(new MouseEvent("click", { bubbles: true }));

        expect(content.hidden).toBe(true);
    });
});
