// Kachelformen des Galerie-Bento-Rasters -- 1:1 dieselbe Logik wie
// hengegroup_theme_get_bento_tile_shapes() in inc/template-parts/helpers.php (siehe dort fuer die
// Begruendung), hier fuer die Editor-Vorschau. Beide Seiten sind getestet (layout.test.js bzw.
// tests/Unit/HelpersTest.php), damit sie nicht auseinanderlaufen.
const BLOCK = ["tall", "square", "square", "wide", "square", "square", "tall", "wide"];
const TAILS = [
    [],
    ["full"],
    ["wide", "square"],
    ["square", "square", "square"],
    ["tall", "square", "square", "wide"],
    ["tall", "square", "square", "wide", "full"],
    ["tall", "square", "square", "wide", "wide", "square"],
    ["tall", "square", "square", "wide", "square", "square", "square"],
];

export function getBentoTileShapes(count) {
    if (count <= 0) {
        return [];
    }

    const shapes = [];

    for (let i = 0; i < Math.floor(count / 8); i++) {
        shapes.push(...BLOCK);
    }

    return [...shapes, ...TAILS[count % 8]];
}

// Literale Klassen je Form (Tailwind muss sie im Quelltext finden) -- identisch zu
// template-parts/blocks/galerie/render.php. Mobil eine Spalte im 4:3-Format, ab `sm` das Bento.
export const TILE_CLASSNAMES = {
    square: "aspect-[4/3] sm:aspect-square",
    wide: "aspect-[4/3] sm:col-span-2 sm:aspect-[2/1]",
    tall: "aspect-[4/3] sm:row-span-2 sm:aspect-auto",
    full: "aspect-[4/3] sm:col-span-3 sm:aspect-[3/1]",
};
