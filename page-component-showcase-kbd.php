<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Kbd
 *
 * Dev-only page template: renders template-parts/base/kbd/kbd.php + kbd-group.php across size
 * (sm/default/lg), pressed state, multi-key combinations (with separators, via kbd.php calls
 * composed by the caller) and grouped blocks (no separators, via kbd-group.php) plus a few
 * in-context usages, for manual visual/functional review during Phase 2 styling work -- not meant
 * for production content or navigation. Analog zur page-component-showcase-badge.php/
 * -button-group.php: every buffered string below only ever feeds another `content`/config value
 * (kbd-group.php's own `content` param, which escapes/documents that contract itself), never gets
 * `echo`ed raw on this page -- direct kbd.php/kbd-group.php calls stay as plain
 * `get_template_part()` calls, same as every other showcase page.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Kbd"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses the
 * existing per-page mechanism instead of a second one.
 *
 * Further slice of the "Komponenten-Showcase-Seite" idea documented as deliberately deferred in
 * docs/entscheidungen.md ("Komponenten-Showcase-Seite und Performance-Tooling") -- not the full
 * one-call-per-base-component page from that entry yet, see docs/to-do.md.
 */

get_header();

$render_kbd_group = static function (array $keys): void {
    ob_start();

    foreach ($keys as $key) {
        get_template_part('template-parts/base/kbd/kbd', null, ['config' => ['text' => $key]]);
    }

    $keys_markup = (string) ob_get_clean();

    get_template_part('template-parts/base/kbd/kbd-group', null, [
        'config' => ['content' => $keys_markup],
    ]);
};
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Kbd — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        Größen × Zustände von <code>template-parts/base/kbd/kbd.php</code> +
        <code>kbd-group.php</code>. Dev-only, nicht für Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Basis</h2>
        <div class="flex flex-wrap items-center gap-2">
            <?php foreach (
                ['⌘', '⇧', '⌥', '⌃', 'Esc', 'Tab', '⏎', '⌫', '↑', '↓', 'K', 'F5']
                as $key
            ): ?>
                <?php get_template_part('template-parts/base/kbd/kbd', null, [
                    'config' => ['text' => $key],
                ]); ?>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Größen (<code>size</code>)</h2>
        <div class="flex flex-col gap-4">
            <?php foreach (['sm', 'default', 'lg'] as $size): ?>
                <div class="flex items-center gap-5">
                    <span class="w-20 shrink-0 text-sm font-semibold text-neutral-500">
                        <?php echo esc_html($size); ?>
                    </span>
                    <div class="flex items-center gap-2">
                        <?php
                        get_template_part('template-parts/base/kbd/kbd', null, [
                            'config' => ['text' => '⌘', 'size' => $size],
                        ]);
                        get_template_part('template-parts/base/kbd/kbd', null, [
                            'config' => ['text' => 'K', 'size' => $size],
                        ]);
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Gedrückter Zustand (<code>pressed</code>)</h2>
        <div class="flex flex-wrap items-center gap-6">
            <div class="flex items-center gap-2">
                <span class="text-sm text-neutral-500">Standard</span>
                <?php get_template_part('template-parts/base/kbd/kbd', null, [
                    'config' => ['text' => 'A'],
                ]); ?>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-neutral-500">Gedrückt</span>
                <?php get_template_part('template-parts/base/kbd/kbd', null, [
                    'config' => ['text' => 'A', 'pressed' => true],
                ]); ?>
            </div>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Kombinationen (mit Trennzeichen)</h2>
        <div class="flex max-w-xl flex-col gap-0 border-t border-border">
            <?php
            $combos = [
                ['Suche öffnen', ['⌘', 'K'], '+'],
                ['Datenblatt herunterladen', ['⌘', '⇧', 'D'], '+'],
                ['Zwischen Feldern springen', ['Tab', '⇧ Tab'], 'oder'],
                ['Dialog schließen', ['Esc'], '+'],
            ];

            foreach ($combos as [$label, $keys, $sep]): ?>
                <div class="flex items-center justify-between gap-6 border-b border-border py-3.5">
                    <span><?php echo esc_html($label); ?></span>
                    <span class="inline-flex items-center gap-1.5">
                        <?php foreach ($keys as $i => $key): ?>
                            <?php if ($i > 0): ?>
                                <span class="text-sm text-neutral-400">
                                    <?php echo esc_html($sep); ?>
                                </span>
                            <?php endif; ?>
                            <?php get_template_part('template-parts/base/kbd/kbd', null, [
                                'config' => ['text' => $key],
                            ]); ?>
                        <?php endforeach; ?>
                    </span>
                </div>
            <?php endforeach;
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Gruppe (ohne Trennzeichen)</h2>
        <div class="flex flex-wrap items-center gap-4">
            <?php
            $render_kbd_group(['⌘', 'C']);
            $render_kbd_group(['⌘', '⇧', 'V']);
            $render_kbd_group(['↑', '↓', '←', '→']);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Im Kontext</h2>
        <div class="flex max-w-xl flex-col gap-6">
            <p class="text-base leading-relaxed text-foreground/80">
                Mit
                <?php get_template_part('template-parts/base/kbd/kbd', null, [
                    'config' => ['text' => '⌘', 'size' => 'sm'],
                ]); ?>
                <?php get_template_part('template-parts/base/kbd/kbd', null, [
                    'config' => ['text' => 'K', 'size' => 'sm'],
                ]); ?>
                öffnen Sie die Produktsuche, mit
                <?php get_template_part('template-parts/base/kbd/kbd', null, [
                    'config' => ['text' => 'Esc', 'size' => 'sm'],
                ]); ?>
                schließen Sie sie wieder.
            </p>

            <div
                class="flex items-center gap-3 rounded-xl border border-border bg-background px-4 py-3.5 shadow-xs"
            >
                <?php get_template_part('template-parts/base/icon', null, [
                    'config' => [
                        'name' => 'search',
                        'set' => 'lucide',
                        'class' => 'size-4.5 text-neutral-400',
                    ],
                ]); ?>
                <span class="flex-1 text-base text-neutral-400">Produkt oder Körnung suchen</span>
                <?php $render_kbd_group(['⌘', 'K']); ?>
            </div>

            <div class="flex flex-wrap gap-3">
                <?php get_template_part('template-parts/base/button', null, [
                    'config' => [
                        'text' => 'Anfrage senden',
                        'icon' => ['name' => 'corner-down-left', 'set' => 'lucide'],
                        'icon_position' => 'end',
                    ],
                ]); ?>
            </div>
        </div>
    </section>
</div>

<?php get_footer(); ?>
