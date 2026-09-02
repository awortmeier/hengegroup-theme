<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Progress
 *
 * Dev-only page template: renders template-parts/base/progress/progress.php +
 * progress-circle.php + progress-steps.php across every documented config option (value/max,
 * size, variant/color, striped, indeterminate, aria) for manual visual/functional review during
 * Phase 2 styling work -- not meant for production content or navigation. Analog zu
 * page-component-showcase-accordion.php/-attachment.php.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Progress"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses
 * the existing per-page mechanism instead of a second one.
 *
 * All three components render only their own indicator, not the surrounding label/value text or
 * card chrome (see each file's own header comment) -- every section below composes that chrome
 * itself with plain markup, same as the design reference does around its own examples. The
 * reference's "Basis" section additionally wires live +10/-10 buttons updating the bar in real
 * time -- progress.php has zero JS (see that file's header), so this page shows static value
 * snapshots instead, same "no fake interactivity on a server-rendered demo page" approach the
 * other showcase pages already take.
 *
 * Design reference: https://claude.ai/code/artifact/742f972a-483b-4310-a64e-fc82e6b1d2d4
 */

get_header();
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Progress — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        Alle Config-Optionen von <code>template-parts/base/progress/progress.php</code> +
        <code>progress-circle.php</code> + <code>progress-steps.php</code>. Dev-only, nicht für
        Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Basis</h2>
        <p class="mb-4 text-sm text-neutral-500">
            Ein Balken mit Label und Wert (Label/Wert-Zeile ist Showcase-Markup, nicht Teil von
            <code>progress.php</code> selbst).
        </p>
        <div class="flex max-w-md flex-col gap-2">
            <div class="flex items-baseline justify-between gap-4">
                <span class="text-base font-semibold">Materialprüfung</span>
                <span class="text-sm tabular-nums text-neutral-500">64 %</span>
            </div>
            <?php get_template_part('template-parts/base/progress/progress', null, [
                'config' => [
                    'value' => 64,
                    'aria_label' => 'Materialprüfung',
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Größen (<code>size</code>)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            <code>sm</code> | <code>base</code> (Default) | <code>lg</code>.
        </p>
        <div class="flex max-w-md flex-col gap-5">
            <?php foreach (
                [
                    ['size' => 'sm', 'name' => 'Fein', 'value' => 35],
                    ['size' => 'base', 'name' => 'Standard', 'value' => 60],
                    ['size' => 'lg', 'name' => 'Stark', 'value' => 82],
                ]
                as $row
            ): ?>
                <div class="flex flex-col gap-2">
                    <div class="flex items-baseline justify-between">
                        <span class="text-sm font-semibold text-neutral-700"><?php echo esc_html(
                            $row['name'],
                        ); ?></span>
                        <span class="text-xs tabular-nums text-neutral-500"><?php echo esc_html(
                            $row['value'] . ' %',
                        ); ?></span>
                    </div>
                    <?php get_template_part('template-parts/base/progress/progress', null, [
                        'config' => [
                            'size' => $row['size'],
                            'value' => $row['value'],
                            'aria_label' => $row['name'],
                        ],
                    ]); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Zustände (<code>variant</code> / <code>striped</code> / <code>value</code>
            weggelassen)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            Laufend, unbestimmt, gestreift, abgeschlossen und fehlerhaft --
            <code>data-state</code> (<code>loading</code>/<code>complete</code>/
            <code>indeterminate</code>) ist rein wertabgeleitet, die Farbe kommt separat über
            <code>variant</code> (siehe Kopfkommentar).
        </p>
        <div class="flex max-w-md flex-col gap-5">
            <div class="flex flex-col gap-2">
                <div class="flex items-baseline justify-between gap-4">
                    <span class="text-base font-semibold">Upload läuft</span>
                    <span class="text-sm tabular-nums text-neutral-500">64 %</span>
                </div>
                <?php get_template_part('template-parts/base/progress/progress', null, [
                    'config' => ['value' => 64, 'aria_label' => 'Upload läuft'],
                ]); ?>
            </div>
            <div class="flex flex-col gap-2">
                <div class="flex items-baseline justify-between gap-4">
                    <span class="text-base font-semibold">Wird verarbeitet</span>
                    <span class="text-sm text-neutral-500">unbestimmt</span>
                </div>
                <?php get_template_part('template-parts/base/progress/progress', null, [
                    'config' => ['variant' => 'henge-blue', 'aria_label' => 'Wird verarbeitet'],
                ]); ?>
            </div>
            <div class="flex flex-col gap-2">
                <div class="flex items-baseline justify-between gap-4">
                    <span class="text-base font-semibold">Charge wird gesiebt</span>
                    <span class="text-sm tabular-nums text-neutral-500">48 %</span>
                </div>
                <?php get_template_part('template-parts/base/progress/progress', null, [
                    'config' => [
                        'value' => 48,
                        'variant' => 'henge-blue',
                        'striped' => true,
                        'aria_label' => 'Charge wird gesiebt',
                    ],
                ]); ?>
            </div>
            <div class="flex flex-col gap-2">
                <div class="flex items-baseline justify-between gap-4">
                    <span class="text-base font-semibold">Prüfung abgeschlossen</span>
                    <span class="text-sm tabular-nums text-henge-green">100 %</span>
                </div>
                <?php get_template_part('template-parts/base/progress/progress', null, [
                    'config' => ['value' => 100, 'aria_label' => 'Prüfung abgeschlossen'],
                ]); ?>
            </div>
            <div class="flex flex-col gap-2">
                <div class="flex items-baseline justify-between gap-4">
                    <span class="text-base font-semibold text-destructive">Abgebrochen</span>
                    <span class="text-sm tabular-nums text-destructive">bei 31 %</span>
                </div>
                <?php get_template_part('template-parts/base/progress/progress', null, [
                    'config' => [
                        'value' => 31,
                        'variant' => 'destructive',
                        'aria_label' => 'Abgebrochen bei 31 %',
                    ],
                ]); ?>
            </div>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Ring (<code>progress-circle.php</code>)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            Kompakte Variante für Kennzahlen und Kacheln -- Karten-Wrapper (Hintergrund, Rand,
            Schatten) ist Showcase-Markup, nicht Teil der Komponente.
        </p>
        <div class="flex flex-wrap gap-4">
            <?php foreach (
                [
                    [
                        'name' => 'Lagerbestand',
                        'meta' => '1.240 t von 1.600 t',
                        'value' => 1240,
                        'max' => 1600,
                        'variant' => 'henge-green',
                    ],
                    [
                        'name' => 'Auslastung Werk',
                        'meta' => 'Schicht 2',
                        'value' => 56,
                        'max' => 100,
                        'variant' => 'henge-blue',
                    ],
                    [
                        'name' => 'Reklamationsquote',
                        'meta' => 'Ziel unter 2 %',
                        'value' => 12,
                        'max' => 100,
                        'variant' => 'henge-grey',
                    ],
                ]
                as $tile
            ): ?>
                <div
                    class="flex min-w-60 flex-none items-center gap-4 rounded-2xl border border-black/5 bg-white px-5 py-4 shadow-xs"
                >
                    <?php get_template_part('template-parts/base/progress/progress-circle', null, [
                        'config' => [
                            'value' => $tile['value'],
                            'max' => $tile['max'],
                            'variant' => $tile['variant'],
                            'aria_label' => $tile['name'],
                        ],
                    ]); ?>
                    <div class="flex flex-col gap-0.5">
                        <div class="text-base font-semibold"><?php echo esc_html(
                            $tile['name'],
                        ); ?></div>
                        <div class="text-sm text-neutral-500"><?php echo esc_html(
                            $tile['meta'],
                        ); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Auf dunklem Grund (<code>progress-steps.php</code>, <code>color: 'light'</code>)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            Segmentierter Fortschritt für mehrstufige Abläufe, z.&nbsp;B. Bewerbung -- Label-Zeile
            unter den Segmenten ist Showcase-Markup (siehe Kopfkommentar von
            <code>progress-steps.php</code>), gefärbt über dieselbe <code>current</code>/
            <code>steps</code>-Zählung.
        </p>
        <?php
        $step_names = ['Daten', 'Unterlagen', 'Prüfung', 'Abschluss'];
        $step_current = 2;
        ?>
        <div
            class="flex flex-col gap-8 rounded-[20px] bg-neutral-900 bg-[radial-gradient(ellipse_60%_50%_at_20%_20%,rgba(255,255,255,0.08),transparent_60%)] px-11 py-10"
        >
            <div class="flex max-w-xl flex-col gap-3">
                <div class="flex items-baseline justify-between">
                    <span class="text-lg font-semibold text-neutral-100">Bewerbung</span>
                    <span class="text-sm tabular-nums text-neutral-100/70">
                        Schritt <?php echo esc_html((string) $step_current); ?> von
                        <?php echo esc_html((string) count($step_names)); ?>
                    </span>
                </div>
                <?php get_template_part('template-parts/base/progress/progress-steps', null, [
                    'config' => [
                        'steps' => count($step_names),
                        'current' => $step_current,
                        'color' => 'light',
                        'aria_label' => 'Bewerbung',
                    ],
                ]); ?>
                <div class="mt-1 flex flex-wrap gap-5">
                    <?php foreach ($step_names as $i => $name): ?>
                        <span
                            class="text-sm <?php echo $i < $step_current
                                ? 'text-neutral-100'
                                : 'text-neutral-100/45'; ?>"
                        >
                            <?php echo esc_html($name); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Custom class (Passthrough)</h2>
        <div class="max-w-md">
            <?php get_template_part('template-parts/base/progress/progress', null, [
                'config' => [
                    'value' => 45,
                    'class' => 'ring-2 ring-henge-green/40 ring-offset-2',
                    'aria_label' => 'Beispiel mit zusätzlichem Ring',
                ],
            ]); ?>
        </div>
    </section>
</div>

<?php get_footer(); ?>
