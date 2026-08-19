// Progressive-enhancement layer for template-parts/base/hover-card.php: enhances the native
// `title`-attribute fallback into a styled, positioned floating preview panel. Positioning math
// is shared with tooltip.js via utils/floating-position.js (see that file's header comment) --
// this module only adds the parts genuinely specific to HoverCard, documented on hover-card.php:
// separate open/close delays, and staying open while the pointer is over the panel itself (not
// just the trigger), since the panel may hold interactive content the user needs to reach.
//
// "Hoverable" open/close: unlike tooltip.js (which listens on one wrapper element, safe because
// its content is never meant to be interacted with), trigger and content are listened to
// separately here. Entering EITHER cancels a pending close and (re)opens; leaving EITHER
// schedules a close after `close_delay`, giving the user a grace period to move the pointer
// across the gap between them without the panel disappearing first.

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

    // Once JS is driving the styled panel, the native title tooltip would just duplicate it.
    wrapper.removeAttribute("title");
    wrapper.dataset.js = "hover-card";
}

export function initHoverCard() {
    document.querySelectorAll('[data-slot="hover-card"]').forEach((wrapper) => {
        setupHoverCard(wrapper);
    });
}
