<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Button Group
 *
 * Dev-only page template: renders template-parts/base/button-group/button-group.php +
 * button-group-text.php (plus the reused template-parts/base/separator.php and button.php as
 * nested content) across orientation, size, icon-only, separator, text-segment and nested-group
 * composition for manual visual/functional review during Phase 2 styling work -- not meant for
 * production content or navigation. Analog zur page-component-showcase-button.php/-badge.php, mit
 * dem Unterschied, dass button-group.php/button-group-text.php content-agnostische Wrapper sind
 * (siehe deren Kopfkommentare) -- jede Sektion puffert ihre Kind-Ausgabe erst per ob_start()/
 * ob_get_clean(), statt die Wrapper direkt mit fertigem Text zu befüllen.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Button Group"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses the
 * existing per-page mechanism instead of a second one.
 *
 * Further slice of the "Komponenten-Showcase-Seite" idea documented as deliberately deferred in
 * docs/entscheidungen.md ("Komponenten-Showcase-Seite und Performance-Tooling") -- not the full
 * one-call-per-base-component page from that entry yet, see docs/to-do.md.
 */

get_header();

$render = static function (string $template_part, array $config): string {
    ob_start();
    get_template_part($template_part, null, ['config' => $config]);

    return (string) ob_get_clean();
};

$vertical_separator_class = 'bg-input m-0! self-stretch data-[orientation=vertical]:h-auto';
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Button Group — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        Orientierung × Größen × Zustände von
        <code>template-parts/base/button-group/button-group.php</code> +
        <code>button-group-text.php</code>. Dev-only, nicht für Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Basisbeispiel (horizontal, <code>outline</code>)</h2>
        <?php
        $group_content =
            $render('template-parts/base/button', [
                'text' => 'Kopieren',
                'variant' => 'outline',
            ]) .
            $render('template-parts/base/button', [
                'text' => 'Verschieben',
                'variant' => 'outline',
            ]) .
            $render('template-parts/base/button', [
                'text' => 'Löschen',
                'variant' => 'outline',
            ]);

        get_template_part('template-parts/base/button-group/button-group', null, [
            'config' => ['content' => $group_content],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Orientierung (<code>orientation: vertical</code>)
        </h2>
        <?php
        $group_content =
            $render('template-parts/base/button', ['text' => 'Kopieren', 'variant' => 'outline']) .
            $render('template-parts/base/button', [
                'text' => 'Verschieben',
                'variant' => 'outline',
            ]) .
            $render('template-parts/base/button', ['text' => 'Löschen', 'variant' => 'outline']);

        get_template_part('template-parts/base/button-group/button-group', null, [
            'config' => ['content' => $group_content, 'orientation' => 'vertical'],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Größen (<code>sm</code> | <code>base</code> | <code>lg</code>)</h2>
        <div class="flex flex-col items-start gap-6">
            <?php foreach (['sm', 'base', 'lg'] as $size): ?>
                <div>
                    <h3 class="mb-2 text-sm font-medium text-neutral-500">
                        <?php echo esc_html($size); ?>
                    </h3>
                    <?php
                    $group_content =
                        $render('template-parts/base/button', [
                            'text' => 'Links',
                            'variant' => 'outline',
                            'size' => $size,
                        ]) .
                        $render('template-parts/base/button', [
                            'text' => 'Mitte',
                            'variant' => 'outline',
                            'size' => $size,
                        ]) .
                        $render('template-parts/base/button', [
                            'text' => 'Rechts',
                            'variant' => 'outline',
                            'size' => $size,
                        ]);

                    get_template_part('template-parts/base/button-group/button-group', null, [
                        'config' => ['content' => $group_content],
                    ]);
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Icon-only (Toolbar)</h2>
        <?php
        $group_content =
            $render('template-parts/base/button', [
                'variant' => 'outline',
                'size' => 'icon-base',
                'icon' => ['name' => 'chevron-left', 'set' => 'lucide'],
                'aria_label' => 'Zurück',
            ]) .
            $render('template-parts/base/button', [
                'variant' => 'outline',
                'size' => 'icon-base',
                'icon' => ['name' => 'search', 'set' => 'lucide'],
                'aria_label' => 'Suchen',
            ]) .
            $render('template-parts/base/button', [
                'variant' => 'outline',
                'size' => 'icon-base',
                'icon' => ['name' => 'ellipsis', 'set' => 'lucide'],
                'aria_label' => 'Weitere Aktionen',
            ]) .
            $render('template-parts/base/button', [
                'variant' => 'outline',
                'size' => 'icon-base',
                'icon' => ['name' => 'chevron-right', 'set' => 'lucide'],
                'aria_label' => 'Weiter',
            ]);

        get_template_part('template-parts/base/button-group/button-group', null, [
            'config' => ['content' => $group_content],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Mit Separator (<code>separator.php</code>, Split-Button)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            shadcns eigene ButtonGroupSeparator ist nur ihre Separator-Komponente mit
            <code>orientation="vertical"</code> + ein paar Override-Klassen -- hier per
            <code>separator.php</code>s <code>class</code>-Config nachgebildet, siehe
            button-group.php Kopfkommentar.
        </p>
        <?php
        $group_content =
            $render('template-parts/base/button', [
                'text' => 'Speichern',
                'variant' => 'henge-green',
            ]) .
            $render('template-parts/base/separator', [
                'orientation' => 'vertical',
                'class' => $vertical_separator_class,
            ]) .
            $render('template-parts/base/button', [
                'variant' => 'henge-green',
                'icon' => ['name' => 'chevron-down', 'set' => 'lucide'],
                'aria_label' => 'Weitere Speicheroptionen',
            ]);

        get_template_part('template-parts/base/button-group/button-group', null, [
            'config' => ['content' => $group_content],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Mit Text-Segment (<code>button-group-text.php</code>)
        </h2>
        <?php
        $group_content =
            $render('template-parts/base/button', [
                'variant' => 'outline',
                'size' => 'icon-base',
                'icon' => ['name' => 'chevron-left', 'set' => 'lucide'],
                'aria_label' => 'Vorherige Seite',
            ]) .
            $render('template-parts/base/button-group/button-group-text', [
                'text' => 'Seite 1 von 10',
            ]) .
            $render('template-parts/base/button', [
                'variant' => 'outline',
                'size' => 'icon-base',
                'icon' => ['name' => 'chevron-right', 'set' => 'lucide'],
                'aria_label' => 'Nächste Seite',
            ]);

        get_template_part('template-parts/base/button-group/button-group', null, [
            'config' => ['content' => $group_content],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Verschachtelte Gruppen (<code>has-[&gt;[data-slot=button-group]]:gap-2</code>)
        </h2>
        <?php
        $inner_group_one = $render('template-parts/base/button-group/button-group', [
            'content' =>
                $render('template-parts/base/button', ['text' => 'Neu', 'variant' => 'outline']) .
                $render('template-parts/base/button', ['text' => 'Öffnen', 'variant' => 'outline']),
        ]);
        $inner_group_two = $render('template-parts/base/button-group/button-group', [
            'content' =>
                $render('template-parts/base/button', [
                    'variant' => 'outline',
                    'size' => 'icon-base',
                    'icon' => ['name' => 'circle-check', 'set' => 'lucide'],
                    'aria_label' => 'Bestätigen',
                ]) .
                $render('template-parts/base/button', [
                    'variant' => 'outline',
                    'size' => 'icon-base',
                    'icon' => ['name' => 'circle-x', 'set' => 'lucide'],
                    'aria_label' => 'Verwerfen',
                ]),
        ]);

        get_template_part('template-parts/base/button-group/button-group', null, [
            'config' => ['content' => $inner_group_one . $inner_group_two],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Custom class (Passthrough)</h2>
        <?php
        $group_content =
            $render('template-parts/base/button', ['text' => 'Links', 'variant' => 'outline']) .
            $render('template-parts/base/button', ['text' => 'Rechts', 'variant' => 'outline']);

        get_template_part('template-parts/base/button-group/button-group', null, [
            'config' => ['content' => $group_content, 'class' => 'mt-4'],
        ]);
        ?>
    </section>
</div>

<?php get_footer(); ?>
