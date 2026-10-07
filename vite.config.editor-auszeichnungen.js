import { createEditorBlockConfig } from "./vite.config.editor.factory.js";

// Builds template-parts/blocks/auszeichnungen's editor script (assets/js/blocks/auszeichnungen/edit.jsx). See
// vite.config.editor.factory.js's header comment for the shared build config.
export default createEditorBlockConfig({
    entry: "assets/js/blocks/auszeichnungen/edit.jsx",
    fileName: "js/blocks/auszeichnungen-edit.js",
    name: "HengegroupThemeAuszeichnungenEditor",
});
