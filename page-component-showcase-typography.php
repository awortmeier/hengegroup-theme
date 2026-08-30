<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Typography
 *
 * Dev-only page template: renders template-parts/base/typography.php across every documented
 * variant (headline-lg | headline-base | headline-sm | headline-xs | body-lg | body-base |
 * body-sm | body-xs), color (default | light | neutral) and the variant/tag decoupling for
 * manual visual/functional review during Phase 2 styling work -- not meant for production content
 * or navigation. Analog zu page-component-showcase-button.php/page-component-showcase-badge.php.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Typography"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses
 * the existing per-page mechanism instead of a second one.
 *
 * Another slice of the "Komponenten-Showcase-Seite" idea documented as deliberately deferred in
 * docs/entscheidungen.md ("Komponenten-Showcase-Seite und Performance-Tooling") -- one page per
 * component, not the full one-call-per-base-component page from that entry yet, see
 * docs/to-do.md.
 */

get_header();

$variants = [
    'headline-lg',
    'headline-base',
    'headline-sm',
    'headline-xs',
    'body-lg',
    'body-base',
    'body-sm',
    'body-xs',
];
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Typography — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        Alle Varianten × Farben von <code>template-parts/base/typography.php</code>. Dev-only,
        nicht für Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Varianten (<code>variant</code>)</h2>
        <div class="flex flex-col gap-6">
            <?php foreach ($variants as $variant): ?>
                <div>
                    <div class="mb-1 text-sm font-medium text-neutral-500">
                        <?php echo esc_html($variant); ?>
                    </div>
                    <?php get_template_part('template-parts/base/typography', null, [
                        'config' => [
                            'variant' => $variant,
                            'text' => 'Schleifmittel für höchste Ansprüche',
                        ],
                    ]); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Fließtext im Kontext (<code>body-lg</code>/<code>body-sm</code>)
        </h2>
        <div class="flex max-w-2xl flex-col gap-6">
            <?php
            get_template_part('template-parts/base/typography', null, [
                'config' => [
                    'variant' => 'body-lg',
                    'text' =>
                        'Hier treffen Tradition und Innovation aufeinander. Mit unserem ' .
                        'Engagement für Qualität und nachhaltige Geschäftspraktiken sind wir ' .
                        'Ihr idealer Partner für zukunftsweisende Lösungen.',
                ],
            ]);
            get_template_part('template-parts/base/typography', null, [
                'config' => [
                    'variant' => 'body-sm',
                    'text' =>
                        'IMEXCO Minerals GmbH: Die globale Vernetzung von Industrie und Handel ' .
                        'wächst rasant und die Anforderungen an zuverlässige internationale ' .
                        'Lieferketten steigen kontinuierlich.',
                ],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Farben (<code>color</code>) — <code>default</code>/<code>neutral</code> auf hellem,
            <code>light</code> auf dunklem Grund
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            <code>neutral</code> ersetzt shadcns frühere eigene <code>muted</code>-Variante — jede
            Variante + <code>color: neutral</code> statt einer eigenen gedämpften Größenstufe.
        </p>
        <div class="mb-4 flex flex-col gap-3">
            <?php
            get_template_part('template-parts/base/typography', null, [
                'config' => ['variant' => 'body-lg', 'color' => 'default', 'text' => 'Default'],
            ]);
            get_template_part('template-parts/base/typography', null, [
                'config' => ['variant' => 'body-lg', 'color' => 'neutral', 'text' => 'Neutral'],
            ]);
            ?>
        </div>
        <div class="flex flex-col gap-3 rounded-lg bg-grey-dark p-6">
            <?php get_template_part('template-parts/base/typography', null, [
                'config' => ['variant' => 'body-lg', 'color' => 'light', 'text' => 'Light'],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Optik ≠ Semantik (<code>variant</code>/<code>tag</code> entkoppelt)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            Gleiche Optik (<code>variant: headline-base</code>), unterschiedliche Dokumentebene
            (<code>tag</code>) — und umgekehrt.
        </p>
        <div class="flex flex-col gap-6">
            <?php
            get_template_part('template-parts/base/typography', null, [
                'config' => [
                    'variant' => 'headline-base',
                    'tag' => 'h2',
                    'text' => 'headline-base-Optik als <h2>',
                ],
            ]);
            get_template_part('template-parts/base/typography', null, [
                'config' => [
                    'variant' => 'headline-base',
                    'tag' => 'h4',
                    'text' => 'headline-base-Optik als <h4> (Box-Titel, kleinere Gliederungsebene)',
                ],
            ]);
            get_template_part('template-parts/base/typography', null, [
                'config' => [
                    'variant' => 'headline-xs',
                    'tag' => 'h2',
                    'text' => 'headline-xs-Optik als <h2> (schlanke Sektionsüberschrift)',
                ],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Akzent-Wörter (<code>accent_words</code>, Crillee-Schrift)
        </h2>
        <?php get_template_part('template-parts/base/typography', null, [
            'config' => [
                'variant' => 'headline-base',
                'text' => 'Willkommen bei der HENGEGROUP',
                'accent_words' => ['HENGEGROUP'],
            ],
        ]); ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Custom class (Passthrough)</h2>
        <?php get_template_part('template-parts/base/typography', null, [
            'config' => [
                'variant' => 'body-sm',
                'text' => 'Mit zusätzlichem Abstand',
                'class' => 'mt-4',
            ],
        ]); ?>
    </section>
</div>

<?php get_footer(); ?>
