// popover.php: moves focus into the panel on open (back to the trigger on Escape), closes on
// outside click/Escape.

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

    wrapper.addEventListener("toggle", () => {
        if (!wrapper.open) {
            return;
        }

        // Dialog pattern: focus inside, falling back to the panel itself (tabindex="-1").
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
