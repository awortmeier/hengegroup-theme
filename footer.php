<?php declare(strict_types=1) ?>
</main>

<footer class="site-footer">
  <div class="container">
    <p><?php echo esc_html(get_bloginfo('name')); ?> &copy; <?php echo esc_html(
     wp_date('Y'),
 ); ?></p>
  </div>
</footer>

</div>
<?php wp_footer(); ?>
</body>
</html>
