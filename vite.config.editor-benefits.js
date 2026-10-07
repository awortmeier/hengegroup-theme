import { createEditorBlockConfig } from "./vite.config.editor.factory.js";

// Builds template-parts/blocks/benefits's editor script (assets/js/blocks/benefits/edit.jsx). See
// vite.config.editor.factory.js's header comment for the shared build config.
export default createEditorBlockConfig({
    entry: "assets/js/blocks/benefits/edit.jsx",
    fileName: "js/blocks/benefits-edit.js",
    name: "HengegroupThemeBenefitsEditor",
});
