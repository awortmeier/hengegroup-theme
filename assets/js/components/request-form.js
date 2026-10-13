// Enhancement for request forms (they work without it): focuses the status banner after the redirect
// so screen readers announce it, and blocks a second submit while the first one is running.

export function initRequestForm(root = document) {
    root.querySelectorAll('[data-slot="request-form-status"]').forEach((status) => {
        status.focus({ preventScroll: true });
    });

    root.querySelectorAll('[data-slot="request-form"]').forEach((form) => {
        form.addEventListener("submit", (event) => {
            const submit = form.querySelector("[data-request-form-submit]");

            if (!submit) {
                return;
            }

            if (submit.getAttribute("aria-busy") === "true") {
                event.preventDefault();
                return;
            }

            submit.setAttribute("aria-busy", "true");
            submit.textContent = "Wird gesendet …";
        });
    });
}
