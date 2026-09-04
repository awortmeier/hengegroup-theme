<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Spinner
 *
 * Dev-only page template: renders template-parts/base/spinner.php across every documented config
 * option (size, color, decorative, in-button composition, class passthrough) for manual visual/
 * functional review during Phase 2 styling work -- not meant for production content or navigation.
 * Analog zu page-component-showcase-attachment.php/-progress.php.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Spinner"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses
 * the existing per-page mechanism instead of a second one.
 *
 * The "Im Kontext" section's card/list chrome (borders, headings, meta text) is showcase markup,
 * not part of spinner.php itself -- same "component renders only the indicator, the caller
 * composes the surrounding chrome" minimalism as progress.php/progress-circle.php. The reference's
 * "Auf dunklem Grund" section is intentionally not reproduced here, see spinner.php's own header.
 *
 * Design reference: https://claude.ai/code/artifact/795f39d7-99e9-4211-9b9a-c15dabacc6ab
 */

get_header();
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Spinner — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        Alle Config-Optionen von <code>template-parts/base/spinner.php</code>. Dev-only, nicht für
        Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Basis</h2>
        <p class="mb-4 text-sm text-neutral-500">
            Ein Ring in Hairline-Stärke, offene Viertelkante, gleichmäßige Drehung --
            <code>decorative</code> weggelassen (Default: <code>false</code>) bzw. auf
            <code>true</code> gesetzt, wenn eigener sichtbarer Statustext danebensteht.
        </p>
        <div class="flex items-center gap-6">
            <?php get_template_part('template-parts/base/spinner', null, [
                'config' => ['aria_label' => 'Wird geladen'],
            ]); ?>
            <div class="flex items-center gap-2 text-sm text-neutral-700">
                <?php get_template_part('template-parts/base/spinner', null, [
                    'config' => ['decorative' => true],
                ]); ?>
                <span>Daten werden geladen</span>
            </div>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Größen (<code>size</code>)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            Die Strichstärke wächst mit dem Durchmesser, damit der Ring auf jeder Größe gleich
            dicht wirkt (Effekt der gemeinsamen 24er-<code>viewBox</code>, siehe Kopfkommentar --
            kein Config-Wert dafür nötig).
        </p>
        <div class="flex flex-wrap items-end gap-8">
            <?php foreach (
                [
                    ['size' => 'sm', 'name' => 'Klein', 'px' => '14 px'],
                    ['size' => 'base', 'name' => 'Standard', 'px' => '20 px'],
                    ['size' => 'lg', 'name' => 'Groß', 'px' => '28 px'],
                    ['size' => 'xl', 'name' => 'Sehr groß', 'px' => '40 px'],
                ]
                as $row
            ): ?>
                <div class="flex flex-col items-center gap-2">
                    <?php get_template_part('template-parts/base/spinner', null, [
                        'config' => ['size' => $row['size'], 'aria_label' => $row['name']],
                    ]); ?>
                    <div class="text-center">
                        <div class="text-sm font-semibold"><?php echo esc_html(
                            $row['name'],
                        ); ?></div>
                        <div class="font-mono text-xs text-neutral-500">
                            <?php echo esc_html($row['px']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Farben (<code>color</code>)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            <code>default</code> (Marken-Akzent) | <code>muted</code> (gedämpft, z.&nbsp;B.
            sekundäre/nachrangige Aktionen) | <code>inherit</code> (keine eigene Farbklasse, übernimmt
            <code>currentColor</code> der Umgebung -- siehe <code>In Buttons</code> unten).
        </p>
        <div class="flex flex-wrap items-center gap-8">
            <?php foreach (
                [
                    ['color' => 'default', 'name' => 'default'],
                    ['color' => 'muted', 'name' => 'muted'],
                ]
                as $row
            ): ?>
                <div class="flex flex-col items-center gap-2">
                    <?php get_template_part('template-parts/base/spinner', null, [
                        'config' => [
                            'size' => 'lg',
                            'color' => $row['color'],
                            'aria_label' => $row['name'],
                        ],
                    ]); ?>
                    <code class="text-xs text-neutral-500"><?php echo esc_html(
                        $row['name'],
                    ); ?></code>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">In Buttons</h2>
        <p class="mb-4 text-sm text-neutral-500">
            <code>button.php</code>s <code>loading</code>-Config komponiert diesen Spinner intern
            (<code>color: 'inherit'</code>, Größe aus der Button-<code>size</code> abgeleitet) --
            der Spinner ersetzt kein Label, er tritt davor. Die Breite des Buttons bleibt stabil.
        </p>
        <div class="flex flex-wrap items-center gap-4">
            <?php get_template_part('template-parts/base/button', null, [
                'config' => ['text' => 'Wird gesendet', 'loading' => true],
            ]); ?>
            <?php get_template_part('template-parts/base/button', null, [
                'config' => [
                    'text' => 'Datenblatt wird erzeugt',
                    'variant' => 'outline',
                    'loading' => true,
                ],
            ]); ?>
            <?php get_template_part('template-parts/base/button', null, [
                'config' => [
                    'text' => 'Prüfe Verfügbarkeit',
                    'variant' => 'ghost',
                    'loading' => true,
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Im Kontext</h2>
        <p class="mb-4 text-sm text-neutral-500">
            Als Platzhalter in einer Karte und als Fußzeile beim Nachladen einer Liste -- Karten-/
            Listen-Chrome ist Showcase-Markup, nicht Teil von <code>spinner.php</code> selbst.
        </p>
        <div class="grid gap-6 sm:grid-cols-2">
            <div
                class="flex min-h-48 flex-col items-center justify-center gap-3 rounded-xl border border-border bg-card px-6 py-10 text-center"
            >
                <?php get_template_part('template-parts/base/spinner', null, [
                    'config' => ['size' => 'lg', 'decorative' => true],
                ]); ?>
                <div>
                    <div class="text-base font-semibold">Kennwerte werden geladen</div>
                    <div class="text-sm text-neutral-500">Werk Nord, Charge 2026-08</div>
                </div>
            </div>
            <div class="overflow-hidden rounded-xl border border-border bg-card">
                <div class="border-b border-border px-5 py-4">
                    <div class="text-base font-semibold">Körnungen</div>
                    <div class="text-sm text-neutral-500">4 von 38</div>
                </div>
                <div class="divide-y divide-border">
                    <?php foreach (
                        ['0,1 – 0,5 mm' => 'verfügbar', '0,4 – 0,8 mm' => 'verfügbar']
                        as $range => $status
                    ): ?>
                        <div class="flex items-center justify-between px-5 py-3 text-sm">
                            <span><?php echo esc_html($range); ?></span>
                            <span class="font-semibold text-henge-green"><?php echo esc_html(
                                $status,
                            ); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="flex items-center gap-2 px-5 py-3 text-sm text-neutral-500">
                        <?php get_template_part('template-parts/base/spinner', null, [
                            'config' => ['size' => 'sm', 'decorative' => true],
                        ]); ?>
                        <span>Weitere Körnungen werden geladen</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Custom class (Passthrough)</h2>
        <p class="mb-4 text-sm text-neutral-500">
            Additive Klasse (Schatten) statt einer <code>text-*</code>-Farbe -- das ueberschreiben
            einer bereits per <code>color</code> gesetzten Utility per <code>class</code> ist nicht
            zuverlaessig, siehe button.php's Kopfkommentar; fuer eine andere Farbe stattdessen
            <code>color</code> nutzen.
        </p>
        <?php get_template_part('template-parts/base/spinner', null, [
            'config' => [
                'size' => 'lg',
                'class' => 'drop-shadow-sm',
                'aria_label' => 'Beispiel mit zusätzlichem Schatten',
            ],
        ]); ?>
    </section>
</div>

<?php get_footer(); ?>
