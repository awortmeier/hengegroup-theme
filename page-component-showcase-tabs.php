<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Tabs
 *
 * Dev-only page template: renders template-parts/base/tabs.php across `variant` (line/default),
 * `orientation` (horizontal/vertical), icon/badge triggers and a disabled item, plus a composed
 * in-context example, for manual visual/functional review during Phase 2 styling work -- not meant
 * for production content or navigation. Analog zur page-component-showcase-kbd.php/
 * -separator.php: every buffered block below only ever feeds tabs.php's own `content` config
 * (caller's responsibility to build/escape, see that file's header), never gets `echo`ed raw on
 * this page -- direct tabs.php calls stay as plain `get_template_part()` calls, same as every
 * other showcase page.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Tabs"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses the
 * existing per-page mechanism instead of a second one.
 *
 * Further slice of the "Komponenten-Showcase-Seite" idea documented as deliberately deferred in
 * docs/entscheidungen.md ("Komponenten-Showcase-Seite und Performance-Tooling") -- not the full
 * one-call-per-base-component page from that entry yet, see docs/to-do.md.
 */

get_header();

// Buffers the HTML between this call and the next `hengegroup_theme_showcase_buffer()` call -- lets
// each tab panel below be written as plain HTML (readable, no manual string escaping) instead of
// one long concatenated string, while still handing tabs.php's `content` config a plain string.
$hengegroup_theme_showcase_buffer = static function (): string {
    return (string) ob_get_clean();
};
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Tabs — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        <code>template-parts/base/tabs.php</code>. Dev-only, nicht für Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Basis (<code>variant: 'line'</code>)</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Unterstrichene Reiter auf der Hairline, wie die Kanten in Tabellen und Listen.
        </p>
        <?php ob_start(); ?>
        <p class="text-base leading-relaxed text-foreground/80">
            Quarzsand HG 04 ist ein gewaschener, klassierter Quarzsand für Filtration, Estriche
            und Strahlanwendungen. Lieferung in Säcken, Big Bags oder lose.
        </p>
        <?php
        $content_overview = $hengegroup_theme_showcase_buffer();

        ob_start();
        ?>
        <p class="text-base leading-relaxed text-foreground/80">
            SiO₂-Gehalt 98,6&nbsp;%, Schüttdichte 1,52&nbsp;t/m³, Restfeuchte &lt;&nbsp;0,2&nbsp;%,
            Härte 7 (Mohs).
        </p>
        <?php
        $content_data = $hengegroup_theme_showcase_buffer();

        ob_start();
        ?>
        <p class="text-base leading-relaxed text-foreground/80">
            Wasseraufbereitung, Estrich- und Betonzuschlag, Sandstrahlen sowie Filterbettmaterial
            in kommunalen und industriellen Anlagen.
        </p>
        <?php
        $content_uses = $hengegroup_theme_showcase_buffer();

        get_template_part('template-parts/base/tabs', null, [
            'config' => [
                'variant' => 'line',
                'items' => [
                    [
                        'value' => 'uebersicht',
                        'trigger' => 'Übersicht',
                        'content' => $content_overview,
                    ],
                    [
                        'value' => 'technische-daten',
                        'trigger' => 'Technische Daten',
                        'content' => $content_data,
                    ],
                    [
                        'value' => 'anwendungen',
                        'trigger' => 'Anwendungen',
                        'content' => $content_uses,
                    ],
                ],
            ],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Segmentiert (<code>variant: 'default'</code>)</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Auf Karten und in Werkzeugleisten, wenn die Reiter eng beieinander stehen sollen.
        </p>
        <?php ob_start(); ?>
        <div class="rounded-xl border border-border bg-card p-6 shadow-xs">
            <div class="mb-4 flex flex-col gap-0.5">
                <span class="text-lg font-semibold">Kennwerte</span>
                <span class="text-sm text-neutral-500">Datenblatt 2026</span>
            </div>
            <dl class="flex flex-col divide-y divide-border">
                <?php foreach (
                    [
                        ['SiO₂-Gehalt', '98,6 %'],
                        ['Schüttdichte', '1,52 t/m³'],
                        ['Restfeuchte', '< 0,2 %'],
                        ['Härte (Mohs)', '7'],
                    ]
                    as [$label, $value]
                ): ?>
                    <div class="flex items-baseline justify-between gap-4 py-3">
                        <dt class="text-sm text-neutral-500"><?php echo esc_html($label); ?></dt>
                        <dd class="text-sm font-semibold tabular-nums">
                            <?php echo esc_html($value); ?>
                        </dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>
        <?php
        $content_kennwerte = $hengegroup_theme_showcase_buffer();

        ob_start();
        ?>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <?php foreach (
                [
                    [
                        'Korund braun',
                        'Entzunderung und Entrostung, hohe Standzeit im Kreislaufbetrieb.',
                    ],
                    ['Glasgranulat', 'Eisenfreies Strahlen empfindlicher Oberflächen.'],
                    ['Stahlkies', 'Oberflächenverfestigung und Reinigung von Guss.'],
                ]
                as [$title, $description]
            ): ?>
                <div class="rounded-xl border border-border bg-card p-5 shadow-xs">
                    <span class="block text-sm font-semibold"><?php echo esc_html($title); ?></span>
                    <span class="text-sm text-neutral-500">
                        <?php echo esc_html($description); ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        $content_koernungen = $hengegroup_theme_showcase_buffer();

        ob_start();
        ?>
        <p class="text-base leading-relaxed text-foreground/80">
            CE-Kennzeichnung nach Bauproduktenverordnung, Trinkwasserzulassung (DIN EN 12902)
            sowie werkseigene Produktionskontrolle nach DIN EN 13383.
        </p>
        <?php
        $content_freigaben = $hengegroup_theme_showcase_buffer();

        get_template_part('template-parts/base/tabs', null, [
            'config' => [
                'items' => [
                    [
                        'value' => 'kennwerte',
                        'trigger' => 'Kennwerte',
                        'content' => $content_kennwerte,
                    ],
                    [
                        'value' => 'koernungen',
                        'trigger' => 'Körnungen',
                        'content' => $content_koernungen,
                    ],
                    [
                        'value' => 'freigaben',
                        'trigger' => 'Freigaben',
                        'content' => $content_freigaben,
                    ],
                ],
            ],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Vertikal (<code>orientation: 'vertical'</code>)</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Für längere Reiterlisten neben dem Inhalt, etwa auf Produktdetailseiten.
        </p>
        <?php
        $vertical_panels = [
            'beschreibung' => [
                'Beschreibung',
                'Der Rohstoff wird im Werk Nord gebrochen, gewaschen und in mehreren Stufen ' .
                'klassiert. Jede Charge wird im hauseigenen Labor auf Körnung und Reinheit geprüft.',
            ],
            'lieferformen' => [
                'Lieferformen',
                'Verfügbar als 25-kg-Sack, 1.000-kg-Big-Bag oder lose per Silofahrzeug ab Werk ' .
                'Nord.',
            ],
            'aufbereitung' => [
                'Aufbereitung',
                'Waschklassierung mit anschließender Trocknung und Siebung auf die bestellte ' .
                'Körnung.',
            ],
            'sicherheit' => [
                'Sicherheit',
                'Sicherheitsdatenblatt nach REACH liegt jeder Lieferung bei, Staubentwicklung ' .
                'siehe Kapitel 8.',
            ],
        ];

        $vertical_items = [];

        foreach ($vertical_panels as $value => [$heading, $text]) {
            ob_start(); ?>
            <h3 class="mb-2 text-lg font-semibold"><?php echo esc_html($heading); ?></h3>
            <p class="text-base leading-relaxed text-foreground/80">
                <?php echo esc_html($text); ?>
            </p>
            <?php $vertical_items[] = [
                'value' => $value,
                'trigger' => $heading,
                'content' => $hengegroup_theme_showcase_buffer(),
            ];
        }

        get_template_part('template-parts/base/tabs', null, [
            'config' => [
                'variant' => 'line',
                'orientation' => 'vertical',
                'items' => $vertical_items,
            ],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Icon, Badge &amp; deaktiviert</h2>
        <?php ob_start(); ?>
        <p class="text-base leading-relaxed text-foreground/80">
            Vier Lieferungen warten auf Wareneingangsprüfung.
        </p>
        <?php
        $content_eingang = $hengegroup_theme_showcase_buffer();

        ob_start();
        ?>
        <p class="text-base leading-relaxed text-foreground/80">Keine offenen Prüfaufträge.</p>
        <?php
        $content_labor = $hengegroup_theme_showcase_buffer();

        ob_start();
        ?>
        <p class="text-base leading-relaxed text-foreground/80">Noch nicht freigeschaltet.</p>
        <?php
        $content_archiv = $hengegroup_theme_showcase_buffer();

        get_template_part('template-parts/base/tabs', null, [
            'config' => [
                'items' => [
                    [
                        'value' => 'eingang',
                        'trigger' => 'Wareneingang',
                        'icon' => ['name' => 'truck', 'set' => 'lucide'],
                        'badge' => ['text' => '4', 'variant' => 'secondary'],
                        'content' => $content_eingang,
                    ],
                    [
                        'value' => 'labor',
                        'trigger' => 'Labor',
                        'icon' => ['name' => 'flask-conical', 'set' => 'lucide'],
                        'content' => $content_labor,
                    ],
                    [
                        'value' => 'archiv',
                        'trigger' => 'Archiv',
                        'icon' => ['name' => 'archive', 'set' => 'lucide'],
                        'disabled' => true,
                        'content' => $content_archiv,
                    ],
                ],
            ],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Im Kontext</h2>
        <div class="max-w-lg rounded-2xl border border-border bg-card p-6 shadow-xs">
            <div class="mb-4 flex flex-col gap-0.5">
                <span class="text-lg font-semibold">Anfrage</span>
                <span class="text-sm text-neutral-500">Direkt an das zuständige Werk</span>
            </div>
            <?php ob_start(); ?>
            <h3 class="mb-1.5 text-base font-semibold">Anfrage stellen</h3>
            <p class="text-base leading-relaxed text-foreground/80">
                Nennen Sie Produkt, Körnung und Menge. Wir melden uns innerhalb eines Werktages
                mit Preis und Liefertermin.
            </p>
            <?php
            $content_anfrage = $hengegroup_theme_showcase_buffer();

            ob_start();
            ?>
            <h3 class="mb-1.5 text-base font-semibold">Beratung</h3>
            <p class="text-base leading-relaxed text-foreground/80">
                Unsere Anwendungstechnik unterstützt bei der Auswahl der passenden Körnung.
            </p>
            <?php
            $content_beratung = $hengegroup_theme_showcase_buffer();

            ob_start();
            ?>
            <h3 class="mb-1.5 text-base font-semibold">Standorte</h3>
            <p class="text-base leading-relaxed text-foreground/80">
                Vier Werke bundesweit, ein zentraler Ansprechpartner für die Disposition.
            </p>
            <?php
            $content_standorte = $hengegroup_theme_showcase_buffer();

            get_template_part('template-parts/base/tabs', null, [
                'config' => [
                    'items' => [
                        [
                            'value' => 'anfrage',
                            'trigger' => 'Anfrage',
                            'content' => $content_anfrage,
                        ],
                        [
                            'value' => 'beratung',
                            'trigger' => 'Beratung',
                            'content' => $content_beratung,
                        ],
                        [
                            'value' => 'standorte',
                            'trigger' => 'Standorte',
                            'content' => $content_standorte,
                        ],
                    ],
                ],
            ]);
            ?>
        </div>
    </section>
</div>

<?php get_footer(); ?>
