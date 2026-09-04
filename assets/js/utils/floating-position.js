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
    // The caller's own side/align Tailwind classes (e.g. tooltip.php's `group-data-[side=...]:`
    // set) still apply `bottom`/`right`/`translate` for the resting, pre-JS state -- once this
    // function takes over, those must be explicitly overridden (not just left alone), or they
    // stack on top of the computed top/left above, and `top`+`bottom` both being non-auto with
    // `height: auto` makes an absolutely/fixed positioned box STRETCH to fill the gap between them
    // instead of shrinking to fit its content (CSS 2.1 10.6.4) -- the "wrong size AND wrong
    // position" bug this fixes. An empty string would only clear an existing INLINE value, not a
    // class-based one, so this must set real overriding values, not "".
    //
    // `translate` (not `transform`) is the one that actually needs clearing here: Tailwind v4's
    // `-translate-x-1/2`/`-translate-y-1/2` utilities (used to center the content on its cross
    // axis in the resting state) set the standalone CSS `translate` property, not `transform` --
    // CSS's Individual Transform Properties spec split `translate`/`rotate`/`scale` out as their
    // own properties that compose ON TOP OF `transform`, they don't alias it. Clearing only
    // `transform` leaves a `translate: -50%` silently still shifting the already-centered
    // computed `left`/`top` above by half the content's own size -- exactly the kind of small,
    // consistent "near the trigger but off" offset that's easy to miss in a quick visual check but
    // shows up immediately once you diff the requested vs. rendered position.
    content.style.right = "auto";
    content.style.bottom = "auto";
    content.style.transform = "none";
    content.style.translate = "none";

    return resolvedSide;
}
