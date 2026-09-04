<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Tooltip
 *
 * Dev-only page template: renders template-parts/base/tooltip.php across `side` (all four),
 * `align` (start/center/end), a plain-text trigger button, an icon-only toolbar and a
 * dotted-underline "help" trigger inside a data row, for manual visual/functional review during
 * Phase 2 styling work -- not meant for production content or navigation. Analog zu
 * page-component-showcase-toast.php/-tabs.php.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Tooltip"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses the
 * existing per-page mechanism instead of a second one.
 *
 * Design reference: https://claude.ai/code/artifact/ee1a05dc-6403-4338-85f2-9e7531331931
 */

get_header();
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Tooltip — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        <code>template-parts/base/tooltip.php</code> + <code>tooltip.js</code>. Dev-only, nicht für
        Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Basis</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Kurzer Hinweis beim Überfahren oder bei Tastaturfokus. Dunkle Karte, ein Satz, keine
            Aktion darin.
        </p>
        <div class="flex flex-wrap items-center gap-10">
            <?php ob_start(); ?>
            <?php get_template_part('template-parts/base/button', null, [
                'config' => ['variant' => 'henge-green', 'text' => 'Datenblatt'],
            ]); ?>
            <?php
            $trigger_datasheet = (string) ob_get_clean();

            get_template_part('template-parts/base/tooltip', null, [
                'config' => ['trigger' => $trigger_datasheet, 'text' => 'PDF, 1,2 MB'],
            ]);

            ob_start();
            ?>
            <?php get_template_part('template-parts/base/button', null, [
                'config' => ['variant' => 'outline', 'text' => 'Zum Überfahren'],
            ]); ?>
            <?php
            $trigger_hover = (string) ob_get_clean();

            get_template_part('template-parts/base/tooltip', null, [
                'config' => ['trigger' => $trigger_hover, 'text' => 'Erscheint nach 700 ms'],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Positionen (<code>side</code>)</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Vier Seiten, jeweils 10&nbsp;px Abstand und mittig ausgerichteter Pfeil.
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
                    <?php get_template_part('template-parts/base/tooltip', null, [
                        'config' => [
                            'trigger' => $side_trigger,
                            'text' => $label . ' ausgerichtet',
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
        <h2 class="mb-2 text-xl font-semibold">Ausrichtung (<code>align</code>)</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Kreuzachse relativ zum Trigger: <code>start</code>/<code>end</code> bündig statt
            <code>center</code> (Default) mittig.
        </p>
        <div
            class="flex flex-wrap items-center gap-10 rounded-2xl border border-neutral-200 bg-neutral-50 p-14"
        >
            <?php
            $aligns = ['start' => 'Start', 'center' => 'Center', 'end' => 'End'];

            foreach ($aligns as $align => $label):
                ob_start(); ?>
                <?php get_template_part('template-parts/base/button', null, [
                    'config' => ['variant' => 'outline', 'text' => $label],
                ]); ?>
                <?php $align_trigger = (string) ob_get_clean(); ?>
                <div class="flex flex-col items-center gap-3">
                    <?php get_template_part('template-parts/base/tooltip', null, [
                        'config' => [
                            'trigger' => $align_trigger,
                            'text' => 'align="' . $align . '"',
                            'side' => 'bottom',
                            'align' => $align,
                        ],
                    ]); ?>
                    <span class="font-mono text-xs text-neutral-400">align="<?php echo esc_html(
                        $align,
                    ); ?>"</span>
                </div>
            <?php
            endforeach;
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Auf Symbolen und Kennwerten</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Der häufigste Fall: ein Symbol ohne Beschriftung oder eine Abkürzung in einer Tabelle.
        </p>
        <div class="flex flex-wrap gap-6">
            <div class="flex-1 basis-72 rounded-xl border border-neutral-200 bg-neutral-50 p-6">
                <span class="mb-4 block text-base font-semibold">Werkzeugleiste</span>
                <div class="flex gap-2.5">
                    <?php
                    $toolbar_icons = [
                        'search' => 'Kennwerte durchsuchen',
                        'eye' => 'Detailansicht öffnen',
                        'archive' => 'In Archiv verschieben',
                        'truck' => 'Versand anfragen',
                    ];

                    foreach ($toolbar_icons as $icon_name => $icon_label):
                        ob_start(); ?>
                        <?php get_template_part('template-parts/base/button', null, [
                            'config' => [
                                'variant' => 'outline',
                                'size' => 'icon-base',
                                'aria_label' => $icon_label,
                                'icon' => ['name' => $icon_name, 'set' => 'lucide'],
                            ],
                        ]); ?>
                        <?php $icon_trigger = (string) ob_get_clean(); ?>
                        <?php get_template_part('template-parts/base/tooltip', null, [
                            'config' => ['trigger' => $icon_trigger, 'text' => $icon_label],
                        ]); ?>
                    <?php
                    endforeach;
                    ?>
                </div>
            </div>

            <div class="flex-1 basis-96 rounded-xl border border-neutral-200 bg-neutral-50 p-6">
                <span class="mb-4 block text-base font-semibold">Kennwerte</span>
                <div class="flex flex-col divide-y divide-neutral-100">
                    <?php
                    $rows = [
                        [
                            'label' => 'SiO₂',
                            'value' => '98,6 %',
                            'text' => 'Siliziumdioxid, Hauptbestandteil des Quarzsands.',
                        ],
                        [
                            'label' => 'd₅₀',
                            'value' => '0,62 mm',
                            'text' => 'Mittlerer Korndurchmesser: 50 % der Körner sind feiner.',
                        ],
                        [
                            'label' => 'pH-Wert',
                            'value' => '7,2',
                            'text' => 'Gemessen in wässriger Suspension nach DIN EN ISO 10390.',
                        ],
                    ];

                    foreach ($rows as $row):
                        $row_trigger = sprintf(
                            '<span tabindex="0" class="cursor-help border-b border-dotted border-neutral-400 text-neutral-500">%s</span>',
                            esc_html($row['label']),
                        ); ?>
                        <div class="flex items-baseline justify-between gap-5 py-3">
                            <?php get_template_part('template-parts/base/tooltip', null, [
                                'config' => [
                                    'trigger' => $row_trigger,
                                    'text' => $row['text'],
                                    'align' => 'start',
                                ],
                            ]); ?>
                            <span class="font-semibold tabular-nums"
                                ><?php echo esc_html($row['value']); ?></span
                            >
                        </div>
                    <?php
                    endforeach;
                    ?>
                </div>
            </div>
        </div>
    </section>
</div>

<?php get_footer(); ?>
