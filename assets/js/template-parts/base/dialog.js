// Progressive-enhancement layer for template-parts/base/dialog.php: native <dialog> +
// showModal()/close() already does the heavy lifting (focus trap, ::backdrop, native
// Escape-to-close, focus return to the trigger) -- see that file's header comment. This module
// does NOT reimplement any of that. It only:
//
//   1. Polyfills `command`/`commandfor` invocation for browsers without native Invoker Commands
//      support (feature-detected once; a no-op in browsers that already handle it natively).
//   2. Upgrades a server-rendered `open: true` dialog (native non-modal baseline, see dialog.php)
//      into a real showModal() on init, when `modal` is not false.
//   3. Adds backdrop-click-to-close (clicking the <dialog> element itself, i.e. outside its
//      content box -- native <dialog> has no built-in "click outside" event).
//   4. Honours `dismissible: false` by preventing the native `cancel` event (Escape) and ignoring
//      backdrop clicks.

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
    // Native support covers show-modal/close/request-close for real command/commandfor pairs --
    // nothing left to wire up manually.
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
