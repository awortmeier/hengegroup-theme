// Unit tests for initBuehne() (see buehne.js's header comment for what it does on top of the
// static markup template-parts/blocks/buehne/render.php renders). Builds the exact DOM shape
// render.php renders directly in jsdom instead of importing the PHP template -- rendering real PHP
// output isn't available in this JS-only test runner, see docs/to-do.md Abschnitt 1 for where a
// WP-backed integration suite would close that gap.
//
// jsdom implements neither matchMedia nor real layout/scrolling -- matchMedia is stubbed locally
// here (not in the shared assets/js/test-setup.js) since no other suite needs it yet; the crossfade
// itself needs no layout stub, it's pure attribute toggling (see goToIndex() in buehne.js).
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { initBuehne } from "./buehne.js";

function stubMatchMedia(reducedMotion) {
    window.matchMedia = vi.fn().mockReturnValue({ matches: reducedMotion });
}

function renderBuehne({ count = 3, autoplay = true, autoplayInterval = 6000, loop = true } = {}) {
    const items = Array.from(
        { length: count },
        (_, index) =>
            `<div data-slot="carousel-item" id="slide-${index}" data-state="${index === 0 ? "active" : "inactive"}"${index === 0 ? "" : ' aria-hidden="true" inert'}>Slide ${index}</div>`
    ).join("");
    const dots = Array.from(
        { length: count },
        (_, index) => `<button data-buehne-dot="slide-${index}">Dot ${index}</button>`
    ).join("");

    document.body.innerHTML = `
        <div class="relative">
            <div
                data-slot="carousel"
                data-block="buehne"
                data-loop="${loop}"
                ${autoplay ? `data-autoplay="true" data-autoplay-interval="${autoplayInterval}"` : ""}
            >${items}</div>
            <div data-buehne-dots>${dots}</div>
        </div>
    `;

    return {
        items: Array.from(document.querySelectorAll('[data-slot="carousel-item"]')),
        dots: Array.from(document.querySelectorAll("[data-buehne-dot]")),
    };
}

beforeEach(() => {
    document.body.innerHTML = "";
    stubMatchMedia(false);
});

afterEach(() => {
    vi.useRealTimers();
});

describe("initBuehne", () => {
    it("marks the first dot active on setup", () => {
        const { dots } = renderBuehne();

        initBuehne();

        expect(dots[0].getAttribute("data-active")).toBe("true");
        expect(dots[1].hasAttribute("data-active")).toBe(false);
    });

    it("crossfades to the matching slide when a dot is clicked", () => {
        const { items, dots } = renderBuehne();

        initBuehne();
        dots[2].click();

        expect(items[2].getAttribute("data-state")).toBe("active");
        expect(items[2].hasAttribute("aria-hidden")).toBe(false);
        expect(items[2].hasAttribute("inert")).toBe(false);
        expect(items[0].getAttribute("data-state")).toBe("inactive");
        expect(items[0].getAttribute("aria-hidden")).toBe("true");
        expect(items[0].hasAttribute("inert")).toBe(true);
        expect(dots[2].getAttribute("data-active")).toBe("true");
    });

    it("advances to the next slide on autoplay", () => {
        vi.useFakeTimers();
        const { items } = renderBuehne({ autoplayInterval: 5000 });

        initBuehne();
        vi.advanceTimersByTime(5000);

        expect(items[1].getAttribute("data-state")).toBe("active");
        expect(items[0].getAttribute("data-state")).toBe("inactive");
    });

    it("wraps back to the first slide when looping past the last one", () => {
        vi.useFakeTimers();
        const { items } = renderBuehne({ count: 2, loop: true, autoplayInterval: 1000 });

        initBuehne();
        vi.advanceTimersByTime(1000); // tick 1: slide 0 -> slide 1
        vi.advanceTimersByTime(1000); // tick 2: slide 1 -> wraps to slide 0

        expect(items[0].getAttribute("data-state")).toBe("active");
    });

    it("does not advance past the last slide when looping is disabled", () => {
        vi.useFakeTimers();
        const { items } = renderBuehne({ count: 2, loop: false, autoplayInterval: 1000 });

        initBuehne();
        vi.advanceTimersByTime(1000); // tick 1: slide 0 -> slide 1
        vi.advanceTimersByTime(1000); // tick 2: would wrap, but loop is disabled

        expect(items[1].getAttribute("data-state")).toBe("active");
    });

    it("does not schedule autoplay when disabled via data-autoplay", () => {
        vi.useFakeTimers();
        const { items } = renderBuehne({ autoplay: false });

        initBuehne();
        vi.advanceTimersByTime(20000);

        expect(items[0].getAttribute("data-state")).toBe("active");
        expect(items[1].getAttribute("data-state")).toBe("inactive");
    });

    it("does not schedule autoplay when the visitor prefers reduced motion", () => {
        stubMatchMedia(true);
        vi.useFakeTimers();
        const { items } = renderBuehne({ autoplayInterval: 1000 });

        initBuehne();
        vi.advanceTimersByTime(1000);

        expect(items[0].getAttribute("data-state")).toBe("active");
        expect(items[1].getAttribute("data-state")).toBe("inactive");
    });

    it("pauses autoplay while the carousel is hovered", () => {
        vi.useFakeTimers();
        const { items } = renderBuehne({ autoplayInterval: 1000 });

        initBuehne();
        document.querySelector('[data-slot="carousel"]').dispatchEvent(new Event("mouseenter"));
        vi.advanceTimersByTime(5000);

        expect(items[0].getAttribute("data-state")).toBe("active");
        expect(items[1].getAttribute("data-state")).toBe("inactive");
    });
});
