<?php declare(strict_types=1);

get_header();
?>

<header class="archive-header">
  <h1><?php the_archive_title(); ?></h1>
  <?php the_archive_description('<div class="archive-description">', '</div>'); ?>
</header>

<?php get_template_part('template-parts/content', 'loop'); ?>

<?php get_footer(); ?>
