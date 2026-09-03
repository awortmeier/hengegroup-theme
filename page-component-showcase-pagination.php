<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Pagination
 *
 * Dev-only page template: renders template-parts/base/pagination/pagination.php +
 * pagination-compact.php across item count/ellipsis, size, active/disabled state and the compact
 * (+ per-page selector) composition, for manual visual/functional review during Phase 2 styling
 * work -- not meant for production content or navigation. Analog zur
 * page-component-showcase-button-group.php: pagination.php's own `items` config is an array the
 * caller assembles by hand (see its Kopfkommentar) -- `build_page_items()` below is this page's
 * own small helper for that (first/last + neighbours + ellipsis gaps), not part of the base
 * component itself (same "caller re-queries/re-builds" split as data-table.php's own pagination
 * config, see pagination.php's Kopfkommentar).
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Pagination"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses the
 * existing per-page mechanism instead of a second one.
 *
 * Further slice of the "Komponenten-Showcase-Seite" idea documented as deliberately deferred in
 * docs/entscheidungen.md ("Komponenten-Showcase-Seite und Performance-Tooling") -- not the full
 * one-call-per-base-component page from that entry yet, see docs/to-do.md.
 */

get_header();

/**
 * Builds a pagination.php `items` array for a given current/total page count: page 1, the current
 * page +/- 1 neighbour, and the last page always stay, with an ellipsis item filling any gap --
 * same "keep the ends + a window around the active page" shape as the Claude-Design reference's
 * own "Mit Auslassung" section (see pagination.php's Kopfkommentar).
 *
 * @param int $current
 * @param int $total
 * @param string $base_url
 * @return array<int, array<string, mixed>>
 */
$build_page_items = static function (int $current, int $total, string $base_url): array {
    $page_numbers = [];

    foreach ([1, 2, $current - 1, $current, $current + 1, $total - 1, $total] as $number) {
        if ($number >= 1 && $number <= $total && !in_array($number, $page_numbers, true)) {
            $page_numbers[] = $number;
        }
    }

    sort($page_numbers);

    $items = [
        [
            'type' => 'previous',
            'href' => $base_url . max(1, $current - 1),
            'disabled' => $current <= 1,
        ],
    ];

    $previous_number = 0;

    foreach ($page_numbers as $number) {
        if ($previous_number > 0 && $number - $previous_number > 1) {
            $items[] = ['type' => 'ellipsis'];
        }

        $items[] = [
            'text' => (string) $number,
            'href' => $base_url . $number,
            'is_active' => $number === $current,
        ];

        $previous_number = $number;
    }

    $items[] = [
        'type' => 'next',
        'href' => $base_url . min($total, $current + 1),
        'disabled' => $current >= $total,
    ];

    return $items;
};
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Pagination — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        Seitenanzahl × Größen × Zustände von
        <code>template-parts/base/pagination/pagination.php</code> +
        <code>pagination-compact.php</code>. Dev-only, nicht für Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Basis (wenige Seiten)</h2>
        <?php get_template_part('template-parts/base/pagination/pagination', null, [
            'config' => ['items' => $build_page_items(1, 4, '#page-')],
        ]); ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Mit Auslassung (viele Seiten)</h2>
        <?php get_template_part('template-parts/base/pagination/pagination', null, [
            'config' => ['items' => $build_page_items(7, 24, '#page-')],
        ]); ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Größen (<code>size</code>, an den verschachtelten button.php durchgereicht)
        </h2>
        <div class="flex flex-col items-start gap-6">
            <?php foreach (
                [
                    'sm' => ['prev_next' => 'sm', 'page' => 'icon-sm'],
                    'base' => ['prev_next' => 'base', 'page' => 'icon-base'],
                    'lg' => ['prev_next' => 'lg', 'page' => 'icon-lg'],
                ]
                as $size_label => $size_map
            ): ?>
                <div>
                    <h3 class="mb-2 text-sm font-medium text-neutral-500">
                        <?php echo esc_html($size_label); ?>
                    </h3>
                    <?php get_template_part('template-parts/base/pagination/pagination', null, [
                        'config' => [
                            'items' => [
                                [
                                    'type' => 'previous',
                                    'href' => '#',
                                    'size' => $size_map['prev_next'],
                                ],
                                ['text' => '1', 'href' => '#', 'size' => $size_map['page']],
                                [
                                    'text' => '2',
                                    'href' => '#',
                                    'is_active' => true,
                                    'size' => $size_map['page'],
                                ],
                                ['text' => '3', 'href' => '#', 'size' => $size_map['page']],
                                ['type' => 'next', 'href' => '#', 'size' => $size_map['prev_next']],
                            ],
                        ],
                    ]); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Randfälle (deaktivierte Vor/Zurück-Steuerung)</h2>
        <div class="flex flex-col items-start gap-6">
            <div>
                <h3 class="mb-2 text-sm font-medium text-neutral-500">Erste Seite aktiv</h3>
                <?php get_template_part('template-parts/base/pagination/pagination', null, [
                    'config' => ['items' => $build_page_items(1, 8, '#page-')],
                ]); ?>
            </div>
            <div>
                <h3 class="mb-2 text-sm font-medium text-neutral-500">Letzte Seite aktiv</h3>
                <?php get_template_part('template-parts/base/pagination/pagination', null, [
                    'config' => ['items' => $build_page_items(8, 8, '#page-')],
                ]); ?>
            </div>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Kompakt (<code>pagination-compact.php</code>)</h2>
        <div class="flex max-w-xl flex-col gap-10">
            <div>
                <h3 class="mb-2 text-sm font-medium text-neutral-500">Nur Vor/Zurück + Status</h3>
                <?php get_template_part('template-parts/base/pagination/pagination-compact', null, [
                    'config' => ['current_page' => 5, 'total_pages' => 18],
                ]); ?>
            </div>
            <div>
                <h3 class="mb-2 text-sm font-medium text-neutral-500">
                    Mit Ergebnis-Range + Eintraege-pro-Seite (<code>per_page_options</code>)
                </h3>
                <?php get_template_part('template-parts/base/pagination/pagination-compact', null, [
                    'config' => [
                        'current_page' => 5,
                        'total_pages' => 18,
                        'total_items' => 212,
                        'per_page' => 12,
                        'per_page_options' => [12, 24, 48],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
</div>

<?php get_footer(); ?>
