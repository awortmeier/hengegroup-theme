<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Dropdown Menu
 *
 * Dev-only page template: renders template-parts/base/dropdown-menu/*.php across an actions menu
 * (items, a shortcut, a disabled item, a destructive item), a groups/checkboxes/radio-group example,
 * and a right-aligned row-actions example over a product list, for manual visual/functional review
 * during Phase 2 styling work -- not meant for production content or navigation. Analog zu
 * page-component-showcase-popover.php/-hover-card.php.
 *
 * Note: DropdownMenuSub (nested submenus) is deliberately out of scope for v1 (see
 * dropdown-menu.php's own header comment) -- the Claude-Design reference's own "Untermenü" section
 * is therefore NOT reproduced here, a documented gap, not an oversight.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Dropdown Menu"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses the
 * existing per-page mechanism instead of a second one.
 *
 * Design reference: https://claude.ai/code/design/p/37768540-95a8-46e1-a647-33070ca71612?file=Dropdown+Menu.dc.html
 * (only the closed triggers rendered during this session, see docs/entscheidungen.md).
 */

get_header();

// dropdown-menu.php's own header comment documents the constraint this helper exists for: `trigger`
// lands inside the native <summary> that IS the dropdown menu's one interactive control -- nesting a
// second real interactive element inside it (a genuine button.php `<button>`) breaks the browser's
// native disclosure toggle entirely (invalid HTML content model). Every trigger below therefore
// reuses button.php's own variant/size class-building logic (no duplicated Tailwind strings to drift
// out of sync) but swaps its outer `<button>` tag for an inert `<span>` so it's safe to nest -- same
// helper/reasoning as popover.php's own showcase page.
$render_dropdown_menu_trigger_look = static function (array $button_config): string {
    ob_start();
    get_template_part('template-parts/base/button', null, ['config' => $button_config]);
    $button_html = (string) ob_get_clean();

    $span_html = preg_replace('/^<button\b/', '<span', $button_html, 1);
    $span_html = preg_replace('/<\/button>$/', '</span>', (string) $span_html, 1);

    return (string) preg_replace('/\s+type="[^"]*"/', '', (string) $span_html, 1);
};
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Dropdown Menu — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        <code>template-parts/base/dropdown-menu/*.php</code> + <code>dropdown-menu.js</code>.
        Dev-only, nicht für Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Basis</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Öffnet auf Klick, schließt nach der Auswahl, bei Klick daneben oder mit Escape. Anders
            als das Popover enthält es nur Aktionen. Trenner gruppieren verwandte Einträge, gesperrte
            Einträge bleiben sichtbar und abgeblendet, zerstörende Aktionen stehen zuletzt.
        </p>
        <?php
        ob_start();
        get_template_part('template-parts/base/dropdown-menu/dropdown-menu-item', null, [
            'config' => [
                'text' => 'Bearbeiten',
                'icon' => ['name' => 'pencil', 'set' => 'lucide'],
                'shortcut' => (static function (): string {
                    ob_start();
                    get_template_part('template-parts/base/kbd/kbd', null, [
                        'config' => ['text' => '⌘'],
                    ]);
                    get_template_part('template-parts/base/kbd/kbd', null, [
                        'config' => ['text' => 'E'],
                    ]);
                    $keys_markup = (string) ob_get_clean();

                    ob_start();
                    get_template_part('template-parts/base/kbd/kbd-group', null, [
                        'config' => ['content' => $keys_markup],
                    ]);

                    return (string) ob_get_clean();
                })(),
            ],
        ]);
        get_template_part('template-parts/base/dropdown-menu/dropdown-menu-item', null, [
            'config' => ['text' => 'Duplizieren', 'icon' => ['name' => 'copy', 'set' => 'lucide']],
        ]);
        get_template_part('template-parts/base/separator/separator', null, ['config' => []]);
        get_template_part('template-parts/base/dropdown-menu/dropdown-menu-item', null, [
            'config' => [
                'text' => 'Archivieren',
                'icon' => ['name' => 'archive', 'set' => 'lucide'],
                'disabled' => true,
            ],
        ]);
        get_template_part('template-parts/base/separator/separator', null, ['config' => []]);
        get_template_part('template-parts/base/dropdown-menu/dropdown-menu-item', null, [
            'config' => [
                'text' => 'Löschen',
                'icon' => ['name' => 'trash-2', 'set' => 'lucide'],
                'variant' => 'destructive',
            ],
        ]);
        $actions_content = (string) ob_get_clean();

        $actions_trigger = $render_dropdown_menu_trigger_look([
            'variant' => 'henge-green',
            'text' => 'Aktionen',
            'icon' => ['name' => 'chevron-down', 'set' => 'lucide'],
            'icon_position' => 'end',
        ]);

        get_template_part('template-parts/base/dropdown-menu/dropdown-menu', null, [
            'config' => [
                'trigger' => $actions_trigger,
                'content' => $actions_content,
            ],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Gruppen, Haken und Auswahl</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Überschriften benennen Gruppen. Mehrfachauswahl (Checkbox-Einträge) bleibt geöffnet,
            Einfachauswahl (Radio-Gruppe) schließt.
        </p>
        <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-8">
            <?php
            ob_start();
            get_template_part('template-parts/base/dropdown-menu/dropdown-menu-label', null, [
                'config' => ['text' => 'Spalten'],
            ]);
            get_template_part(
                'template-parts/base/dropdown-menu/dropdown-menu-checkbox-item',
                null,
                [
                    'config' => ['text' => 'Name', 'checked' => true],
                ],
            );
            get_template_part(
                'template-parts/base/dropdown-menu/dropdown-menu-checkbox-item',
                null,
                [
                    'config' => ['text' => 'Preis', 'checked' => true],
                ],
            );
            get_template_part(
                'template-parts/base/dropdown-menu/dropdown-menu-checkbox-item',
                null,
                [
                    'config' => ['text' => 'Lagerbestand'],
                ],
            );
            get_template_part(
                'template-parts/base/dropdown-menu/dropdown-menu-checkbox-item',
                null,
                [
                    'config' => ['text' => 'Kategorie'],
                ],
            );
            get_template_part('template-parts/base/separator/separator', null, ['config' => []]);
            get_template_part('template-parts/base/dropdown-menu/dropdown-menu-label', null, [
                'config' => ['text' => 'Sortierung'],
            ]);
            get_template_part('template-parts/base/dropdown-menu/dropdown-menu-radio-group', null, [
                'config' => [
                    'items' => [
                        ['value' => 'koernung', 'text' => 'Körnung'],
                        ['value' => 'preis', 'text' => 'Preis'],
                        ['value' => 'name', 'text' => 'Name'],
                    ],
                    'value' => 'koernung',
                ],
            ]);
            $columns_content = (string) ob_get_clean();

            $columns_trigger = $render_dropdown_menu_trigger_look([
                'variant' => 'outline',
                'text' => 'Spalten',
                'icon' => ['name' => 'chevron-down', 'set' => 'lucide'],
                'icon_position' => 'end',
            ]);

            get_template_part('template-parts/base/dropdown-menu/dropdown-menu', null, [
                'config' => [
                    'trigger' => $columns_trigger,
                    'content' => $columns_content,
                ],
            ]);
            ?>
            <p class="mt-4 text-sm text-muted-foreground">
                2 von 4 Spalten &middot; Sortierung: Körnung
            </p>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Im Kontext</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Zeilenmenü in einer Produktliste. Am rechten Rand richtet sich das Menü rechts aus
            (<code>align="end"</code>).
        </p>
        <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-6">
            <div class="flex flex-col divide-y divide-neutral-100">
                <?php
                $products = [
                    ['name' => 'Quarzsand HG 04', 'grain' => '0,1 – 0,5 mm'],
                    ['name' => 'Quarzsand HG 12', 'grain' => '0,7 – 1,2 mm'],
                    ['name' => 'Korund braun F 36', 'grain' => '0,4 – 0,6 mm'],
                    ['name' => 'Chamotte 0/2', 'grain' => '0 – 2 mm'],
                ];

                foreach ($products as $product):

                    ob_start();
                    get_template_part(
                        'template-parts/base/dropdown-menu/dropdown-menu-item',
                        null,
                        [
                            'config' => [
                                'text' => 'Ansehen',
                                'icon' => ['name' => 'eye', 'set' => 'lucide'],
                            ],
                        ],
                    );
                    get_template_part(
                        'template-parts/base/dropdown-menu/dropdown-menu-item',
                        null,
                        [
                            'config' => [
                                'text' => 'Bearbeiten',
                                'icon' => ['name' => 'pencil', 'set' => 'lucide'],
                            ],
                        ],
                    );
                    get_template_part('template-parts/base/separator/separator', null, [
                        'config' => [],
                    ]);
                    get_template_part(
                        'template-parts/base/dropdown-menu/dropdown-menu-item',
                        null,
                        [
                            'config' => [
                                'text' => 'Löschen',
                                'icon' => ['name' => 'trash-2', 'set' => 'lucide'],
                                'variant' => 'destructive',
                            ],
                        ],
                    );
                    $row_content = (string) ob_get_clean();

                    $row_trigger = $render_dropdown_menu_trigger_look([
                        'variant' => 'ghost',
                        'size' => 'icon-sm',
                        'icon' => ['name' => 'ellipsis', 'set' => 'lucide'],
                        'aria_label' => 'Aktionen für ' . $product['name'],
                    ]);
                    ?>
                    <div class="flex items-center justify-between gap-5 py-3.5">
                        <span class="text-base"><?php echo esc_html($product['name']); ?></span>
                        <div class="flex items-center gap-4">
                            <span class="text-sm text-muted-foreground tabular-nums"
                                ><?php echo esc_html($product['grain']); ?></span
                            >
                            <?php get_template_part(
                                'template-parts/base/dropdown-menu/dropdown-menu',
                                null,
                                [
                                    'config' => [
                                        'trigger' => $row_trigger,
                                        'content' => $row_content,
                                        'align' => 'end',
                                    ],
                                ],
                            ); ?>
                        </div>
                    </div>
                <?php
                endforeach;
                ?>
            </div>
        </div>
    </section>
</div>

<?php get_footer(); ?>
