// Progressive-enhancement layer for template-parts/blocks/buehne/render.php: crossfades between
// slides (opacity-only, no scrolling -- see docs/entscheidungen.md "Buehne: Opacity-Crossfade
// statt Scroll-Snap" for why this diverges from carousel.php's own documented scroll-snap
// contract) plus the dot navigation's active state/click-to-select. Does NOT modify
// assets/js/template-parts/base/carousel.js -- render.php no longer renders a
// [data-slot="carousel-content"] scroll container at all, so that file's scroll-snap wiring simply
// has nothing to attach to here anymore.
//
// Dots are NOT part of template-parts/base/carousel/*.php (see carousel.php's own header comment)
// -- render.php renders them as a sibling of the [data-slot="carousel"] root inside a shared
// wrapper div, matched here via [data-buehne-dots]/[data-buehne-dot] instead of a data-slot, same
// "carousel-specific meaning layered on top, not a new data-slot" idiom carousel-previous.php uses
// for its own `data-carousel-nav`.
//
// The active slide is tracked directly in `currentIndex` here (set by dot clicks/autoplay, never
// inferred from scroll position -- there is nothing to scroll anymore, so the old
// IntersectionObserver-based tracking is gone). goToIndex() sets `data-state="active"/"inactive"`
// on each [data-slot="carousel-item"] (drives the opacity transition in project CSS, see
// render.php) and toggles `aria-hidden`/`inert` on the inactive ones so their buttons/links aren't
// keyboard-/screen-reader-reachable while invisible behind the active slide.

function getItems(carousel) {
    return Array.from(carousel.querySelectorAll(':scope > [data-slot="carousel-item"]'));
}

function prefersReducedMotion() {
    return (
        typeof window.matchMedia === "function" &&
        window.matchMedia("(prefers-reduced-motion: reduce)").matches
    );
}

function setupBuehne(carousel) {
    const dotsWrapper = carousel.parentElement
        ? carousel.parentElement.querySelector("[data-buehne-dots]")
        : null;

    if (!dotsWrapper) {
        return;
    }

    const items = getItems(carousel);
    const dots = Array.from(dotsWrapper.querySelectorAll("[data-buehne-dot]"));

    if (items.length === 0 || dots.length === 0) {
        return;
    }

    const loop = carousel.getAttribute("data-loop") === "true";
    const autoplay = carousel.getAttribute("data-autoplay") === "true";
    const autoplayInterval = parseInt(
        carousel.getAttribute("data-autoplay-interval") || "6000",
        10
    );

    let currentIndex = 0;
    let timer = null;

    function setActiveDot(index) {
        dots.forEach((dot, dotIndex) => {
            if (dotIndex === index) {
                dot.setAttribute("data-active", "true");
            } else {
                dot.removeAttribute("data-active");
            }
        });
    }

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

        currentIndex = index;
        setActiveDot(index);
    }

    dots.forEach((dot, index) => {
        dot.addEventListener("click", () => goToIndex(index));
    });

    function stopAutoplay() {
        if (timer !== null) {
            window.clearInterval(timer);
            timer = null;
        }
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

    function startAutoplay() {
        if (!autoplay || items.length < 2 || prefersReducedMotion()) {
            return;
        }

        stopAutoplay();
        timer = window.setInterval(goToNext, autoplayInterval);
    }

    carousel.addEventListener("mouseenter", stopAutoplay);
    carousel.addEventListener("mouseleave", startAutoplay);
    carousel.addEventListener("focusin", stopAutoplay);
    carousel.addEventListener("focusout", startAutoplay);

    setActiveDot(0);
    startAutoplay();
    carousel.setAttribute("data-js", "buehne");
}

export function initBuehne() {
    document
        .querySelectorAll('[data-slot="carousel"][data-block="buehne"]')
        .forEach((carousel) => setupBuehne(carousel));
}
