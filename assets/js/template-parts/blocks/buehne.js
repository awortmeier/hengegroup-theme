// Buehne: crossfade between slides plus dot navigation and a pause toggle for autoplay.

import { prefersReducedMotion } from "../../utils/reduced-motion.js";

function getItems(carousel) {
    return Array.from(carousel.querySelectorAll(':scope > [data-slot="carousel-item"]'));
}

function isKeyboardFocus(element) {
    try {
        return element.matches(":focus-visible");
    } catch {
        return true;
    }
}

function setupBuehne(carousel) {
    const root = carousel.parentElement;
    const dotsWrapper = root ? root.querySelector("[data-buehne-dots]") : null;

    if (!dotsWrapper) {
        return;
    }

    const items = getItems(carousel);
    const dots = Array.from(dotsWrapper.querySelectorAll("[data-buehne-dot]"));

    if (items.length === 0 || dots.length === 0) {
        return;
    }

    const loop = carousel.getAttribute("data-loop") === "true";
    const autoplay =
        carousel.getAttribute("data-autoplay") === "true" &&
        items.length > 1 &&
        !prefersReducedMotion();
    const autoplayInterval = parseInt(
        carousel.getAttribute("data-autoplay-interval") || "6000",
        10
    );
    const pauseButton = root.querySelector("[data-buehne-pause]");
    const playIconTemplate = root.querySelector("template[data-buehne-play-icon]");
    const pauseIcon = pauseButton ? pauseButton.querySelector("svg") : null;
    const playIcon = playIconTemplate
        ? playIconTemplate.content.querySelector("svg")?.cloneNode(true)
        : null;

    let currentIndex = 0;
    let timer = null;
    let hovered = false;
    let focused = false;
    let paused = false;

    function goToIndex(index) {
        items.forEach((item, itemIndex) => {
            if (itemIndex === index) {
                item.setAttribute("data-state", "active");
                item.removeAttribute("aria-hidden");
                item.removeAttribute("inert");
            } else {
                item.setAttribute("data-state", "inactive");
                item.setAttribute("aria-hidden", "true");
                item.setAttribute("inert", "");
            }
        });

        dots.forEach((dot, dotIndex) => {
            if (dotIndex === index) {
                dot.setAttribute("data-active", "true");
                dot.setAttribute("aria-current", "true");
            } else {
                dot.removeAttribute("data-active");
                dot.removeAttribute("aria-current");
            }
        });

        currentIndex = index;
    }

    function goToNext() {
        const nextIndex = currentIndex + 1;

        if (nextIndex >= items.length) {
            if (!loop) {
                return;
            }

            goToIndex(0);
            return;
        }

        goToIndex(nextIndex);
    }

    // Restarting also resets the interval, so a manual slide change gets the full display time.
    function updateAutoplay() {
        if (timer !== null) {
            window.clearInterval(timer);
            timer = null;
        }

        const running = autoplay && !paused && !hovered && !focused;

        if (running) {
            timer = window.setInterval(goToNext, autoplayInterval);
        }

        // Announce slide changes only when they are user-initiated.
        carousel.setAttribute("aria-live", running ? "off" : "polite");
    }

    dots.forEach((dot, index) => {
        dot.addEventListener("click", () => {
            goToIndex(index);
            updateAutoplay();
        });
    });

    if (pauseButton && autoplay) {
        pauseButton.hidden = false;
        pauseButton.setAttribute("data-state", "playing");
        pauseButton.addEventListener("click", () => {
            paused = !paused;
            pauseButton.setAttribute("aria-pressed", paused ? "true" : "false");
            pauseButton.setAttribute("data-state", paused ? "paused" : "playing");

            const nextIcon = paused ? playIcon : pauseIcon;
            const currentIcon = pauseButton.querySelector("svg");

            if (nextIcon && currentIcon && nextIcon !== currentIcon) {
                currentIcon.replaceWith(nextIcon);
            }

            updateAutoplay();
        });
    }

    // The dots sit outside the carousel element, so hover/focus are tracked on the shared wrapper.
    root.addEventListener("mouseenter", () => {
        hovered = true;
        updateAutoplay();
    });
    root.addEventListener("mouseleave", () => {
        hovered = false;
        updateAutoplay();
    });
    // Chrome focuses buttons on mouse click; only keyboard focus should hold the slide.
    root.addEventListener("focusin", (event) => {
        if (!isKeyboardFocus(event.target)) {
            return;
        }

        focused = true;
        updateAutoplay();
    });
    root.addEventListener("focusout", (event) => {
        if (root.contains(event.relatedTarget)) {
            return;
        }

        focused = false;
        updateAutoplay();
    });

    goToIndex(0);
    updateAutoplay();
    carousel.setAttribute("data-js", "buehne");
}

export function initBuehne() {
    document
        .querySelectorAll('[data-slot="carousel"][data-block="buehne"]')
        .forEach((carousel) => setupBuehne(carousel));
}
