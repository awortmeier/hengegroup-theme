<?php

declare(strict_types=1);

// Produkt-Kontaktformular (Designs "Produktuebersicht", Abschnitt "Kontakt", und
// "Produktdetailseite", Abschnitt "Ihr Ansprechpartner im Vertrieb"), verarbeitet von
// inc/setup/theme-product-inquiries.php -- Eintrag im Menuepunkt "Produktanfragen", keine E-Mail.
//
// Gleiche Bauweise wie template-parts/components/job-application-form.php (Basis-Felder aus
// template-parts/base/, je Feld field/field.php + field-label.php + field-error.php ueber
// hengegroup_theme_render_form_field(), Fehler je Feld mit `aria-describedby`, Zusammenfassung oben mit
// `role="alert"`, Honeypot, Pflicht-Checkbox fuer die Datenschutzhinweise als Ergaenzung zum
// Design). Kein eigenes Skript: reines HTML-Formular mit nativer Pflichtfeld-Pruefung.
//
// Supported args:
//   product_id      int      Produkt, zu dem angefragt wird (0 = allgemeine Anfrage)
//   with_location   bool     PLZ/Ort-Felder (Pflicht) anzeigen -- Produktuebersicht ja, Detailseite nein
//   tone            string   light | dark -- Beschriftungsfarbe passend zum Sektionshintergrund
//   message_label   string   Beschriftung des Nachrichtenfelds (default "Nachricht")

$product_id = (int) ($args['product_id'] ?? 0);
$with_location = !empty($args['with_location']);
$is_dark = ($args['tone'] ?? 'light') === 'dark';
$message_label = trim((string) ($args['message_label'] ?? ''));
$message_label = $message_label !== '' ? $message_label : __('Nachricht', 'hengegroup-theme');

$state = hengegroup_theme_get_inquiry_state();
$values = $state['values'];
$errors = $state['errors'];
$form_id = wp_unique_id('hengegroup-theme-inquiry-');
$value = static fn(string $field): string => (string) ($values[$field] ?? '');
$text_class = $is_dark ? 'text-grey-light' : 'text-grey-dark';
$muted_class = $is_dark ? 'text-grey-light/70' : 'text-grey-dark/70';
$link_class = $is_dark ? 'text-grey-light' : 'text-grey-dark';

$label_class = 'text-sm font-semibold ' . $text_class;
$error_class = $is_dark ? '!text-red-300' : '';

// Gibt ein Feld aus hengegroup_theme_render_form_field() aus (field/field.php + field-label.php +
// field-error.php). Die Datenschutz-Checkbox bringt ein eigenes <label> mit statt field-label.php,
// weil ihr Text einen Link enthaelt und label.php reinen Text escaped.
$print_field = static function (array $field): void {
    printf(
        '%s',
        hengegroup_theme_render_form_field($field), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered by base components.
    );
};

$input = static function (
    string $field,
    string $label_text,
    string $type = 'text',
    bool $required = false,
    string $autocomplete = '',
) use ($form_id, $value, $errors, $label_class, $error_class, $print_field): void {
    $control_id = $form_id . '-' . $field;

    ob_start();
    get_template_part('template-parts/base/input', null, [
        'config' => [
            'id' => $control_id,
            'type' => $type,
            'name' => $field,
            'value' => $value($field),
            'autocomplete' => $autocomplete,
            'required' => $required,
            'maxlength' => '200',
            'aria_invalid' => isset($errors[$field]),
            'attributes' => isset($errors[$field])
                ? ['aria-describedby' => hengegroup_theme_field_error_id($control_id)]
                : [],
        ],
    ]);

    $print_field([
        'control_id' => $control_id,
        'control' => (string) ob_get_clean(),
        'label' => $required ? $label_text . '*' : $label_text,
        'error' => (string) ($errors[$field] ?? ''),
        'label_class' => $label_class,
        'error_class' => $error_class,
    ]);
};

// Feldzeilen: [Feld, Label, Typ, Pflicht, autocomplete] -- als Daten statt einzelner Aufrufe im
// HTML-Teil (@prettier/plugin-php formatiert lange Einzelaufrufe dort nicht stabil).
$field_rows = [
    [
        ['company', __('Firma', 'hengegroup-theme'), 'text', true, 'organization'],
        ['name', __('Name, Vorname', 'hengegroup-theme'), 'text', true, 'name'],
    ],
];

if ($with_location) {
    $field_rows[] = [
        ['postal_code', __('Postleitzahl', 'hengegroup-theme'), 'text', true, 'postal-code'],
        ['city', __('Ort', 'hengegroup-theme'), 'text', true, 'address-level2'],
    ];
}

$field_rows[] = [
    ['email', __('E-Mail-Adresse', 'hengegroup-theme'), 'email', true, 'email'],
    ['phone', __('Telefon', 'hengegroup-theme'), 'tel', false, 'tel'],
];

$privacy_url = get_privacy_policy_url();
$privacy_label =
    $privacy_url !== ''
        ? sprintf(
            /* translators: %s: link to the privacy policy. */
            esc_html__(
                'Ich habe die %s gelesen und bin mit der Verarbeitung meiner Angaben zur Bearbeitung meiner Anfrage einverstanden.*',
                'hengegroup-theme',
            ),
            '<a class="' .
                esc_attr($link_class) .
                ' underline underline-offset-4" href="' .
                esc_url($privacy_url) .
                '" target="_blank" rel="noopener">' .
                esc_html__('Datenschutzhinweise', 'hengegroup-theme') .
                '</a>',
        )
        : esc_html__(
            'Ich bin mit der Verarbeitung meiner Angaben zur Bearbeitung meiner Anfrage einverstanden.*',
            'hengegroup-theme',
        );
?>
<?php if ($state['status'] === 'gesendet'): ?>
  <div class="rounded-2xl bg-henge-green px-6 py-5 text-base text-henge-green-foreground" role="status" tabindex="-1">
    <?php echo esc_html($state['message']); ?>
  </div>
<?php endif; ?>
<?php if ($state['status'] !== 'gesendet'): ?>
  <form
    id="<?php echo esc_attr($form_id); ?>"
    class="flex flex-col gap-5"
    action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
    method="post"
    data-slot="inquiry-form"
  >
    <?php if ($state['message'] !== ''): ?>
      <div class="rounded-2xl border border-destructive/40 bg-red-50 px-6 py-4 text-base text-grey-dark" role="alert" tabindex="-1">
        <p class="font-semibold"><?php echo esc_html($state['message']); ?></p>
        <?php if ($errors !== []): ?>
          <ul class="mt-2 list-disc pl-5 text-sm">
            <?php foreach ($errors as $message): ?>
              <li><?php echo esc_html((string) $message); ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <input type="hidden" name="action" value="<?php echo esc_attr(
        HENGEGROUP_THEME_INQUIRY_ACTION,
    ); ?>">
    <input type="hidden" name="product_id" value="<?php echo esc_attr((string) $product_id); ?>">
    <input type="hidden" name="return_id" value="<?php echo esc_attr(
        (string) get_queried_object_id(),
    ); ?>">
    <input type="hidden" name="with_location" value="<?php echo $with_location ? '1' : ''; ?>">
    <input type="hidden" name="started" value="<?php echo esc_attr((string) time()); ?>">
    <?php wp_nonce_field(
        HENGEGROUP_THEME_INQUIRY_ACTION,
        '_hengegroup_theme_inquiry_nonce',
        false,
    ); ?>
    <div class="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
      <label for="<?php echo esc_attr($form_id . '-website'); ?>">Website</label>
      <input type="text" id="<?php echo esc_attr(
          $form_id . '-website',
      ); ?>" name="website" tabindex="-1" autocomplete="off">
    </div>

    <?php foreach ($field_rows as $field_row): ?>
      <div class="grid gap-5 sm:grid-cols-2">
        <?php foreach ($field_row as $field_config) {
            $input(...$field_config);
        } ?>
      </div>
    <?php endforeach; ?>

    <?php
    ob_start();
    get_template_part('template-parts/base/textarea', null, [
        'config' => [
            'id' => $form_id . '-message',
            'name' => 'message',
            'value' => $value('message'),
            'rows' => 4,
            'maxlength' => '5000',
        ],
    ]);
    $print_field([
        'control_id' => $form_id . '-message',
        'control' => (string) ob_get_clean(),
        'label' => $message_label,
        'label_class' => $label_class,
    ]);
    ?>

    <?php
    ob_start();
    get_template_part('template-parts/base/checkbox', null, [
        'config' => [
            'id' => $form_id . '-privacy',
            'name' => 'privacy',
            'value' => '1',
            'required' => true,
            'checked' => $value('privacy') === '1',
            'aria_invalid' => isset($errors['privacy']),
            'class' => 'mt-0.5',
            'attributes' => isset($errors['privacy'])
                ? ['aria-describedby' => hengegroup_theme_field_error_id($form_id . '-privacy')]
                : [],
        ],
    ]);
    $privacy_control = sprintf(
        '<div class="flex items-start gap-3">%1$s<label for="%2$s" class="%3$s">%4$s</label></div>',
        (string) ob_get_clean(),
        esc_attr($form_id . '-privacy'),
        esc_attr('text-sm leading-normal ' . $text_class),
        wp_kses_post($privacy_label),
    );
    $print_field([
        'control_id' => $form_id . '-privacy',
        'control' => $privacy_control,
        'error' => (string) ($errors['privacy'] ?? ''),
        'error_class' => $error_class,
    ]);
    ?>

    <div class="flex flex-col-reverse items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
      <p class="<?php echo esc_attr('text-sm ' . $muted_class); ?>"><?php esc_html_e(
    '* Pflichtfelder',
    'hengegroup-theme',
); ?></p>
      <?php get_template_part('template-parts/base/button', null, [
          'config' => [
              'text' => __('Senden', 'hengegroup-theme'),
              'type' => 'submit',
              'variant' => 'henge-green',
              'size' => 'lg',
          ],
      ]); ?>
    </div>
  </form>
<?php endif; ?>
