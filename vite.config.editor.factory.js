import { defineConfig } from "vite";
import path from "node:path";

// Shared build config for every template-parts/blocks/<name>/'s editor script -- one classic,
// non-module IIFE per block, built against WordPress' OWN `wp.*` globals instead of bundling React
// again (see docs/entscheidungen.md's "Phase-3-Block-Architektur" entry). Factored out once a
// SECOND block needed it (`ueberschrift-text`, alongside `buehne`) instead of speculatively upfront
// -- each block still gets its OWN thin `vite.config.editor-<name>.js` file calling this factory
// with its own entry/fileName/global name, and its own `vite build --config ...` call in
// `package.json`'s `build:assets`, because Rollup/Vite's iife/umd lib-mode output does not support
// multiple entry points in a single build (tried as one shared array-of-configs file first, same
// limitation that already ruled out merging this with the main `vite.config.js`, see that decision's
// own entry).
// `emptyOutDir: false` is load-bearing: every block's build shares `dist/assets` with
// `vite.config.js`'s own (non-editor) build, which must run FIRST and is the only one allowed to
// empty that directory.
export function createEditorBlockConfig({ entry, fileName, name }) {
    return defineConfig({
        base: "./",
        // esbuild's JSX pragma: every block's edit.jsx imports `createElement as el`/`Fragment`
        // from the externalized `@wordpress/element` (see `external`/`globals` below) instead of
        // React, so compiled JSX must call THAT `el`, not the default `React.createElement`.
        esbuild: {
            jsx: "transform",
            jsxFactory: "el",
            jsxFragment: "Fragment",
        },
        build: {
            outDir: "dist/assets",
            emptyOutDir: false,
            lib: {
                entry: path.resolve(import.meta.dirname, entry),
                formats: ["iife"],
                name,
                fileName: () => fileName,
            },
            rollupOptions: {
                external: [
                    "@wordpress/blocks",
                    "@wordpress/element",
                    "@wordpress/block-editor",
                    "@wordpress/components",
                    "@wordpress/data",
                    "@wordpress/dom-ready",
                    "@wordpress/hooks",
                    "@wordpress/html-entities",
                    "@wordpress/i18n",
                    "@wordpress/rich-text",
                    "@wordpress/server-side-render",
                ],
                output: {
                    globals: {
                        "@wordpress/blocks": "wp.blocks",
                        "@wordpress/element": "wp.element",
                        "@wordpress/block-editor": "wp.blockEditor",
                        "@wordpress/components": "wp.components",
                        "@wordpress/data": "wp.data",
                        "@wordpress/dom-ready": "wp.domReady",
                        "@wordpress/hooks": "wp.hooks",
                        "@wordpress/html-entities": "wp.htmlEntities",
                        "@wordpress/i18n": "wp.i18n",
                        "@wordpress/rich-text": "wp.richText",
                        "@wordpress/server-side-render": "wp.serverSideRender",
                    },
                },
            },
        },
    });
}
