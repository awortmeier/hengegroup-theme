import { describe, expect, it } from "vitest";
import { initJobApplication, validateFileSizes } from "./job-application.js";

describe("validateFileSizes", () => {
    it("accepts files within the limit and names the first file that is too large", () => {
        const files = [
            { name: "lebenslauf.pdf", size: 1000 },
            { name: "zeugnis.pdf", size: 6 * 1024 * 1024 },
        ];

        expect(validateFileSizes(files.slice(0, 1), 5 * 1024 * 1024)).toBe("");
        expect(validateFileSizes(files, 5 * 1024 * 1024)).toContain("zeugnis.pdf");
    });
});

describe("initJobApplication", () => {
    it("blocks a second submit while the first one is running", () => {
        document.body.innerHTML = `
            <form data-application-form>
                <button type="submit" data-application-submit>Bewerbung senden</button>
            </form>`;
        initJobApplication();

        const form = document.querySelector("form");
        const first = new Event("submit", { cancelable: true });
        const second = new Event("submit", { cancelable: true });

        form.dispatchEvent(first);
        form.dispatchEvent(second);

        expect(first.defaultPrevented).toBe(false);
        expect(second.defaultPrevented).toBe(true);
        expect(form.querySelector("button").getAttribute("aria-busy")).toBe("true");
    });
});
