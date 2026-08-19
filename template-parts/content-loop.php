<?php declare(strict_types=1) ?>
<?php if (have_posts()): ?>
  <div class="content-grid">
    <?php while (have_posts()):
        the_post(); ?>
      <article <?php post_class('content-card'); ?>>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <div><?php the_excerpt(); ?></div>
      </article>
    <?php
    endwhile; ?>
  </div>
<?php else: ?>
  <p><?php esc_html_e('Keine Inhalte gefunden.', 'hengegroup-theme'); ?></p>
<?php endif; ?>
