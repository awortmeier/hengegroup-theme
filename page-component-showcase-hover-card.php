<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Hover Card
 *
 * Dev-only page template: renders template-parts/base/hover-card.php across a rich product-preview
 * example, a contact-card example, all four `side` values, and a product-list example reusing the
 * same preview shape, for manual visual/functional review during Phase 2 styling work -- not meant
 * for production content or navigation. Analog zu page-component-showcase-popover.php/-tooltip.php.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Hover Card"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses the
 * existing per-page mechanism instead of a second one.
 *
 * Design reference: https://claude.ai/code/artifact/d9a5a3e2-3a09-494f-926b-206c5fa23e93
 */

get_header();

// This project has no standalone avatar.php yet (see docs/to-do.md) -- the initials circle below
// is dev-only decorative markup for this one demo trigger, not a reusable base component, same
// spirit as popover.php's own showcase building one-off demo markup (its filter checkbox list)
// that isn't part of any base component either.
$hover_card_avatar_markup =
    '<span class="inline-flex size-9 items-center justify-center rounded-full bg-henge-green ' .
    'text-sm font-semibold text-henge-green-foreground">MK</span>';
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Hover Card — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        <code>template-parts/base/hover-card.php</code> + <code>hover-card.js</code>. Dev-only,
        nicht für Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Basis</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Vorschau beim Überfahren eines Links. Öffnet nach kurzer Verzögerung, bleibt offen,
            solange die Karte selbst überfahren wird. Kein Klick nötig, deshalb nur ergänzende
            Inhalte darin.
        </p>
        <div class="flex flex-wrap items-start gap-14 pt-2 pb-16">
            <?php
            $product_trigger = sprintf(
                '<a class="font-semibold text-henge-green underline decoration-henge-green/35 underline-offset-4" href="#">%s</a>',
                esc_html('Quarzsand H 33'),
            );

            $product_content =
                '<div class="mb-3.5 flex items-start gap-3.5">' .
                '<span class="h-14 w-14 flex-none rounded-[10px] bg-neutral-200"></span>' .
                '<div class="flex flex-col gap-0.5">' .
                '<span class="text-base font-semibold">Quarzsand H 33</span>' .
                '<span class="text-sm text-muted-foreground">Gewaschen und getrocknet</span>' .
                '</div></div>' .
                '<p class="mb-3.5 text-sm text-muted-foreground text-pretty">' .
                'Rundkörniger Quarzsand für Filteranlagen und Estriche. Körnung 0,3 – 0,8 mm, ' .
                'SiO₂ 98,6 %.</p>' .
                '<div class="flex gap-5 border-t border-border pt-3">' .
                '<div class="flex flex-col gap-0.5">' .
                '<span class="text-[11px] tracking-wide text-muted-foreground uppercase">Lieferform</span>' .
                '<span class="text-sm font-semibold">Big Bag, Silo</span>' .
                '</div>' .
                '<div class="flex flex-col gap-0.5">' .
                '<span class="text-[11px] tracking-wide text-muted-foreground uppercase">Verfügbar</span>' .
                '<span class="text-sm font-semibold">ab Lager</span>' .
                '</div></div>';

            $person_content =
                '<div class="mb-3 flex flex-col gap-0.5">' .
                '<span class="text-base font-semibold">Martin Kolbe</span>' .
                '<span class="text-sm text-muted-foreground">Vertrieb Industrieminerale</span>' .
                '</div>' .
                '<div class="mb-3 flex flex-col gap-1.5 text-sm text-muted-foreground">' .
                '<span>+49 2364 1080-42</span>' .
                '<a class="font-semibold text-foreground" href="#">m.kolbe@hengegroup.de</a>' .
                '</div>' .
                '<span class="text-xs text-muted-foreground">Erreichbar Mo–Fr, 8–17 Uhr</span>';
            ?>
            <p class="max-w-md text-[17px] leading-relaxed text-neutral-600 text-pretty">
                Geliefert wird ab Werk Haltern, verarbeitet als
                <?php get_template_part('template-parts/base/hover-card', null, [
                    'config' => [
                        'trigger' => $product_trigger,
                        'content' => $product_content,
                    ],
                ]); ?>
                in Wasserwerken und Betonwerken.
            </p>

            <?php get_template_part('template-parts/base/hover-card', null, [
                'config' => [
                    'trigger' => sprintf(
                        '<a class="flex items-center gap-2.5 text-neutral-800" href="#">%s' .
                            '<span class="text-sm font-semibold">Martin Kolbe</span></a>',
                        $hover_card_avatar_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ),
                    'content' => $person_content,
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Positionen (<code>side</code>)</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Vier Seiten, 12&nbsp;px Abstand (mehr als beim Tooltip, damit der Zeiger die Karte
            erreicht, ohne sie zu schließen), Pfeil mittig.
        </p>
        <div
            class="grid grid-cols-2 gap-6 rounded-2xl border border-neutral-200 bg-neutral-50 p-14 sm:grid-cols-4"
        >
            <?php
            $sides = [
                'top' => 'Oben',
                'right' => 'Rechts',
                'bottom' => 'Unten',
                'left' => 'Links',
            ];

            foreach ($sides as $side => $label):
                ob_start(); ?>
                <?php get_template_part('template-parts/base/button', null, [
                    'config' => ['variant' => 'outline', 'text' => $label],
                ]); ?>
                <?php $side_trigger = (string) ob_get_clean(); ?>
                <div class="flex flex-col items-center gap-3">
                    <?php get_template_part('template-parts/base/hover-card', null, [
                        'config' => [
                            'trigger' => $side_trigger,
                            'content' =>
                                '<p class="text-sm text-muted-foreground text-pretty">' .
                                esc_html($label . ' ausgerichtet, Pfeil zeigt zum Auslöser.') .
                                '</p>',
                            'side' => $side,
                        ],
                    ]); ?>
                    <span class="font-mono text-xs text-neutral-400">side="<?php echo esc_html(
                        $side,
                    ); ?>"</span>
                </div>
            <?php
            endforeach;
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">In der Anwendung</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Vorschau in einer Produktliste: Kennwerte und Datenblatt, ohne die Seite zu verlassen.
        </p>
        <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-6 pb-20">
            <span class="mb-4 block text-lg font-semibold">Quarzsande</span>
            <div class="flex flex-col divide-y divide-neutral-100">
                <?php
                $products = [
                    [
                        'name' => 'Quarzsand H 31',
                        'grain' => '0,1 – 0,5 mm',
                        'sio' => '99,1 %',
                        'form' => 'Sackware',
                        'use' => 'Filtersand für Wasserwerke',
                    ],
                    [
                        'name' => 'Quarzsand H 33',
                        'grain' => '0,3 – 0,8 mm',
                        'sio' => '98,6 %',
                        'form' => 'Big Bag, Silo',
                        'use' => 'Estriche und Trockenmörtel',
                    ],
                    [
                        'name' => 'Quarzkies H 40',
                        'grain' => '2,0 – 4,0 mm',
                        'sio' => '98,2 %',
                        'form' => 'Silo ab 24 t',
                        'use' => 'Stützschicht in Filteranlagen',
                    ],
                ];

                foreach ($products as $product):

                    $product_row_trigger = sprintf(
                        '<a class="font-semibold text-neutral-800 hover:text-henge-green" href="#">%s</a>',
                        esc_html($product['name']),
                    );

                    $product_row_content =
                        '<div class="mb-3.5 flex flex-col gap-0.5">' .
                        '<span class="text-base font-semibold">' .
                        esc_html($product['name']) .
                        '</span>' .
                        '<span class="text-sm text-muted-foreground">' .
                        esc_html($product['use']) .
                        '</span></div>' .
                        '<div class="mb-3.5 flex flex-col gap-2 text-sm">' .
                        '<div class="flex items-baseline justify-between gap-4">' .
                        '<span class="text-muted-foreground">Körnung</span>' .
                        '<span class="font-semibold tabular-nums">' .
                        esc_html($product['grain']) .
                        '</span></div>' .
                        '<div class="flex items-baseline justify-between gap-4">' .
                        '<span class="text-muted-foreground">SiO₂</span>' .
                        '<span class="font-semibold tabular-nums">' .
                        esc_html($product['sio']) .
                        '</span></div>' .
                        '<div class="flex items-baseline justify-between gap-4">' .
                        '<span class="text-muted-foreground">Lieferform</span>' .
                        '<span class="font-semibold">' .
                        esc_html($product['form']) .
                        '</span></div></div>' .
                        '<a class="text-sm font-semibold text-henge-green" href="#">Datenblatt öffnen</a>';
                    ?>
                    <div class="flex items-baseline justify-between gap-5 py-3.5">
                        <?php get_template_part('template-parts/base/hover-card', null, [
                            'config' => [
                                'trigger' => $product_row_trigger,
                                'content' => $product_row_content,
                            ],
                        ]); ?>
                        <span class="text-sm text-muted-foreground tabular-nums"
                            ><?php echo esc_html($product['grain']); ?></span
                        >
                    </div>
                <?php
                endforeach;
                ?>
            </div>
        </div>
    </section>
</div>

<?php get_footer(); ?>
