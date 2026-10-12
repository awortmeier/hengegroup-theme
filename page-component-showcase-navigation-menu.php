<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Navigation Menu
 *
 * Dev-Seite zur visuellen Pruefung der Komponente (Seite mit noindex anlegen).
 */

get_header();

$panel_link_class = 'flex flex-col gap-1 rounded-lg p-2.5 hover:bg-henge-green/10';

$render_panel_links = static function (array $items) use ($panel_link_class): string {
    ob_start();

    foreach ($items as [$title, $description]) {
        get_template_part('template-parts/base/navigation-menu/navigation-menu-link', null, [
            'config' => [
                'href' => '#',
                'class' => $panel_link_class,
                'content' =>
                    '<span class="text-sm font-semibold">' .
                    esc_html($title) .
                    '</span>' .
                    '<span class="text-sm text-muted-foreground">' .
                    esc_html($description) .
                    '</span>',
            ],
        ]);
    }

    return (string) ob_get_clean();
};

$featured_tile =
    '<a href="#" class="flex flex-none w-56 flex-col justify-end gap-2 rounded-xl bg-henge-green ' .
    'p-5 text-grey-light">' .
    '<span class="font-accent text-xl leading-tight">Gesamtsortiment</span>' .
    '<span class="text-sm text-grey-light/75">Alle Körnungen, Lieferformen und ' .
    'Werkszeugnisse in einer Übersicht.</span>' .
    '</a>';

$products_items = [
    ['Quarzsande', 'Gewaschen, getrocknet, klassiert von 0,1 bis 4 mm.'],
    ['Korunde', 'Braun und weiß, F 12 bis F 220.'],
    ['Chamotte', 'Feuerfeste Schamottekörnungen 0/2 bis 3/8.'],
    ['Filterkiese', 'Mehrschichtaufbau für Wasseraufbereitung.'],
];

$applications_items = [
    ['Bauchemie', 'Trockenmörtel, Estriche, Fugenmassen.'],
    ['Gießerei', 'Formsande und Kernsande.'],
    ['Wasseraufbereitung', 'Filterschichten für Trink- und Brauchwasser.'],
    ['Oberflächentechnik', 'Strahlen, Entgraten, Aufrauen.'],
];

$company_items = [
    ['Über die Hengegroup', 'Standorte, Werke und Geschichte.'],
    ['Qualität und Prüfung', 'Werkslabor, Zeugnisse, Normen.'],
    ['Karriere', 'Offene Stellen in Werk, Labor und Verwaltung.'],
];

$build_items = static function () use (
    $render_panel_links,
    $featured_tile,
    $products_items,
    $applications_items,
    $company_items,
): array {
    return [
        [
            'text' => 'Produkte',
            'content' =>
                $featured_tile .
                // minmax()-Untergrenze, sonst schrumpfen die Spalten auf 0.
                '<div class="grid flex-1 grid-cols-[repeat(2,minmax(13rem,1fr))] content-start gap-0.5">' .
                $render_panel_links($products_items) .
                '</div>',
        ],
        [
            'text' => 'Anwendungen',
            'content' =>
                '<div class="grid grid-cols-[repeat(2,minmax(13rem,1fr))] gap-0.5">' .
                $render_panel_links($applications_items) .
                '</div>',
        ],
        [
            'text' => 'Unternehmen',
            'content' =>
                '<div class="grid grid-cols-[minmax(14rem,1fr)] gap-0.5">' .
                $render_panel_links($company_items) .
                '</div>',
        ],
        ['text' => 'Kontakt', 'href' => '#', 'active' => true],
    ];
};
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Navigation Menu — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        <code>template-parts/base/navigation-menu/*.php</code> + <code>navigation-menu.js</code>.
        Dev-only, nicht für Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Basis</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Öffnet beim Überfahren oder Klicken eines Eintrags mit Panel, schließt beim Verlassen der
            Navigation oder mit Escape. Jedes Panel positioniert sich direkt unter seinem eigenen
            Auslöser (keine gemeinsame, zwischen Panels gleitende Ansicht, siehe
            <code>navigation-menu.php</code>s Kopfkommentar).
        </p>
        <div class="rounded-2xl border border-border bg-card p-8">
            <?php get_template_part('template-parts/base/navigation-menu/navigation-menu', null, [
                'config' => [
                    'aria_label' => 'Hauptnavigation (Basis)',
                    'items' => $build_items(),
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Auf dunklem Grund</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Gleiche Maße und gleiches Verhalten, <code>color: 'light'</code>. Die Panel-Karte bekommt
            unabhängig vom Hintergrund ihre eigene dunkle Fläche, da sie über beliebigem Seiteninhalt
            schwebt.
        </p>
        <div class="rounded-2xl bg-neutral-900 p-8">
            <?php get_template_part('template-parts/base/navigation-menu/navigation-menu', null, [
                'config' => [
                    'aria_label' => 'Hauptnavigation (auf dunklem Grund)',
                    'color' => 'light',
                    'items' => $build_items(),
                ],
            ]); ?>
        </div>
    </section>
</div>

<?php get_footer(); ?>
