// tooltip.php: styled panel on hover/focus after a delay, hidden on leave/blur/Escape, repositioned
// on scroll/resize. Positioning in utils/floating-position.js.

import { positionFloatingElement } from "../../utils/floating-position.js";

// Matches tooltip.php's calc(100%+10px).
const GAP = 10;

function setupTooltip(wrapper) {
    const trigger = wrapper.querySelector('[data-slot="tooltip-trigger"]');
    const content = wrapper.querySelector('[data-slot="tooltip-content"]');

    if (!trigger || !content) {
        return;
    }

    const side = wrapper.dataset.side || "top";
    const align = wrapper.dataset.align || "center";
    const delay = Number(wrapper.dataset.delay) || 700;
    let showTimer = null;

    const reposition = () => {
        wrapper.dataset.side = positionFloatingElement(trigger, content, side, {
            gap: GAP,
            align,
        });
    };

    const open = () => {
        wrapper.dataset.state = "open";
        reposition();
    };

    const close = () => {
        window.clearTimeout(showTimer);
        wrapper.dataset.state = "closed";
    };

    const scheduleOpen = () => {
        window.clearTimeout(showTimer);
        showTimer = window.setTimeout(open, delay);
    };

    wrapper.addEventListener("mouseenter", scheduleOpen);
    wrapper.addEventListener("mouseleave", close);
    wrapper.addEventListener("focusin", scheduleOpen);
    wrapper.addEventListener("focusout", close);

    wrapper.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && wrapper.dataset.state === "open") {
            close();
        }
    });

    window.addEventListener(
        "scroll",
        () => {
            if (wrapper.dataset.state === "open") {
                reposition();
            }
        },
        { passive: true, capture: true }
    );

    window.addEventListener("resize", () => {
        if (wrapper.dataset.state === "open") {
            reposition();
        }
    });

    wrapper.removeAttribute("title");
    wrapper.dataset.js = "tooltip";
}

export function initTooltip() {
    document.querySelectorAll('[data-slot="tooltip"]').forEach((wrapper) => {
        setupTooltip(wrapper);
    });
}
