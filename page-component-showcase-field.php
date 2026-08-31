<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Field
 *
 * Dev-only page template: renders the template-parts/base/field/ family (field.php,
 * field-set.php, field-legend.php, field-group.php, field-content.php, field-title.php,
 * field-description.php, field-error.php, field-separator.php, field-label.php) across
 * orientation, error-state and composition scenarios, plus the form components they wrap
 * (input.php/checkbox.php/native-select.php), for manual visual/functional review during Phase 2
 * styling work -- not meant for production content or navigation. Same content-agnostic-wrapper
 * caveat as page-component-showcase-button-group.php: every section buffers its child output via
 * ob_start()/ob_get_clean() before handing it to the wrapping field.php/field-set.php/
 * field-group.php call as `content`, see those files' own header comments for the composition
 * pattern and the hengegroup_theme_field_describedby()/_description_id()/_error_id() id-wiring
 * helpers used throughout.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Field"), and tick "noindex" in that page's own SEO metabox
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
?>

<div class="mx-auto max-w-3xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Field — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        Orientierung × Fehlerzustände × Kompositionen von
        <code>template-parts/base/field/</code>. Dev-only, nicht für Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Basisbeispiel (Label + Input + Description)</h2>
        <?php
        $control_id = 'showcase-field-email';

        $field_content =
            $render('template-parts/base/field/field-label', [
                'text' => 'E-Mail',
                'for' => $control_id,
            ]) .
            $render('template-parts/base/input', [
                'id' => $control_id,
                'type' => 'email',
                'placeholder' => 'du@beispiel.de',
                'attributes' => [
                    'aria-describedby' => hengegroup_theme_field_describedby(
                        $control_id,
                        true,
                        false,
                    ),
                ],
            ]) .
            $render('template-parts/base/field/field-description', [
                'for' => $control_id,
                'text' => 'Wir geben deine E-Mail-Adresse niemals weiter.',
            ]);

        get_template_part('template-parts/base/field/field', null, [
            'config' => ['content' => $field_content],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Mit Fehlermeldung (<code>invalid</code> + <code>field-error.php</code>)</h2>
        <?php
        $control_id = 'showcase-field-username';

        $field_content =
            $render('template-parts/base/field/field-label', [
                'text' => 'Benutzername',
                'for' => $control_id,
            ]) .
            $render('template-parts/base/input', [
                'id' => $control_id,
                'value' => 'ab',
                'aria_invalid' => true,
                'attributes' => [
                    'aria-describedby' => hengegroup_theme_field_describedby(
                        $control_id,
                        false,
                        true,
                    ),
                ],
            ]) .
            $render('template-parts/base/field/field-error', [
                'for' => $control_id,
                'text' => 'Der Benutzername muss mindestens 3 Zeichen lang sein.',
            ]);

        get_template_part('template-parts/base/field/field', null, [
            'config' => ['content' => $field_content, 'invalid' => true],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Mehrere Fehler (<code>field-error.php</code>'s <code>errors</code>)</h2>
        <?php
        $control_id = 'showcase-field-password';

        $field_content =
            $render('template-parts/base/field/field-label', [
                'text' => 'Passwort',
                'for' => $control_id,
            ]) .
            $render('template-parts/base/input', [
                'id' => $control_id,
                'type' => 'password',
                'aria_invalid' => true,
                'attributes' => [
                    'aria-describedby' => hengegroup_theme_field_describedby(
                        $control_id,
                        false,
                        true,
                    ),
                ],
            ]) .
            $render('template-parts/base/field/field-error', [
                'for' => $control_id,
                'errors' => [
                    'Mindestens 8 Zeichen.',
                    'Mindestens eine Zahl.',
                    'Mindestens ein Sonderzeichen.',
                ],
            ]);

        get_template_part('template-parts/base/field/field', null, [
            'config' => ['content' => $field_content, 'invalid' => true],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Orientierung <code>horizontal</code> (<code>field-content.php</code> + Checkbox)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            Label + Description wandern in <code>field-content.php</code>, die Kontrolle bleibt
            deren Geschwister -- <code>field.php</code>s Klasse
            <code>has-[&gt;[data-slot=field-content]]:items-start</code> richtet ein natives
            <code>role="checkbox"</code>/<code>role="radio"</code> an der ersten Zeile des Labels
            aus; da checkbox.php/radio.php bewusst native Inputs ohne <code>role</code>-Attribut
            sind (siehe deren Kopfkommentare), bleibt diese eine Klausel hier inert -- die
            Ausrichtung fällt stattdessen auf das <code>items-center</code>-Default zurück, was für
            ein natives Checkbox-Icon ebenfalls passt.
        </p>
        <?php
        $control_id = 'showcase-field-notifications';

        $field_content =
            $render('template-parts/base/field/field-content', [
                'content' =>
                    $render('template-parts/base/field/field-label', [
                        'text' => 'Benachrichtigungen aktivieren',
                        'for' => $control_id,
                    ]) .
                    $render('template-parts/base/field/field-description', [
                        'for' => $control_id,
                        'text' => 'Erhalte eine E-Mail bei neuen Kommentaren.',
                    ]),
            ]) .
            $render('template-parts/base/checkbox', [
                'id' => $control_id,
                'checked' => true,
                'attributes' => [
                    'aria-describedby' => hengegroup_theme_field_describedby(
                        $control_id,
                        true,
                        false,
                    ),
                ],
            ]);

        get_template_part('template-parts/base/field/field', null, [
            'config' => ['content' => $field_content, 'orientation' => 'horizontal'],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Orientierung <code>responsive</code> (<code>field-group.php</code>'s Container-Query)
        </h2>
        <p class="mb-4 text-sm text-neutral-500">
            Beide Felder stapeln sich schmal, stehen aber ab der
            <code>@container/field-group</code>-Breakpoint des umgebenden
            <code>field-group.php</code> nebeneinander -- reagiert auf die tatsächliche Breite
            dieses Containers, nicht auf den Viewport (verkleinere das Browserfenster/Panel, nicht
            zwingend das ganze Fenster, um den Umbruch zu sehen).
        </p>
        <?php
        $first_id = 'showcase-field-firstname';
        $last_id = 'showcase-field-lastname';

        $first_field = $render('template-parts/base/field/field', [
            'orientation' => 'responsive',
            'content' =>
                $render('template-parts/base/field/field-label', [
                    'text' => 'Vorname',
                    'for' => $first_id,
                ]) .
                $render('template-parts/base/input', ['id' => $first_id, 'placeholder' => 'Max']),
        ]);
        $last_field = $render('template-parts/base/field/field', [
            'orientation' => 'responsive',
            'content' =>
                $render('template-parts/base/field/field-label', [
                    'text' => 'Nachname',
                    'for' => $last_id,
                ]) .
                $render('template-parts/base/input', [
                    'id' => $last_id,
                    'placeholder' => 'Mustermann',
                ]),
        ]);

        get_template_part('template-parts/base/field/field-group', null, [
            'config' => ['content' => $first_field . $last_field],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Field Set + Legend (<code>field-set.php</code>, <code>field-legend.php</code>)
        </h2>
        <?php
        $city_id = 'showcase-field-city';
        $zip_id = 'showcase-field-zip';

        $city_field = $render('template-parts/base/field/field', [
            'content' =>
                $render('template-parts/base/field/field-label', [
                    'text' => 'Stadt',
                    'for' => $city_id,
                ]) . $render('template-parts/base/input', ['id' => $city_id]),
        ]);
        $zip_field = $render('template-parts/base/field/field', [
            'content' =>
                $render('template-parts/base/field/field-label', [
                    'text' => 'PLZ',
                    'for' => $zip_id,
                ]) . $render('template-parts/base/input', ['id' => $zip_id]),
        ]);
        $address_group = $render('template-parts/base/field/field-group', [
            'content' => $city_field . $zip_field,
        ]);

        $fieldset_content =
            $render('template-parts/base/field/field-legend', ['text' => 'Lieferadresse']) .
            $render('template-parts/base/field/field-description', [
                'text' => 'Wohin sollen wir dein Paket schicken?',
            ]) .
            $address_group;

        get_template_part('template-parts/base/field/field-set', null, [
            'config' => ['content' => $fieldset_content],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Field Separator (<code>field-separator.php</code>)</h2>
        <?php
        $email_id = 'showcase-field-separator-email';
        $sso_id = 'showcase-field-separator-sso';

        $email_field = $render('template-parts/base/field/field', [
            'content' =>
                $render('template-parts/base/field/field-label', [
                    'text' => 'E-Mail',
                    'for' => $email_id,
                ]) . $render('template-parts/base/input', ['id' => $email_id, 'type' => 'email']),
        ]);
        $sso_field = $render('template-parts/base/field/field', [
            'content' =>
                $render('template-parts/base/field/field-label', [
                    'text' => 'Single Sign-On',
                    'for' => $sso_id,
                ]) .
                $render('template-parts/base/native-select', [
                    'id' => $sso_id,
                    'placeholder' => 'Anbieter wählen',
                    'options' => [
                        ['value' => 'google', 'text' => 'Google'],
                        ['value' => 'microsoft', 'text' => 'Microsoft'],
                    ],
                ]),
        ]);

        get_template_part('template-parts/base/field/field-group', null, [
            'config' => [
                'content' =>
                    $email_field .
                    $render('template-parts/base/field/field-separator', ['text' => 'Oder']) .
                    $sso_field .
                    $render('template-parts/base/field/field-separator', []) .
                    $render('template-parts/base/button', [
                        'text' => 'Weiter',
                        'full_width' => true,
                    ]),
            ],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Field Title (<code>field-title.php</code>)</h2>
        <p class="mb-4 text-sm text-neutral-500">
            Reine Anzeige-Beschriftung ohne <code>for</code>-Pairing -- z. B. wenn es kein
            einzelnes Kontroll-Element zum Benennen gibt, oder wenn das eigentliche
            <code>field-label.php</code> bereits an anderer Stelle sitzt.
        </p>
        <?php
        $field_content = $render('template-parts/base/field/field-content', [
            'content' =>
                $render('template-parts/base/field/field-title', ['text' => 'Speicherplatz']) .
                $render('template-parts/base/field/field-description', [
                    'text' => '4,2 GB von 15 GB belegt.',
                ]),
        ]);

        get_template_part('template-parts/base/field/field', null, [
            'config' => ['content' => $field_content],
        ]);
        ?>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Bekannte Lücke: Field Label "Choice Card" (<code>has-data-[state=checked]</code>)
        </h2>
        <p class="text-sm text-neutral-500">
            shadcns <code>FieldLabel</code> kann eine komplette
            <code>&lt;div data-slot="field"&gt;</code> als direktes Kind umschließen und sich dann
            selbst als markierbare Karte darstellen (Rahmen + <code>henge-green</code>-Highlight bei
            <code>data-state="checked"</code>, siehe label.php's Kopfkommentar) -- eine gängige
            "Radio Card"-Auswahl. Hier bewusst NICHT vorgeführt: label.php's Config-API nimmt nur
            reinen, escapten Text entgegen (<code>text</code>/<code>label</code>), keinen
            beliebigen HTML-<code>content</code> zum Verschachteln -- ein Radio/eine Checkbox plus
            ein verschachteltes field.php ließen sich also gar nicht über die öffentliche Config
            hinein-komponieren, ohne label.php's API zu erweitern (nicht Teil dieses Auftrags). Die
            Klassen liegen bereits bereit, sobald diese Erweiterung ansteht.
        </p>
    </section>
</div>

<?php get_footer(); ?>
