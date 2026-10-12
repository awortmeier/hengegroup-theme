// hover-card.php: styled panel with separate open/close delays. Trigger and content are listened to
// separately so the pointer can cross the gap without closing the panel.

import { positionFloatingElement } from "../../utils/floating-position.js";

function setupHoverCard(wrapper) {
    const trigger = wrapper.querySelector('[data-slot="hover-card-trigger"]');
    const content = wrapper.querySelector('[data-slot="hover-card-content"]');

    if (!trigger || !content) {
        return;
    }

    const side = wrapper.dataset.side || "bottom";
    const align = wrapper.dataset.align || "center";
    const openDelay = Number(wrapper.dataset.openDelay) || 700;
    const closeDelay = Number(wrapper.dataset.closeDelay) || 300;
    let openTimer = null;
    let closeTimer = null;

    const reposition = () => {
        wrapper.dataset.side = positionFloatingElement(trigger, content, side, { align });
    };

    const open = () => {
        window.clearTimeout(closeTimer);
        wrapper.dataset.state = "open";
        reposition();
    };

    const scheduleOpen = () => {
        window.clearTimeout(closeTimer);
        window.clearTimeout(openTimer);
        openTimer = window.setTimeout(open, openDelay);
    };

    const close = () => {
        wrapper.dataset.state = "closed";
    };

    const scheduleClose = () => {
        window.clearTimeout(openTimer);
        window.clearTimeout(closeTimer);
        closeTimer = window.setTimeout(close, closeDelay);
    };

    [trigger, content].forEach((element) => {
        element.addEventListener("mouseenter", scheduleOpen);
        element.addEventListener("mouseleave", scheduleClose);
        element.addEventListener("focusin", scheduleOpen);
        element.addEventListener("focusout", scheduleClose);
    });

    wrapper.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && wrapper.dataset.state === "open") {
            window.clearTimeout(openTimer);
            window.clearTimeout(closeTimer);
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
    wrapper.dataset.js = "hover-card";
}

export function initHoverCard() {
    document.querySelectorAll('[data-slot="hover-card"]').forEach((wrapper) => {
        setupHoverCard(wrapper);
    });
}
