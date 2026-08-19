// Progressive-enhancement layer for template-parts/base/date-picker.php: closes the honest zero-JS
// gaps documented in that file's header comment -- outside-click/Escape to close (mirrors
// dropdown-menu.js), reformatting the trigger's summary text after a live pick inside the nested
// calendar.php (which fires a native `change` event on its radio/checkbox day inputs automatically,
// no special wiring needed there), and actually preventing the panel from opening while
// `data-disabled` is set (native <details>/<summary> has no disabled concept of its own, so this is
// the JS-active half of that gap; the PHP side only sets the aria-disabled/data-disabled hooks).
// Month navigation inside the panel is calendar.js's own job -- it initializes independently off of
// `[data-slot="calendar"]`, nested or not.

function formatSelectedText(wrapper, isoDates) {
    if (isoDates.length === 0) {
        return wrapper.getAttribute("data-placeholder") || "";
    }

    const mode = wrapper.getAttribute("data-mode") === "multiple" ? "multiple" : "single";

    if (mode === "multiple" && isoDates.length > 2) {
        const template = wrapper.getAttribute("data-count-template") || "%d dates selected";
        return template.replace("%d", String(isoDates.length));
    }

    const locale = document.documentElement.lang || undefined;
    const formatter = new Intl.DateTimeFormat(locale, {
        month: "long",
        day: "numeric",
        year: "numeric",
    });

    return isoDates
        .map((iso) => {
            const [year, month, day] = iso.split("-").map(Number);
            return formatter.format(new Date(year, month - 1, day));
        })
        .join(" - ");
}

function getSelectedIsoDates(content) {
    return Array.from(content.querySelectorAll('[data-slot="toggle-input"]:checked')).map(
        (input) => input.value
    );
}

function setupDatePicker(wrapper) {
    const trigger = wrapper.querySelector('[data-slot="date-picker-trigger"]');
    const content = wrapper.querySelector('[data-slot="date-picker-content"]');
    const valueEl = wrapper.querySelector('[data-slot="date-picker-value"]');

    if (!trigger || !content || !valueEl) {
        return;
    }

    const close = () => {
        wrapper.removeAttribute("open");
    };

    trigger.addEventListener("click", (event) => {
        if (wrapper.hasAttribute("data-disabled")) {
            event.preventDefault();
        }
    });

    content.addEventListener("change", (event) => {
        if (!event.target.matches('[data-slot="toggle-input"]')) {
            return;
        }

        const selected = getSelectedIsoDates(content);
        valueEl.textContent = formatSelectedText(wrapper, selected);

        if (wrapper.getAttribute("data-mode") !== "multiple") {
            close();
            trigger.focus();
        }
    });

    document.addEventListener("click", (event) => {
        if (wrapper.open && !wrapper.contains(event.target)) {
            close();
        }
    });

    wrapper.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && wrapper.open) {
            event.preventDefault();
            close();
            trigger.focus();
        }
    });

    wrapper.setAttribute("data-js", "date-picker");
}

export function initDatePicker() {
    document.querySelectorAll('[data-slot="date-picker"]').forEach((wrapper) => {
        setupDatePicker(wrapper);
    });
}
