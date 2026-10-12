<?php

declare(strict_types=1);

// WooCommerce-Loop-Item: nur der <li>-Rahmen um product-card.php. Feuert bewusst nicht
// woocommerce_before/after_shop_loop_item, weil WC dort eigenes Markup (Link, Warenkorb) anhaengt.
// col-span-* wirkt im .wrapper-Grid (das <ul> ist display: contents) und ist sonst wirkungslos.

defined('ABSPATH') || exit();

global $product;

if (empty($product) || !$product->is_visible()) {
    return;
}

$product_classes = function_exists('wc_get_product_class')
    ? implode(' ', wc_get_product_class('', $product))
    : 'product';
$product_classes .= ' col-span-12 sm:col-span-6 lg:col-span-3';
?>
<li class="<?php echo esc_attr($product_classes); ?>">
  <?php get_template_part('template-parts/components/product-card', null, [
      'product' => $product,
      'variant' => 'default',
  ]); ?>
</li>
