import { createEditorBlockConfig } from "./vite.config.editor.factory.js";

// Builds template-parts/blocks/stellen-liste's editor script (assets/js/blocks/stellen-liste/
// edit.jsx). See vite.config.editor.factory.js's header comment for the shared build config.
export default createEditorBlockConfig({
    entry: "assets/js/blocks/stellen-liste/edit.jsx",
    fileName: "js/blocks/stellen-liste-edit.js",
    name: "HengegroupThemeStellenListeEditor",
});
