<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Attachment
 *
 * Dev-only page template: renders template-parts/base/attachment/attachment.php +
 * attachment-group.php across every documented config option (media icon/image, state, size,
 * orientation, href, actions/composition patterns, class passthrough) for manual visual/functional
 * review during Phase 2 styling work -- not meant for production content or navigation. Analog zu
 * page-component-showcase-accordion.php.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Attachment"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses
 * the existing per-page mechanism instead of a second one.
 *
 * Another slice of the "Komponenten-Showcase-Seite" idea documented as deliberately deferred in
 * docs/entscheidungen.md ("Komponenten-Showcase-Seite und Performance-Tooling") -- one page per
 * component, not the full one-call-per-base-component page from that entry yet, see
 * docs/to-do.md.
 *
 * Icon choices below are limited to what's actually synced under assets/images/icons/lucide (see
 * icon.php's own header) -- there is no dedicated "file"/"document" icon in that subset, so
 * `circle` stands in as a neutral generic-file glyph purely for this demo, not a real recommendation.
 *
 * The inline SVG data-URI below is a self-contained placeholder image (no Media Library
 * dependency) purely so the "Bild" section has something to render -- swap for a real
 * `attachment_id`/`src` in actual content.
 */

get_header();

$placeholder_image_src =
    "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 96 96'%3E" .
    "%3Crect width='96' height='96' fill='%23e5e5e5'/%3E" .
    "%3Cg fill='none' stroke='%23a3a3a3' stroke-width='4' stroke-linecap='round' " .
    "stroke-linejoin='round'%3E%3Crect x='14' y='14' width='68' height='68' rx='8'/%3E" .
    "%3Ccircle cx='36' cy='36' r='6'/%3E%3Cpath d='M14 66l20-20 14 14 12-12 22 22'/%3E%3C/g%3E%3C/svg%3E";

$view_remove_actions = static function (): string {
    ob_start();
    get_template_part('template-parts/base/button', null, [
        'config' => [
            'variant' => 'ghost',
            'size' => 'icon-sm',
            'icon' => ['name' => 'eye', 'set' => 'lucide'],
            'aria_label' => 'Ansehen',
        ],
    ]);
    get_template_part('template-parts/base/button', null, [
        'config' => [
            'variant' => 'ghost',
            'size' => 'icon-sm',
            'icon' => ['name' => 'x', 'set' => 'lucide'],
            'aria_label' => 'Entfernen',
        ],
    ]);

    return (string) ob_get_clean();
};
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Attachment — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        Alle Config-Optionen von <code>template-parts/base/attachment/attachment.php</code> +
        <code>attachment-group.php</code>. Dev-only, nicht für Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Basis (<code>media.variant: 'icon'</code>)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            Icon-Hintergrundfarbe kommt über <code>media.class</code> (Passthrough) — kein
            eigenes <code>accent</code>-Config, siehe Kopfkommentar.
        </p>
        <div class="flex max-w-xl flex-col gap-3">
            <?php get_template_part('template-parts/base/attachment/attachment', null, [
                'config' => [
                    'title' => 'produktdatenblatt-korund.pdf',
                    'description' => 'PDF · 2,4 MB',
                    'media' => [
                        'icon' => ['name' => 'circle', 'set' => 'lucide'],
                        'class' => 'bg-henge-blue text-henge-blue-foreground',
                    ],
                    'actions' => $view_remove_actions(),
                ],
            ]); ?>
            <?php get_template_part('template-parts/base/attachment/attachment', null, [
                'config' => [
                    'title' => 'lieferschein-2026-08.xlsx',
                    'description' => 'XLSX · 184 KB',
                    'media' => [
                        'icon' => ['name' => 'circle', 'set' => 'lucide'],
                        'class' => 'bg-henge-green text-henge-green-foreground',
                    ],
                    'actions' => $view_remove_actions(),
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Bild (<code>media.variant: 'image'</code>)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            Bild statt Icon — startet bei <code>opacity-60</code>, springt bei
            <code>state: 'done'</code>/<code>'idle'</code> auf volle Deckkraft (shadcns eigenes
            Verhalten, siehe Kopfkommentar).
        </p>
        <div class="flex max-w-xl flex-col gap-3">
            <?php get_template_part('template-parts/base/attachment/attachment', null, [
                'config' => [
                    'title' => 'werk-offenbach.jpg',
                    'description' => 'JPG · 1,1 MB',
                    'media' => [
                        'variant' => 'image',
                        'image' => [
                            'src' => $placeholder_image_src,
                            'alt' => '',
                            'decorative' => true,
                        ],
                    ],
                    'actions' => $view_remove_actions(),
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Zustände (<code>state</code>)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            <code>idle</code> | <code>uploading</code> | <code>processing</code> |
            <code>error</code> | <code>done</code> (Default). <code>actions</code> kann auch ein
            Status statt Buttons enthalten, z. B. <code>spinner.php</code> während
            <code>processing</code> — keine Pflicht zu Button-Inhalten.
            <code>uploading</code> hier zusätzlich mit <code>progress</code> (nested
            <code>progress.php</code>, innerhalb der Karte unter der Description).
        </p>
        <div class="flex max-w-xl flex-col gap-3">
            <?php get_template_part('template-parts/base/attachment/attachment', null, [
                'config' => [
                    'state' => 'idle',
                    'title' => 'angebot-2026.pdf',
                    'description' => 'Bereit zum Hochladen',
                    'media' => ['icon' => ['name' => 'circle', 'set' => 'lucide']],
                    'actions' => (function (): string {
                        ob_start();
                        get_template_part('template-parts/base/button', null, [
                            'config' => [
                                'text' => 'Hochladen',
                                'variant' => 'henge-green',
                                'size' => 'sm',
                            ],
                        ]);
                        return (string) ob_get_clean();
                    })(),
                ],
            ]); ?>
            <?php get_template_part('template-parts/base/attachment/attachment', null, [
                'config' => [
                    'state' => 'uploading',
                    'title' => 'zertifikate.zip',
                    'description' => 'Wird hochgeladen · 64 %',
                    'media' => ['icon' => ['name' => 'circle', 'set' => 'lucide']],
                    'progress' => [
                        'value' => 64,
                        'aria_label' => 'Upload-Fortschritt: zertifikate.zip',
                    ],
                    'actions' => (function (): string {
                        ob_start();
                        get_template_part('template-parts/base/button', null, [
                            'config' => [
                                'variant' => 'ghost',
                                'size' => 'icon-sm',
                                'icon' => ['name' => 'x', 'set' => 'lucide'],
                                'aria_label' => 'Abbrechen',
                            ],
                        ]);
                        return (string) ob_get_clean();
                    })(),
                ],
            ]); ?>
            <?php get_template_part('template-parts/base/attachment/attachment', null, [
                'config' => [
                    'state' => 'processing',
                    'title' => 'marktanalyse.pdf',
                    'description' => 'Dokument wird verarbeitet',
                    'media' => ['icon' => ['name' => 'circle', 'set' => 'lucide']],
                    'actions' => (function (): string {
                        ob_start();
                        get_template_part('template-parts/base/spinner', null, [
                            'config' => [
                                'class' => 'size-4 text-henge-grey',
                                'aria_label' => 'Verarbeitung läuft',
                            ],
                        ]);
                        return (string) ob_get_clean();
                    })(),
                ],
            ]); ?>
            <?php get_template_part('template-parts/base/attachment/attachment', null, [
                'config' => [
                    'state' => 'error',
                    'title' => 'kalkulation.xlsx',
                    'description' => 'Upload fehlgeschlagen',
                    'media' => ['icon' => ['name' => 'triangle-alert', 'set' => 'lucide']],
                    'actions' => (function (): string {
                        ob_start();
                        get_template_part('template-parts/base/button', null, [
                            'config' => [
                                'text' => 'Erneut versuchen',
                                'variant' => 'outline',
                                'size' => 'sm',
                            ],
                        ]);
                        return (string) ob_get_clean();
                    })(),
                ],
            ]); ?>
            <?php get_template_part('template-parts/base/attachment/attachment', null, [
                'config' => [
                    'state' => 'done',
                    'title' => 'agb-henge-baustoff.pdf',
                    'description' => 'Hochgeladen · 1,8 MB',
                    'media' => ['icon' => ['name' => 'circle-check', 'set' => 'lucide']],
                    'actions' => $view_remove_actions(),
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Größen (<code>size</code>)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            <code>xs</code> lässt <code>description</code> hier bewusst weg (kein automatisches
            Ausblenden per CSS, siehe Kopfkommentar — Caller-Entscheidung).
        </p>
        <div class="flex max-w-xl flex-col gap-3">
            <?php get_template_part('template-parts/base/attachment/attachment', null, [
                'config' => [
                    'size' => 'default',
                    'title' => 'Standard',
                    'description' => 'PDF · 2,4 MB',
                    'media' => ['icon' => ['name' => 'circle', 'set' => 'lucide']],
                    'actions' => $view_remove_actions(),
                ],
            ]); ?>
            <?php get_template_part('template-parts/base/attachment/attachment', null, [
                'config' => [
                    'size' => 'sm',
                    'title' => 'Klein',
                    'description' => 'PDF · 2,4 MB',
                    'media' => ['icon' => ['name' => 'circle', 'set' => 'lucide']],
                    'actions' => $view_remove_actions(),
                ],
            ]); ?>
            <?php get_template_part('template-parts/base/attachment/attachment', null, [
                'config' => [
                    'size' => 'xs',
                    'title' => 'Kompakt',
                    'media' => ['icon' => ['name' => 'circle', 'set' => 'lucide']],
                    'actions' => $view_remove_actions(),
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Ausrichtung (<code>orientation: 'vertical'</code>)
        </h2>
        <div class="flex flex-wrap gap-3">
            <?php foreach (
                [
                    ['n' => 'briefing.pdf', 'i' => 'circle'],
                    ['n' => 'werk.jpg', 'i' => 'circle'],
                    ['n' => 'kunden.csv', 'i' => 'circle'],
                ]
                as $tile
            ): ?>
                <?php get_template_part('template-parts/base/attachment/attachment', null, [
                    'config' => [
                        'orientation' => 'vertical',
                        'title' => $tile['n'],
                        'media' => ['icon' => ['name' => $tile['i'], 'set' => 'lucide']],
                        'actions' => (function (): string {
                            ob_start();
                            get_template_part('template-parts/base/button', null, [
                                'config' => [
                                    'variant' => 'ghost',
                                    'size' => 'icon-sm',
                                    'icon' => ['name' => 'x', 'set' => 'lucide'],
                                    'aria_label' => 'Entfernen',
                                ],
                            ]);
                            return (string) ob_get_clean();
                        })(),
                    ],
                ]); ?>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Gruppe (<code>attachment-group.php</code>)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            Horizontal scrollbare Reihe — nested scroll-area.php, Rand-Fade + versteckte Scrollbar
            per Regel-1-Ausnahme (siehe assets/css/app.css).
        </p>
        <?php
        $group_items = '';

        foreach (
            [
                ['n' => 'briefing.pdf', 'm' => 'PDF · 1,4 MB', 'i' => 'circle'],
                ['n' => 'werk-offenbach.jpg', 'm' => 'JPG · 820 KB', 'i' => 'circle'],
                ['n' => 'kunden.csv', 'm' => 'CSV · 18 KB', 'i' => 'circle'],
                ['n' => 'renderer.tsx', 'm' => 'TSX · 12 KB', 'i' => 'circle'],
                ['n' => 'zertifikate.zip', 'm' => 'ZIP · 6,2 MB', 'i' => 'circle'],
            ]
            as $item
        ) {
            ob_start();
            get_template_part('template-parts/base/attachment/attachment', null, [
                'config' => [
                    'size' => 'sm',
                    'title' => $item['n'],
                    'description' => $item['m'],
                    'media' => ['icon' => ['name' => $item['i'], 'set' => 'lucide']],
                ],
            ]);
            $group_items .= (string) ob_get_clean();
        }

        get_template_part('template-parts/base/attachment/attachment-group', null, [
            'config' => ['content' => $group_items, 'class' => 'max-w-xl'],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Als Link (<code>href</code>)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            Ganze Karte als Link/Overlay (<code>data-slot="attachment-trigger"</code>) — Actions
            bleiben trotzdem eigenständig klickbar (Stacking-Reihenfolge, siehe Kopfkommentar).
        </p>
        <div class="max-w-xl">
            <?php get_template_part('template-parts/base/attachment/attachment', null, [
                'config' => [
                    'title' => 'produktkatalog-2026.pdf',
                    'description' => 'PDF · 6,8 MB · als Download öffnen',
                    'href' => '#',
                    'media' => ['icon' => ['name' => 'circle', 'set' => 'lucide']],
                    'actions' => $view_remove_actions(),
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Custom class (Passthrough)</h2>
        <div class="max-w-xl">
            <?php get_template_part('template-parts/base/attachment/attachment', null, [
                'config' => [
                    'title' => 'Beispiel mit zusätzlichem Ring',
                    'description' => 'class landet auf dem äußeren Wrapper',
                    'class' => 'ring-2 ring-henge-green/40',
                    'media' => ['icon' => ['name' => 'circle', 'set' => 'lucide']],
                ],
            ]); ?>
        </div>
    </section>
</div>

<?php get_footer(); ?>
