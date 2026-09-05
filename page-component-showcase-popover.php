<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Popover
 *
 * Dev-only page template: renders template-parts/base/popover.php across a form-content example,
 * a plain-text info example, all four `side` values, all three `align` values, and a right-aligned
 * filter example over a product list, for manual visual/functional review during Phase 2 styling
 * work -- not meant for production content or navigation. Analog zu
 * page-component-showcase-tooltip.php/-toast.php.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Popover"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses the
 * existing per-page mechanism instead of a second one.
 *
 * Design reference: https://claude.ai/code/artifact/527a7d35-e7c6-43b4-ab6f-9f85baf2b43c
 */

get_header();

// popover.php's own header comment documents the constraint this helper exists for: `trigger`
// lands inside the native <summary> that IS the popover's one interactive control -- nesting a
// second real interactive element inside it (a genuine button.php `<button>`/`<a>`) breaks the
// browser's native disclosure toggle entirely (invalid HTML content model: interactive content
// inside interactive content), it does not merely look wrong. Every trigger below therefore reuses
// button.php's own variant/size class-building logic (no duplicated Tailwind strings to drift out
// of sync) but swaps its outer `<button>` tag for an inert `<span>` so it's safe to nest. Dev-only
// showcase concern, not something popover.php's own API needs to solve -- real callers are
// expected to pass plain text/icon content instead (see popover.php's/dropdown-menu.php's header
// comments), this is only for visually matching the design reference's button-look triggers here.
$render_popover_trigger_look = static function (array $button_config): string {
    ob_start();
    get_template_part('template-parts/base/button', null, ['config' => $button_config]);
    $button_html = (string) ob_get_clean();

    $span_html = preg_replace('/^<button\b/', '<span', $button_html, 1);
    $span_html = preg_replace('/<\/button>$/', '</span>', (string) $span_html, 1);

    return (string) preg_replace('/\s+type="[^"]*"/', '', (string) $span_html, 1);
};
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Popover — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        <code>template-parts/base/popover.php</code> + <code>popover.js</code>. Dev-only, nicht für
        Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Basis</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Öffnet auf Klick, schließt bei Klick daneben oder mit Escape. Helle Karte, enthält im
            Gegensatz zum Tooltip auch Eingaben und Aktionen.
        </p>
        <div class="flex flex-wrap items-start gap-10">
            <?php
            ob_start();
            get_template_part('template-parts/base/label', null, [
                'config' => ['text' => 'Körnung', 'class' => 'text-base font-semibold'],
            ]);
            ?>
            <p class="text-sm text-muted-foreground">
                Bereich in Millimetern, wird für die Anfrage übernommen.
            </p>
            <?php $popover_form_header = (string) ob_get_clean(); ?>

            <?php
            ob_start();
            get_template_part('template-parts/base/input', null, [
                'config' => ['label' => 'Von', 'value' => '0,4'],
            ]);
            get_template_part('template-parts/base/input', null, [
                'config' => ['label' => 'Bis', 'value' => '0,8'],
            ]);
            $popover_form_fields = (string) ob_get_clean();
            ?>

            <?php
            ob_start();
            get_template_part('template-parts/base/button', null, [
                'config' => ['variant' => 'outline', 'size' => 'sm', 'text' => 'Abbrechen'],
            ]);
            get_template_part('template-parts/base/button', null, [
                'config' => ['variant' => 'henge-green', 'size' => 'sm', 'text' => 'Übernehmen'],
            ]);
            $popover_form_actions = (string) ob_get_clean();

            $popover_form_content =
                '<div class="mb-3.5 flex flex-col gap-1">' .
                $popover_form_header .
                '</div><div class="mb-3.5 flex flex-col gap-3">' .
                $popover_form_fields .
                '</div><div class="flex justify-end gap-2">' .
                $popover_form_actions .
                '</div>';

            $popover_form_trigger = $render_popover_trigger_look([
                'variant' => 'henge-green',
                'text' => 'Körnung wählen',
            ]);

            get_template_part('template-parts/base/popover', null, [
                'config' => [
                    'trigger' => $popover_form_trigger,
                    'content' => $popover_form_content,
                    'align' => 'start',
                ],
            ]);

            ob_start();
            ?>
            <p class="mb-2.5 text-base font-semibold">Lieferform</p>
            <p class="mb-2.5 text-sm text-muted-foreground text-pretty">
                Big Bag zu 1.000 kg, Sackware zu 25 kg oder als Silofahrzeug ab 24 t.
            </p>
            <a class="text-sm font-semibold text-henge-green hover:text-henge-green/80" href="#"
                >Zum Datenblatt</a
            >
            <?php
            $popover_info_content = (string) ob_get_clean();

            $popover_info_trigger = $render_popover_trigger_look([
                'variant' => 'outline',
                'size' => 'icon-base',
                'aria_label' => 'Hinweise',
                'text' => 'i',
            ]);

            get_template_part('template-parts/base/popover', null, [
                'config' => [
                    'trigger' => $popover_info_trigger,
                    'content' => $popover_info_content,
                    'align' => 'start',
                    'aria_label' => 'Hinweise zur Lieferform',
                ],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Positionen (<code>side</code>)</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Vier Seiten, 10&nbsp;px Abstand, Pfeil mittig ausgerichtet.
        </p>
        <div
            class="grid grid-cols-2 gap-6 rounded-2xl border border-neutral-200 bg-neutral-50 p-14 sm:grid-cols-4"
        >
            <?php
            $sides = [
                'top' => 'Oben',
                'right' => 'Rechts',
                'bottom' => 'Unten',
                'left' => 'Links',
            ];

            foreach ($sides as $side => $label):
                $side_trigger = $render_popover_trigger_look([
                    'variant' => 'outline',
                    'text' => $label,
                ]); ?>
                <div class="flex flex-col items-center gap-3">
                    <?php get_template_part('template-parts/base/popover', null, [
                        'config' => [
                            'trigger' => $side_trigger,
                            'content' =>
                                '<p class="text-sm text-muted-foreground text-pretty">' .
                                esc_html($label . ' ausgerichtet, Pfeil zeigt zum Auslöser.') .
                                '</p>',
                            'side' => $side,
                        ],
                    ]); ?>
                    <span class="font-mono text-xs text-neutral-400">side="<?php echo esc_html(
                        $side,
                    ); ?>"</span>
                </div>
            <?php
            endforeach;
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Ausrichtung (<code>align</code>)</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Kreuzachse relativ zum Trigger: <code>start</code>/<code>end</code> bündig statt
            <code>center</code> (Default) mittig.
        </p>
        <div
            class="flex flex-wrap items-center gap-10 rounded-2xl border border-neutral-200 bg-neutral-50 p-14"
        >
            <?php
            $aligns = ['start' => 'Start', 'center' => 'Center', 'end' => 'End'];

            foreach ($aligns as $align => $label):
                $align_trigger = $render_popover_trigger_look([
                    'variant' => 'outline',
                    'text' => $label,
                ]); ?>
                <div class="flex flex-col items-center gap-3">
                    <?php get_template_part('template-parts/base/popover', null, [
                        'config' => [
                            'trigger' => $align_trigger,
                            'content' =>
                                '<p class="text-sm text-muted-foreground">align="' .
                                esc_html($align) .
                                '"</p>',
                            'side' => 'bottom',
                            'align' => $align,
                        ],
                    ]); ?>
                    <span class="font-mono text-xs text-neutral-400">align="<?php echo esc_html(
                        $align,
                    ); ?>"</span>
                </div>
            <?php
            endforeach;
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">In der Anwendung</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Filter über einer Produktliste. Rechts ausgerichtet (<code>align="end"</code>), weil der
            Auslöser am rechten Rand sitzt.
        </p>
        <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-6">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
                <span class="text-lg font-semibold">Quarzsande</span>
                <?php ob_start(); ?>
                <div class="flex flex-col gap-2.5">
                    <?php get_template_part('template-parts/base/checkbox', null, [
                        'config' => ['label' => 'Gewaschen', 'checked' => true],
                    ]); ?>
                    <?php get_template_part('template-parts/base/checkbox', null, [
                        'config' => ['label' => 'Getrocknet', 'checked' => true],
                    ]); ?>
                    <?php get_template_part('template-parts/base/checkbox', null, [
                        'config' => ['label' => 'Ungewaschen'],
                    ]); ?>
                    <?php get_template_part('template-parts/base/checkbox', null, [
                        'config' => ['label' => 'Gefärbt'],
                    ]); ?>
                </div>
                <div class="my-3.5 h-px bg-border"></div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm text-muted-foreground">2 aktiv</span>
                    <?php get_template_part('template-parts/base/button', null, [
                        'config' => [
                            'variant' => 'henge-green',
                            'size' => 'sm',
                            'text' => 'Anwenden',
                        ],
                    ]); ?>
                </div>
                <?php
                $filter_content = (string) ob_get_clean();

                $filter_trigger = $render_popover_trigger_look([
                    'variant' => 'outline',
                    'size' => 'sm',
                    'text' => 'Filter',
                ]);

                get_template_part('template-parts/base/popover', null, [
                    'config' => [
                        'trigger' => $filter_trigger,
                        'content' =>
                            '<p class="mb-3.5 text-base font-semibold">Filter</p>' .
                            $filter_content,
                        'align' => 'end',
                    ],
                ]);
                ?>
            </div>
            <div class="flex flex-col divide-y divide-neutral-100">
                <?php
                $products = [
                    ['name' => 'Quarzsand H 31', 'grain' => '0,1 – 0,5 mm'],
                    ['name' => 'Quarzsand H 33', 'grain' => '0,3 – 0,8 mm'],
                    ['name' => 'Quarzkies H 40', 'grain' => '2,0 – 4,0 mm'],
                ];

                foreach ($products as $product): ?>
                    <div class="flex items-baseline justify-between gap-5 py-3.5">
                        <span class="text-base"><?php echo esc_html($product['name']); ?></span>
                        <span class="text-sm text-muted-foreground tabular-nums"
                            ><?php echo esc_html($product['grain']); ?></span
                        >
                    </div>
                <?php endforeach;
                ?>
            </div>
        </div>
    </section>
</div>

<?php get_footer(); ?>
