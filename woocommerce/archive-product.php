<?php

declare(strict_types=1);

// WooCommerce template override (theme templates under woocommerce/ take precedence over the
// plugin's own, see WC_Template_Loader) for the shop/product-archive page -- replaces WC's own
// `<ul class="products columns-N">` grid (float-based, unstyled since this theme dequeues WC's
// frontend CSS, see theme-hardening-woocommerce.php) with the site's own `.wrapper` 12-column grid
// (assets/css/app.css), the same 1600px-capped, responsive-padding container every other page
// section already uses (buehne/render.php etc.) -- NOT a second, independently-scaled grid nested
// inside it (explicit request 2026-09-22, see docs/entscheidungen.md: the previous version put its
// own `grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4` INSIDE a `.wrapper`-wrapped `col-span-12`
// div, two grids nested one level apart instead of one). `<ul class="contents">` (`display:
// contents`) keeps the semantic list element without introducing a second grid context: its `<li>`
// children (rendered by content-product.php) become direct children of `.wrapper`'s OWN
// `grid-cols-12` track, placed via `col-span-12 sm:col-span-6 lg:col-span-3` -- 1/2/4 per row,
// same breakpoints as before, now expressed as this project's own col-span idiom (see
// content-product.php for where that class lives, since it's on the `<li>` itself). Every OTHER
// direct child of `.wrapper` (title, WC's before/after-loop hook output) gets an explicit
// `col-span-12` wrapper too -- an unspanned grid child only occupies its default single column
// (see app.css's own comment on `.wrapper`), so leaving one out would silently squeeze it into a
// sliver instead of a full-width row. `.wrapper` itself only sets `gap-x-*` (see app.css), so
// `gap-y-6` is added here for spacing between the title/hook rows and the product rows -- content-
// product.php (the individual box) is otherwise untouched.
//
// Bewusst "einfach" gehalten (explizite Nachfrage): kein woocommerce_sidebar()-Aufruf. WCs eigene
// Default-Hooks auf woocommerce_before_shop_loop/woocommerce_after_shop_loop (Ergebnis-Zaehler,
// Sortierung, Pagination) bleiben unangetastet bestehen -- nicht neu hinzugefuegt, nur nicht aktiv
// entfernt, damit z. B. Pagination bei mehr Produkten als `posts_per_page` weiter funktioniert.

defined('ABSPATH') || exit();

get_header('shop');

do_action('woocommerce_before_main_content');
?>

<div class="wrapper gap-y-6 py-12 sm:py-16 lg:py-20">
  <div class="col-span-12">
    <?php if (apply_filters('woocommerce_show_page_title', true)): ?>
      <?php get_template_part('template-parts/base/typography', null, [
          'config' => [
              'variant' => 'headline-sm',
              'tag' => 'h1',
              'text' => woocommerce_page_title(false),
              'class' => 'mb-8',
          ],
      ]); ?>
    <?php endif; ?>

    <?php do_action('woocommerce_archive_description'); ?>
  </div>

  <?php if (woocommerce_product_loop()): ?>
    <div class="col-span-12">
      <?php do_action('woocommerce_before_shop_loop'); ?>
    </div>

    <ul class="contents">
      <?php while (have_posts()):
          the_post();
          do_action('woocommerce_shop_loop');
          wc_get_template_part('content', 'product');
      endwhile; ?>
    </ul>

    <div class="col-span-12">
      <?php do_action('woocommerce_after_shop_loop'); ?>
    </div>
  <?php else: ?>
    <div class="col-span-12">
      <?php do_action('woocommerce_no_products_found'); ?>
    </div>
  <?php endif; ?>
</div>

<?php
do_action('woocommerce_after_main_content');

get_footer('shop');

