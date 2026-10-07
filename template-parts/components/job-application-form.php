<?php

declare(strict_types=1);

// Bewerbungsformular einer Stellenanzeige (Design "Stellenangebot einzelseite", Abschnitt
// "Bewerbungsformular") -- exklusiv fuer die angezeigte Stelle (verstecktes `job_id`, keine
// Stellen-Auswahl wie im Design-Entwurf), verarbeitet von inc/setup/theme-careers-application.php
// (siehe dort fuer Ablauf, Spam-Schutz und Datenschutz-Entscheidung).
//
// Komposition aus template-parts/base/: field/field.php + field-label.php + field-error.php (je Feld
// ueber hengegroup_theme_render_form_field(), inc/template-parts/helpers.php), input.php,
// native-select.php, textarea.php, checkbox.php, button.php -- die Basis-Felder sind weiss und stehen auf dem hellgrauen Grund der
// Bewerbungs-Sektion (neutral-200, single-stellenangebote.php); Beschriftungen in grey-dark.
// Fehler stehen je Feld darunter (`aria-describedby` + `aria-invalid`, IDs ueber
// hengegroup_theme_field_error_id()), zusaetzlich eine Zusammenfassung oben mit `role="alert"`.
//
// Ergaenzt gegenueber dem Design: Pflicht-Checkbox fuer die Datenschutzhinweise (Bewerbungsdaten
// sind personenbezogen, Art. 13 DSGVO) und ein unsichtbares Honeypot-Feld gegen Spam (fuer Menschen
// unsichtbar, `aria-hidden` und per `tabindex="-1"` nicht per Tab erreichbar, Bots fuellen es aus).
//
// Hinweis Formatierung: im HTML-Teil unten keine PHP-Bloecke, die nur aus einem Kommentar bestehen
// -- @prettier/plugin-php verschiebt und vervielfacht Kommentare dann bei jedem Lauf.
//
// Datei-Felder: das native <input type="file"> liegt visuell versteckt (`sr-only`) in einem als
// Button gestalteten <label> (bleibt per Tastatur fokussierbar, Fokusring ueber
// `has-[:focus-visible]`); assets/js/components/job-application.js zeigt die gewaehlten Dateinamen
// an und prueft die 5-MB-Grenze schon vor dem Absenden.
//
// Supported args:
//   job   array   hengegroup_theme_get_job_data() der angezeigten Stelle (Pflicht)

if (!isset($args['job']) || !is_array($args['job'])) {
    return;
}

$job = $args['job'];
$state = hengegroup_theme_get_application_state();
$values = $state['values'];
$errors = $state['errors'];
$options = hengegroup_theme_get_job_application_options();
$form_id = 'hengegroup-theme-application-' . (int) $job['id'];
$value = static fn(string $field): string => (string) ($values[$field] ?? '');

// Gibt ein Feld aus hengegroup_theme_render_form_field() aus (field/field.php + field-label.php +
// field-error.php). Die Datenschutz-Checkbox bringt ein eigenes <label> mit statt field-label.php,
// weil ihr Text einen Link enthaelt und label.php reinen Text escaped.
$print_field = static function (array $field): void {
    printf(
        '%s',
        hengegroup_theme_render_form_field($field), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered by base components.
    );
};

$field = static function (
    string $control_id,
    string $field,
    string $label_text,
    string $control,
    bool $required = false,
) use ($errors, $print_field): void {
    $print_field([
        'control_id' => $control_id,
        'control' => $control,
        'label' => $required ? $label_text . ' *' : $label_text,
        'error' => (string) ($errors[$field] ?? ''),
        'label_class' => 'text-sm font-semibold text-grey-dark',
    ]);
};

$control_attributes = static function (string $control_id, string $field) use ($errors): array {
    return isset($errors[$field])
        ? ['aria-describedby' => hengegroup_theme_field_error_id($control_id)]
        : [];
};

$select_options = static function (array $choices): array {
    $result = [];

    foreach ($choices as $choice_value => $choice_label) {
        $result[] = ['value' => (string) $choice_value, 'text' => $choice_label];
    }

    return $result;
};

$file_field = static function (
    string $control_id,
    string $name,
    string $label_text,
    bool $multiple,
) use ($errors): string {
    $field = rtrim($name, '[]');
    $describedby = isset($errors[$field])
        ? ' aria-describedby="' . esc_attr(hengegroup_theme_field_error_id($control_id)) . '"'
        : '';

    return sprintf(
        '<div class="flex flex-col gap-2"><span id="%1$s-label" class="text-sm font-semibold text-grey-dark">%2$s</span><div class="flex flex-wrap items-center gap-3"><label class="inline-flex h-10 cursor-pointer items-center rounded-full bg-grey-light px-5 text-sm font-semibold whitespace-nowrap text-grey-dark transition-colors hover:bg-white has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-ring/50">%3$s<input class="sr-only" type="file" id="%1$s" name="%4$s" accept=".pdf,.png,.jpg,.jpeg,application/pdf,image/png,image/jpeg"%5$s aria-labelledby="%1$s-label"%6$s%7$s data-max-bytes="%8$d" data-application-file></label><span class="text-sm text-grey-dark/70" data-application-file-name="%1$s">%9$s</span></div></div>',
        esc_attr($control_id),
        esc_html($label_text),
        esc_html__('Datei auswählen', 'hengegroup-theme'),
        esc_attr($name),
        $multiple ? ' multiple' : '',
        $describedby, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        isset($errors[$field]) ? ' aria-invalid="true"' : '',
        absint(hengegroup_theme_get_application_max_file_bytes()),
        esc_html__('Keine ausgewählt', 'hengegroup-theme'),
    );
};

// Selects bekommen `value` nur, wenn wirklich etwas gewaehlt war -- ein leerer `value` wuerde den
// Platzhalter abwaehlen und der Browser die erste echte Option vorbelegen (native-select.php).
$select_value = static fn(string $field): array => $value($field) !== ''
    ? ['value' => $value($field)]
    : [];

$max_file_size = size_format(hengegroup_theme_get_application_max_file_bytes());

$cv_label = sprintf(
    /* translators: %s: maximum file size, e.g. "5 MB". */
    __('Lebenslauf (PDF, PNG, JPG) max. %s, optional', 'hengegroup-theme'),
    $max_file_size,
);

$certificates_label = sprintf(
    /* translators: 1: maximum file size, e.g. "5 MB", 2: maximum number of files. */
    __('Zeugnisse (PDF, PNG, JPG) je max. %1$s, bis zu %2$d Dateien, optional', 'hengegroup-theme'),
    $max_file_size,
    HENGEGROUP_THEME_APPLICATION_MAX_CERTIFICATES,
);

$privacy_url = get_privacy_policy_url();
$privacy_label =
    $privacy_url !== ''
        ? sprintf(
            /* translators: %s: link to the privacy policy. */
            esc_html__(
                'Ich habe die %s gelesen und bin mit der Verarbeitung meiner Angaben zur Bearbeitung meiner Bewerbung einverstanden. *',
                'hengegroup-theme',
            ),
            '<a class="text-grey-dark underline underline-offset-4" href="' .
                esc_url($privacy_url) .
                '" target="_blank" rel="noopener">' .
                esc_html__('Datenschutzhinweise', 'hengegroup-theme') .
                '</a>',
        )
        : esc_html__(
            'Ich bin mit der Verarbeitung meiner Angaben zur Bearbeitung meiner Bewerbung einverstanden. *',
            'hengegroup-theme',
        );
?>
<?php if ($state['status'] === 'gesendet'): ?>
  <div class="rounded-2xl bg-henge-green px-6 py-5 text-base text-henge-green-foreground" role="status" tabindex="-1" data-application-status>
    <?php echo esc_html($state['message']); ?>
  </div>
<?php endif; ?>
<?php if ($state['status'] !== 'gesendet'): ?>
  <form
    id="<?php echo esc_attr($form_id); ?>"
    class="flex flex-col gap-5"
    action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
    method="post"
    enctype="multipart/form-data"
    data-application-form
  >
    <?php if ($state['message'] !== ''): ?>
      <div class="rounded-2xl border border-destructive/40 bg-red-50 px-6 py-4 text-base text-grey-dark" role="alert" tabindex="-1" data-application-status>
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
        HENGEGROUP_THEME_APPLICATION_ACTION,
    ); ?>">
    <input type="hidden" name="job_id" value="<?php echo esc_attr((string) $job['id']); ?>">
    <input type="hidden" name="started" value="<?php echo esc_attr((string) time()); ?>">
    <?php wp_nonce_field(
        HENGEGROUP_THEME_APPLICATION_ACTION . '_' . (int) $job['id'],
        '_hengegroup_theme_application_nonce',
        false,
    ); ?>
    <div class="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
      <label for="<?php echo esc_attr($form_id . '-website'); ?>">Website</label>
      <input type="text" id="<?php echo esc_attr(
          $form_id . '-website',
      ); ?>" name="website" tabindex="-1" autocomplete="off">
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
      <?php
      ob_start();
      get_template_part('template-parts/base/input', null, [
          'config' => [
              'id' => $form_id . '-name',
              'name' => 'name',
              'value' => $value('name'),
              'autocomplete' => 'name',
              'required' => true,
              'maxlength' => '120',
              'aria_invalid' => isset($errors['name']),
              'attributes' => $control_attributes($form_id . '-name', 'name'),
          ],
      ]);
      $field(
          $form_id . '-name',
          'name',
          __('Name, Vorname', 'hengegroup-theme'),
          (string) ob_get_clean(),
          true,
      );
      ?>
      <?php
      ob_start();
      get_template_part('template-parts/base/input', null, [
          'config' => [
              'id' => $form_id . '-age',
              'type' => 'number',
              'name' => 'age',
              'value' => $value('age'),
              'min' => '14',
              'max' => '99',
              'required' => true,
              'aria_invalid' => isset($errors['age']),
              'attributes' => $control_attributes($form_id . '-age', 'age'),
          ],
      ]);
      $field(
          $form_id . '-age',
          'age',
          __('Alter', 'hengegroup-theme'),
          (string) ob_get_clean(),
          true,
      );
      ?>
    </div>

    <?php
    ob_start();
    get_template_part('template-parts/base/native-select', null, [
        'config' => array_merge(
            [
                'id' => $form_id . '-experience',
                'name' => 'experience',
                'placeholder' => __('Bitte Berufserfahrung auswählen', 'hengegroup-theme'),
                'options' => $select_options($options['experience']),
                'aria_invalid' => isset($errors['experience']),
                'attributes' => $control_attributes($form_id . '-experience', 'experience'),
            ],
            $select_value('experience'),
        ),
    ]);
    $field(
        $form_id . '-experience',
        'experience',
        __('Berufserfahrung', 'hengegroup-theme'),
        (string) ob_get_clean(),
    );
    ?>

    <div class="grid gap-5 sm:grid-cols-2">
      <?php
      ob_start();
      get_template_part('template-parts/base/input', null, [
          'config' => [
              'id' => $form_id . '-email',
              'type' => 'email',
              'name' => 'email',
              'value' => $value('email'),
              'autocomplete' => 'email',
              'required' => true,
              'aria_invalid' => isset($errors['email']),
              'attributes' => $control_attributes($form_id . '-email', 'email'),
          ],
      ]);
      $field(
          $form_id . '-email',
          'email',
          __('E-Mail', 'hengegroup-theme'),
          (string) ob_get_clean(),
          true,
      );
      ?>
      <?php
      ob_start();
      get_template_part('template-parts/base/input', null, [
          'config' => [
              'id' => $form_id . '-phone',
              'type' => 'tel',
              'name' => 'phone',
              'value' => $value('phone'),
              'autocomplete' => 'tel',
              'aria_invalid' => isset($errors['phone']),
              'attributes' => $control_attributes($form_id . '-phone', 'phone'),
          ],
      ]);
      $field(
          $form_id . '-phone',
          'phone',
          __('Telefon', 'hengegroup-theme'),
          (string) ob_get_clean(),
      );
      ?>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
      <?php
      ob_start();
      get_template_part('template-parts/base/native-select', null, [
          'config' => array_merge(
              [
                  'id' => $form_id . '-contact-method',
                  'name' => 'contact_method',
                  'placeholder' => __('Bitte wählen', 'hengegroup-theme'),
                  'options' => $select_options($options['contact_method']),
                  'aria_invalid' => isset($errors['contact_method']),
                  'attributes' => $control_attributes(
                      $form_id . '-contact-method',
                      'contact_method',
                  ),
              ],
              $select_value('contact_method'),
          ),
      ]);
      $field(
          $form_id . '-contact-method',
          'contact_method',
          __('Wie dürfen wir dich kontaktieren?', 'hengegroup-theme'),
          (string) ob_get_clean(),
      );
      ?>
      <?php
      ob_start();
      get_template_part('template-parts/base/native-select', null, [
          'config' => array_merge(
              [
                  'id' => $form_id . '-contact-time',
                  'name' => 'contact_time',
                  'placeholder' => __('Bitte wählen', 'hengegroup-theme'),
                  'options' => $select_options($options['contact_time']),
                  'aria_invalid' => isset($errors['contact_time']),
                  'attributes' => $control_attributes($form_id . '-contact-time', 'contact_time'),
              ],
              $select_value('contact_time'),
          ),
      ]);
      $field(
          $form_id . '-contact-time',
          'contact_time',
          __('Wann dürfen wir dich kontaktieren?', 'hengegroup-theme'),
          (string) ob_get_clean(),
      );
      ?>
    </div>

    <div class="grid gap-5 pt-1 sm:grid-cols-2">
      <?php // Datei-Felder bringen ihr eigenes Label mit (siehe $file_field oben) -- daher ohne `label`.


      $field($form_id . '-cv', 'cv', '', $file_field($form_id . '-cv', 'cv', $cv_label, false));
      $field(
          $form_id . '-certificates',
          'certificates',
          '',
          $file_field($form_id . '-certificates', 'certificates[]', $certificates_label, true),
      );
      ?>
    </div>

    <?php
    ob_start();
    get_template_part('template-parts/base/textarea', null, [
        'config' => [
            'id' => $form_id . '-message',
            'name' => 'message',
            'value' => $value('message'),
            'rows' => 4,
            'maxlength' => '5000',
            'aria_invalid' => isset($errors['message']),
            'attributes' => $control_attributes($form_id . '-message', 'message'),
        ],
    ]);
    $field(
        $form_id . '-message',
        'message',
        __('Deine Nachricht / Fragen an uns', 'hengegroup-theme'),
        (string) ob_get_clean(),
    );
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
            'attributes' => $control_attributes($form_id . '-privacy', 'privacy'),
        ],
    ]);
    $field(
        $form_id . '-privacy',
        'privacy',
        '',
        sprintf(
            '<div class="flex items-start gap-3">%1$s<label for="%2$s" class="text-sm leading-normal text-grey-dark">%3$s</label></div>',
            (string) ob_get_clean(),
            esc_attr($form_id . '-privacy'),
            wp_kses_post($privacy_label),
        ),
    );
    ?>

    <div class="flex flex-col-reverse items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
      <p class="text-sm text-grey-dark/70"><?php esc_html_e(
          '* Pflichtfelder',
          'hengegroup-theme',
      ); ?></p>
      <?php get_template_part('template-parts/base/button', null, [
          'config' => [
              'text' => __('Bewerbung senden', 'hengegroup-theme'),
              'type' => 'submit',
              'variant' => 'henge-green',
              'size' => 'lg',
              'data_attributes' => ['application-submit' => 'true'],
          ],
      ]); ?>
    </div>
  </form>
<?php endif; ?>
