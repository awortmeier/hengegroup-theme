<?php

declare(strict_types=1);

// 404-Seite nach Claude-Design "404": grosse "404" in der Akzentschrift (Crillee) mit dem
// Markenverlauf als Textfarbe, daneben Ueberschrift, Hinweistext und "Zur Startseite"-Button.
// Breite ueber `.wrapper-small` (1000px, explizite Nachfrage 2026-10-07) statt der 1400px des
// Designs. Ab `md` stehen Zahl und Text nebeneinander, darunter umbrechend untereinander.
//
// Der Verlauf ist derselbe wie an Header-Oberkante/Footer-Linie (Markenfarben-Tokens statt
// Hex-Werten, `from`/`via`/`to` wie im Benefit-Block), per `bg-clip-text` als Schriftfarbe. Die
// "404" ist rein dekorativ (`aria-hidden`), die Aussage traegt die H1 (typography.php `headline-sm`,
// ab `md` eine Stufe groesser -- feste Skala statt des `clamp()` aus dem Design). "Produktuebersicht"
// im Hinweistext ist ein Link (hengegroup_theme_get_products_page_url()); der Absatz bleibt deshalb
// eigenes Markup, typography.php escaped reinen Text. Der Button nutzt die
// Theme-Variante `henge-green` statt des dunkleren Gruens aus dem Design -- gleiche Buttons wie auf
// den uebrigen Seiten. Der Header ist `fixed` und liegt ueber dem Inhalt (siehe header.php) --
// daher der grosse obere Abstand.

defined('ABSPATH') || exit();
?>
<?php get_header(); ?>

<section class="wrapper-small min-h-[70vh] content-center pt-36 pb-24 sm:pt-40 lg:pt-44 lg:pb-32">
    <div
        class="col-span-12 flex flex-wrap items-center justify-center gap-x-14 gap-y-6"
        data-slot="not-found"
    >
        <p
            class="font-accent bg-linear-90 from-henge-grey via-henge-green to-henge-blue bg-clip-text text-[clamp(140px,18vw,260px)] leading-[0.9] text-transparent"
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
            <p class="mb-9 max-w-[560px] text-xl leading-normal text-pretty text-grey-dark">
                <?php printf(
                    /* translators: %s: link to the product overview. */ esc_html__(
                        'Die aufgerufene Adresse existiert nicht oder wurde verschoben. Über die Startseite oder unsere %s finden Sie schnell zurück.',
                        'hengegroup-theme',
                    ),
                    '<a class="text-grey-dark underline underline-offset-4 hover:text-henge-green" href="' .
                        esc_url(hengegroup_theme_get_products_page_url()) .
                        '">' .
                        esc_html__('Produktübersicht', 'hengegroup-theme') .
                        '</a>',
                ); ?>
            </p>
            <?php get_template_part('template-parts/base/button', null, [
                'config' => [
                    'text' => __('Zur Startseite', 'hengegroup-theme'),
                    'href' => home_url('/'),
                    'variant' => 'henge-green',
                    'size' => 'lg',
                    'class' => '!font-semibold',
                ],
            ]); ?>
        </div>
    </div>
</section>

<?php get_footer(); ?>
