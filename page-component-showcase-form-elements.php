<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Form Elements
 *
 * Dev-only page template: renders every form-input base component under
 * `template-parts/base/` (input, textarea, checkbox, radio/radio-group, switch, native-select,
 * select, combobox, slider, date-picker, input-group, field) across their documented
 * states for manual visual/functional review during Phase 2 styling work -- not meant for
 * production content or navigation.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Form Elements"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses
 * the existing per-page mechanism instead of a second one.
 *
 * Another slice of the "Komponenten-Showcase-Seite" idea documented as deliberately deferred in
 * docs/entscheidungen.md ("Komponenten-Showcase-Seite und Performance-Tooling") -- one page per
 * component family (see page-component-showcase-button.php/page-component-showcase-badge.php),
 * not the full one-call-per-base-component page from that entry yet, see docs/to-do.md.
 */

get_header();

$select_options = [
    ['value' => 'apple', 'text' => 'Apple'],
    ['value' => 'banana', 'text' => 'Banana'],
    ['value' => 'cherry', 'text' => 'Cherry'],
    [
        'label' => 'Citrus',
        'options' => [
            ['value' => 'lemon', 'text' => 'Lemon'],
            ['value' => 'orange', 'text' => 'Orange'],
        ],
    ],
];

$combobox_options = [
    ['value' => 'apple', 'text' => 'Apple', 'group' => 'Fruit'],
    ['value' => 'banana', 'text' => 'Banana', 'group' => 'Fruit'],
    ['value' => 'carrot', 'text' => 'Carrot', 'group' => 'Vegetable'],
    ['value' => 'potato', 'text' => 'Potato', 'group' => 'Vegetable'],
];
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Form Elements — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        Alle Form-Input Base-Komponenten unter <code>template-parts/base/</code> über ihre
        wichtigsten Zustände. Dev-only, nicht für Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Input (<code>template-parts/base/input.php</code>)
        </h2>

        <h3 class="mb-2 text-sm font-medium text-neutral-500">Typen</h3>
        <div class="mb-6 flex max-w-sm flex-col gap-3">
            <?php
            $input_types = ['text', 'email', 'password', 'number', 'tel', 'url', 'search', 'date'];
            foreach ($input_types as $type):
                get_template_part('template-parts/base/input', null, [
                    'config' => [
                        'type' => $type,
                        'placeholder' => ucfirst($type) . '…',
                        'label' => ucfirst($type),
                    ],
                ]);
            endforeach;
            ?>
        </div>

        <h3 class="mb-2 text-sm font-medium text-neutral-500">Zustände</h3>
        <div class="flex max-w-sm flex-col gap-3">
            <?php
            get_template_part('template-parts/base/input', null, [
                'config' => ['placeholder' => 'Normal'],
            ]);
            get_template_part('template-parts/base/input', null, [
                'config' => ['placeholder' => 'Mit Wert', 'value' => 'Vorausgefüllt'],
            ]);
            get_template_part('template-parts/base/input', null, [
                'config' => ['placeholder' => 'Disabled', 'disabled' => true],
            ]);
            get_template_part('template-parts/base/input', null, [
                'config' => [
                    'placeholder' => 'Readonly',
                    'value' => 'Nur lesbar',
                    'readonly' => true,
                ],
            ]);
            get_template_part('template-parts/base/input', null, [
                'config' => ['placeholder' => 'Required', 'required' => true],
            ]);
            get_template_part('template-parts/base/input', null, [
                'config' => ['placeholder' => 'Invalid', 'aria_invalid' => true],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Textarea (<code>template-parts/base/textarea.php</code>)
        </h2>
        <div class="flex max-w-sm flex-col gap-3">
            <?php
            get_template_part('template-parts/base/textarea', null, [
                'config' => ['placeholder' => 'Normal', 'label' => 'Nachricht'],
            ]);
            get_template_part('template-parts/base/textarea', null, [
                'config' => ['placeholder' => 'Disabled', 'disabled' => true],
            ]);
            get_template_part('template-parts/base/textarea', null, [
                'config' => ['placeholder' => 'Invalid', 'aria_invalid' => true],
            ]);
            get_template_part('template-parts/base/textarea', null, [
                'config' => ['value' => 'Vorausgefüllter Text über mehrere Zeilen.', 'rows' => 4],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Checkbox (<code>template-parts/base/checkbox.php</code>)
        </h2>
        <div class="flex flex-col gap-3">
            <?php
            get_template_part('template-parts/base/checkbox', null, [
                'config' => ['label' => 'Unchecked'],
            ]);
            get_template_part('template-parts/base/checkbox', null, [
                'config' => ['label' => 'Checked', 'checked' => true],
            ]);
            get_template_part('template-parts/base/checkbox', null, [
                'config' => ['label' => 'Disabled', 'disabled' => true],
            ]);
            get_template_part('template-parts/base/checkbox', null, [
                'config' => [
                    'label' => 'Disabled + Checked',
                    'disabled' => true,
                    'checked' => true,
                ],
            ]);
            get_template_part('template-parts/base/checkbox', null, [
                'config' => ['label' => 'Indeterminate', 'indeterminate' => true],
            ]);
            get_template_part('template-parts/base/checkbox', null, [
                'config' => ['label' => 'Invalid', 'aria_invalid' => true],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Radio (<code>template-parts/base/radio/radio.php</code> +
            <code>radio-group.php</code>)
        </h2>

        <h3 class="mb-2 text-sm font-medium text-neutral-500">Einzeln (manuell gruppiert)</h3>
        <div class="mb-6 flex flex-col gap-3">
            <?php
            get_template_part('template-parts/base/radio/radio', null, [
                'config' => [
                    'name' => 'showcase-radio-manual',
                    'value' => 'a',
                    'label' => 'Option A',
                    'checked' => true,
                ],
            ]);
            get_template_part('template-parts/base/radio/radio', null, [
                'config' => [
                    'name' => 'showcase-radio-manual',
                    'value' => 'b',
                    'label' => 'Option B',
                ],
            ]);
            get_template_part('template-parts/base/radio/radio', null, [
                'config' => [
                    'name' => 'showcase-radio-manual',
                    'value' => 'c',
                    'label' => 'Disabled',
                    'disabled' => true,
                ],
            ]);
            ?>
        </div>

        <h3 class="mb-2 text-sm font-medium text-neutral-500">Gruppe (<code>radio-group.php</code>)</h3>
        <div class="mb-6">
            <?php get_template_part('template-parts/base/radio/radio-group', null, [
                'config' => [
                    'value' => 'medium',
                    'items' => [
                        ['value' => 'small', 'label' => 'Small'],
                        ['value' => 'medium', 'label' => 'Medium'],
                        ['value' => 'large', 'label' => 'Large'],
                        ['value' => 'xlarge', 'label' => 'XL (disabled)', 'disabled' => true],
                    ],
                ],
            ]); ?>
        </div>

        <h3 class="mb-2 text-sm font-medium text-neutral-500">Gruppe (horizontal, disabled, invalid)</h3>
        <div class="flex flex-col gap-6">
            <?php get_template_part('template-parts/base/radio/radio-group', null, [
                'config' => [
                    'orientation' => 'horizontal',
                    'items' => [
                        ['value' => 'yes', 'label' => 'Ja'],
                        ['value' => 'no', 'label' => 'Nein'],
                    ],
                ],
            ]); ?>
            <?php get_template_part('template-parts/base/radio/radio-group', null, [
                'config' => [
                    'disabled' => true,
                    'items' => [
                        ['value' => 'yes', 'label' => 'Ja'],
                        ['value' => 'no', 'label' => 'Nein'],
                    ],
                ],
            ]); ?>
            <?php get_template_part('template-parts/base/radio/radio-group', null, [
                'config' => [
                    'aria_invalid' => true,
                    'aria_label' => 'Invalid Gruppe',
                    'items' => [
                        ['value' => 'yes', 'label' => 'Ja'],
                        ['value' => 'no', 'label' => 'Nein'],
                    ],
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Switch (<code>template-parts/base/switch.php</code>)
        </h2>
        <div class="flex flex-col gap-3">
            <?php
            get_template_part('template-parts/base/switch', null, [
                'config' => ['label' => 'Unchecked'],
            ]);
            get_template_part('template-parts/base/switch', null, [
                'config' => ['label' => 'Checked', 'checked' => true],
            ]);
            get_template_part('template-parts/base/switch', null, [
                'config' => ['label' => 'Disabled', 'disabled' => true],
            ]);
            get_template_part('template-parts/base/switch', null, [
                'config' => [
                    'label' => 'Disabled + Checked',
                    'disabled' => true,
                    'checked' => true,
                ],
            ]);
            get_template_part('template-parts/base/switch', null, [
                'config' => ['label' => 'Small (size: sm)', 'size' => 'sm'],
            ]);
            get_template_part('template-parts/base/switch', null, [
                'config' => ['label' => 'Invalid', 'aria_invalid' => true],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Native Select (<code>template-parts/base/native-select.php</code>)
        </h2>
        <div class="flex max-w-sm flex-col gap-3">
            <?php
            get_template_part('template-parts/base/native-select', null, [
                'config' => [
                    'label' => 'Frucht',
                    'placeholder' => 'Bitte wählen…',
                    'options' => $select_options,
                ],
            ]);
            get_template_part('template-parts/base/native-select', null, [
                'config' => [
                    'label' => 'Vorausgewählt',
                    'value' => 'banana',
                    'options' => $select_options,
                ],
            ]);
            get_template_part('template-parts/base/native-select', null, [
                'config' => [
                    'label' => 'Mehrfachauswahl',
                    'multiple' => true,
                    'value' => ['apple', 'lemon'],
                    'options' => $select_options,
                ],
            ]);
            get_template_part('template-parts/base/native-select', null, [
                'config' => [
                    'label' => 'Disabled',
                    'disabled' => true,
                    'options' => $select_options,
                ],
            ]);
            get_template_part('template-parts/base/native-select', null, [
                'config' => [
                    'label' => 'Invalid',
                    'aria_invalid' => true,
                    'options' => $select_options,
                ],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Select — JS-enhanced (<code>template-parts/base/select.php</code>)
        </h2>
        <div class="flex max-w-sm flex-col gap-3">
            <?php
            get_template_part('template-parts/base/select', null, [
                'config' => [
                    'label' => 'Frucht',
                    'placeholder' => 'Bitte wählen…',
                    'options' => $select_options,
                ],
            ]);
            get_template_part('template-parts/base/select', null, [
                'config' => [
                    'label' => 'Vorausgewählt',
                    'value' => 'banana',
                    'options' => $select_options,
                ],
            ]);
            get_template_part('template-parts/base/select', null, [
                'config' => [
                    'label' => 'Small (size: sm)',
                    'size' => 'sm',
                    'value' => 'apple',
                    'options' => $select_options,
                ],
            ]);
            get_template_part('template-parts/base/select', null, [
                'config' => [
                    'label' => 'Disabled',
                    'disabled' => true,
                    'options' => $select_options,
                ],
            ]);
            get_template_part('template-parts/base/select', null, [
                'config' => [
                    'label' => 'Invalid',
                    'aria_invalid' => true,
                    'options' => $select_options,
                ],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Combobox (<code>template-parts/base/combobox.php</code>)
        </h2>
        <div class="flex max-w-sm flex-col gap-3">
            <?php
            get_template_part('template-parts/base/combobox', null, [
                'config' => [
                    'label' => 'Zutat',
                    'placeholder' => 'Suchen…',
                    'options' => $combobox_options,
                ],
            ]);
            get_template_part('template-parts/base/combobox', null, [
                'config' => [
                    'label' => 'Vorausgewählt',
                    'value' => 'carrot',
                    'options' => $combobox_options,
                ],
            ]);
            get_template_part('template-parts/base/combobox', null, [
                'config' => [
                    'label' => 'Disabled',
                    'disabled' => true,
                    'options' => $combobox_options,
                ],
            ]);
            get_template_part('template-parts/base/combobox', null, [
                'config' => [
                    'label' => 'Invalid',
                    'aria_invalid' => true,
                    'options' => $combobox_options,
                ],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Slider (<code>template-parts/base/slider.php</code>)
        </h2>
        <div class="flex max-w-sm flex-col gap-6">
            <?php
            get_template_part('template-parts/base/slider', null, [
                'config' => ['label' => 'Default (0–100)', 'value' => 33],
            ]);
            get_template_part('template-parts/base/slider', null, [
                'config' => [
                    'label' => 'Min/Max/Step',
                    'min' => 0,
                    'max' => 10,
                    'step' => 1,
                    'value' => 5,
                ],
            ]);
            get_template_part('template-parts/base/slider', null, [
                'config' => ['label' => 'Disabled', 'disabled' => true, 'value' => 50],
            ]);
            get_template_part('template-parts/base/slider', null, [
                'config' => ['label' => 'Invalid', 'aria_invalid' => true, 'value' => 50],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Date Picker (<code>template-parts/base/date-picker.php</code>)
        </h2>
        <div class="flex max-w-sm flex-col gap-3">
            <?php
            get_template_part('template-parts/base/date-picker', null, [
                'config' => ['aria_label' => 'Datum wählen'],
            ]);
            get_template_part('template-parts/base/date-picker', null, [
                'config' => [
                    'aria_label' => 'Vorausgewähltes Datum',
                    'selected' => gmdate('Y-m-d'),
                ],
            ]);
            get_template_part('template-parts/base/date-picker', null, [
                'config' => ['aria_label' => 'Mehrfachauswahl', 'mode' => 'multiple'],
            ]);
            get_template_part('template-parts/base/date-picker', null, [
                'config' => ['aria_label' => 'Disabled', 'disabled' => true],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Input Group (<code>template-parts/base/input-group/input-group.php</code>)
        </h2>
        <div class="flex max-w-sm flex-col gap-3">
            <?php
            // Icon-Addon vor dem Input.
            ob_start();
            get_template_part('template-parts/base/input-group/input-group-addon', null, [
                'config' => [
                    'content' => hengegroup_theme_render_icon([
                        'name' => 'search',
                        'set' => 'lucide',
                    ]),
                ],
            ]);
            get_template_part('template-parts/base/input', null, [
                'config' => [
                    'type' => 'search',
                    'placeholder' => 'Suchen…',
                    'data_slot' => 'input-group-control',
                ],
            ]);
            $search_group = (string) ob_get_clean();
            get_template_part('template-parts/base/input-group/input-group', null, [
                'config' => ['content' => $search_group],
            ]);

            // Text-Addon nach dem Input.
            ob_start();
            get_template_part('template-parts/base/input', null, [
                'config' => [
                    'type' => 'text',
                    'placeholder' => 'meinname',
                    'data_slot' => 'input-group-control',
                ],
            ]);
            get_template_part('template-parts/base/input-group/input-group-addon', null, [
                'config' => [
                    'align' => 'inline-end',
                    'content' => '<span data-slot="input-group-text">@firma.de</span>',
                ],
            ]);
            $email_group = (string) ob_get_clean();
            get_template_part('template-parts/base/input-group/input-group', null, [
                'config' => ['content' => $email_group],
            ]);

            // Button-Addon (Passwort mit Sichtbarkeits-Toggle-Button, rein statisch/unverkabelt).
            ob_start();
            get_template_part('template-parts/base/input', null, [
                'config' => [
                    'type' => 'password',
                    'placeholder' => 'Passwort',
                    'data_slot' => 'input-group-control',
                ],
            ]);
            ob_start();
            get_template_part('template-parts/base/button', null, [
                'config' => [
                    'variant' => 'ghost',
                    'size' => 'icon-sm',
                    'icon' => ['name' => 'eye', 'set' => 'lucide'],
                    'aria_label' => 'Passwort anzeigen',
                ],
            ]);
            $button_addon_content = (string) ob_get_clean();
            get_template_part('template-parts/base/input-group/input-group-addon', null, [
                'config' => ['align' => 'inline-end', 'content' => $button_addon_content],
            ]);
            $password_group = (string) ob_get_clean();
            get_template_part('template-parts/base/input-group/input-group', null, [
                'config' => ['content' => $password_group],
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Field (<code>template-parts/base/field/field.php</code> — Label + Control +
            Description/Error)
        </h2>
        <div class="flex max-w-sm flex-col gap-6">
            <?php
            // Vollständige Komposition: Label + Input + Description, korrekt per
            // aria-describedby verdrahtet (siehe field.php Kopfkommentar).
            $email_id = 'hengegroup-theme-showcase-field-email';
            ob_start();
            get_template_part('template-parts/base/field/field-label', null, [
                'config' => ['text' => 'E-Mail', 'for' => $email_id],
            ]);
            get_template_part('template-parts/base/input', null, [
                'config' => [
                    'id' => $email_id,
                    'type' => 'email',
                    'attributes' => [
                        'aria-describedby' => hengegroup_theme_field_describedby($email_id),
                    ],
                ],
            ]);
            get_template_part('template-parts/base/field/field-description', null, [
                'config' => [
                    'for' => $email_id,
                    'text' => 'Wir geben deine E-Mail niemals weiter.',
                ],
            ]);
            $email_field_content = (string) ob_get_clean();
            get_template_part('template-parts/base/field/field', null, [
                'config' => ['content' => $email_field_content],
            ]);

            // Mit Error statt Description, `invalid` gesetzt.
            $username_id = 'hengegroup-theme-showcase-field-username';
            ob_start();
            get_template_part('template-parts/base/field/field-label', null, [
                'config' => ['text' => 'Benutzername', 'for' => $username_id],
            ]);
            get_template_part('template-parts/base/input', null, [
                'config' => [
                    'id' => $username_id,
                    'aria_invalid' => true,
                    'attributes' => [
                        'aria-describedby' => hengegroup_theme_field_describedby(
                            $username_id,
                            false,
                            true,
                        ),
                    ],
                ],
            ]);
            get_template_part('template-parts/base/field/field-error', null, [
                'config' => [
                    'for' => $username_id,
                    'text' => 'Dieser Benutzername ist bereits vergeben.',
                ],
            ]);
            $username_field_content = (string) ob_get_clean();
            get_template_part('template-parts/base/field/field', null, [
                'config' => ['content' => $username_field_content, 'invalid' => true],
            ]);

            // Horizontale Ausrichtung (z. B. Checkbox neben ihrem Label-Text).
            $newsletter_id = 'hengegroup-theme-showcase-field-newsletter';
            ob_start();
            get_template_part('template-parts/base/field/field-label', null, [
                'config' => ['text' => 'Newsletter abonnieren', 'for' => $newsletter_id],
            ]);
            get_template_part('template-parts/base/checkbox', null, [
                'config' => ['id' => $newsletter_id],
            ]);
            $checkbox_field_content = (string) ob_get_clean();
            get_template_part('template-parts/base/field/field', null, [
                'config' => ['content' => $checkbox_field_content, 'orientation' => 'horizontal'],
            ]);
            ?>
        </div>
    </section>
</div>

<?php get_footer(); ?>
