<?php

declare(strict_types=1);

// Render-Helfer fuer die Produktbox und das Produktraster.

/**
 * Badge mit Name und Farbe des Unternehmens; '' ohne Unternehmen.
 */
function hengegroup_theme_render_product_badge(int $product_id): string
{
    $company = hengegroup_theme_get_product_company($product_id);

    return $company !== null
        ? hengegroup_theme_render_label_badge($company['name'], $company['variant'])
        : '';
}

/**
 * "Anwendungen"-Label + Badges ohne Link; '' ohne Anwendungen.
 */
function hengegroup_theme_render_product_anwendung_badges(int $product_id): string
{
    $anwendungen = hengegroup_theme_get_product_anwendungen($product_id);

    if ($anwendungen === []) {
        return '';
    }

    $badges_markup = '';

    foreach ($anwendungen as $anwendung) {
        ob_start();
        get_template_part('template-parts/base/badge', null, [
            'config' => [
                'text' => $anwendung->name,
                'variant' => 'outline',
            ],
        ]);
        $badges_markup .= (string) ob_get_clean();
    }

    ob_start();
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => 'body-xs',
            'tag' => 'span',
            'text' => __('Anwendungen', 'hengegroup-theme'),
            'class' => 'mb-1 block font-medium tracking-wider uppercase',
        ],
    ]);
    $label_markup = (string) ob_get_clean();

    return sprintf(
        '<div data-slot="product-anwendungen">%s<div class="flex flex-wrap gap-1.5">%s</div></div>',
        $label_markup,
        $badges_markup,
    );
}

/**
 * Produktboxen in der gegebenen Reihenfolge als `<ul class="contents">`, damit die <li> im
 * umgebenden .wrapper-Grid liegen; '' ohne sichtbare Produkte.
 *
 * @param int[] $product_ids
 */
function hengegroup_theme_render_product_cards(
    array $product_ids,
    string $variant = 'default',
): string {
    if (!function_exists('wc_get_product') || $product_ids === []) {
        return '';
    }

    $product_ids = array_map('intval', $product_ids);
    // Laedt Posts, Meta und Terms in einer Abfrage statt einzeln pro wc_get_product().
    _prime_post_caches($product_ids);

    $markup = '';

    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);

        if (!($product instanceof WC_Product) || !$product->is_visible()) {
            continue;
        }

        ob_start();
        echo '<li class="col-span-12 sm:col-span-6 lg:col-span-3">';
        get_template_part('template-parts/components/product-card', null, [
            'product' => $product,
            'variant' => $variant,
        ]);
        echo '</li>';
        $markup .= (string) ob_get_clean();
    }

    return $markup !== '' ? '<ul class="contents">' . $markup . '</ul>' : '';
}
