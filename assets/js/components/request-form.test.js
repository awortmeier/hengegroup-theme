import { describe, expect, it } from "vitest";
import { initRequestForm } from "./request-form.js";

describe("initRequestForm", () => {
    it("blocks a second submit while the first one is running", () => {
        document.body.innerHTML = `
            <form data-slot="request-form">
                <button type="submit" data-request-form-submit>Senden</button>
            </form>`;
        initRequestForm();

        const form = document.querySelector("form");
        const first = new Event("submit", { cancelable: true });
        const second = new Event("submit", { cancelable: true });

        form.dispatchEvent(first);
        form.dispatchEvent(second);

        expect(first.defaultPrevented).toBe(false);
        expect(second.defaultPrevented).toBe(true);
        expect(form.querySelector("button").getAttribute("aria-busy")).toBe("true");
    });

    it("focuses the status banner", () => {
        document.body.innerHTML = `<div data-slot="request-form-status" tabindex="-1">Danke</div>`;
        initRequestForm();

        expect(document.activeElement).toBe(document.querySelector("[data-slot]"));
    });
});
