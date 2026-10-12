import { defineConfig } from "vite";
import path from "node:path";

// One IIFE per block against WordPress' `wp.*` globals. Separate config per block because lib-mode
// iife output allows only one entry. `emptyOutDir: false`: vite.config.js runs first and owns dist/assets.
export function createEditorBlockConfig({ entry, fileName, name }) {
    return defineConfig({
        base: "./",
        // edit.jsx files import `createElement as el` from @wordpress/element instead of React.
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
