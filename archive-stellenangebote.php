<?php

declare(strict_types=1);

// Overview page for the "stellenangebote" custom post type (Karriere/Jobs), served at /karriere/
// (see inc/setup/theme-careers.php's `has_archive`/`rewrite` config). Same `.wrapper` 12-column-
// grid + `display: contents` technique as woocommerce/archive-product.php (see that file's own
// header comment), reusing card.php for each job instead of a bespoke markup (unlike the product
// box, see docs/entscheidungen.md "Produktbox: eigenes Markup statt card.php") -- there is no
// project-specific shape to fit here yet (title + excerpt only), so the generic component is the
// right level of abstraction for now. Kept deliberately simple, see docs/entscheidungen.md
// "Stellenangebote: Custom-Post-Type angelegt" for what's still missing (Standort/Unternehmen/etc.
// fields, pending further specs).

defined('ABSPATH') || exit();

get_header();
?>

<div class="wrapper gap-y-6 py-12 sm:py-16 lg:py-20">
  <div class="col-span-12">
    <?php get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => 'headline-sm',
            'tag' => 'h1',
            'text' => post_type_archive_title('', false),
            'class' => 'mb-8',
        ],
    ]); ?>
  </div>

  <?php if (have_posts()): ?>
    <ul class="contents">
      <?php while (have_posts()):
          the_post(); ?>
        <li class="col-span-12 sm:col-span-6 lg:col-span-4">
          <?php get_template_part('template-parts/base/card', null, [
              'config' => [
                  'title' => get_the_title(),
                  'title_tag' => 'h2',
                  'description' => get_the_excerpt(),
                  'href' => get_permalink(),
                  'tag' => 'article',
              ],
          ]); ?>
        </li>
      <?php
      endwhile; ?>
    </ul>
  <?php else: ?>
    <div class="col-span-12">
      <?php get_template_part('template-parts/base/typography', null, [
          'config' => [
              'variant' => 'body-base',
              'text' => __('Aktuell sind keine Stellenangebote verfügbar.', 'hengegroup-theme'),
          ],
      ]); ?>
    </div>
  <?php endif; ?>
</div>

<?php get_footer(); ?>
