// Global jsdom polyfills shared by every Vitest suite under assets/js/ (see vitest.config.js's
// `setupFiles`). jsdom deliberately doesn't implement layout, so APIs that depend on it are simply
// missing -- select.js/combobox.js both call scrollIntoView() to keep the active listbox item in
// view, which is exactly this case. A no-op stub is enough here: these unit tests assert on
// ARIA/data-* state, not actual scroll position.
if (!Element.prototype.scrollIntoView) {
    Element.prototype.scrollIntoView = function scrollIntoView() {};
}
