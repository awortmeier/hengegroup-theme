import { createEditorBlockConfig } from "./vite.config.editor.factory.js";

// Builds template-parts/blocks/karriere-teaser's editor script (assets/js/blocks/karriere-teaser/
// edit.jsx, also registers the hidden karriere-teaser-liste preview block). See
// vite.config.editor.factory.js's header comment for the shared build config.
export default createEditorBlockConfig({
    entry: "assets/js/blocks/karriere-teaser/edit.jsx",
    fileName: "js/blocks/karriere-teaser-edit.js",
    name: "HengegroupThemeKarriereTeaserEditor",
});
