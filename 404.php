<?php

declare(strict_types=1);

// 404-Seite. Die "404" ist dekorativ (aria-hidden), die Aussage traegt die H1. Grosser oberer
// Abstand, weil der Header fixed ueber dem Inhalt liegt.

defined('ABSPATH') || exit();
?>
<?php get_header(); ?>

<div class="header-offset">
<section class="wrapper-small min-h-[70vh] content-center section-spacing">
    <div
        class="col-span-12 flex flex-wrap items-center justify-center gap-x-14 gap-y-6"
        data-slot="not-found"
    >
        <p
            class="font-accent bg-linear-90 brand-gradient bg-clip-text text-[clamp(140px,18vw,260px)] leading-[0.9] text-transparent"
            aria-hidden="true"
        >404</p>

        <div>
            <?php get_template_part('template-parts/base/typography', null, [
                'config' => [
                    'variant' => 'headline-sm',
                    'tag' => 'h1',
                    'text' => __('Diese Seite wurde nicht gefunden', 'hengegroup-theme'),
                    'class' => 'mb-5 text-balance md:text-5xl',
                ],
            ]); ?>
            <?php $link_class =
                'text-grey-dark underline underline-offset-4 hover:text-henge-green'; ?>
            <p class="max-w-[560px] text-xl leading-normal text-pretty text-grey-dark">
                <?php printf(
                    /* translators: 1: link to the home page, 2: link to the product overview. */ esc_html__(
                        'Die aufgerufene Adresse existiert nicht oder wurde verschoben. Über die %1$s oder unsere %2$s finden Sie schnell zurück.',
                        'hengegroup-theme',
                    ),
                    '<a class="' .
                        esc_attr($link_class) .
                        '" href="' .
                        esc_url(home_url('/')) .
                        '">' .
                        esc_html__('Startseite', 'hengegroup-theme') .
                        '</a>',
                    '<a class="' .
                        esc_attr($link_class) .
                        '" href="' .
                        esc_url(hengegroup_theme_get_products_page_url()) .
                        '">' .
                        esc_html__('Produktübersicht', 'hengegroup-theme') .
                        '</a>',
                ); ?>
            </p>
        </div>
    </div>
</section>
</div>

<?php get_footer(); ?>
