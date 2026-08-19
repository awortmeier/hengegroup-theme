// Progressive-enhancement layer for template-parts/base/popover.php: closes the honest zero-JS
// gaps documented in that file's header comment -- moving focus into the panel on open (and back
// to the trigger on an Escape-close), plus outside-click/Escape to dismiss (mirrors
// dropdown-menu.js/date-picker.js). Native <details>/<summary> already provides click-to-toggle
// and keeps `content` in normal tab order without any of this.

const FOCUSABLE_SELECTOR =
    "a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), " +
    'textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

function getFirstFocusable(content) {
    return content.querySelector(FOCUSABLE_SELECTOR);
}

function setupPopover(wrapper) {
    const trigger = wrapper.querySelector('[data-slot="popover-trigger"]');
    const content = wrapper.querySelector('[data-slot="popover-content"]');

    if (!trigger || !content) {
        return;
    }

    const close = ({ focusTrigger = false } = {}) => {
        wrapper.removeAttribute("open");

        if (focusTrigger) {
            trigger.focus();
        }
    };

    // Native <details> fires `toggle` on both the open and the close transition -- same event
    // dropdown-menu.js already relies on for its own roving-tabindex setup.
    wrapper.addEventListener("toggle", () => {
        if (!wrapper.open) {
            return;
        }

        // WAI-ARIA dialog pattern: initial focus goes inside the panel, falling back to the panel
        // itself (its own tabindex="-1", see popover.php) when there's nothing focusable in it.
        (getFirstFocusable(content) || content).focus();
    });

    wrapper.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && wrapper.open) {
            event.preventDefault();
            close({ focusTrigger: true });
        }
    });

    document.addEventListener("click", (event) => {
        if (wrapper.open && !wrapper.contains(event.target)) {
            close();
        }
    });

    wrapper.setAttribute("data-js", "popover");
}

export function initPopover() {
    document.querySelectorAll('[data-slot="popover"]').forEach((wrapper) => {
        setupPopover(wrapper);
    });
}
