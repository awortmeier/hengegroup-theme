<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Skeleton
 *
 * Dev-only page template: renders template-parts/base/skeleton.php across every documented config
 * option (shape, color, decorative, class passthrough) plus a few realistic compositions, for
 * manual visual/functional review during Phase 2 styling work -- not meant for production content
 * or navigation. Analog zu page-component-showcase-spinner.php.
 *
 * The "Karte"/"Liste und Tabelle" sections' card/table chrome (borders, headings, cell layout) is
 * showcase markup, not part of skeleton.php itself -- same "component renders only the shape, the
 * caller composes the surrounding chrome" minimalism as spinner.php's own "Im Kontext" section.
 * "Übergang" is shown as a static side-by-side comparison (Platzhalter | Geladen) rather than the
 * reference's interactive toggle button -- the point being demonstrated (skeleton dimensions match
 * the real content exactly, nothing shifts on load) doesn't need real interactivity to verify, and
 * this avoids introducing a new show/hide interaction pattern purely for a dev-only showcase page.
 *
 * Design reference: https://claude.ai/artifact/AgUuYjLPw8bHzEXojvtqyj
 */

get_header();
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Skeleton — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        Alle Config-Optionen von <code>template-parts/base/skeleton.php</code>. Dev-only, nicht für
        Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Basis</h2>
        <p class="mb-4 text-sm text-neutral-500">
            Ein Platzhalter nimmt die Form des späteren Inhalts vorweg -- hier ein Avatar
            (<code>circle</code>) neben zwei Textzeilen und ein Absatz aus drei Zeilen
            unterschiedlicher Breite.
        </p>
        <div class="flex items-start gap-4">
            <?php get_template_part('template-parts/base/skeleton', null, [
                'config' => ['shape' => 'circle'],
            ]); ?>
            <div class="flex flex-1 flex-col gap-2">
                <?php get_template_part('template-parts/base/skeleton', null, [
                    'config' => ['class' => 'w-1/3'],
                ]); ?>
                <?php get_template_part('template-parts/base/skeleton', null, [
                    'config' => ['class' => 'w-1/4'],
                ]); ?>
            </div>
            <div class="flex flex-1 flex-col gap-2">
                <?php get_template_part('template-parts/base/skeleton', null, ['config' => []]); ?>
                <?php get_template_part('template-parts/base/skeleton', null, ['config' => []]); ?>
                <?php get_template_part('template-parts/base/skeleton', null, [
                    'config' => ['class' => 'w-2/3'],
                ]); ?>
            </div>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Formen (<code>shape</code>)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            Vier Grundformen decken alle Fälle ab. Der Radius folgt dem Element, das ersetzt wird.
        </p>
        <div class="flex flex-wrap items-end gap-8">
            <?php foreach (
                [
                    ['shape' => 'line', 'name' => 'Zeile', 'hint' => 'Text', 'class' => 'w-48'],
                    ['shape' => 'block', 'name' => 'Block', 'hint' => 'Bild', 'class' => 'w-32'],
                    ['shape' => 'circle', 'name' => 'Kreis', 'hint' => 'Avatar', 'class' => ''],
                    ['shape' => 'pill', 'name' => 'Pille', 'hint' => 'Button', 'class' => ''],
                ]
                as $row
            ): ?>
                <div class="flex flex-col items-start gap-2">
                    <?php get_template_part('template-parts/base/skeleton', null, [
                        'config' => [
                            'shape' => $row['shape'],
                            'class' => $row['class'],
                            'aria_label' => $row['name'],
                        ],
                    ]); ?>
                    <div>
                        <div class="text-sm font-semibold"><?php echo esc_html(
                            $row['name'],
                        ); ?></div>
                        <div class="font-mono text-xs text-neutral-500">
                            <?php echo esc_html($row['hint']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Karte</h2>
        <p class="mb-4 text-sm text-neutral-500">
            Der Platzhalter übernimmt Rahmen, Radius und Innenabstand der fertigen Karte, damit
            beim Laden nichts springt -- Kartenrahmen ist <code>card.php</code>, Bild-/Text-/Button-
            Platzhalter darin reine <code>skeleton.php</code>-Formen (<code>block</code>/<code>line</code>/<code>pill</code>).
        </p>
        <div class="grid gap-6 sm:grid-cols-3">
            <?php foreach (range(1, 3) as $i): ?>
                <?php ob_start(); ?>
                <div class="flex flex-col gap-2">
                    <?php get_template_part('template-parts/base/skeleton', null, [
                        'config' => ['shape' => 'block', 'class' => 'mb-2 h-28'],
                    ]); ?>
                    <?php get_template_part('template-parts/base/skeleton', null, [
                        'config' => ['class' => 'w-2/3'],
                    ]); ?>
                    <?php get_template_part('template-parts/base/skeleton', null, [
                        'config' => [],
                    ]); ?>
                    <?php get_template_part('template-parts/base/skeleton', null, [
                        'config' => ['class' => 'w-1/2'],
                    ]); ?>
                </div>
                <?php
                $content = (string) ob_get_clean();

                ob_start();
                ?>
                <?php get_template_part('template-parts/base/skeleton', null, [
                    'config' => ['shape' => 'pill'],
                ]); ?>
                <?php
                $footer = (string) ob_get_clean();

                get_template_part('template-parts/base/card', null, [
                    'config' => [
                        'content' => $content,
                        'footer' => $footer,
                    ],
                ]);
                ?>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Liste und Tabelle</h2>
        <p class="mb-4 text-sm text-neutral-500">
            Zeilenhöhe und Spaltenbreiten bleiben erhalten. Nur die Inhalte fehlen noch -- Tabellen-
            Chrome ist Showcase-Markup, nicht Teil von <code>skeleton.php</code>.
        </p>
        <div class="overflow-hidden rounded-xl border border-border">
            <div class="grid grid-cols-4 gap-4 bg-muted px-5 py-3 text-xs font-semibold tracking-wide text-neutral-500 uppercase">
                <span>Bezeichnung</span>
                <span>Körnung</span>
                <span>Werk</span>
                <span>Menge</span>
            </div>
            <div class="divide-y divide-border">
                <?php foreach (range(1, 4) as $i): ?>
                    <div class="grid grid-cols-4 items-center gap-4 px-5 py-3">
                        <div class="flex items-center gap-3">
                            <?php get_template_part('template-parts/base/skeleton', null, [
                                'config' => ['shape' => 'circle', 'class' => 'size-6'],
                            ]); ?>
                            <?php get_template_part('template-parts/base/skeleton', null, [
                                'config' => ['class' => 'w-32'],
                            ]); ?>
                        </div>
                        <?php get_template_part('template-parts/base/skeleton', null, [
                            'config' => ['class' => 'w-16'],
                        ]); ?>
                        <?php get_template_part('template-parts/base/skeleton', null, [
                            'config' => ['class' => 'w-20'],
                        ]); ?>
                        <?php get_template_part('template-parts/base/skeleton', null, [
                            'config' => ['class' => 'w-12'],
                        ]); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Übergang</h2>
        <p class="mb-4 text-sm text-neutral-500">
            Derselbe Block als Platzhalter und als geladener Inhalt -- die Maße stimmen überein
            (statischer Vergleich statt der Referenz-Toggle, siehe Dateikopf).
        </p>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-border bg-card p-4">
                <div class="mb-2 text-xs font-semibold text-neutral-500">Platzhalter</div>
                <div class="flex items-center gap-3">
                    <?php get_template_part('template-parts/base/skeleton', null, [
                        'config' => ['shape' => 'circle'],
                    ]); ?>
                    <div class="flex flex-1 flex-col gap-2">
                        <?php get_template_part('template-parts/base/skeleton', null, [
                            'config' => ['class' => 'w-2/3'],
                        ]); ?>
                        <?php get_template_part('template-parts/base/skeleton', null, [
                            'config' => ['class' => 'w-1/2'],
                        ]); ?>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-border bg-card p-4">
                <div class="mb-2 text-xs font-semibold text-neutral-500">Geladen</div>
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-full bg-henge-green text-sm font-semibold text-henge-green-foreground">
                        MK
                    </div>
                    <div>
                        <div class="text-sm font-semibold">Martina Kessler</div>
                        <div class="text-sm text-neutral-500">
                            Vertrieb Mineralstoffe, Werk Nord.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Auf dunklem Grund (<code>color</code>)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            <code>default</code> (Standard, <code>bg-accent</code>) | <code>light</code>:
            aufgehellte Flächen statt abgedunkelter, der Streifen läuft mit geringerer Deckkraft --
            adaptiert nur die Formen selbst, malt nicht die dunkle Fläche darunter (das bleibt
            Aufgabe des Aufrufers, siehe Dateikopf).
        </p>
        <div class="flex items-center gap-4 rounded-xl bg-grey-dark p-6">
            <?php get_template_part('template-parts/base/skeleton', null, [
                'config' => ['shape' => 'circle', 'color' => 'light'],
            ]); ?>
            <div class="flex flex-1 flex-col gap-2">
                <?php get_template_part('template-parts/base/skeleton', null, [
                    'config' => ['color' => 'light', 'class' => 'w-2/3'],
                ]); ?>
                <?php get_template_part('template-parts/base/skeleton', null, [
                    'config' => ['color' => 'light', 'class' => 'w-1/2'],
                ]); ?>
            </div>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Custom class (Passthrough)</h2>
        <p class="mb-4 text-sm text-neutral-500">
            Additive Klasse für eine Größe/Form jenseits der <code>shape</code>-Defaults -- gleiches
            shadcn-Docs-Beispiel wie im Dateikopf zitiert.
        </p>
        <?php get_template_part('template-parts/base/skeleton', null, [
            'config' => [
                'class' => 'h-[20px] w-[100px] rounded-full',
                'aria_label' => 'Beispiel mit individueller Größe',
            ],
        ]); ?>
    </section>
</div>

<?php get_footer(); ?>
