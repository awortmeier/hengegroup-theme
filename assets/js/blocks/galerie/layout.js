// Same logic as hengegroup_theme_get_bento_tile_shapes() (helpers.php), for the editor preview.
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

// Literal classes (Tailwind scanner), identical to galerie/render.php.
export const TILE_CLASSNAMES = {
    square: "aspect-[4/3] sm:aspect-square",
    wide: "aspect-[4/3] sm:col-span-2 sm:aspect-[2/1]",
    tall: "aspect-[4/3] sm:row-span-2 sm:aspect-auto",
    full: "aspect-[4/3] sm:col-span-3 sm:aspect-[3/1]",
};
