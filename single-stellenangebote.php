<?php

declare(strict_types=1);

// Single-job template for the "stellenangebote" custom post type, served at /karriere/<slug>/ (see
// inc/setup/theme-careers.php's `rewrite` config). Deliberately minimal for now -- title +
// the_content() only, same shape as single.php/page.php: the job text itself is authored entirely
// via the standard Gutenberg editor (explicit request), no structured fields yet. See
// docs/entscheidungen.md "Stellenangebote: Custom-Post-Type angelegt".

defined('ABSPATH') || exit();

get_header();
?>

<?php while (have_posts()):
    the_post(); ?>
  <article <?php post_class('wrapper gap-y-6 py-12 sm:py-16 lg:py-20'); ?>>
    <div class="col-span-12">
      <?php get_template_part('template-parts/base/typography', null, [
          'config' => [
              'variant' => 'headline-sm',
              'tag' => 'h1',
              'text' => get_the_title(),
              'class' => 'mb-8',
          ],
      ]); ?>
    </div>

    <div class="col-span-12">
      <?php the_content(); ?>
    </div>
  </article>
<?php
endwhile; ?>

<?php get_footer(); ?>
