<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Card
 *
 * Dev-only page template: renders template-parts/base/card.php across a data-list card with
 * footer actions, a form card with a `footer_divider`, a `href`+`media_badge` clickable product
 * grid, `size: 'sm'` stat cards (content-only, no header/footer), for manual visual/functional
 * review during Phase 2 styling work -- not meant for production content or navigation. Analog zu
 * page-component-showcase-popover.php/-toast.php.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Card"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses the
 * existing per-page mechanism instead of a second one.
 *
 * Design reference: https://claude.ai/code/artifact/c2fdca5b-79fc-47b7-92c8-e861966ac106
 *
 * The inline SVG data-URIs below are self-contained placeholder images (no Media Library
 * dependency), same technique as page-component-showcase-attachment.php's own
 * `$placeholder_image_src` -- swap for a real `attachment_id`/`src` in actual content.
 */

get_header();

$placeholder_image = static function (string $fill): string {
    return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 170'%3E" .
        "%3Crect width='320' height='170' fill='%23{$fill}'/%3E%3C/svg%3E";
};

$specs_content =
    '<div class="flex flex-col border-t border-border">' .
    implode(
        '',
        array_map(
            static fn(array $row): string => sprintf(
                '<div class="flex items-baseline justify-between gap-6 border-b border-border py-3 text-sm"><span class="text-muted-foreground">%s</span><span class="font-medium">%s</span></div>',
                esc_html($row['label']),
                esc_html($row['value']),
            ),
            [
                ['label' => 'Al₂O₃', 'value' => '99,3 %'],
                ['label' => 'Körnungen', 'value' => 'F 12 – F 220'],
                ['label' => 'Lieferform', 'value' => '25 kg / Big Bag'],
            ],
        ),
    ) .
    '</div>';

ob_start();
get_template_part('template-parts/base/button', null, [
    'config' => ['variant' => 'henge-green', 'size' => 'sm', 'text' => 'Anfrage stellen'],
]);
get_template_part('template-parts/base/button', null, [
    'config' => ['variant' => 'outline', 'size' => 'sm', 'text' => 'Muster'],
]);
$specs_footer = (string) ob_get_clean();

ob_start();
get_template_part('template-parts/base/input', null, [
    'config' => ['label' => 'Firma', 'placeholder' => 'Musterwerk GmbH'],
]);
get_template_part('template-parts/base/input', null, [
    'config' => ['label' => 'E-Mail', 'type' => 'email', 'placeholder' => 'einkauf@musterwerk.de'],
]);
$form_fields = (string) ob_get_clean();

ob_start();
get_template_part('template-parts/base/button', null, [
    'config' => ['variant' => 'henge-green', 'full_width' => true, 'text' => 'Anfrage senden'],
]);
?>
<p class="text-center text-sm text-muted-foreground">
    Bereits Kunde?
    <a class="font-medium text-henge-green hover:text-henge-green/80" href="#">Zum Kundenportal</a>
</p>
<?php $form_footer = (string) ob_get_clean(); ?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Card — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        <code>template-parts/base/card.php</code>. Dev-only, nicht für Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Basis</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Kopf mit Titel/Beschreibung/Aktion, Inhalt, Fuß -- beide Karten nutzen dieselbe Datei,
            nur <code>content</code>/<code>footer</code> unterscheiden sich. Die rechte zeigt
            <code>footer_divider: true</code>.
        </p>
        <div class="grid grid-cols-1 items-start gap-6 md:grid-cols-2">
            <?php get_template_part('template-parts/base/card', null, [
                'config' => [
                    'tag' => 'article',
                    'title' => 'Edelkorund weiß',
                    'description' => 'Hochreines Aluminiumoxid für eisenfreie Bearbeitung.',
                    'action' =>
                        '<a class="shrink-0 text-sm font-medium text-henge-green hover:text-henge-green/80" href="#">Datenblatt</a>',
                    'content' => $specs_content,
                    'footer' => $specs_footer,
                ],
            ]); ?>
            <?php get_template_part('template-parts/base/card', null, [
                'config' => [
                    'tag' => 'article',
                    'title' => 'Musteranfrage',
                    'description' => 'Wir melden uns innerhalb eines Werktags.',
                    'content' => '<div class="flex flex-col gap-4">' . $form_fields . '</div>',
                    'footer' =>
                        '<div class="flex w-full flex-col gap-3">' . $form_footer . '</div>',
                    'footer_divider' => true,
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">
            Mit Bild und Label (<code>href</code> + <code>media_badge</code>)
        </h2>
        <p class="mb-6 text-sm text-neutral-500">
            <code>href</code> macht die ganze Karte zum Klickziel (Hover hebt sie an),
            <code>media_badge</code> legt ein Label über das Bild.
        </p>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <?php
            $products = [
                [
                    'fill' => '075f8f',
                    'badge' => ['variant' => 'henge-blue', 'text' => 'Strahlmittel'],
                    'title' => 'Normalkorund braun',
                    'text' =>
                        'Zähes, wirtschaftliches Strahlmittel für Entzunderung und Entrostung.',
                ],
                [
                    'fill' => '3b875e',
                    'badge' => ['variant' => 'henge-green', 'text' => 'Schleifmittel'],
                    'title' => 'Siliciumcarbid',
                    'text' => 'Härtestes Korn im Programm, für harte und spröde Werkstoffe.',
                ],
                [
                    'fill' => '646a6c',
                    'badge' => ['variant' => 'henge-grey', 'text' => 'Feuerfest'],
                    'title' => 'Schmelzmagnesia',
                    'text' => 'Hitzebeständiger Rohstoff für feuerfeste Massen und Steine.',
                ],
            ];

            foreach ($products as $product) {
                ob_start();
                get_template_part('template-parts/base/badge', null, [
                    'config' => $product['badge'],
                ]);
                $badge_markup = (string) ob_get_clean();

                get_template_part('template-parts/base/card', null, [
                    'config' => [
                        'href' => '#',
                        'image' => [
                            'src' => $placeholder_image($product['fill']),
                            'alt' => '',
                            'decorative' => true,
                            'class' => 'aspect-[16/9] w-full object-cover',
                        ],
                        'media_badge' => $badge_markup,
                        'title' => $product['title'],
                        'description' => $product['text'],
                        'content' =>
                            '<span class="text-sm font-medium text-henge-green">Zum Produkt</span>',
                    ],
                ]);
            }
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">
            Kompakt (nur <code>content</code>, <code>size: 'sm'</code>)
        </h2>
        <p class="mb-6 text-sm text-neutral-500">
            Kennzahlen ohne Kopf/Fuß -- Titel/Beschreibung/Footer bleiben einfach unbenutzt.
        </p>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <?php
            $stats = [
                ['label' => 'Standorte', 'value' => '6', 'note' => 'in Europa'],
                ['label' => 'Produkte', 'value' => '15', 'note' => 'in drei Bereichen'],
                ['label' => 'Lieferzeit', 'value' => '3–5', 'note' => 'Werktage'],
                ['label' => 'Mindestmenge', 'value' => '500', 'note' => 'Kilogramm'],
            ];

            foreach ($stats as $stat) {
                get_template_part('template-parts/base/card', null, [
                    'config' => [
                        'size' => 'sm',
                        'content' => sprintf(
                            '<div class="flex flex-col gap-1"><span class="text-xs font-medium tracking-wide text-muted-foreground uppercase">%s</span><span class="text-3xl leading-none font-semibold">%s</span><span class="text-sm text-muted-foreground">%s</span></div>',
                            esc_html($stat['label']),
                            esc_html($stat['value']),
                            esc_html($stat['note']),
                        ),
                    ],
                ]);
            }
            ?>
        </div>
    </section>
</div>

<?php get_footer(); ?>
