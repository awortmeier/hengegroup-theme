<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Accordion
 *
 * Dev-only page template: renders template-parts/base/accordion.php across every documented config
 * option (type: single/multiple, color: default/light, heading_tag, icon true/false/custom,
 * default_open, class passthrough) for manual visual/functional review during Phase 2 styling work
 * -- not meant for production content or navigation. Analog zu
 * page-component-showcase-badge.php/page-component-showcase-typography.php.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Accordion"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses
 * the existing per-page mechanism instead of a second one.
 *
 * Another slice of the "Komponenten-Showcase-Seite" idea documented as deliberately deferred in
 * docs/entscheidungen.md ("Komponenten-Showcase-Seite und Performance-Tooling") -- one page per
 * component, not the full one-call-per-base-component page from that entry yet, see
 * docs/to-do.md.
 */

get_header();
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Accordion — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        Alle Config-Optionen von <code>template-parts/base/accordion.php</code>. Dev-only, nicht
        für Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Basis (<code>type: single</code>)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            Nur ein Eintrag gleichzeitig geöffnet — native
            <code>&lt;details name="..."&gt;</code>-Gruppierung, kein JS.
        </p>
        <div class="max-w-2xl">
            <?php get_template_part('template-parts/base/accordion', null, [
                'config' => [
                    'items' => [
                        [
                            'trigger' => 'Welche Körnungen sind lieferbar?',
                            'content' =>
                                'Wir liefern FEPA-F- und FEPA-P-Körnungen in Makro- und ' .
                                'Mikroqualität sowie metrische Körnungen. Sonderkörnungen ' .
                                'fertigen wir auf Anfrage.',
                        ],
                        [
                            'trigger' => 'Wie erfolgt die Lieferung?',
                            'content' =>
                                'Standardmäßig in 25-kg-Säcken, Big Bags à 1.000 kg oder lose ' .
                                'im Silofahrzeug. Die Lieferzeit beträgt in der Regel drei bis ' .
                                'fünf Werktage.',
                        ],
                        [
                            'trigger' => 'Können Strahlmittel aufbereitet werden?',
                            'content' =>
                                'Ja. Gebrauchtes Material nehmen wir zurück, sieben und ' .
                                'klassieren es und führen es dem Kreislauf wieder zu. ' .
                                'Sprechen Sie uns dazu an.',
                        ],
                    ],
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Mehrfachauswahl (<code>type: multiple</code>)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            Jeder Eintrag klappt unabhängig auf/zu — kein <code>name</code>-Attribut, also keine
            native Gruppierung. Zweiter Eintrag hier per <code>default_open</code> initial offen.
        </p>
        <div class="max-w-2xl">
            <?php get_template_part('template-parts/base/accordion', null, [
                'config' => [
                    'type' => 'multiple',
                    'items' => [
                        [
                            'trigger' => 'Strahlmittel',
                            'content' =>
                                'Mineralische und metallische Strahlmittel für Entzunderung, ' .
                                'Entrostung und Oberflächenverfestigung.',
                        ],
                        [
                            'trigger' => 'Schleifmittel',
                            'content' =>
                                'Normalkorund, Edelkorund und Siliciumcarbid für gebundene und ' .
                                'beschichtete Schleifmittel.',
                            'default_open' => true,
                        ],
                        [
                            'trigger' => 'Feuerfest-Produkte',
                            'content' =>
                                'Hitzebeständige Rohstoffe für feuerfeste Massen, Steine und ' .
                                'Gießereianwendungen.',
                        ],
                    ],
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Auf dunklem Grund (<code>color: light</code>)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            Recolort nur Text/Ränder, siehe Kopfkommentar — die dunkle Fläche selbst
            (<code>bg-grey-dark</code>, Radius, Padding) bleibt Aufgabe der aufrufenden Stelle,
            gleiche Konvention wie <code>typography.php</code>s eigenes <code>color: light</code>
            oben in dessen Showcase.
        </p>
        <div class="max-w-2xl rounded-lg bg-grey-dark p-6">
            <?php get_template_part('template-parts/base/accordion', null, [
                'config' => [
                    'color' => 'light',
                    'items' => [
                        [
                            'trigger' => 'Ab welcher Menge liefern Sie?',
                            'content' =>
                                'Die Mindestabnahme liegt bei 500 kg. Für Musterlieferungen ' .
                                'und Erstversuche stellen wir kleinere Mengen bereit.',
                        ],
                        [
                            'trigger' => 'Liefern Sie auch international?',
                            'content' =>
                                'Ja, über unsere Standorte in Europa liefern wir weltweit. ' .
                                'Zoll- und Versandabwicklung übernehmen wir auf Wunsch komplett.',
                        ],
                        [
                            'trigger' => 'Wie lange sind Angebote gültig?',
                            'content' =>
                                'Angebote sind 30 Tage gültig. Bei Rahmenverträgen ' .
                                'vereinbaren wir feste Preise über die gesamte Laufzeit.',
                        ],
                    ],
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Mit Überschrift (<code>heading_tag</code>)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            Empfohlenes a11y-Pattern für FAQ-Abschnitte (WAI-ARIA APG) — jeder Trigger-Text steckt
            zusätzlich in einer echten <code>&lt;h3&gt;</code>.
        </p>
        <div class="max-w-2xl">
            <?php get_template_part('template-parts/base/accordion', null, [
                'config' => [
                    'heading_tag' => 'h3',
                    'items' => [
                        [
                            'trigger' => 'Welche Zertifikate liegen vor?',
                            'content' =>
                                'Für alle Produkte stellen wir Werkszeugnisse, ' .
                                'Sicherheitsdatenblätter nach REACH und auf Wunsch ' .
                                'Konformitätsnachweise bereit.',
                        ],
                        [
                            'trigger' => 'Bieten Sie auch Analysen an?',
                            'content' =>
                                'Ja, unser hauseigenes Labor führt Korngrößen- und ' .
                                'Reinheitsanalysen nach Kundenwunsch durch.',
                        ],
                    ],
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Ohne Icon (<code>icon: false</code>)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            Ohne Chevron übernimmt der native <code>&lt;summary&gt;</code>-Marker die
            Auf/Zu-Anzeige — kein <code>list-none</code>, kein Marker-Hiding.
        </p>
        <div class="max-w-2xl">
            <?php get_template_part('template-parts/base/accordion', null, [
                'config' => [
                    'icon' => false,
                    'items' => [
                        [
                            'trigger' => 'Gibt es einen Mindestbestellwert?',
                            'content' =>
                                'Nein, es gilt lediglich die Mindestabnahmemenge pro Produkt.',
                        ],
                        [
                            'trigger' => 'Wie kann ich ein Angebot anfragen?',
                            'content' => 'Über das Kontaktformular oder direkt per Telefon/E-Mail.',
                        ],
                    ],
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Eigenes Icon (<code>icon</code>)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            Beliebiges <code>icon.php</code>-Config statt des Standard-Chevrons — rotiert beim
            Öffnen genauso über <code>group-open:</code>.
        </p>
        <div class="max-w-2xl">
            <?php get_template_part('template-parts/base/accordion', null, [
                'config' => [
                    'icon' => ['name' => 'circle-check', 'set' => 'lucide'],
                    'items' => [
                        [
                            'trigger' => 'Sind Ihre Produkte REACH-konform?',
                            'content' =>
                                'Ja, alle Produkte entsprechen den aktuellen ' .
                                'REACH-Anforderungen der EU.',
                        ],
                    ],
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Custom class (Passthrough)</h2>
        <p class="mb-4 text-sm text-neutral-500">
            <code>class</code> landet auf dem äußeren Wrapper (<code>data-slot="accordion"</code>).
        </p>
        <div class="max-w-2xl">
            <?php get_template_part('template-parts/base/accordion', null, [
                'config' => [
                    'class' => 'rounded-lg border border-border px-4',
                    'items' => [
                        [
                            'trigger' => 'Beispiel mit zusätzlichem Wrapper-Styling',
                            'content' =>
                                'Dieser Accordion bekommt via <code>class</code> einen ' .
                                'zusätzlichen Rahmen und horizontales Padding.',
                        ],
                    ],
                ],
            ]); ?>
        </div>
    </section>
</div>

<?php get_footer(); ?>
