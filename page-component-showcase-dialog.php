<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Dialog
 *
 * Dev-Seite zur visuellen Pruefung der Komponente (Seite mit noindex anlegen).
 */

get_header();

$delivery_options = [
    ['value' => 'big-bag', 'label' => 'Big Bag', 'meta' => '1.000 kg', 'checked' => true],
    ['value' => 'sackware', 'label' => 'Sackware', 'meta' => '25 kg'],
    ['value' => 'silo', 'label' => 'Silofahrzeug', 'meta' => 'ab 24 t'],
];

// Demo-Optionsliste. has-[:checked]: statt peer-checked:, weil peer auf alle folgenden Zeilen wirken wuerde.
function hengegroup_theme_showcase_dialog_options(array $options, string $name): string
{
    $rows_markup = '';

    foreach ($options as $option) {
        ob_start();
        get_template_part('template-parts/base/radio/radio', null, [
            'config' => [
                'name' => $name,
                'value' => $option['value'],
                'checked' => !empty($option['checked']),
            ],
        ]);
        $radio_markup = (string) ob_get_clean();

        $rows_markup .= sprintf(
            '<label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl ' .
                'border border-border p-4 transition-colors has-[:checked]:border-henge-green ' .
                'has-[:checked]:bg-henge-green/5">' .
                '<span class="flex items-center gap-3">%1$s<span class="font-semibold">%2$s</span></span>' .
                '<span class="text-sm text-muted-foreground">%3$s</span></label>',
            $radio_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            esc_html($option['label']),
            esc_html($option['meta']),
        );
    }

    return sprintf('<div class="flex flex-col gap-3">%s</div>', $rows_markup); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

ob_start();
?>
<?php get_template_part('template-parts/base/button', null, [
    'config' => [
        'text' => 'Abbrechen',
        'variant' => 'outline',
        'attributes' => ['command' => 'close', 'commandfor' => 'showcase-dialog-basis'],
    ],
]); ?>
<?php get_template_part('template-parts/base/button', null, [
    'config' => [
        'text' => 'Übernehmen',
        'attributes' => ['command' => 'close', 'commandfor' => 'showcase-dialog-basis'],
    ],
]); ?>
<?php $delivery_footer = (string) ob_get_clean(); ?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Dialog — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        <code>template-parts/base/dialog.php</code> + <code>dialog.js</code>. Dev-only, nicht für
        Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Basis</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Modal über abgedunkeltem Grund. Schließt über Abbrechen, das Kreuz, Klick auf den Grund
            oder mit Escape.
        </p>
        <div class="flex flex-wrap items-center gap-3">
            <?php get_template_part('template-parts/base/button', null, [
                'config' => [
                    'text' => 'Anfrage senden',
                    'attributes' => [
                        'command' => 'show-modal',
                        'commandfor' => 'showcase-dialog-basis',
                    ],
                ],
            ]); ?>
            <?php get_template_part('template-parts/base/button', null, [
                'config' => [
                    'text' => 'Datenblatt ansehen',
                    'variant' => 'outline',
                    'attributes' => [
                        'command' => 'show-modal',
                        'commandfor' => 'showcase-dialog-datenblatt',
                    ],
                ],
            ]); ?>
        </div>

        <?php get_template_part('template-parts/base/dialog', null, [
            'config' => [
                'id' => 'showcase-dialog-basis',
                'title' => 'Lieferform ändern',
                'description' => 'Die Auswahl gilt für alle Positionen dieser Anfrage.',
                'content' => hengegroup_theme_showcase_dialog_options(
                    $delivery_options,
                    'showcase-dialog-basis-delivery',
                ),
                'footer' => $delivery_footer,
            ],
        ]); ?>

        <?php ob_start(); ?>
        <p class="text-sm text-pretty text-muted-foreground">
            PDF, 480&nbsp;KB, Stand 03/2026. Enthält Körnung, chemische Kennwerte und
            Lieferformen.
        </p>
        <?php
        $datenblatt_content = (string) ob_get_clean();
        ob_start();
        ?>
        <?php get_template_part('template-parts/base/button', null, [
            'config' => [
                'text' => 'Schließen',
                'variant' => 'outline',
                'attributes' => [
                    'command' => 'close',
                    'commandfor' => 'showcase-dialog-datenblatt',
                ],
            ],
        ]); ?>
        <?php $datenblatt_footer = (string) ob_get_clean(); ?>
        <?php get_template_part('template-parts/base/dialog', null, [
            'config' => [
                'id' => 'showcase-dialog-datenblatt',
                'title' => 'Quarzsand H 33',
                'description' => 'Technisches Datenblatt',
                'content' => $datenblatt_content,
                'footer' => $datenblatt_footer,
            ],
        ]); ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Aufbau</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Kopf mit Titel und Beschreibung, Inhalt, Fußzeile mit bis zu zwei Aktionen. 32&nbsp;px
            Innenabstand, <code>rounded-2xl</code> (16&nbsp;px) Radius.
        </p>
        <div class="relative min-h-[420px] overflow-hidden rounded-2xl bg-neutral-900 p-8">
            <?php get_template_part('template-parts/base/dialog', null, [
                'config' => [
                    'id' => 'showcase-dialog-aufbau',
                    'title' => 'Lieferform ändern',
                    'description' => 'Die Auswahl gilt für alle Positionen dieser Anfrage.',
                    'content' => hengegroup_theme_showcase_dialog_options(
                        $delivery_options,
                        'showcase-dialog-aufbau-delivery',
                    ),
                    'footer' => $delivery_footer,
                    'open' => true,
                    'modal' => false,
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Varianten</h2>
        <p class="mb-6 text-sm text-neutral-500">
            <code>show_close_button: false</code>, <code>dismissible: false</code> (kein Escape/
            Klick-auf-Grund) und <code>modal: false</code> (kein Fokus-Trap, kein abgedunkelter
            Grund).
        </p>
        <div class="flex flex-wrap items-center gap-3">
            <?php get_template_part('template-parts/base/button', null, [
                'config' => [
                    'text' => 'Ohne Schließen-Button',
                    'variant' => 'outline',
                    'attributes' => [
                        'command' => 'show-modal',
                        'commandfor' => 'showcase-dialog-no-close',
                    ],
                ],
            ]); ?>
            <?php get_template_part('template-parts/base/button', null, [
                'config' => [
                    'text' => 'Nicht schließbar',
                    'variant' => 'outline',
                    'attributes' => [
                        'command' => 'show-modal',
                        'commandfor' => 'showcase-dialog-not-dismissible',
                    ],
                ],
            ]); ?>
        </div>

        <?php ob_start(); ?>
        <?php get_template_part('template-parts/base/button', null, [
            'config' => [
                'text' => 'Verstanden',
                'attributes' => ['command' => 'close', 'commandfor' => 'showcase-dialog-no-close'],
            ],
        ]); ?>
        <?php $no_close_footer = (string) ob_get_clean(); ?>
        <?php get_template_part('template-parts/base/dialog', null, [
            'config' => [
                'id' => 'showcase-dialog-no-close',
                'title' => 'Ohne eingebauten Schließen-Button',
                'description' => 'Schließen nur über den Grund, Escape oder eine eigene Aktion.',
                'footer' => $no_close_footer,
                'show_close_button' => false,
            ],
        ]); ?>

        <?php ob_start(); ?>
        <?php get_template_part('template-parts/base/button', null, [
            'config' => [
                'text' => 'Änderungen verwerfen',
                'variant' => 'outline',
                'attributes' => [
                    'command' => 'close',
                    'commandfor' => 'showcase-dialog-not-dismissible',
                ],
            ],
        ]); ?>
        <?php $not_dismissible_footer = (string) ob_get_clean(); ?>
        <?php get_template_part('template-parts/base/dialog', null, [
            'config' => [
                'id' => 'showcase-dialog-not-dismissible',
                'title' => 'Ungespeicherte Änderungen',
                'description' =>
                    'Nur über die Schaltfläche unten verlassbar, nicht über Escape oder Klick auf den Grund.',
                'footer' => $not_dismissible_footer,
                'dismissible' => false,
            ],
        ]); ?>

        <p class="mt-10 mb-3 text-sm text-neutral-500">
            <code>modal: false</code> lässt sich nicht über <code>command="show-modal"</code>
            öffnen -- dieser native Befehlswert ruft immer <code>showModal()</code> auf, unabhängig
            von der Konfiguration des Ziels (ein dokumentierter, echter Native-Plattform-Befehl,
            keine Design-Entscheidung dieses Themes). Daher <code>open: true</code> direkt gerendert,
            ohne Auslöser -- kein Fokus-Trap, kein abgedunkelter Grund, die Seite dahinter bleibt
            bedienbar.
        </p>
        <div class="relative min-h-[240px] rounded-2xl border border-dashed border-neutral-300 p-6">
            <?php get_template_part('template-parts/base/dialog', null, [
                'config' => [
                    'id' => 'showcase-dialog-non-modal',
                    'title' => 'Nicht modal',
                    'description' =>
                        'Kein Fokus-Trap, kein abgedunkelter Grund -- die Seite dahinter bleibt bedienbar.',
                    'open' => true,
                    'modal' => false,
                ],
            ]); ?>
        </div>
    </section>
</div>

<?php get_footer(); ?>
