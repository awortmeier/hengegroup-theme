export function initHeader() {
    const header = document.getElementById("siteHeader");
    const topbar = document.querySelector("[data-topbar]");
    const sentinel = document.querySelector("[data-header-sentinel]");

    if (!header || !sentinel) {
        return;
    }

    const minimumTopOffset = 10;
    let isTicking = false;

    const updateHeaderTop = () => {
        if (!topbar) {
            header.style.top = `${minimumTopOffset}px`;
            return;
        }

        const topbarBottom = topbar.getBoundingClientRect().bottom;
        const nextTopOffset = Math.max(minimumTopOffset, topbarBottom);

        header.style.top = `${nextTopOffset}px`;
    };

    const requestHeaderTopUpdate = () => {
        if (isTicking) {
            return;
        }

        isTicking = true;

        window.requestAnimationFrame(() => {
            updateHeaderTop();
            isTicking = false;
        });
    };

    const observer = new IntersectionObserver(
        ([entry]) => {
            header.classList.toggle("is-floating", !entry.isIntersecting);
        },
        {
            root: null,
            threshold: 0,
            rootMargin: `80px 0px 0px 0px`,
        }
    );

    observer.observe(sentinel);
    updateHeaderTop();

    window.addEventListener("scroll", requestHeaderTopUpdate, { passive: true });
    window.addEventListener("resize", requestHeaderTopUpdate);
}
