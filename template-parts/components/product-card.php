<?php

declare(strict_types=1);

// Produktbox (Design `Produktbox.dc.html`) als Komponente -- aus woocommerce/content-product.php
// herausgeloest, damit dieselbe Box in zwei Auspraegungen existiert statt als zwei Kopien:
//   - `default`: Bild + Badge, Name, Kurzbeschreibung, Trenner + "Anwendungen"-Chips, Button
//     (Produktuebersicht, Produkte-Block, Anwendungsseite, alle WooCommerce-Loops).
//   - `minimal`: dieselbe Box ohne Anwendungen/Trenner, niedrigeres Bild (160 statt 180 px) --
//     Abschnitt "Verwandte Produkte" der Produktdetailseite (Design "Produktdetailseite").
//
// Alle Gestaltungsentscheidungen (Farben, Radius, Schatten, Bild-/Badge-Masse aus der Referenz,
// Kurzbeschreibung statt Langtext, kein Preis/Warenkorb) sind unveraendert aus content-product.php
// uebernommen -- siehe dessen Kopfkommentar und docs/entscheidungen.md "Produktbox: ...".
//
// Supported args:
//   product   WC_Product   Pflicht
//   variant   string       default | minimal (default: default)

$product = $args['product'] ?? null;

if (!($product instanceof WC_Product)) {
    return;
}

$variant = ($args['variant'] ?? 'default') === 'minimal' ? 'minimal' : 'default';
$product_id = $product->get_id();

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

// Kurzbeschreibung (WCs post_excerpt), nicht die lange Produktbeschreibung -- explizite Nachfrage
// 2026-09-22, siehe docs/entscheidungen.md. wp_strip_all_tags() vor wp_trim_words(): die
// Kurzbeschreibung kann einfaches HTML enthalten, die Karte zeigt reinen Text; 24 Woerter reichen
// fuer die im Referenz-Design gezeigten 2-3 Zeilen.
$short_description = wp_strip_all_tags((string) $product->get_short_description());
$description = $short_description !== '' ? wp_trim_words($short_description, 24) : '';

$anwendung_badges_markup =
    $variant === 'default' ? hengegroup_theme_render_product_anwendung_badges($product_id) : '';
?>
<article
  class="flex h-full flex-col rounded-2xl bg-neutral-50 shadow-[0_8px_24px_rgba(0,0,0,0.25)]"
  data-product-id="<?php echo esc_attr((string) $product_id); ?>"
  data-slot="product-card"
  data-variant="<?php echo esc_attr($variant); ?>"
>
  <div class="<?php echo esc_attr(
      $variant === 'minimal' ? 'relative h-40 p-3 pb-0' : 'relative h-50 p-3 pb-0',
  ); ?>">
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

    if ($description !== '') {
        get_template_part('template-parts/base/typography', null, [
            'config' => [
                'variant' => 'body-sm',
                'text' => $description,
                'class' => 'mb-2 text-pretty',
            ],
        ]);
    }

    if ($anwendung_badges_markup !== '') {
        // Trenner nur, wenn es tatsaechlich einen Anwendungen-Block gibt (explizite Nachfrage).
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
