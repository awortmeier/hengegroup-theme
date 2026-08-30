<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Button
 *
 * Dev-only page template: renders template-parts/base/button.php across every documented variant
 * (henge-green | henge-blue | henge-grey | grey-dark | grey-light | destructive | outline | ghost |
 * link), size (default | xs | sm | lg | icon | icon-xs | icon-sm | icon-lg) and state (disabled,
 * loading, icon-only, icon start/end, href/link, custom class passthrough) for manual visual/
 * functional review during Phase 2 styling work -- not meant for production content or navigation.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Button"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses the
 * existing per-page mechanism instead of a second one.
 *
 * First, button-only slice of the "Komponenten-Showcase-Seite" idea documented as deliberately
 * deferred in docs/entscheidungen.md ("Komponenten-Showcase-Seite und Performance-Tooling") -- not
 * the full one-call-per-base-component page from that entry yet, see docs/to-do.md.
 */

get_header();

$variants = [
    'henge-green',
    'henge-blue',
    'henge-grey',
    'grey-dark',
    'grey-light',
    'destructive',
    'outline',
    'ghost',
    'link',
];
$text_sizes = ['xs', 'sm', 'default', 'lg'];
$icon_sizes = ['icon-xs', 'icon-sm', 'icon', 'icon-lg'];
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Button — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        Alle Varianten × Größen × Zustände von
        <code>template-parts/base/button.php</code>. Dev-only, nicht für Produktivinhalte
        verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Varianten × Textgrößen</h2>
        <?php foreach ($variants as $variant): ?>
            <div class="mb-6">
                <h3 class="mb-2 text-sm font-medium text-neutral-500">
                    <?php echo esc_html($variant); ?>
                </h3>
                <div class="flex flex-wrap items-center gap-3">
                    <?php foreach ($text_sizes as $size): ?>
                        <?php get_template_part('template-parts/base/button', null, [
                            'config' => [
                                'text' => ucfirst($size) . ' Button',
                                'variant' => $variant,
                                'size' => $size,
                            ],
                        ]); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Varianten × Icon-Größen (icon-only)</h2>
        <?php foreach ($variants as $variant): ?>
            <div class="mb-6">
                <h3 class="mb-2 text-sm font-medium text-neutral-500">
                    <?php echo esc_html($variant); ?>
                </h3>
                <div class="flex flex-wrap items-center gap-3">
                    <?php foreach ($icon_sizes as $size): ?>
                        <?php get_template_part('template-parts/base/button', null, [
                            'config' => [
                                'variant' => $variant,
                                'size' => $size,
                                'icon' => ['name' => 'check', 'set' => 'lucide'],
                                'aria_label' => sprintf('Bestätigen (%s, %s)', $variant, $size),
                            ],
                        ]); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Icon-Position (Text + Icon)</h2>
        <div class="flex flex-wrap items-center gap-3">
            <?php
            get_template_part('template-parts/base/button', null, [
                'config' => [
                    'text' => 'Weiter',
                    'icon' => ['name' => 'arrow-right', 'set' => 'lucide'],
                    'icon_position' => 'end',
                ],
            ]);
            get_template_part('template-parts/base/button', null, [
                'config' => [
                    'text' => 'Zurück',
                    'icon' => ['name' => 'arrow-left', 'set' => 'lucide'],
                    'icon_position' => 'start',
                ],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Zustände</h2>
        <div class="flex flex-wrap items-center gap-3">
            <?php
            get_template_part('template-parts/base/button', null, [
                'config' => ['text' => 'Normal'],
            ]);
            get_template_part('template-parts/base/button', null, [
                'config' => ['text' => 'Disabled', 'disabled' => true],
            ]);
            get_template_part('template-parts/base/button', null, [
                'config' => ['text' => 'Loading', 'loading' => true],
            ]);
            get_template_part('template-parts/base/button', null, [
                'config' => [
                    'loading' => true,
                    'aria_label' => 'Wird geladen (icon-only)',
                ],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Als Link (<code>href</code>)</h2>
        <div class="flex flex-wrap items-center gap-3">
            <?php
            get_template_part('template-parts/base/button', null, [
                'config' => ['text' => 'Link als Button', 'href' => '#'],
            ]);
            get_template_part('template-parts/base/button', null, [
                'config' => ['text' => 'Disabled Link', 'href' => '#', 'disabled' => true],
            ]);
            get_template_part('template-parts/base/button', null, [
                'config' => ['text' => 'Link-Variante', 'variant' => 'link', 'href' => '#'],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Full-width (<code>full_width</code>)</h2>
        <div class="max-w-sm space-y-3">
            <?php
            get_template_part('template-parts/base/button', null, [
                'config' => ['text' => 'Henge Green', 'full_width' => true],
            ]);
            get_template_part('template-parts/base/button', null, [
                'config' => ['text' => 'Outline', 'variant' => 'outline', 'full_width' => true],
            ]);
            get_template_part('template-parts/base/button', null, [
                'config' => [
                    'text' => 'Mit Icon',
                    'icon' => ['name' => 'arrow-right', 'set' => 'lucide'],
                    'icon_position' => 'end',
                    'full_width' => true,
                ],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Custom class (Passthrough)</h2>
        <div class="flex flex-wrap items-center gap-3">
            <?php get_template_part('template-parts/base/button', null, [
                'config' => ['text' => 'Mit zusätzlichem Abstand', 'class' => 'mt-4'],
            ]); ?>
        </div>
    </section>
</div>

<?php get_footer(); ?>
