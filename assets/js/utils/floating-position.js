// Shared side-flip floating-position math for hover-triggered panels anchored to a trigger
// element (tooltip.php's styled panel, hover-card.php's preview card). Simple single-axis flip if
// the preferred `side` doesn't fit in the viewport, then clamps into the viewport on the cross
// axis -- not full collision detection (same documented scope limit as tooltip.php's own header
// comment, which this util was extracted from). Not a component itself, doesn't live under
// template-parts/ -- shared, component-agnostic JS utility (see utils/dom-ready.js for the same
// "cross-cutting, no 1:1 template-part" precedent, and helpers.php's reasoning applied
// to JS).
//
// align only affects the cross axis (horizontal for top/bottom sides, vertical for left/right
// sides): 'center' (default, matches every current caller) centers the content on the trigger;
// 'start'/'end' flush it to the trigger's leading/trailing edge instead (hover-card.php's own
// `align` config, shadcn/Radix's own HoverCardContent vocabulary).
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

    return resolvedSide;
}
