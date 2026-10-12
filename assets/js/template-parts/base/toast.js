// Toast API following sonner: toast(message, options), .success/.error/.warning/.info/.loading/
// .message/.custom(html)/.dismiss(id)/.promise(). initToast() wires server-rendered toasts. One
// viewport per page. Every toast has a DOM id. Strings are hardcoded (no WP i18n in JS). The
// CLASSES below mirror toast.php; keep both in sync.

const DEFAULT_DURATION = 4000;

let idCounter = 0;
const timers = new Map();

const ICON_WRAP_CLASSES = "mt-0.5 shrink-0 [&_svg:not([class*='size-'])]:size-5";
const CONTENT_CLASSES = "flex min-w-0 flex-1 flex-col gap-1";
const TITLE_CLASSES = "text-base leading-[1.3] font-semibold";
const DESCRIPTION_CLASSES = "text-sm leading-[1.45] text-pretty";
const ACTIONS_CLASSES = "flex shrink-0 items-center gap-2 self-center";
const ACTION_CLASSES =
    "inline-flex items-center justify-center rounded-lg border border-foreground/16 px-3.5 py-1.5 " +
    "text-sm font-semibold text-foreground transition-colors hover:border-henge-green hover:text-henge-green";
const CANCEL_CLASSES =
    "inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-sm font-medium " +
    "text-muted-foreground transition-colors hover:bg-foreground/6 hover:text-foreground";
const CLOSE_CLASSES =
    "-mt-1 -mr-1.5 ml-1 flex size-7 shrink-0 self-center items-center justify-center rounded-lg " +
    "transition-colors [&_svg:not([class*='size-'])]:size-4";
const CLOSE_CLASSES_DEFAULT = "text-foreground/45 hover:bg-foreground/6 hover:text-foreground";
const CLOSE_CLASSES_ERROR = "text-destructive/60 hover:bg-destructive/10 hover:text-destructive";
const LIFE_CLASSES =
    "absolute inset-x-0 bottom-0 h-0.5 origin-left bg-current opacity-50 " +
    "animate-[hg-toast-life_var(--toast-duration)_linear_forwards]";
const CARD_BASE_CLASSES =
    "pointer-events-auto relative flex w-full items-start gap-3 overflow-hidden rounded-2xl " +
    "border px-4 py-4 shadow-lg animate-[hg-toast-in_0.28s_cubic-bezier(0.2,0.9,0.3,1)]";
const CARD_CLASSES_DEFAULT = "border-foreground/8 bg-card";
const CARD_CLASSES_ERROR = "border-destructive/25 bg-destructive/6";

// Icon and countdown bar use the type color via currentColor; only error tints the text.
const TYPE_ACCENT_CLASSES = {
    default: "text-muted-foreground",
    success: "text-henge-green",
    error: "text-destructive",
    warning: "text-amber-600",
    info: "text-henge-blue",
    loading: "text-muted-foreground",
};

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

function getLifeBar(toastEl) {
    return toastEl.querySelector('[data-slot="toast-life"]');
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

// Pauses the dismiss timer and the countdown animation together.
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

        const lifeBar = getLifeBar(toastEl);

        if (lifeBar) {
            lifeBar.style.animationPlayState = "paused";
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

        const lifeBar = getLifeBar(toastEl);

        if (lifeBar) {
            lifeBar.style.animationPlayState = "running";
        }
    });
}

// Wires close/cancel (dismiss), hover pause and auto-dismiss. Actions are handled in show().
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

// Shared builder. An existing `options.id` is rebuilt in place (used by toast.promise()), so the
// entrance animation does not replay.
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

    const isError = type === "error";

    toastEl.setAttribute("data-type", type);
    toastEl.className = [
        CARD_BASE_CLASSES,
        isError ? CARD_CLASSES_ERROR : CARD_CLASSES_DEFAULT,
        TYPE_ACCENT_CLASSES[type] || TYPE_ACCENT_CLASSES.default,
    ].join(" ");
    toastEl.innerHTML = "";

    if (icon !== false) {
        const template = getTypeIconTemplate(type);

        if (template) {
            const iconWrap = document.createElement("div");
            iconWrap.setAttribute("data-slot", "toast-icon");
            iconWrap.className = ICON_WRAP_CLASSES;
            iconWrap.appendChild(template.content.cloneNode(true));
            toastEl.appendChild(iconWrap);
        }
    }

    const contentEl = document.createElement("div");
    contentEl.setAttribute("data-slot", "toast-content");
    contentEl.className = CONTENT_CLASSES;

    if (message) {
        const titleEl = document.createElement("div");
        titleEl.setAttribute("data-slot", "toast-title");
        titleEl.className = `${TITLE_CLASSES} ${isError ? "text-destructive" : "text-foreground"}`;
        titleEl.textContent = message;
        contentEl.appendChild(titleEl);
    }

    if (description) {
        const descriptionEl = document.createElement("div");
        descriptionEl.setAttribute("data-slot", "toast-description");
        descriptionEl.className = `${DESCRIPTION_CLASSES} ${
            isError ? "text-destructive/80" : "text-muted-foreground"
        }`;
        descriptionEl.textContent = description;
        contentEl.appendChild(descriptionEl);
    }

    toastEl.appendChild(contentEl);

    // toast.custom(): HTML is appended after title/description.
    if (html) {
        contentEl.insertAdjacentHTML("beforeend", html);
    }

    if (action || cancel) {
        const actionsEl = document.createElement("div");
        actionsEl.setAttribute("data-slot", "toast-actions");
        actionsEl.className = ACTIONS_CLASSES;

        if (action && action.label) {
            const actionButton = document.createElement("button");
            actionButton.type = "button";
            actionButton.setAttribute("data-slot", "toast-action");
            actionButton.className = ACTION_CLASSES;
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
            cancelButton.className = CANCEL_CLASSES;
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
        button.className = `${CLOSE_CLASSES} ${isError ? CLOSE_CLASSES_ERROR : CLOSE_CLASSES_DEFAULT}`;
        button.setAttribute("aria-label", "Close");

        const template = getCloseIconTemplate();

        if (template) {
            button.appendChild(template.content.cloneNode(true));
        }

        toastEl.appendChild(button);
    }

    const resolvedDuration = duration === undefined ? getViewportDuration() : duration;
    toastEl.dataset.duration = String(resolvedDuration);

    if (resolvedDuration > 0) {
        toastEl.style.setProperty("--toast-duration", `${resolvedDuration}ms`);

        const lifeBar = document.createElement("span");
        lifeBar.setAttribute("data-slot", "toast-life");
        lifeBar.className = LIFE_CLASSES;
        toastEl.appendChild(lifeBar);
    } else {
        toastEl.style.removeProperty("--toast-duration");
    }

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

// Loading toast without auto-dismiss, updated in place to success/error (string or function).
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

// Global for non-module callers (inline onclick, console).
window.hengegroupTheme = window.hengegroupTheme || {};
window.hengegroupTheme.toast = toast;
