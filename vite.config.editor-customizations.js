import { createEditorBlockConfig } from "./vite.config.editor.factory.js";

// Builds assets/js/editor/editor-customizations.js -- einziger Unterschied zu den block-eigenen
// vite.config.editor-<name>.js-Dateien (siehe deren Kopfkommentare): der Entry ist kein einzelnes
// block.json's editorScript, sondern ein block-editor-weites Script (siehe
// editor-customizations.js's Kopfkommentar). Nutzt dieselbe Factory, weil der Build (externes IIFE
// gegen wp.*-Globals) identisch ist.
export default createEditorBlockConfig({
    entry: "assets/js/editor/editor-customizations.js",
    fileName: "js/editor/editor-customizations.js",
    name: "HengegroupThemeEditorCustomizations",
});
