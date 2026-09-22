import { createEditorBlockConfig } from "./vite.config.editor.factory.js";

// Builds template-parts/blocks/buehne's editor script (assets/js/blocks/buehne/edit.jsx). See
// vite.config.editor.factory.js's header comment for the shared build config/why each block gets
// its own config file + `vite build --config ...` call, and edit.jsx's header comment +
// docs/entscheidungen.md's "Phase-3-Block-Architektur" for why this is a plain Vite entry instead
// of `@wordpress/scripts`/webpack as a second toolchain.
export default createEditorBlockConfig({
    entry: "assets/js/blocks/buehne/edit.jsx",
    fileName: "js/blocks/buehne-edit.js",
    name: "HengegroupThemeBuehneEditor",
});
