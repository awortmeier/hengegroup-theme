// Unit tests for initSelect() (see select.js's header comment for the "select-only combobox" WAI-
// ARIA pattern it builds on top of the native <select> in template-parts/base/select.php). Builds
// the native-select + trigger/content markup select.php renders directly in jsdom instead of
// importing the PHP template -- rendering real PHP output isn't available in this JS-only test
// runner, see docs/to-do.md Abschnitt 1 for where a WP-backed integration suite
// would close that gap.
import { beforeEach, describe, expect, it } from "vitest";
import { initSelect } from "./select.js";

function click(el) {
    el.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true, button: 0 }));
}

function key(el, key) {
    el.dispatchEvent(new KeyboardEvent("keydown", { key, bubbles: true, cancelable: true }));
}

function renderSelect() {
    document.body.innerHTML = `
        <div data-slot="select">
            <select data-slot="native-select" id="fruit">
                <option value="apple">Apple</option>
                <optgroup label="Citrus">
                    <option value="lemon">Lemon</option>
                    <option value="lime" disabled>Lime</option>
                </optgroup>
                <option value="mango" selected>Mango</option>
            </select>
            <button type="button" data-slot="select-trigger" aria-expanded="false">
                <span data-slot="select-value"></span>
            </button>
            <div data-slot="select-content" id="fruit-content" role="listbox" hidden></div>
            <template data-slot="select-item-indicator-template"><span class="check"></span></template>
        </div>
    `;

    return {
        wrapper: document.querySelector('[data-slot="select"]'),
        nativeSelect: document.getElementById("fruit"),
        trigger: document.querySelector('[data-slot="select-trigger"]'),
        content: document.querySelector('[data-slot="select-content"]'),
        valueEl: document.querySelector('[data-slot="select-value"]'),
    };
}

beforeEach(() => {
    document.body.innerHTML = "";
});

describe("initSelect", () => {
    it("builds one listbox option per native <option>, grouping by <optgroup>, and marks itself initialized", () => {
        const { wrapper, content } = renderSelect();

        initSelect();

        expect(content.querySelectorAll('[role="option"]')).toHaveLength(4);
        expect(content.querySelector('[role="group"] [data-slot="select-label"]').textContent).toBe(
            "Citrus"
        );
        expect(wrapper.getAttribute("data-js")).toBe("select");
    });

    it("marks the initially selected option as aria-selected and a disabled option as aria-disabled", () => {
        const { content } = renderSelect();

        initSelect();

        expect(content.querySelector('[data-value="mango"]').getAttribute("aria-selected")).toBe(
            "true"
        );
        expect(content.querySelector('[data-value="lime"]').getAttribute("aria-disabled")).toBe(
            "true"
        );
    });

    it("opens the listbox on trigger click and activates the currently selected item", () => {
        const { trigger, content } = renderSelect();
        initSelect();

        click(trigger);

        expect(trigger.getAttribute("aria-expanded")).toBe("true");
        expect(content.hidden).toBe(false);
        const mango = content.querySelector('[data-value="mango"]');
        expect(trigger.getAttribute("aria-activedescendant")).toBe(mango.id);
        expect(mango.getAttribute("data-active")).toBe("true");
    });

    it("closes the listbox on a second trigger click", () => {
        const { trigger, content } = renderSelect();
        initSelect();

        click(trigger);
        click(trigger);

        expect(trigger.getAttribute("aria-expanded")).toBe("false");
        expect(content.hidden).toBe(true);
    });

    it("does not open when the trigger is disabled", () => {
        const { trigger, content } = renderSelect();
        trigger.disabled = true;
        initSelect();

        click(trigger);

        expect(trigger.getAttribute("aria-expanded")).toBe("false");
        expect(content.hidden).toBe(true);
    });

    it("moves the active item with Home and ArrowDown, skipping a disabled option", () => {
        const { trigger, content } = renderSelect();
        initSelect();

        click(trigger);
        key(trigger, "Home");
        expect(content.querySelector('[data-value="apple"]').getAttribute("data-active")).toBe(
            "true"
        );

        key(trigger, "ArrowDown");
        expect(content.querySelector('[data-value="lemon"]').getAttribute("data-active")).toBe(
            "true"
        );

        // lime is disabled and gets skipped, landing straight on mango
        key(trigger, "ArrowDown");
        expect(content.querySelector('[data-value="mango"]').getAttribute("data-active")).toBe(
            "true"
        );
    });

    it("selects the active item on Enter, updates the native select and the visible value, and closes", () => {
        const { trigger, content, nativeSelect, valueEl } = renderSelect();
        initSelect();

        click(trigger);
        key(trigger, "Home");
        key(trigger, "Enter");

        expect(nativeSelect.value).toBe("apple");
        expect(valueEl.textContent).toBe("Apple");
        expect(trigger.getAttribute("aria-expanded")).toBe("false");
        expect(content.hidden).toBe(true);
    });

    it("selects an item via click and moves focus back to the trigger", () => {
        const { trigger, content, nativeSelect } = renderSelect();
        initSelect();

        click(trigger);
        click(content.querySelector('[data-value="lemon"]'));

        expect(nativeSelect.value).toBe("lemon");
        expect(document.activeElement).toBe(trigger);
    });

    it("ignores clicks on a disabled item", () => {
        const { trigger, content, nativeSelect } = renderSelect();
        initSelect();

        click(trigger);
        click(content.querySelector('[data-value="lime"]'));

        expect(nativeSelect.value).not.toBe("lime");
    });

    it("closes on Escape", () => {
        const { trigger, content } = renderSelect();
        initSelect();

        click(trigger);
        key(trigger, "Escape");

        expect(trigger.getAttribute("aria-expanded")).toBe("false");
        expect(content.hidden).toBe(true);
    });

    it("closes when clicking outside the wrapper", () => {
        const { trigger, content } = renderSelect();
        initSelect();

        click(trigger);
        click(document.body);

        expect(content.hidden).toBe(true);
    });

    it("resyncs the custom listbox and visible value when the native select changes from outside this module", () => {
        const { nativeSelect, valueEl, content } = renderSelect();
        initSelect();

        nativeSelect.value = "lemon";
        nativeSelect.dispatchEvent(new Event("change", { bubbles: true }));

        expect(valueEl.textContent).toBe("Lemon");
        expect(content.querySelector('[data-value="lemon"]').getAttribute("aria-selected")).toBe(
            "true"
        );
    });

    it("redirects focus from the native select to the visible trigger", () => {
        const { nativeSelect, trigger } = renderSelect();
        initSelect();

        nativeSelect.dispatchEvent(new Event("focus"));

        expect(document.activeElement).toBe(trigger);
    });
});
