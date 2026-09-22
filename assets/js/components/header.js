export function initHeader() {
    const header = document.getElementById("siteHeader");

    if (!header) {
        return;
    }

    const scrolledThreshold = 40;
    let isTicking = false;

    const updateScrolled = () => {
        header.dataset.scrolled = window.scrollY > scrolledThreshold ? "true" : "false";
    };

    const requestScrolledUpdate = () => {
        if (isTicking) {
            return;
        }

        isTicking = true;

        window.requestAnimationFrame(() => {
            updateScrolled();
            isTicking = false;
        });
    };

    updateScrolled();

    window.addEventListener("scroll", requestScrolledUpdate, { passive: true });
}
