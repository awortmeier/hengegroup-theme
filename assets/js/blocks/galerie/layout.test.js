import { describe, expect, it } from "vitest";
import { getBentoTileShapes } from "./layout.js";

const CELLS = { square: 1, wide: 2, tall: 2, full: 3 };

describe("getBentoTileShapes", () => {
    it("returns one shape per image", () => {
        for (let count = 0; count <= 25; count++) {
            expect(getBentoTileShapes(count)).toHaveLength(count);
        }
    });

    it("always fills complete rows of three columns (flush rectangle)", () => {
        for (let count = 1; count <= 25; count++) {
            const cells = getBentoTileShapes(count).reduce((sum, shape) => sum + CELLS[shape], 0);
            expect(cells % 3).toBe(0);
        }
    });

    it("uses the fixed 3x4 pattern for each full group of eight", () => {
        expect(getBentoTileShapes(9)).toEqual([
            "tall",
            "square",
            "square",
            "wide",
            "square",
            "square",
            "tall",
            "wide",
            "full",
        ]);
    });
});
