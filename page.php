<?php declare(strict_types=1);

get_header();
?>

<?php while (have_posts()):

    the_post();

    // Beginnt die Seite mit einer Buehne, liefert die das Bild oben: Seitentitel dann nur fuer
    // Screenreader, bzw. gar nicht, wenn die statische Buehne ihren Titel selbst als h1 ausgibt.
    $first_block = parse_blocks((string) get_the_content())[0] ?? [];
    $first_block_name = (string) ($first_block['blockName'] ?? '');
    $title_class = in_array(
        $first_block_name,
        ['hengegroup-theme/buehne', 'hengegroup-theme/buehne-statisch'],
        true,
    )
        ? 'sr-only'
        : '';
    $hide_title =
        $first_block_name === 'hengegroup-theme/buehne-statisch' &&
        ($first_block['attrs']['titleTag'] ?? 'h1') === 'h1' &&
        trim((string) ($first_block['attrs']['title'] ?? '')) !== '';
    ?>
  <article <?php post_class('content-page'); ?>>
    <?php if (!$hide_title): ?>
      <h1 class="<?php echo esc_attr($title_class); ?>"><?php the_title(); ?></h1>
    <?php endif; ?>
    <?php the_content(); ?>
  </article>
<?php
endwhile; ?>

<?php get_footer(); ?>
