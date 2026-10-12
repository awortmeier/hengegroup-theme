// date-picker.php: outside click/Escape close, updates the trigger text on selection, blocks
// opening while data-disabled. Month navigation is calendar.js's job.

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
