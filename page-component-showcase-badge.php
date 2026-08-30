<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Badge
 *
 * Dev-only page template: renders template-parts/base/badge.php across every documented variant
 * (grey-dark | grey-light | henge-blue | henge-green | henge-grey | outline) and state (icon-only,
 * icon start/end, href/link, custom class passthrough) for manual visual/functional review during
 * Phase 2 styling work -- not meant for production content or navigation. Analog zur
 * page-component-showcase-button.php, ohne deren Größen-/Disabled-/Loading-Abschnitte -- badge.php
 * kennt kein `size`/`disabled`/`loading` (siehe dessen Kopfkommentar).
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Badge"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses the
 * existing per-page mechanism instead of a second one.
 *
 * Second slice of the "Komponenten-Showcase-Seite" idea documented as deliberately deferred in
 * docs/entscheidungen.md ("Komponenten-Showcase-Seite und Performance-Tooling") -- not the full
 * one-call-per-base-component page from that entry yet, see docs/to-do.md.
 */

get_header();

$variants = ['grey-dark', 'grey-light', 'henge-blue', 'henge-green', 'henge-grey', 'outline'];
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Badge — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        Alle Varianten × Zustände von
        <code>template-parts/base/badge.php</code>. Dev-only, nicht für Produktivinhalte
        verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Varianten</h2>
        <div class="flex flex-wrap items-center gap-3">
            <?php foreach ($variants as $variant): ?>
                <?php get_template_part('template-parts/base/badge', null, [
                    'config' => [
                        'text' => ucfirst($variant),
                        'variant' => $variant,
                    ],
                ]); ?>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Varianten (icon-only)</h2>
        <div class="flex flex-wrap items-center gap-3">
            <?php foreach ($variants as $variant): ?>
                <?php get_template_part('template-parts/base/badge', null, [
                    'config' => [
                        'variant' => $variant,
                        'icon' => ['name' => 'check', 'set' => 'lucide'],
                        'aria_label' => sprintf('Bestätigt (%s)', $variant),
                    ],
                ]); ?>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Schriftart (<code>font</code>)</h2>
        <div class="flex flex-wrap items-center gap-3">
            <?php foreach ($variants as $variant): ?>
                <?php get_template_part('template-parts/base/badge', null, [
                    'config' => [
                        'text' => ucfirst($variant),
                        'variant' => $variant,
                        'font' => 'accent',
                    ],
                ]); ?>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Icon-Position (Text + Icon)</h2>
        <div class="flex flex-wrap items-center gap-3">
            <?php
            get_template_part('template-parts/base/badge', null, [
                'config' => [
                    'text' => 'Neu',
                    'icon' => ['name' => 'info', 'set' => 'lucide'],
                    'icon_position' => 'start',
                ],
            ]);
            get_template_part('template-parts/base/badge', null, [
                'config' => [
                    'text' => 'Erledigt',
                    'icon' => ['name' => 'check', 'set' => 'lucide'],
                    'icon_position' => 'end',
                ],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Als Link (<code>href</code>)</h2>
        <div class="flex flex-wrap items-center gap-3">
            <?php
            get_template_part('template-parts/base/badge', null, [
                'config' => ['text' => 'Link als Badge', 'href' => '#'],
            ]);
            get_template_part('template-parts/base/badge', null, [
                'config' => ['text' => 'Outline-Link', 'variant' => 'outline', 'href' => '#'],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Custom class (Passthrough)</h2>
        <div class="flex flex-wrap items-center gap-3">
            <?php get_template_part('template-parts/base/badge', null, [
                'config' => ['text' => 'Mit zusätzlichem Abstand', 'class' => 'mt-4'],
            ]); ?>
        </div>
    </section>
</div>

<?php get_footer(); ?>
