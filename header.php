<?php declare(strict_types=1) ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body>
<?php wp_body_open(); ?>
<div class="min-h-screen">
    <?php
// Full-width, fixed dark header with a scroll-triggered translucent/blurred state and a
// brand-gradient top edge, composed from template-parts/base/* components on top of the
// Claude-Design "Hengegroup" header reference. Replaces the base-theme scaffold's own
// shrink-to-pill scroll behaviour (see docs/entscheidungen.md "Header: Scroll-Verhalten aus
// dem Referenzdesign statt der Vorlagen-eigenen Pill-Logik"). Overlays whatever content
// follows with no spacer/offset -- the reference's own hero section sits directly underneath
// at the same top:0, by design.
//
// `data-scrolled` is toggled by assets/js/components/header.js (a plain scroll listener, no
// IntersectionObserver/sentinel needed now that the header no longer changes position) --
// background/blur are the only two properties that change, expressed as `data-[scrolled=true]:`
// Tailwind variants (CLAUDE.md Regel 1), the same state-attribute pattern `group-open:`/
// `aria-invalid:` already use elsewhere in this project's base components.
//
// `[border-image:...]` is a Tailwind ARBITRARY VALUE (CLAUDE.md Regel 1 explicitly allows
// these, not a raw-CSS exception) -- no stock border-* utility supports a multi-stop gradient
// on one edge, so the reference's own literal gradient is expressed inline via this project's
// brand color tokens instead of a hardcoded hex triplet.
?>
    <header
        class="fixed inset-x-0 top-0 z-50 border-t-2 border-t-transparent bg-grey-dark px-6 py-4.5 transition-[background-color,backdrop-filter] duration-300 ease-out sm:px-12 data-[scrolled=true]:bg-grey-dark/70 data-[scrolled=true]:backdrop-blur-[6px] [border-image:linear-gradient(90deg,var(--color-henge-grey),var(--color-henge-green),var(--color-henge-blue))_1]"
        id="siteHeader"
        data-site-header
        data-scrolled="false"
    >
        <div class="mx-auto flex max-w-[2000px] items-center justify-between gap-4">
            <?php if (has_custom_logo()): ?>
                <div class="shrink-0 [&_img]:h-8 [&_img]:w-auto">
                    <?php the_custom_logo(); ?>
                </div>
            <?php else: ?>
                <a
                    class="inline-flex shrink-0 items-center text-xl font-semibold text-grey-light no-underline"
                    href="<?php echo esc_url(home_url('/')); ?>"
                    aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>"
                >
                    <span><?php bloginfo('name'); ?></span>
                </a>
            <?php endif; ?>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <?php get_template_part(
                    'template-parts/base/navigation-menu/navigation-menu',
                    null,
                    [
                        'config' => [
                            'aria_label' => __('Hauptnavigation', 'hengegroup-theme'),
                            'color' => 'light',
                            'items' => hengegroup_theme_primary_navigation_items(),
                        ],
                    ],
                ); ?>

                <?php get_template_part('template-parts/base/button', null, [
                    'config' => [
                        'text' => __('Kontakt', 'hengegroup-theme'),
                        'href' => '#kontakt',
                        'variant' => 'grey-light',
                    ],
                ]); ?>

                <?php
                // Language switcher: UI only, no real translation logic yet -- see
                // docs/entscheidungen.md "Header: Sprachumschalter als reines UI-Element". This
                // project's planned multi-language approach is a WordPress Multisite network
                // (one site per language), not a single-site plugin -- see that same file's
                // "Mehrsprachigkeit ueber Multisite" entry; wiring real per-language URLs belongs
                // there, once that network exists.
                //
                // The trigger is plain inline markup, not a button.php call -- dropdown-menu.php's
                // own header comment: `trigger` must not itself be a focusable element, since its
                // <summary> is already the one interactive control.
                $language_trigger = sprintf(
                    '<span class="inline-flex items-center gap-1.5 rounded-full border border-grey-light/35 px-3.5 py-2 text-sm font-medium text-grey-light transition-colors hover:bg-grey-light/10">%1$s%2$s</span>',
                    esc_html__('DE', 'hengegroup-theme'),
                    hengegroup_theme_render_icon([
                        'name' => 'chevron-down',
                        'set' => 'lucide',
                        'class' => 'size-2.5',
                    ]),
                );

                ob_start();
                get_template_part('template-parts/base/dropdown-menu/dropdown-menu-item', null, [
                    'config' => ['text' => __('Deutsch', 'hengegroup-theme'), 'href' => '#'],
                ]);
                get_template_part('template-parts/base/dropdown-menu/dropdown-menu-item', null, [
                    'config' => ['text' => __('English', 'hengegroup-theme'), 'href' => '#'],
                ]);
                $language_menu_content = (string) ob_get_clean();

                get_template_part('template-parts/base/dropdown-menu/dropdown-menu', null, [
                    'config' => [
                        'trigger' => $language_trigger,
                        'content' => $language_menu_content,
                        'align' => 'end',
                    ],
                ]);
                ?>
            </div>
        </div>
    </header>

    <main class="min-h-1000">
