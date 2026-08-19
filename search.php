<?php declare(strict_types=1);

get_header();
?>

<header class="archive-header">
  <p class="eyebrow"><?php esc_html_e('Suche', 'base-theme'); ?></p>
  <h1>
    <?php printf(
        esc_html__('Suchergebnisse fuer: %s', 'base-theme'),
        '<span>' . esc_html(get_search_query()) . '</span>',
    ); ?>
  </h1>
</header>

<?php get_template_part('template-parts/content', 'loop'); ?>

<?php get_footer(); ?>
