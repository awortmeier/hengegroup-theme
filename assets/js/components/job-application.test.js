import { describe, expect, it } from "vitest";
import { validateFileSizes } from "./job-application.js";

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
