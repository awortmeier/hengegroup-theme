// Progressive-enhancement layer for template-parts/base/carousel/*.php. The zero-JS baseline is
// already a real, fully working carousel -- carousel-content.php is a native CSS Scroll Snap
// container (`overflow-x`/`-y: auto` + `scroll-snap-type`, set in project CSS off
// [data-slot="carousel-content"]/the parent's [data-orientation]) with `tabindex="0"`, so
// touch/trackpad swipe, mouse-wheel, the native scrollbar and keyboard arrow keys on a focused
// scrollable region all work with zero JS (see that file's header comment). The one honest,
// documented gap this module closes: carousel-previous.php/carousel-next.php render as plain
// <button>s with no native scroll behaviour of their own (see carousel-previous.php's header
// comment for why a static server-rendered element can't encode "whichever slide is currently
// adjacent") -- same class of gap as dropdown-menu.php's menuitemcheckbox/menuitemradio items,
// not a regression of an otherwise-working baseline.
//
// DOM is the only data source: slides are read directly from the
// [data-slot="carousel-item"] children of [data-slot="carousel-content"], nothing is duplicated
// into a second JS-side config. `data-orientation`/`data-loop` on the [data-slot="carousel"] root
// (rendered by carousel.php) drive the scroll axis and whether Previous/Next wrap at the bounds
// instead of disabling. Previous/Next buttons are matched via `data-carousel-nav="previous"|"next"`
// (set internally by carousel-previous.php/carousel-next.php, see those files) rather than a new
// data-slot -- button.php stays the single source of truth for what "is a button" looks like.
//
// Current-slide tracking uses IntersectionObserver against the scroll container itself as `root`
// instead of computing scroll offsets by hand -- the item most visible becomes "current", used
// only to enable/disable Previous/Next at the bounds (skipped entirely when `loop` is set).

function getItems(content) {
    return Array.from(content.querySelectorAll(':scope > [data-slot="carousel-item"]'));
}

function scrollToItem(item, vertical) {
    item.scrollIntoView({
        behavior: "smooth",
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
