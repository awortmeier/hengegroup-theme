<?php

declare(strict_types=1);

// shadcn/ui's CarouselPrevious: a Button (`variant="outline" size="icon"`) with an ArrowLeft icon
// and `disabled={!canScrollPrev}` from embla's API. Nests button.php directly for that exact
// variant/size/icon combination instead of duplicating its markup -- this file
// renders nothing of its own beyond translating carousel-specific config into a button.php call,
// same "thin translation, no extra wrapper" shape as e.g. button-group-text.php's `tag`.
//
// Honest gap (documented, not silently papered over): a static server-rendered
// element -- an `<a href>`, a plain `<button>`, anything -- cannot know "which slide is currently
// adjacent" the way calendar.php's real prev/next `<a href>` links know the previous/next month
// (that's pure date arithmetic, no client scroll position involved). This renders as a plain
// `<button type="button">` that does nothing until
// assets/js/template-parts/base/carousel.js attaches a real click handler (scrolls
// carousel-content.php's container by one item and keeps `disabled` in sync at the bounds). The
// carousel itself remains fully usable without JS regardless -- touch/trackpad drag, mouse-wheel
// and native keyboard arrow-key scrolling on carousel-content.php's focusable, CSS-Scroll-Snap
// container all work already (see that file's header comment) -- only this dedicated button
// shortcut needs JS, same class of gap as dropdown-menu.php's menuitemcheckbox/menuitemradio
// items. The CSS Carousel spec's own `::scroll-button()` pseudo-element would close this gap with
// zero JS, but isn't cross-browser yet -- worth being aware of as a future direction, not
// something to build against today (same "not currently a live target" note as typography.php's
// header comment on shadcn's Typeset system).
//
// Matched by assets/js/template-parts/base/carousel.js via `data-carousel-nav="previous"` (set
// internally below, merged into the nested button.php call's `data_attributes`) rather than a new
// data-slot -- button.php stays the single source of truth for what "is a button" looks like, the
// carousel-specific meaning is a separate data-attribute layered on top, same idiom as
// dropdown-menu-item.php's shortcut slot reusing kbd.php unchanged.
//
// Supported config:
//   aria_label   string   default: translated "Previous slide" (esc_html__(), component-owned UI
//                           text, same i18n precedent as calendar.php's own nav labels)
//   icon         array    icon.php config override (default: ['name' => 'arrow-left', 'set' =>
//                           'lucide'])
//   disabled     bool     default false. Initial disabled state -- kept false rather than guessing
//                           "always disabled at the first slide", since that guess would be wrong
//                           for a `loop`-configured carousel; assets/js/template-parts/base/
//                           carousel.js corrects this immediately after mount either way
//   class / attributes / data_attributes   passthrough onto the nested button.php call

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$aria_label = trim((string) ($config['aria_label'] ?? ''));
$icon_config = is_array($config['icon'] ?? null)
    ? $config['icon']
    : ['name' => 'arrow-left', 'set' => 'lucide'];
$disabled = !empty($config['disabled']);
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($aria_label === '') {
    $aria_label = esc_html__('Previous slide', 'hengegroup-theme');
}

$data_attributes['carousel-nav'] = 'previous';

get_template_part('template-parts/base/button', null, [
    'config' => [
        'variant' => 'outline',
        'size' => 'icon-base',
        'icon' => $icon_config,
        'aria_label' => $aria_label,
        'disabled' => $disabled,
        'class' => $class_name,
        'attributes' => $attributes,
        'data_attributes' => $data_attributes,
    ],
]);
