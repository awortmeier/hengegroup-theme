<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Calendar
 *
 * Dev-only page template: renders template-parts/base/calendar.php across its documented config
 * axes (mode single/multiple, selected/disabled/min/max dates, week_starts_on,
 * show_outside_days, navigation) plus its main real-world composition,
 * template-parts/base/date-picker.php, for manual visual/functional review during Phase 2
 * styling work -- not meant for production content or navigation. Analog zu
 * page-component-showcase-button.php/page-component-showcase-badge.php; date-picker.php already
 * gets a short section in page-component-showcase-form-elements.php too, but calendar.php's own
 * grid -- the actual Phase-2-styled surface, see that file's header comment -- has no page of its
 * own yet, and every other config axis here (multiple/min-max/week_starts_on/show_outside_days/
 * navigation) is calendar.php-specific, not shared with the rest of that form-elements page.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Calendar"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses
 * the existing per-page mechanism instead of a second one.
 *
 * Another slice of the "Komponenten-Showcase-Seite" idea documented as deliberately deferred in
 * docs/entscheidungen.md ("Komponenten-Showcase-Seite und Performance-Tooling") -- one page per
 * component (family), not the full one-call-per-base-component page from that entry yet, see
 * docs/to-do.md.
 */

get_header();

$today = gmdate('Y-m-d');
$in_5_days = gmdate('Y-m-d', strtotime('+5 days'));
$in_10_days = gmdate('Y-m-d', strtotime('+10 days'));
$in_14_days = gmdate('Y-m-d', strtotime('+14 days'));
$in_21_days = gmdate('Y-m-d', strtotime('+21 days'));
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Calendar — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        Alle Konfigurations-Achsen von <code>template-parts/base/calendar.php</code> plus dessen
        Haupt-Komposition <code>template-parts/base/date-picker.php</code>. Dev-only, nicht für
        Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Einzelauswahl, Standardzustand (<code>mode: 'single'</code>)
        </h2>
        <div class="max-w-sm">
            <?php get_template_part('template-parts/base/calendar', null, [
                'config' => ['aria_label' => 'Datum wählen'],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Termine buchen: Vorauswahl, Sperren, Zeitraum</h2>
        <p class="mb-6 text-sm text-neutral-500">
            <code>selected</code> + <code>disabled_dates</code> + <code>min_date</code>/
            <code>max_date</code> kombiniert, wie bei einer echten Terminbuchung: Tage vor heute
            und nach dem Buchungshorizont sind gesperrt, ein Termin dazwischen ist bereits
            ausgebucht.
        </p>
        <div class="max-w-sm">
            <?php get_template_part('template-parts/base/calendar', null, [
                'config' => [
                    'aria_label' => 'Termin buchen',
                    'selected' => $in_5_days,
                    'disabled_dates' => [$in_10_days],
                    'min_date' => $today,
                    'max_date' => $in_21_days,
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Mehrfachauswahl (<code>mode: 'multiple'</code>)</h2>
        <div class="max-w-sm">
            <?php get_template_part('template-parts/base/calendar', null, [
                'config' => [
                    'aria_label' => 'Mehrere Termine wählen',
                    'mode' => 'multiple',
                    'selected' => [$in_5_days, $in_10_days, $in_14_days],
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Wochenstart &amp; Randtage</h2>
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
            <div>
                <h3 class="mb-2 text-sm font-medium text-neutral-500">
                    <code>week_starts_on: 1</code> (Montag)
                </h3>
                <?php get_template_part('template-parts/base/calendar', null, [
                    'config' => [
                        'aria_label' => 'Woche beginnt Montag',
                        'week_starts_on' => 1,
                    ],
                ]); ?>
            </div>
            <div>
                <h3 class="mb-2 text-sm font-medium text-neutral-500">
                    <code>show_outside_days: false</code>
                </h3>
                <?php get_template_part('template-parts/base/calendar', null, [
                    'config' => [
                        'aria_label' => 'Ohne Randtage',
                        'show_outside_days' => false,
                    ],
                ]); ?>
            </div>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Fest angepinnter Monat (<code>navigation: false</code>)
        </h2>
        <p class="mb-6 text-sm text-neutral-500">
            Für Aufrufer, die den Monat extern steuern -- keine Vor-/Zurück-Links, kein
            Monatstitel.
        </p>
        <div class="max-w-sm">
            <?php get_template_part('template-parts/base/calendar', null, [
                'config' => [
                    'aria_label' => 'Dezember 2026',
                    'month' => 12,
                    'year' => 2026,
                    'navigation' => false,
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            In Kombination: Date Picker (<code>template-parts/base/date-picker.php</code>)
        </h2>
        <p class="mb-6 text-sm text-neutral-500">
            Die eigentliche Alltags-Verwendung: <code>calendar.php</code> als Popover-Panel hinter
            einem Trigger, der wie ein Input-Feld aussieht -- so wie in der Design-Vorlage. Klick
            auf den Trigger öffnet das Panel.
        </p>
        <div class="flex max-w-sm flex-col gap-3">
            <?php
            get_template_part('template-parts/base/date-picker', null, [
                'config' => ['aria_label' => 'Datum wählen'],
            ]);
            get_template_part('template-parts/base/date-picker', null, [
                'config' => [
                    'aria_label' => 'Vorausgewähltes Datum',
                    'selected' => $in_5_days,
                ],
            ]);
            ?>
        </div>
    </section>
</div>

<?php get_footer(); ?>
