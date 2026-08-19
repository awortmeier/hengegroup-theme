import { defineConfig } from "vite";
import tailwindcss from "@tailwindcss/vite";
import path from "node:path";

export default defineConfig({
    base: "./",
    plugins: [tailwindcss()],
    build: {
        outDir: "dist/assets",
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                app: path.resolve(import.meta.dirname, "assets/js/app.js"),
                login: path.resolve(import.meta.dirname, "assets/js/login.js"),
            },
            output: {
                entryFileNames: "js/[name]-[hash].js",
                chunkFileNames: "js/[name]-[hash].js",
                assetFileNames: ({ name }) => {
                    if (name && name.endsWith(".css")) {
                        return "css/[name]-[hash][extname]";
                    }

                    if (name && /\.(woff2?|ttf|otf|eot)$/i.test(name)) {
                        return "fonts/[name]-[hash][extname]";
                    }

                    if (name && /\.(png|jpe?g|gif|svg|webp|avif)$/i.test(name)) {
                        return "images/[name]-[hash][extname]";
                    }

                    return "[name]-[hash][extname]";
                },
            },
        },
    },
});
