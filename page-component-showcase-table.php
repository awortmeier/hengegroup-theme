<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Table
 *
 * Dev-only page template: renders template-parts/base/table/table.php (+ its table-*.php family)
 * and template-parts/base/table/data-table.php across the Claude-Design reference "Hengegroup"'s
 * own sections (Basis, Varianten, Data Table), for manual visual/functional review during Phase 2
 * styling work -- not meant for production content or navigation. Analog zur
 * page-component-showcase-pagination.php.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Table"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses the
 * existing per-page mechanism instead of a second one.
 */

get_header();

/**
 * Buffers table-caption.php + table-header.php (built from $header_cells) + table-body.php (built
 * from $body_rows, each a buffered table-row.php call) + an optional table-footer.php, in HTML-spec
 * order (caption first), then passes the combined content to table.php -- same composition example
 * table.php's own file header documents.
 *
 * @param string $header_cells   Pre-rendered table-head.php calls, wrapped in one table-row.php
 * @param string $body_rows      Pre-rendered table-row.php calls (each wrapping table-cell.php)
 * @param string $caption
 * @param string $footer_row     Pre-rendered table-row.php call, or '' to omit table-footer.php
 * @param array<string, mixed> $table_config   Extra config merged onto the table.php call (e.g.
 *                                              `striped`, `class`)
 */
$render_table = static function (
    string $header_cells,
    string $body_rows,
    string $caption = '',
    string $footer_row = '',
    array $table_config = [],
): void {
    $content = '';

    if ($caption !== '') {
        ob_start();
        get_template_part('template-parts/base/table/table-caption', null, [
            'config' => ['text' => $caption],
        ]);
        $content .= (string) ob_get_clean();
    }

    ob_start();
    get_template_part('template-parts/base/table/table-header', null, [
        'config' => ['content' => $header_cells],
    ]);
    $content .= (string) ob_get_clean();

    ob_start();
    get_template_part('template-parts/base/table/table-body', null, [
        'config' => ['content' => $body_rows],
    ]);
    $content .= (string) ob_get_clean();

    if ($footer_row !== '') {
        ob_start();
        get_template_part('template-parts/base/table/table-footer', null, [
            'config' => ['content' => $footer_row],
        ]);
        $content .= (string) ob_get_clean();
    }

    get_template_part('template-parts/base/table/table', null, [
        'config' => array_merge(['content' => $content], $table_config),
    ]);
};

/**
 * @param list<array{content: string, scope?: string, align?: string}> $cells
 */
$render_head_row = static function (array $cells): string {
    $cells_markup = '';

    foreach ($cells as $cell) {
        ob_start();
        get_template_part('template-parts/base/table/table-head', null, [
            'config' => [
                'content' => $cell['content'],
                'scope' => $cell['scope'] ?? 'col',
                'align' => $cell['align'] ?? 'start',
            ],
        ]);
        $cells_markup .= (string) ob_get_clean();
    }

    ob_start();
    get_template_part('template-parts/base/table/table-row', null, [
        'config' => ['content' => $cells_markup],
    ]);

    return (string) ob_get_clean();
};

/**
 * @param list<array{content: string, align?: string, scope?: string}> $cells
 */
$render_body_row = static function (array $cells): string {
    $cells_markup = '';

    foreach ($cells as $cell) {
        if (isset($cell['scope'])) {
            ob_start();
            get_template_part('template-parts/base/table/table-head', null, [
                'config' => [
                    'content' => $cell['content'],
                    'scope' => $cell['scope'],
                    'align' => $cell['align'] ?? 'start',
                ],
            ]);
            $cells_markup .= (string) ob_get_clean();
            continue;
        }

        ob_start();
        get_template_part('template-parts/base/table/table-cell', null, [
            'config' => [
                'content' => $cell['content'],
                'align' => $cell['align'] ?? 'start',
            ],
        ]);
        $cells_markup .= (string) ob_get_clean();
    }

    ob_start();
    get_template_part('template-parts/base/table/table-row', null, [
        'config' => ['content' => $cells_markup],
    ]);

    return (string) ob_get_clean();
};

$eur = static fn(int $cents): string => number_format($cents / 100, 2, ',', '.') . ' €';

$products = [
    [
        'artikel' => 'Edelkorund weiß',
        'koernung' => 'F 80',
        'gebinde' => '25-kg-Sack',
        'preis' => 124000,
    ],
    [
        'artikel' => 'Normalkorund braun',
        'koernung' => 'F 36',
        'gebinde' => 'Big Bag 1 t',
        'preis' => 78000,
    ],
    [
        'artikel' => 'Siliciumcarbid schwarz',
        'koernung' => 'F 120',
        'gebinde' => '25-kg-Sack',
        'preis' => 169000,
    ],
    [
        'artikel' => 'Glasperlen',
        'koernung' => '100 – 200 µm',
        'gebinde' => 'Big Bag 1 t',
        'preis' => 112000,
    ],
    [
        'artikel' => 'Stahlkies kantig',
        'koernung' => '0,7 – 1,0 mm',
        'gebinde' => 'lose',
        'preis' => 64000,
    ],
];

$technical_specs = [
    ['label' => 'Härte (Mohs)', 'wert' => '9'],
    ['label' => 'Schüttdichte', 'wert' => '1,85 kg/dm³'],
    ['label' => 'Kornform', 'wert' => 'kantig'],
    ['label' => 'Schmelzpunkt', 'wert' => '2.050 °C'],
    ['label' => 'Farbe', 'wert' => 'weiß'],
    ['label' => 'Norm', 'wert' => 'FEPA F'],
];
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Table / Data Table — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        Zusammensetzung × Varianten × Sortierung/Pagination von
        <code>template-parts/base/table/table.php</code> (+ Familie) und
        <code>template-parts/base/table/data-table.php</code>. Dev-only, nicht für
        Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Basis (Kopfzeile, Fußzeile, Caption)</h2>
        <?php
        $header_row = $render_head_row([
            ['content' => esc_html('Artikel')],
            ['content' => esc_html('Körnung')],
            ['content' => esc_html('Gebinde')],
            ['content' => esc_html('Preis / t'), 'align' => 'end'],
        ]);

        $body_rows = '';
        $total_cents = 0;

        foreach ($products as $product) {
            $total_cents += $product['preis'];
            $body_rows .= $render_body_row([
                ['content' => esc_html($product['artikel'])],
                ['content' => esc_html($product['koernung'])],
                ['content' => esc_html($product['gebinde'])],
                ['content' => esc_html($eur($product['preis'])), 'align' => 'end'],
            ]);
        }

        $footer_row = $render_body_row([
            ['content' => esc_html__('Gesamt', 'hengegroup-theme')],
            ['content' => ''],
            ['content' => ''],
            ['content' => esc_html($eur($total_cents)), 'align' => 'end'],
        ]);

        $render_table(
            $header_row,
            $body_rows,
            'Listenpreise ab Werk, zzgl. MwSt. Stand September 2026.',
            $footer_row,
        );
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Varianten</h2>
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
            <div>
                <h3 class="mb-2 text-sm font-medium text-neutral-500">
                    Gestreift (<code>striped</code>)
                </h3>
                <?php
                $header_row = $render_head_row([
                    ['content' => esc_html('Oxid')],
                    ['content' => esc_html('Anteil'), 'align' => 'end'],
                ]);
                $body_rows = '';

                foreach (
                    [
                        ['name' => 'Al₂O₃', 'wert' => '99,5 %'],
                        ['name' => 'Na₂O', 'wert' => '0,30 %'],
                        ['name' => 'SiO₂', 'wert' => '0,08 %'],
                        ['name' => 'Fe₂O₃', 'wert' => '0,05 %'],
                    ]
                    as $row
                ) {
                    $body_rows .= $render_body_row([
                        ['content' => esc_html($row['name'])],
                        ['content' => esc_html($row['wert']), 'align' => 'end'],
                    ]);
                }

                $render_table($header_row, $body_rows, '', '', ['striped' => true]);
                ?>
            </div>
            <div>
                <h3 class="mb-2 text-sm font-medium text-neutral-500">
                    Kompakt, ohne Rahmen (<code>card: false</code> + <code>scope: 'row'</code>)
                </h3>
                <?php
                $body_rows = '';

                foreach ($technical_specs as $row) {
                    $body_rows .= $render_body_row([
                        ['content' => esc_html($row['label']), 'scope' => 'row'],
                        ['content' => esc_html($row['wert']), 'align' => 'end'],
                    ]);
                }

                $render_table('', $body_rows, '', '', ['card' => false]);
                ?>
            </div>
        </div>
    </section>

    <?php
    $data_table_columns = [
        ['key' => 'artikel', 'label' => 'Artikel', 'sortable' => true],
        ['key' => 'kategorie', 'label' => 'Kategorie', 'sortable' => true, 'toggleable' => true],
        ['key' => 'koernung', 'label' => 'Körnung', 'toggleable' => true],
        ['key' => 'bestand', 'label' => 'Bestand', 'align' => 'end', 'sortable' => true],
    ];

    // More rows than the default per_page (10), so the "Data Table" example below actually
    // demonstrates client-side paging, not just a single always-full page.
    $inventory = [
        [
            'artikel' => 'Edelkorund weiß',
            'kategorie' => 'Schleifmittel',
            'koernung' => 'F 80',
            'bestand' => 42,
        ],
        [
            'artikel' => 'Normalkorund braun',
            'kategorie' => 'Schleifmittel',
            'koernung' => 'F 36',
            'bestand' => 118,
        ],
        [
            'artikel' => 'Siliciumcarbid schwarz',
            'kategorie' => 'Schleifmittel',
            'koernung' => 'F 120',
            'bestand' => 0,
        ],
        [
            'artikel' => 'Stahlkies kantig',
            'kategorie' => 'Strahlmittel',
            'koernung' => '0,7 – 1,0 mm',
            'bestand' => 64,
        ],
        [
            'artikel' => 'Glasperlen',
            'kategorie' => 'Strahlmittel',
            'koernung' => '100 – 200 µm',
            'bestand' => 12,
        ],
        [
            'artikel' => 'Schmelzkorund gesintert',
            'kategorie' => 'Feuerfest',
            'koernung' => '1 – 3 mm',
            'bestand' => 87,
        ],
        [
            'artikel' => 'Bauxit kalziniert',
            'kategorie' => 'Feuerfest',
            'koernung' => '0 – 1 mm',
            'bestand' => 5,
        ],
        [
            'artikel' => 'Korundmehl',
            'kategorie' => 'Feuerfest',
            'koernung' => 'F 400',
            'bestand' => 33,
        ],
        [
            'artikel' => 'Hochofenschlacke',
            'kategorie' => 'Strahlmittel',
            'koernung' => '0,2 – 0,5 mm',
            'bestand' => 0,
        ],
        [
            'artikel' => 'Edelkorund rosa',
            'kategorie' => 'Schleifmittel',
            'koernung' => 'F 220',
            'bestand' => 27,
        ],
        [
            'artikel' => 'Zirkonkorund',
            'kategorie' => 'Schleifmittel',
            'koernung' => 'F 60',
            'bestand' => 19,
        ],
        [
            'artikel' => 'Quarzsand',
            'kategorie' => 'Strahlmittel',
            'koernung' => '0,3 – 0,8 mm',
            'bestand' => 76,
        ],
    ];

    $render_status_badge = static function (int $bestand): string {
        if ($bestand === 0) {
            return '<span class="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground"><span class="size-1.5 rounded-full bg-neutral-400"></span>Anfrage</span>';
        }

        if ($bestand <= 20) {
            return '<span class="inline-flex items-center gap-1.5 rounded-full bg-henge-blue/10 px-2.5 py-0.5 text-xs font-medium text-henge-blue"><span class="size-1.5 rounded-full bg-henge-blue"></span>Knapp</span>';
        }

        return '<span class="inline-flex items-center gap-1.5 rounded-full bg-henge-green/10 px-2.5 py-0.5 text-xs font-medium text-henge-green"><span class="size-1.5 rounded-full bg-henge-green"></span>Lager</span>';
    };

    // Pre-sorted by 'artikel' to match the `orderby`/`order` config below (data-table.php only
    // reflects the INITIAL sort state in aria-sort/the active icon now, see its own header comment
    // -- the caller still owns sorting `rows` for the first render, same as before, just not for
    // every subsequent click any more).
    usort($inventory, static fn(array $a, array $b): int => strcmp($a['artikel'], $b['artikel']));

    $data_table_rows = array_map(
        static fn(array $row, int $index): array => [
            'cells' => [
                'artikel' => esc_html($row['artikel']),
                'kategorie' => esc_html($row['kategorie']),
                'koernung' =>
                    '<span class="font-mono text-xs">' . esc_html($row['koernung']) . '</span>',
                'bestand' =>
                    ($row['bestand'] === 0 ? '—' : $row['bestand'] . ' t') .
                    ' ' .
                    $render_status_badge($row['bestand']),
            ],
            // 'bestand's cell mixes a number with a unit/status badge -- sort_values overrides the
            // auto-computed (stripped-text, non-numeric) sort key so the column sorts numerically
            // instead of lexicographically, see data-table.php's own header comment.
            'sort_values' => ['bestand' => $row['bestand']],
            'selected' => $index === 1,
        ],
        $inventory,
        array_keys($inventory),
    );
    ?>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Data Table (Suche, Kategorie-Filter, Spalten, Sortierung, Pagination -- alles per JS)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            Zeile 2 zeigt den <code>selected</code>-Zustand (henge-green-Tönung, siehe
            table-row.php). Ohne JavaScript zeigt diese Tabelle alle Zeilen ungefiltert/
            unpaginiert -- siehe data-table.php's Kopfkommentar.
        </p>
        <?php get_template_part('template-parts/base/table/data-table', null, [
            'config' => [
                'columns' => $data_table_columns,
                'rows' => $data_table_rows,
                'orderby' => 'artikel',
                'order' => 'asc',
                'filter_column' => 'kategorie',
                'per_page' => 6,
            ],
        ]); ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Data Table (leer)</h2>
        <?php get_template_part('template-parts/base/table/data-table', null, [
            'config' => [
                'columns' => $data_table_columns,
                'rows' => [],
            ],
        ]); ?>
    </section>
</div>

<?php get_footer(); ?>
