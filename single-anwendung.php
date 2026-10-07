<?php

declare(strict_types=1);

// Einzelseite einer Anwendung unter /anwendungen/<slug>/ (Post-Type `anwendung`, siehe
// inc/setup/theme-products.php). Fuer diese Seite gibt es noch kein eigenes Design -- aufgebaut aus
// den Bausteinen der Produktdetailseite (woocommerce/single-product.php): Intro mit Icon-Pill,
// Titel, Textauszug und Beitragsbild, danach der Gutenberg-Inhalt und alle Produkte mit dieser
// Anwendung als Produktboxen.
//
// Anwendungen verlinken zu Produkten, nicht umgekehrt (explizite Vorgabe): die Produktboxen hier
// fuehren auf die Produktseiten, die Produkte selbst zaehlen ihre Anwendungen nur auf.
//
// Der Header ist `fixed` und liegt ueber dem Inhalt (siehe header.php) -- daher der groessere
// obere Abstand des Intros.

defined('ABSPATH') || exit();

get_header();

while (have_posts()):

    the_post();
    $anwendung_id = (int) get_the_ID();
    $image_id = (int) get_post_thumbnail_id();
    $excerpt = has_excerpt() ? trim(wp_strip_all_tags(get_the_excerpt())) : '';
    $icon = hengegroup_theme_render_anwendung_icon($anwendung_id, 'size-[22px]');
    $products_markup = hengegroup_theme_render_product_cards(
        hengegroup_theme_get_anwendung_product_ids($anwendung_id),
    );
    ?>
  <article <?php post_class(); ?> data-slot="anwendung">
    <header class="pt-28 pb-12 sm:pt-32 lg:pt-36">
      <div class="wrapper items-center gap-y-10">
        <div class="<?php echo esc_attr(
            $image_id > 0 ? 'col-span-12 lg:col-span-8' : 'col-span-12 lg:col-span-9',
        ); ?>">
          <div class="mb-4.5 flex items-center gap-3">
            <?php if ($icon !== ''): ?>
              <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-henge-blue text-henge-blue-foreground">
                <?php printf(
                    '%s',
                    $icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                ); ?>
              </span>
            <?php endif; ?>
            <?php get_template_part('template-parts/base/badge', null, [
                'config' => [
                    'text' => __('Anwendung', 'hengegroup-theme'),
                    'variant' => 'henge-blue',
                    'font' => 'accent',
                    'class' => 'uppercase tracking-wide',
                ],
            ]); ?>
          </div>

          <?php get_template_part('template-parts/base/typography', null, [
              'config' => [
                  'variant' => 'headline-sm',
                  'tag' => 'h1',
                  'text' => get_the_title(),
                  'class' => 'mb-5 text-balance lg:text-[44px] lg:leading-[1.1]',
              ],
          ]); ?>

          <?php if ($excerpt !== ''): ?>
            <?php get_template_part('template-parts/base/typography', null, [
                'config' => [
                    'variant' => 'body-base',
                    'text' => $excerpt,
                    'class' => 'lg:text-[19px]',
                ],
            ]); ?>
          <?php endif; ?>
        </div>

        <?php if ($image_id > 0): ?>
          <div class="col-span-12 lg:col-span-4">
            <?php $anwendung_image = hengegroup_theme_render_image([
                'attachment_id' => $image_id,
                'size' => 'large',
                'alt' => get_the_title(),
                'class' => 'h-80 w-full rounded-[20px] object-cover lg:h-105',
            ]); ?>
            <?php printf(
                '%s',
                $anwendung_image, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ); ?>
          </div>
        <?php endif; ?>
      </div>
    </header>

    <?php if (trim((string) get_the_content()) !== ''): ?>
      <div class="wrapper pb-16">
        <div class="col-span-12 text-base leading-relaxed text-grey-dark lg:col-span-8 [&_a]:text-henge-green [&_a]:underline [&_h2]:mt-8 [&_h2]:mb-3 [&_h2]:text-2xl [&_h2]:font-semibold [&_h3]:mt-6 [&_h3]:mb-3 [&_h3]:text-xl [&_h3]:font-bold [&_li]:leading-[1.7] [&_ol]:mb-3.5 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mb-3.5 [&_ul]:mb-3.5 [&_ul]:list-disc [&_ul]:pl-5">
          <?php the_content(); ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($products_markup !== ''): ?>
      <section class="bg-neutral-200 py-16 md:py-25" aria-labelledby="anwendung-produkte-titel">
        <div class="wrapper gap-y-6">
          <div class="col-span-12">
            <?php get_template_part('template-parts/base/typography', null, [
                'config' => [
                    'variant' => 'headline-sm',
                    'tag' => 'h2',
                    'text' => __('Passende Produkte', 'hengegroup-theme'),
                    'class' => 'mb-4',
                    'attributes' => ['id' => 'anwendung-produkte-titel'],
                ],
            ]); ?>
          </div>
          <?php printf(
              '%s',
              $products_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          ); ?>
        </div>
      </section>
    <?php endif; ?>
  </article>
<?php
endwhile;

get_footer();
