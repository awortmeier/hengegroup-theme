import { createEditorBlockConfig } from "./vite.config.editor.factory.js";

// Builds template-parts/blocks/galerie's editor script (assets/js/blocks/galerie/edit.jsx). See
// vite.config.editor.factory.js's header comment for the shared build config.
export default createEditorBlockConfig({
    entry: "assets/js/blocks/galerie/edit.jsx",
    fileName: "js/blocks/galerie-edit.js",
    name: "HengegroupThemeGalerieEditor",
});
