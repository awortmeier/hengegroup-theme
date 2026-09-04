<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Separator
 *
 * Dev-only page template: renders template-parts/base/separator/separator.php +
 * separator-label.php across orientation, `weight`, `style`, labeled/dot compositions and a few
 * in-context usages, for manual visual/functional review during Phase 2 styling work -- not meant
 * for production content or navigation. Analog zur page-component-showcase-kbd.php/-pagination.php.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Separator"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses the
 * existing per-page mechanism instead of a second one.
 *
 * Further slice of the "Komponenten-Showcase-Seite" idea documented as deliberately deferred in
 * docs/entscheidungen.md ("Komponenten-Showcase-Seite und Performance-Tooling") -- not the full
 * one-call-per-base-component page from that entry yet, see docs/to-do.md.
 */

get_header();
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Separator — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        <code>template-parts/base/separator/separator.php</code> +
        <code>separator-label.php</code>. Dev-only, nicht für Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Basis</h2>
        <div class="flex max-w-xl flex-col gap-2">
            <div class="flex flex-col gap-0.5">
                <span class="text-lg font-semibold">Hengegroup</span>
                <span class="text-sm text-neutral-500">Mineralische Rohstoffe und Aufbereitung</span>
            </div>
            <?php get_template_part('template-parts/base/separator/separator'); ?>
            <div class="flex flex-wrap gap-5 text-sm text-neutral-600">
                <span>Produkte</span>
                <span>Anwendungen</span>
                <span>Downloads</span>
            </div>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Stärken (<code>weight</code>)</h2>
        <div class="flex max-w-xl flex-col gap-6">
            <?php foreach (
                [
                    'thin' => ['Fein', '1 px · 8 %'],
                    'default' => ['Standard', '1 px · 12 %'],
                    'thick' => ['Kräftig', '1 px · 24 %'],
                    'section' => ['Abschnitt', '3 px · 16 %'],
                ]
                as $weight => [$label, $meta]
            ): ?>
                <div class="flex flex-col gap-2">
                    <div class="flex items-baseline justify-between gap-4">
                        <span class="text-sm font-semibold text-neutral-600">
                            <?php echo esc_html($label); ?>
                        </span>
                        <span class="font-mono text-xs text-neutral-400">
                            <?php echo esc_html($meta); ?>
                        </span>
                    </div>
                    <?php get_template_part('template-parts/base/separator/separator', null, [
                        'config' => ['weight' => $weight],
                    ]); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Vertikal (<code>orientation</code>)</h2>
        <div class="flex flex-col gap-6">
            <div class="flex items-center gap-4 text-sm text-neutral-600">
                <span>Quarzsand</span>
                <?php get_template_part('template-parts/base/separator/separator', null, [
                    'config' => ['orientation' => 'vertical'],
                ]); ?>
                <span>0,1 – 0,5 mm</span>
                <?php get_template_part('template-parts/base/separator/separator', null, [
                    'config' => ['orientation' => 'vertical'],
                ]); ?>
                <span>Werk Nord</span>
            </div>

            <div
                class="inline-flex w-fit items-stretch overflow-hidden rounded-xl border border-border bg-background shadow-xs"
            >
                <?php foreach (
                    ['Übersicht', 'Körnungen', 'Kennwerte', 'Freigaben']
                    as $i => $label
                ): ?>
                    <?php if ($i > 0): ?>
                        <?php get_template_part('template-parts/base/separator/separator', null, [
                            'config' => ['orientation' => 'vertical'],
                        ]); ?>
                    <?php endif; ?>
                    <span class="px-4.5 py-2.5 text-sm font-semibold text-neutral-700">
                        <?php echo esc_html($label); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Mit Beschriftung (<code>separator-label.php</code>)
        </h2>
        <div class="flex max-w-xl flex-col gap-8">
            <?php get_template_part('template-parts/base/separator/separator-label', null, [
                'config' => ['label' => 'Technische Daten'],
            ]); ?>
            <?php get_template_part('template-parts/base/separator/separator-label', null, [
                'config' => ['label' => 'oder', 'position' => 'center'],
            ]); ?>
            <?php get_template_part('template-parts/base/separator/separator-label'); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Stile (<code>style</code>)</h2>
        <div class="flex max-w-xl flex-col gap-6">
            <div class="flex flex-col gap-2">
                <span class="text-sm font-semibold text-neutral-600">Gestrichelt</span>
                <?php get_template_part('template-parts/base/separator/separator', null, [
                    'config' => ['style' => 'dashed'],
                ]); ?>
            </div>
            <div class="flex flex-col gap-2">
                <span class="text-sm font-semibold text-neutral-600">Akzent</span>
                <?php get_template_part('template-parts/base/separator/separator', null, [
                    'config' => ['weight' => 'section', 'style' => 'accent', 'class' => 'w-16'],
                ]); ?>
            </div>
            <div class="flex flex-col gap-2">
                <span class="text-sm font-semibold text-neutral-600">Verlauf</span>
                <?php get_template_part('template-parts/base/separator/separator', null, [
                    'config' => [
                        'class' => 'bg-gradient-to-r from-foreground/22 to-transparent',
                    ],
                ]); ?>
            </div>
            <div class="flex flex-col gap-2">
                <span class="text-sm font-semibold text-neutral-600">
                    Farbverlauf (henge-blue – henge-green – henge-grey)
                </span>
                <?php get_template_part('template-parts/base/separator/separator', null, [
                    'config' => ['weight' => 'section', 'style' => 'gradient'],
                ]); ?>
            </div>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Im Kontext</h2>
        <div class="flex flex-wrap gap-6">
            <div
                class="flex-1 basis-80 overflow-hidden rounded-xl border border-border bg-background shadow-xs"
            >
                <div class="flex flex-col gap-0.5 px-6 pt-5 pb-4">
                    <span class="text-lg font-semibold">Quarzsand HG 04</span>
                    <span class="text-sm text-neutral-500">Datenblatt 2026</span>
                </div>
                <?php get_template_part('template-parts/base/separator/separator'); ?>
                <div class="flex flex-col px-6 py-1.5">
                    <?php foreach (
                        [
                            ['Körnung', '0,1 – 0,5 mm'],
                            ['SiO₂-Gehalt', '98,6 %'],
                            ['Schüttdichte', '1,52 t/m³'],
                        ]
                        as $i => [$label, $value]
                    ): ?>
                        <?php if ($i > 0): ?>
                            <?php get_template_part(
                                'template-parts/base/separator/separator',
                                null,
                                [
                                    'config' => ['weight' => 'thin'],
                                ],
                            ); ?>
                        <?php endif; ?>
                        <div class="flex items-baseline justify-between gap-5 py-3">
                            <span class="text-sm text-neutral-500"><?php echo esc_html(
                                $label,
                            ); ?></span>
                            <span class="text-sm font-semibold tabular-nums">
                                <?php echo esc_html($value); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php get_template_part('template-parts/base/separator/separator'); ?>
                <div class="flex items-center gap-3.5 px-6 py-4">
                    <a href="#" class="text-sm font-semibold">Datenblatt laden</a>
                    <?php get_template_part('template-parts/base/separator/separator', null, [
                        'config' => ['orientation' => 'vertical'],
                    ]); ?>
                    <a href="#" class="text-sm font-semibold">Anfrage</a>
                </div>
            </div>

            <div
                class="flex flex-1 basis-80 flex-col gap-4.5 rounded-xl border border-border bg-background p-6 shadow-xs"
            >
                <div class="flex flex-col gap-1.5">
                    <span class="text-lg font-semibold">Standorte</span>
                    <span class="text-sm text-neutral-500">Vier Werke, ein Ansprechpartner</span>
                </div>
                <?php get_template_part('template-parts/base/separator/separator'); ?>
                <p class="text-base leading-relaxed text-foreground/80">
                    Die Trennlinie hält Abschnitte auseinander, ohne eine zweite Fläche
                    einzuführen.
                </p>
                <?php get_template_part('template-parts/base/separator/separator', null, [
                    'config' => ['style' => 'dashed'],
                ]); ?>
                <div class="flex flex-wrap items-center gap-3 text-sm text-neutral-500">
                    <span>Zuletzt geprüft</span>
                    <?php get_template_part('template-parts/base/separator/separator', null, [
                        'config' => ['orientation' => 'vertical'],
                    ]); ?>
                    <span>03.09.2026</span>
                </div>
            </div>
        </div>
    </section>
</div>

<?php get_footer(); ?>
