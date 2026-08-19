<?php declare(strict_types=1);

get_header();
?>

<section class="content-page">
  <p class="eyebrow"><?php esc_html_e('Fehler 404', 'base-theme'); ?></p>
  <h1><?php esc_html_e('Diese Seite wurde nicht gefunden.', 'base-theme'); ?></h1>
  <p><?php esc_html_e(
      'Der Link ist ungueltig oder der Inhalt wurde verschoben.',
      'base-theme',
  ); ?></p>
  <p>
    <a href="<?php echo esc_url(home_url('/')); ?>">
      <?php esc_html_e('Zur Startseite', 'base-theme'); ?>
    </a>
  </p>
</section>

<?php get_footer(); ?>
