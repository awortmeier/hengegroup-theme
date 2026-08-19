// Separate from vite.config.js on purpose: that file configures the production asset *build*
// (Tailwind plugin, multi-entry rollupOptions, dist/ output) -- none of which the JS unit suite
// needs, so a dedicated minimal config avoids coupling test runs to build-only settings. See
// docs/to-do.md Abschnitt 1 for the wider testing roadmap this is one piece of.
import { defineConfig } from "vitest/config";

export default defineConfig({
    test: {
        environment: "jsdom",
        include: ["assets/js/**/*.test.js"],
        setupFiles: ["assets/js/test-setup.js"],
    },
});
