// jsdom has no layout: scrollIntoView() (used by select.js/combobox.js) is stubbed.
if (!Element.prototype.scrollIntoView) {
    Element.prototype.scrollIntoView = function scrollIntoView() {};
}
