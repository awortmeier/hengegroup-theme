// Public API + progressive-enhancement layer for template-parts/base/toast.php, rebuilt against
// the real "sonner" npm package's own calling convention: a positional `message` argument plus a
// `type`-per-method shape (toast.success()/.error()/.warning()/.info()/.loading()/.message()),
// instead of a single toast({ title, variant }) options object. import { toast } from this module
// in any other JS to show a toast imperatively; initToast() wires up any server-rendered flash
// toasts (the `toasts` config) the same way. Assumes a single toaster viewport per page (render
// toast.php once, e.g. in footer.php).
//
// Every toast (server-rendered or JS-created) has a real DOM `id` -- either PHP's auto-generated
// one or, for JS-created toasts, one generated here -- so toast.dismiss(id) and toast.promise()
// can target/update a specific toast later instead of only acting on the element toast() happened
// to return.
//
// Note: the close/cancel/"Close" fallback strings are hardcoded here (unlike the PHP side, which
// uses esc_html__()/esc_attr__() for the same strings) -- this module has no access to WordPress'
// i18n system on its own; bridging that needs wp_set_script_translations(), not solved here.
//
// Not supported from the JS-side toast()/toast.success()/etc. API (PHP-only, see toast.php):
//   - a per-call custom icon -- only `icon: false` (disable) works here; a real custom icon needs
//     the toaster-level `icons` override or a pre-rendered `toasts[].icon`, both PHP config
//   - swipe-to-dismiss (touch gesture handling)

const DEFAULT_DURATION = 4000;

let idCounter = 0;
const timers = new Map();

function getViewport() {
    return document.querySelector('[data-slot="toaster"]');
}

function getCloseIconTemplate() {
    return document.querySelector('[data-slot="toast-close-icon-template"]');
}

function getTypeIconTemplate(type) {
    return document.querySelector(`[data-slot="toast-icon-template"][data-type="${type}"]`);
}

function nextId() {
    idCounter += 1;

    return `hengegroup-theme-toast-js-${idCounter}`;
}

function getViewportDuration() {
    const viewport = getViewport();

    return viewport ? Number(viewport.dataset.duration) || DEFAULT_DURATION : DEFAULT_DURATION;
}

function getViewportCloseButtonDefault() {
    const viewport = getViewport();

    return Boolean(viewport && viewport.dataset.closeButton === "true");
}

function dismiss(toastEl) {
    timers.delete(toastEl.id);
    toastEl.remove();
}

function dismissById(id) {
    const toastEl = document.getElementById(id);

    if (toastEl && toastEl.matches('[data-slot="toast"]')) {
        dismiss(toastEl);
    }
}

function armTimer(id, toastEl, duration) {
    const existing = timers.get(id);

    if (existing && existing.timer) {
        window.clearTimeout(existing.timer);
    }

    if (!duration || duration <= 0) {
        timers.delete(id);

        return;
    }

    const state = { remaining: duration, startedAt: Date.now(), timer: null };
    state.timer = window.setTimeout(() => dismissById(id), state.remaining);
    timers.set(id, state);
}

function wireHoverPause(toastEl) {
    if (toastEl.dataset.hoverWired) {
        return;
    }

    toastEl.dataset.hoverWired = "true";

    toastEl.addEventListener("mouseenter", () => {
        const state = timers.get(toastEl.id);

        if (state && state.timer) {
            window.clearTimeout(state.timer);
            state.timer = null;
            state.remaining -= Date.now() - state.startedAt;
        }
    });

    toastEl.addEventListener("mouseleave", () => {
        const state = timers.get(toastEl.id);

        if (!state) {
            return;
        }

        state.remaining = Math.max(state.remaining, 0);
        state.startedAt = Date.now();
        state.timer = window.setTimeout(() => dismissById(toastEl.id), state.remaining);
    });
}

// Wires close/cancel buttons already present in toastEl's DOM (server-rendered or just built by
// show() below) to a plain dismiss, plus hover-pause and the auto-dismiss timer from
// data-duration. Action buttons are NOT wired here: a pre-rendered `<a href>` action needs no JS,
// and a JS-created action's optional onClick is attached directly in show() instead (see below) --
// this function's job is only the two buttons that always just mean "dismiss".
function wireToast(toastEl) {
    const closeButton = toastEl.querySelector('[data-slot="toast-close"]');

    if (closeButton && !closeButton.dataset.wired) {
        closeButton.dataset.wired = "true";
        closeButton.addEventListener("click", () => dismissById(toastEl.id));
    }

    const cancelButton = toastEl.querySelector('[data-slot="toast-cancel"]');

    if (cancelButton && !cancelButton.dataset.wired) {
        cancelButton.dataset.wired = "true";
        cancelButton.addEventListener("click", () => dismissById(toastEl.id));
    }

    wireHoverPause(toastEl);
    armTimer(toastEl.id, toastEl, Number(toastEl.dataset.duration) || 0);
}

export function initToast() {
    const viewport = getViewport();

    if (!viewport) {
        return;
    }

    viewport.querySelectorAll('[data-slot="toast"]').forEach((toastEl) => {
        wireToast(toastEl);
    });

    viewport.setAttribute("data-js", "toast");
}

// Core builder shared by toast()/toast.success()/.../toast.custom()/toast.promise(). When
// `options.id` matches an already-visible toast, rebuilds that element in place (used by
// toast.promise() to turn a "loading" toast into "success"/"error") instead of creating a second
// one -- same id, same DOM position, no stacking-order jump.
function show(message, options = {}, type = "default") {
    const viewport = getViewport();

    if (!viewport) {
        return null;
    }

    const {
        description = "",
        duration,
        id = nextId(),
        icon,
        action,
        cancel,
        closeButton,
        html,
    } = options;

    if (!message && !description && !html) {
        return null;
    }

    let toastEl = document.getElementById(id);
    const isUpdate = Boolean(toastEl && toastEl.matches('[data-slot="toast"]'));

    if (!toastEl) {
        toastEl = document.createElement("li");
        toastEl.id = id;
        toastEl.setAttribute("data-slot", "toast");
    }

    toastEl.setAttribute("data-type", type);
    toastEl.innerHTML = "";

    if (icon !== false) {
        const template = getTypeIconTemplate(type);

        if (template) {
            const iconWrap = document.createElement("div");
            iconWrap.setAttribute("data-slot", "toast-icon");
            iconWrap.appendChild(template.content.cloneNode(true));
            toastEl.appendChild(iconWrap);
        }
    }

    const contentEl = document.createElement("div");
    contentEl.setAttribute("data-slot", "toast-content");

    if (message) {
        const titleEl = document.createElement("div");
        titleEl.setAttribute("data-slot", "toast-title");
        titleEl.textContent = message;
        contentEl.appendChild(titleEl);
    }

    if (description) {
        const descriptionEl = document.createElement("div");
        descriptionEl.setAttribute("data-slot", "toast-description");
        descriptionEl.textContent = description;
        contentEl.appendChild(descriptionEl);
    }

    toastEl.appendChild(contentEl);

    // toast.custom(): caller-provided pre-rendered HTML, same convention as tooltip.php's
    // `trigger`/attachment.php's `actions` -- appended after title/description, not replacing
    // them, so a custom toast can still carry a normal message plus rich content.
    if (html) {
        contentEl.insertAdjacentHTML("beforeend", html);
    }

    if (action || cancel) {
        const actionsEl = document.createElement("div");
        actionsEl.setAttribute("data-slot", "toast-actions");

        if (action && action.label) {
            const actionButton = document.createElement("button");
            actionButton.type = "button";
            actionButton.setAttribute("data-slot", "toast-action");
            actionButton.dataset.wired = "true";
            actionButton.textContent = action.label;
            actionButton.addEventListener("click", (event) => {
                if (typeof action.onClick === "function") {
                    action.onClick(event);
                }

                dismissById(id);
            });
            actionsEl.appendChild(actionButton);
        }

        if (cancel && cancel.label) {
            const cancelButton = document.createElement("button");
            cancelButton.type = "button";
            cancelButton.setAttribute("data-slot", "toast-cancel");
            cancelButton.dataset.wired = "true";
            cancelButton.textContent = cancel.label;
            cancelButton.addEventListener("click", (event) => {
                if (typeof cancel.onClick === "function") {
                    cancel.onClick(event);
                }

                dismissById(id);
            });
            actionsEl.appendChild(cancelButton);
        }

        toastEl.appendChild(actionsEl);
    }

    const showClose =
        closeButton === undefined ? getViewportCloseButtonDefault() : Boolean(closeButton);

    if (showClose) {
        const button = document.createElement("button");
        button.type = "button";
        button.setAttribute("data-slot", "toast-close");
        button.setAttribute("aria-label", "Close");

        const template = getCloseIconTemplate();

        if (template) {
            button.appendChild(template.content.cloneNode(true));
        }

        toastEl.appendChild(button);
    }

    const resolvedDuration = duration === undefined ? getViewportDuration() : duration;
    toastEl.dataset.duration = String(resolvedDuration);

    if (!isUpdate) {
        viewport.appendChild(toastEl);
    }

    wireToast(toastEl);

    return id;
}

export function toast(message, options = {}) {
    return show(message, options, options.type || "default");
}

toast.success = (message, options = {}) => show(message, options, "success");
toast.error = (message, options = {}) => show(message, options, "error");
toast.warning = (message, options = {}) => show(message, options, "warning");
toast.info = (message, options = {}) => show(message, options, "info");
toast.loading = (message, options = {}) => show(message, options, "loading");
toast.message = (message, options = {}) => show(message, options, "default");

toast.custom = (html, options = {}) => show("", { ...options, html }, options.type || "default");

toast.dismiss = (id) => {
    if (id === undefined) {
        const viewport = getViewport();

        if (viewport) {
            viewport.querySelectorAll('[data-slot="toast"]').forEach((toastEl) => dismiss(toastEl));
        }

        return;
    }

    dismissById(id);
};

// sonner's own toast.promise(): shows a "loading" toast immediately (no auto-dismiss while
// pending, duration: 0), then rewrites that SAME toast in place to "success"/"error" once the
// promise settles -- `success`/`error` may be a plain string or a function(data|err) => string,
// matching sonner's own API.
toast.promise = (promise, { loading = "", success, error } = {}) => {
    const id = show(loading, { duration: 0 }, "loading");

    Promise.resolve(promise).then(
        (data) => {
            const message = typeof success === "function" ? success(data) : success;
            show(message || "", { id }, "success");
        },
        (reason) => {
            const message = typeof error === "function" ? error(reason) : error;
            show(message || "", { id }, "error");
        }
    );

    return id;
};
