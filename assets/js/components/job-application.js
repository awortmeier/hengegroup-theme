// Enhancement for the application form (works without it): shows chosen file names, checks the size
// limit before submit (setCustomValidity), prevents double submit and focuses the status message
// after the redirect so screen readers announce it.

function formatNames(files, emptyLabel) {
    if (!files || files.length === 0) {
        return emptyLabel;
    }

    return Array.from(files)
        .map((file) => file.name)
        .join(", ");
}

export function validateFileSizes(files, maxBytes) {
    const tooLarge = Array.from(files || []).find((file) => file.size > maxBytes);

    if (!tooLarge) {
        return "";
    }

    const megabytes = Math.round((maxBytes / 1024 / 1024) * 10) / 10;

    return `${tooLarge.name}: Die Datei ist größer als ${String(megabytes).replace(".", ",")} MB.`;
}

export function initJobApplication(root = document) {
    root.querySelectorAll("[data-application-status]").forEach((status) => {
        status.focus({ preventScroll: true });
    });

    root.querySelectorAll("[data-application-form]").forEach((form) => {
        form.querySelectorAll("[data-application-file]").forEach((input) => {
            const nameTarget = form.querySelector(`[data-application-file-name="${input.id}"]`);
            const emptyLabel = nameTarget ? nameTarget.textContent : "";
            const maxBytes = parseInt(input.dataset.maxBytes || "0", 10);

            input.addEventListener("change", () => {
                input.setCustomValidity(
                    maxBytes > 0 ? validateFileSizes(input.files, maxBytes) : ""
                );

                if (nameTarget) {
                    nameTarget.textContent = formatNames(input.files, emptyLabel);
                }

                input.reportValidity();
            });
        });

        form.addEventListener("submit", (event) => {
            const submit = form.querySelector("[data-application-submit]");

            if (!submit || submit.getAttribute("aria-busy") === "true") {
                if (submit) {
                    event.preventDefault();
                }

                return;
            }

            submit.setAttribute("aria-busy", "true");
            submit.dataset.originalText = submit.textContent;
            submit.textContent = "Wird gesendet …";
        });
    });
}
