// Progressive-enhancement layer for template-parts/base/tooltip.php: enhances the native
// `title`-attribute fallback into a styled, positioned floating panel. Shows on hover/focus after
// a delay, hides on mouseleave/blur/Escape, repositions on scroll/resize while open. Positioning
// math (single-axis flip, viewport clamp) lives in utils/floating-position.js -- shared with
// hover-card.js, which needs the exact same anchored-panel placement (see that util's header
// comment).

import { positionFloatingElement } from "../../utils/floating-position.js";

const GAP = 8;

function setupTooltip(wrapper) {
    const trigger = wrapper.querySelector('[data-slot="tooltip-trigger"]');
    const content = wrapper.querySelector('[data-slot="tooltip-content"]');

    if (!trigger || !content) {
        return;
    }

    const side = wrapper.dataset.side || "top";
    const delay = Number(wrapper.dataset.delay) || 700;
    let showTimer = null;

    const reposition = () => {
        wrapper.dataset.side = positionFloatingElement(trigger, content, side, { gap: GAP });
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

    // Once JS is driving the styled panel, the native title tooltip would just duplicate it.
    wrapper.removeAttribute("title");
    wrapper.dataset.js = "tooltip";
}

export function initTooltip() {
    document.querySelectorAll('[data-slot="tooltip"]').forEach((wrapper) => {
        setupTooltip(wrapper);
    });
}
