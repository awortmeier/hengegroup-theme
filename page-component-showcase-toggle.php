<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Toggle
 *
 * Dev-only page template: renders template-parts/base/toggle/toggle.php and
 * template-parts/base/toggle/toggle-group.php across `variant` (default/outline/accent), `size`
 * (sm/default/lg), the `multiple`-selection group mode, an icon example and a disabled state, for
 * manual visual/functional review during Phase 2 styling work -- not meant for production content
 * or navigation. Analog zu page-component-showcase-tabs.php/-kbd.php.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Toggle"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses the
 * existing per-page mechanism instead of a second one.
 *
 * Design reference: https://claude.ai/code/artifact/120c0655-89f0-4c42-b99b-bb5227b96ccc
 */

get_header();
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Toggle — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        <code>template-parts/base/toggle/toggle.php</code> +
        <code>template-parts/base/toggle/toggle-group.php</code> + <code>toggle.js</code>/
        <code>toggle-group.js</code>. Dev-only, nicht für Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Basis (<code>toggle-group.php</code>, <code>single</code>)</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Ein Schalter mit zwei Zuständen. Aktiv ist er anthrazit gefüllt, inaktiv reine Textfarbe
            ohne Kante.
        </p>
        <?php get_template_part('template-parts/base/toggle/toggle-group', null, [
            'config' => [
                'aria_label' => 'Liefereinheit',
                'items' => [
                    ['value' => 'sackware', 'text' => 'Sackware'],
                    ['value' => 'big-bag', 'text' => 'Big Bag'],
                    ['value' => 'silo', 'text' => 'Silo'],
                ],
                'value' => 'sackware',
            ],
        ]); ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Varianten</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Ohne Kante für Werkzeugleisten, mit Kante wenn der Schalter allein steht, in Akzentfarbe
            für die primäre Auswahl.
        </p>
        <div class="flex flex-col gap-6">
            <div class="flex items-center gap-4">
                <span class="w-28 shrink-0 text-sm font-medium">Ohne Kante</span>
                <?php get_template_part('template-parts/base/toggle/toggle', null, [
                    'config' => ['variant' => 'default', 'pressed' => true, 'text' => 'Ansicht'],
                ]); ?>
                <span class="text-sm text-muted-foreground">Werkzeugleisten</span>
            </div>
            <div class="flex items-center gap-4">
                <span class="w-28 shrink-0 text-sm font-medium">Mit Kante</span>
                <?php get_template_part('template-parts/base/toggle/toggle', null, [
                    'config' => ['variant' => 'outline', 'text' => 'Werkszeugnis'],
                ]); ?>
                <span class="text-sm text-muted-foreground">einzeln stehend</span>
            </div>
            <div class="flex items-center gap-4">
                <span class="w-28 shrink-0 text-sm font-medium">Akzent</span>
                <?php get_template_part('template-parts/base/toggle/toggle', null, [
                    'config' => ['variant' => 'accent', 'pressed' => true, 'text' => 'Kreislauf'],
                ]); ?>
                <span class="text-sm text-muted-foreground">primäre Auswahl</span>
            </div>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Größen</h2>
        <p class="mb-6 text-sm text-neutral-500">Klein in Tabellenzeilen, Standard in Filtern, groß in Formularen.</p>
        <div class="flex flex-col gap-6">
            <div class="flex items-center gap-4">
                <span class="w-20 shrink-0 text-sm font-medium">Klein</span>
                <?php get_template_part('template-parts/base/toggle/toggle', null, [
                    'config' => ['size' => 'sm', 'text' => 'Lose'],
                ]); ?>
            </div>
            <div class="flex items-center gap-4">
                <span class="w-20 shrink-0 text-sm font-medium">Standard</span>
                <?php get_template_part('template-parts/base/toggle/toggle', null, [
                    'config' => ['size' => 'default', 'pressed' => true, 'text' => 'Sackware'],
                ]); ?>
            </div>
            <div class="flex items-center gap-4">
                <span class="w-20 shrink-0 text-sm font-medium">Groß</span>
                <?php get_template_part('template-parts/base/toggle/toggle', null, [
                    'config' => ['size' => 'lg', 'text' => 'Big Bag'],
                ]); ?>
            </div>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Icon, Mehrfachauswahl &amp; deaktiviert</h2>
        <p class="mb-6 text-sm text-neutral-500">
            <code>type: 'multiple'</code> erlaubt unabhängig voneinander gedrückte Items (Checkbox statt
            Radio), ein deaktiviertes Item bleibt sichtbar aber nicht bedienbar.
        </p>
        <div class="flex flex-wrap items-center gap-10">
            <?php get_template_part('template-parts/base/toggle/toggle-group', null, [
                'config' => [
                    'type' => 'multiple',
                    'aria_label' => 'Textformatierung',
                    'variant' => 'outline',
                    'items' => [
                        [
                            'value' => 'bold',
                            'icon' => ['name' => 'bold', 'set' => 'lucide'],
                            'aria_label' => 'Fett',
                        ],
                        [
                            'value' => 'italic',
                            'icon' => ['name' => 'italic', 'set' => 'lucide'],
                            'aria_label' => 'Kursiv',
                        ],
                        [
                            'value' => 'underline',
                            'icon' => ['name' => 'underline', 'set' => 'lucide'],
                            'aria_label' => 'Unterstrichen',
                            'disabled' => true,
                        ],
                    ],
                    'value' => ['bold'],
                ],
            ]); ?>
            <?php get_template_part('template-parts/base/toggle/toggle', null, [
                'config' => ['text' => 'Gesperrt', 'disabled' => true],
            ]); ?>
        </div>
    </section>
</div>

<?php get_footer(); ?>
