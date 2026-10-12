// dialog.php: polyfills command/commandfor, upgrades `open` to showModal(), closes on backdrop click
// and honors dismissible: false (blocks Escape and backdrop).

function setupDialog(dialog) {
    if (dialog.dataset.js === "dialog") {
        return;
    }

    const isModal = dialog.dataset.modal !== "false";
    const isDismissible = dialog.dataset.dismissible !== "false";

    if (dialog.dataset.open === "true" && dialog.hasAttribute("open") && isModal) {
        dialog.removeAttribute("open");
        dialog.showModal();
    }

    dialog.addEventListener("click", (event) => {
        if (event.target === dialog && isModal && isDismissible) {
            dialog.close();
        }
    });

    dialog.addEventListener("cancel", (event) => {
        if (!isDismissible) {
            event.preventDefault();
        }
    });

    dialog.dataset.js = "dialog";
}

function setupCommandFallback() {
    if ("command" in HTMLButtonElement.prototype) {
        return;
    }

    document.querySelectorAll("[commandfor]").forEach((invoker) => {
        if (invoker.dataset.js === "dialog-command") {
            return;
        }

        const targetId = invoker.getAttribute("commandfor");
        const command = invoker.getAttribute("command");
        const target = targetId ? document.getElementById(targetId) : null;

        if (!(target instanceof HTMLDialogElement) || !command) {
            return;
        }

        invoker.dataset.js = "dialog-command";
        invoker.addEventListener("click", () => {
            if (command === "show-modal" && !target.open) {
                target.showModal();
            } else if ((command === "close" || command === "request-close") && target.open) {
                target.close();
            }
        });
    });
}

export function initDialog() {
    document.querySelectorAll('dialog[data-slot="dialog-content"]').forEach((dialog) => {
        setupDialog(dialog);
    });

    setupCommandFallback();
}
