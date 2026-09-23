<?php

declare(strict_types=1);

// WooCommerce template override (theme templates under woocommerce/ take precedence over the
// plugin's own bundled ones, see WC_Template_Loader) for the single product-loop item -- used by
// every WC loop context automatically (shop page, product category/tag archives, the [products]
// shortcode, related/upsell/cross-sell loops), not just one page, which is the whole point of
// overriding this file instead of building a one-off template-part (explicit request 2026-09-22,
// see docs/entscheidungen.md "Produktbox: WooCommerce-Template statt eigenem template-part").
//
// Keeps ONE of WC's own structural conventions: the `<li>` wrapper + wc_get_product_class() (WC's
// default archive-product.php wraps loop items in `<ul class="products">`, so a valid child MUST
// be an `<li>`; other WC/plugin CSS or JS keyed off `.product`/`.type-product`/etc. still finds
// them) -- but replaces WC's own default inner markup (price/rating/add-to-cart included) entirely
// with this theme's own base components (button.php/badge.php/typography.php/image.php) via
// inc/template-parts/woocommerce-product-card.php's render helpers, composed directly here instead
// of through template-parts/base/card.php (explicit request 2026-09-22, see
// docs/entscheidungen.md "Produktbox: eigenes Markup statt card.php") -- the reference design
// (`Produktbox.dc.html`) insets the image inside padding on three sides rather than bleeding it
// edge-to-edge, and needs a fixed-height media area + literal-pixel badge offset that don't match
// card.php's own `media`/`media_badge` slot geometry (that slot assumes a full-bleed cover image
// with a negative top margin, see card.php's own header comment) -- card.php would need bending to
// fit this one shape, so this file builds its own <article> markup instead, same
// button.php/badge.php/typography.php/image.php building blocks, no shared "card" abstraction.
//
// Deliberately does NOT fire woocommerce_before_shop_loop_item/woocommerce_after_shop_loop_item
// (bugfix 2026-09-22, see docs/entscheidungen.md) -- these are NOT neutral "loop item boundary"
// markers, WC core itself hooks its OWN default markup onto them
// (woocommerce_template_loop_product_link_open()/_close() wrap an extra `<a
// class="woocommerce-LoopProduct-link">` around the whole item, woocommerce_template_loop_add_to_cart()
// renders the add-to-cart/"Weiterlesen" button) -- firing them reintroduced exactly the WC-default
// markup this file exists to replace, nested around/after our own output. A previous version of
// this file fired both "for 3rd-party plugin compatibility", which was simply wrong for these two
// particular hook names.
//
// `col-span-12 sm:col-span-6 lg:col-span-3` on the `<li>` itself (explicit request 2026-09-22, see
// docs/entscheidungen.md): archive-product.php renders the `<ul>` as `display: contents`, so these
// `<li>`s become direct children of ITS parent's `.wrapper` 12-column grid -- 1/2/4 per row, same
// breakpoints the earlier standalone grid used. Harmless outside a 12-column grid parent
// (col-span-* is a no-op without a CSS grid ancestor), so this stays safe for any other WC loop
// context (related products, [products] shortcode, ...) that doesn't use `.wrapper`.
//
// No price/add-to-cart yet -- buying comes in a later phase (see docs/entscheidungen.md). The
// product link lives on a single "Produkt ansehen" button at the bottom instead of a
// ganzflaechigen Karten-Link (explicit request 2026-09-22, matches the reference design's
// full-width button).
//
// Colors/radius/shadow below are literal reference values from `Produktbox.dc.html`, mapped onto
// Tailwind either as an exact scale step where one exists, or as an arbitrary value where it
// doesn't (CLAUDE.md Regel 1 explicitly allows arbitrary Tailwind values) -- same
// "reference-literal-when-no-stock-step-fits" convention as popover.php's/tooltip.php's own
// `shadow-[...]` entries, see those files' header comments:
//   - `bg-neutral-50`/near-black text: the reference's `rgb(250,249,245)`/`rgb(30,29,28)` are the
//     SAME literal values already mapped to Tailwind's `neutral-50`/`neutral-900` in tooltip.php's
//     own Phase-2 entry (see docs/entscheidungen.md), not this project's own `--color-background`/
//     `-foreground` tokens (those are pinned to `neutral-800`/pure white, a visibly different,
//     cooler/darker pairing) -- referenced directly again here for the same reason: closer match,
//     no new token needed for one component.
//   - `rounded-2xl` (16px) instead of the reference's literal 20px -- this project's own
//     established radius for card/floating surfaces (popover.php/toast.php/card.php's own
//     deviation, see card.php's header comment for the "standardize across surfaces instead of
//     over-fitting to one demo number" precedent).
//   - `shadow-[0_8px_24px_rgba(0,0,0,0.25)]` -- the reference's own literal box-shadow, no stock
//     Tailwind shadow step reaches this size/darkness (same reasoning as popover.php's/tooltip.php's
//     own arbitrary shadow values).
//   - media area `h-[180px]`/`top-[22px] left-[22px]` badge offset -- literal reference pixels, not
//     on Tailwind's spacing scale (180/22 aren't stock steps); the badge offset is measured from the
//     media container's own border edge (CSS containing-block-for-absolutely-positioned-elements
//     rule: the padding box, i.e. the container's outer edge), so it does NOT need to add the
//     container's own `p-3` on top -- it's independent of that padding, not additive with it.
//   - badge color: the henge-blue/henge-green/henge-grey brand tokens already match this
//     reference's own badge colors closely (`--color-henge-blue: #075f8f` vs. the reference's
//     `rgb(7,95,143)`; `rgb(27,110,70)` is the same literal value table-row.php's own Phase-2
//     entry already treats as this project's henge-green, see that file's header comment) -- no
//     new color token needed. Rendering lives in inc/template-parts/woocommerce-product-card.php;
//     the color itself is now a direct per-product editorial choice (product-editor "Badge" meta
//     box, see inc/setup/theme-woocommerce-products.php), not derived from a company name.

defined('ABSPATH') || exit();

global $product;

if (empty($product) || !$product->is_visible()) {
    return;
}

$product_id = $product->get_id();
$product_classes = function_exists('wc_get_product_class')
    ? implode(' ', wc_get_product_class('', $product))
    : 'product';
$product_classes .= ' col-span-12 sm:col-span-6 lg:col-span-3';
?>
<li class="<?php echo esc_attr($product_classes); ?>">
  <article
    class="flex h-full flex-col rounded-2xl bg-neutral-50 shadow-[0_8px_24px_rgba(0,0,0,0.25)]"
    data-product-id="<?php echo esc_attr((string) $product_id); ?>"
  >
    <?php
    $image_id = $product->get_image_id();
    $image_config =
        $image_id > 0
            ? [
                'attachment_id' => $image_id,
                'size' => 'woocommerce_thumbnail',
                'alt' => $product->get_name(),
                'class' => 'h-full w-full rounded-xl object-cover',
            ]
            : [
                'src' => wc_placeholder_img_src('woocommerce_thumbnail'),
                'alt' => $product->get_name(),
                'class' => 'h-full w-full rounded-xl object-cover',
            ];

    $badge_markup = hengegroup_theme_render_product_badge($product_id);
    ?>
    <div class="relative h-50 p-3 pb-0">
      <?php printf(
          '%s',
          hengegroup_theme_render_image($image_config), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      ); ?>
      <?php if ($badge_markup !== ''): ?>
        <div class="absolute top-5.5 left-5.5">
          <?php printf(
              '%s',
              $badge_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          ); ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="flex flex-1 flex-col p-3 pb-4">
      <?php
      get_template_part('template-parts/base/typography', null, [
          'config' => [
              'variant' => 'body-base',
              'tag' => 'h3',
              'text' => $product->get_name(),
              'class' => 'mb-1 font-bold leading-tight',
          ],
      ]);

      // Kurzbeschreibung (WCs post_excerpt), nicht die lange Produktbeschreibung -- explizite
      // Nachfrage 2026-09-22, siehe docs/entscheidungen.md. wp_strip_all_tags() vor
      // wp_trim_words(): die Kurzbeschreibung kann einfaches HTML enthalten (WCs eigener Editor
      // erlaubt das), die Karte zeigt reinen Text. 24 Woerter reichen fuer die im Referenz-Design
      // gezeigten 2-3 Zeilen, ohne bei einer laenger gepflegten Kurzbeschreibung die Kartenhoehe
      // im Grid zu sprengen.
      $short_description = wp_strip_all_tags((string) $product->get_short_description());
      $description = $short_description !== '' ? wp_trim_words($short_description, 24) : '';

      if ($description !== '') {
          get_template_part('template-parts/base/typography', null, [
              'config' => [
                  'variant' => 'body-sm',
                  'text' => $description,
                  'class' => 'mb-2 text-pretty',
              ],
          ]);
      }

      $anwendung_badges_markup = hengegroup_theme_render_product_anwendung_badges($product_id);

      if ($anwendung_badges_markup !== '') {
          // separator.php's own defaults (decorative, weight 'default') -- explicit request to
          // visually split the description from the Anwendungen section; only rendered when
          // there's actually an Anwendungen block to separate from.
          get_template_part('template-parts/base/separator/separator', null, [
              'config' => [
                  'class' => 'mb-2',
              ],
          ]);

          printf(
              '<div class="mb-2">%s</div>',
              $anwendung_badges_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          );
      }
      ?>

      <div class="mt-auto">
        <?php get_template_part('template-parts/base/button', null, [
            'config' => [
                'text' => __('Produkt ansehen', 'hengegroup-theme'),
                'href' => get_permalink($product_id),
                'variant' => 'grey-dark',
                'size' => 'lg',
                'full_width' => true,
                'class' => 'mt-2 !font-semibold',
            ],
        ]); ?>
      </div>
    </div>
  </article>
</li>
