// Prev/next buttons for carousel/*.php (swiping, wheel and arrow keys work natively via scroll
// snap). Buttons are found via data-carousel-nav; the current slide is tracked with an
// IntersectionObserver on the scroll container to disable prev/next at the ends (not with loop).

import { prefersReducedMotion } from "../../utils/reduced-motion.js";

function getItems(content) {
    return Array.from(content.querySelectorAll(':scope > [data-slot="carousel-item"]'));
}

function scrollToItem(item, vertical) {
    item.scrollIntoView({
        behavior: prefersReducedMotion() ? "auto" : "smooth",
        block: vertical ? "start" : "nearest",
        inline: vertical ? "nearest" : "start",
    });
}

function setupCarousel(carousel) {
    const content = carousel.querySelector('[data-slot="carousel-content"]');

    if (!content) {
        return;
    }

    const items = getItems(content);

    if (items.length === 0) {
        return;
    }

    const vertical = carousel.getAttribute("data-orientation") === "vertical";
    const loop = carousel.getAttribute("data-loop") === "true";
    const previousButton = carousel.querySelector('[data-carousel-nav="previous"]');
    const nextButton = carousel.querySelector('[data-carousel-nav="next"]');

    let currentIndex = 0;

    function updateButtons() {
        if (loop) {
            return;
        }

        if (previousButton) {
            previousButton.disabled = currentIndex <= 0;
        }

        if (nextButton) {
            nextButton.disabled = currentIndex >= items.length - 1;
        }
    }

    const observer = new IntersectionObserver(
        (entries) => {
            const mostVisible = entries
                .filter((entry) => entry.isIntersecting)
                .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

            if (!mostVisible) {
                return;
            }

            currentIndex = items.indexOf(mostVisible.target);
            updateButtons();
        },
        { root: content, threshold: 0.6 }
    );

    items.forEach((item) => observer.observe(item));

    function goTo(index) {
        const target = loop
            ? (index + items.length) % items.length
            : Math.min(Math.max(index, 0), items.length - 1);

        scrollToItem(items[target], vertical);
    }

    if (previousButton) {
        previousButton.addEventListener("click", () => goTo(currentIndex - 1));
    }

    if (nextButton) {
        nextButton.addEventListener("click", () => goTo(currentIndex + 1));
    }

    updateButtons();
    carousel.setAttribute("data-js", "carousel");
}

export function initCarousel() {
    document.querySelectorAll('[data-slot="carousel"]').forEach((carousel) => {
        setupCarousel(carousel);
    });
}
