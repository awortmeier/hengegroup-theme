import { createEditorBlockConfig } from "./vite.config.editor.factory.js";

// Builds template-parts/blocks/produktkategorie's editor script (assets/js/blocks/produktkategorie/edit.jsx). See
// vite.config.editor.factory.js's header comment for the shared build config/why each block gets
// its own config file + `vite build --config ...` call.
export default createEditorBlockConfig({
    entry: "assets/js/blocks/produktkategorie/edit.jsx",
    fileName: "js/blocks/produktkategorie-edit.js",
    name: "HengegroupThemeProduktkategorieEditor",
});
