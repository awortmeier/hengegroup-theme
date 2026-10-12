// Positions a floating panel next to its trigger: flips to the opposite side if it doesn't fit,
// then clamps into the viewport on the cross axis. align start/center/end affects the cross axis.
export function positionFloatingElement(
    trigger,
    content,
    side,
    { gap = 8, align = "center" } = {}
) {
    const triggerRect = trigger.getBoundingClientRect();
    const contentRect = content.getBoundingClientRect();
    const viewportWidth = document.documentElement.clientWidth;
    const viewportHeight = document.documentElement.clientHeight;

    let resolvedSide = side;

    if (side === "top" && triggerRect.top - contentRect.height - gap < 0) {
        resolvedSide = "bottom";
    } else if (
        side === "bottom" &&
        triggerRect.bottom + contentRect.height + gap > viewportHeight
    ) {
        resolvedSide = "top";
    } else if (side === "left" && triggerRect.left - contentRect.width - gap < 0) {
        resolvedSide = "right";
    } else if (side === "right" && triggerRect.right + contentRect.width + gap > viewportWidth) {
        resolvedSide = "left";
    }

    let top;
    let left;

    if (resolvedSide === "top" || resolvedSide === "bottom") {
        top =
            resolvedSide === "top"
                ? triggerRect.top - contentRect.height - gap
                : triggerRect.bottom + gap;

        if (align === "start") {
            left = triggerRect.left;
        } else if (align === "end") {
            left = triggerRect.right - contentRect.width;
        } else {
            left = triggerRect.left + triggerRect.width / 2 - contentRect.width / 2;
        }
    } else {
        left =
            resolvedSide === "left"
                ? triggerRect.left - contentRect.width - gap
                : triggerRect.right + gap;

        if (align === "start") {
            top = triggerRect.top;
        } else if (align === "end") {
            top = triggerRect.bottom - contentRect.height;
        } else {
            top = triggerRect.top + triggerRect.height / 2 - contentRect.height / 2;
        }
    }

    left = Math.max(gap, Math.min(left, viewportWidth - contentRect.width - gap));
    top = Math.max(gap, Math.min(top, viewportHeight - contentRect.height - gap));

    content.style.position = "fixed";
    content.style.top = `${top}px`;
    content.style.left = `${left}px`;
    // Override the resting-state classes (bottom/right/translate) with real values, not "": with
    // both top and bottom set the box stretches instead of fitting its content. Tailwind's
    // -translate-* set the standalone `translate` property, so clearing `transform` is not enough.
    content.style.right = "auto";
    content.style.bottom = "auto";
    content.style.transform = "none";
    content.style.translate = "none";

    return resolvedSide;
}
