<?php

declare(strict_types=1);

// Bewerbungsformular einer Stellenanzeige (Design "Stellenangebot einzelseite", Abschnitt
// "Bewerbungsformular") -- exklusiv fuer die angezeigte Stelle (verstecktes `job_id`, keine
// Stellen-Auswahl wie im Design-Entwurf), verarbeitet von inc/setup/theme-careers-application.php
// (siehe dort fuer Ablauf, Spam-Schutz und Datenschutz-Entscheidung).
//
// Komposition aus template-parts/base/: label.php, input.php, native-select.php, textarea.php,
// checkbox.php, button.php -- die Basis-Felder sind weiss und stehen hier wie im Design auf dunklem
// Grund; nur die Beschriftungen bekommen helle Schrift. Fehler stehen je Feld darunter
// (`aria-describedby` + `aria-invalid`, IDs ueber hengegroup_theme_field_error_id()), zusaetzlich
// eine Zusammenfassung oben mit `role="alert"`.
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

$label = static function (string $for, string $text, bool $required = false): void {
    get_template_part('template-parts/base/label', null, [
        'config' => [
            'for' => $for,
            'text' => $required ? $text . ' *' : $text,
            'class' => 'text-sm font-semibold text-grey-light',
        ],
    ]);
};

$error = static function (string $control_id, string $field) use ($errors): void {
    if (!isset($errors[$field])) {
        return;
    }

    printf(
        '<p id="%1$s" class="text-sm text-red-300">%2$s</p>',
        esc_attr(hengegroup_theme_field_error_id($control_id)),
        esc_html((string) $errors[$field]),
    );
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
) use ($errors): void {
    $field = rtrim($name, '[]');
    $describedby = isset($errors[$field])
        ? ' aria-describedby="' . esc_attr(hengegroup_theme_field_error_id($control_id)) . '"'
        : '';

    printf(
        '<div class="flex flex-col gap-2"><span id="%1$s-label" class="text-sm font-semibold text-grey-light">%2$s</span><div class="flex flex-wrap items-center gap-3"><label class="inline-flex h-10 cursor-pointer items-center rounded-full bg-grey-light px-5 text-sm font-semibold whitespace-nowrap text-grey-dark transition-colors hover:bg-white has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-ring/50">%3$s<input class="sr-only" type="file" id="%1$s" name="%4$s" accept=".pdf,.png,.jpg,.jpeg,application/pdf,image/png,image/jpeg"%5$s aria-labelledby="%1$s-label"%6$s%7$s data-max-bytes="%8$d" data-application-file></label><span class="text-sm text-grey-light/70" data-application-file-name="%1$s">%9$s</span></div></div>',
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
            '<a class="text-grey-light underline underline-offset-4" href="' .
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
      <div class="rounded-2xl border border-red-300/60 bg-red-950/40 px-6 py-4 text-base text-grey-light" role="alert" tabindex="-1" data-application-status>
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
      <div class="flex flex-col gap-2">
        <?php $label($form_id . '-name', __('Name, Vorname', 'hengegroup-theme'), true); ?>
        <?php get_template_part('template-parts/base/input', null, [
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
        ]); ?>
        <?php $error($form_id . '-name', 'name'); ?>
      </div>
      <div class="flex flex-col gap-2">
        <?php $label($form_id . '-age', __('Alter', 'hengegroup-theme'), true); ?>
        <?php get_template_part('template-parts/base/input', null, [
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
        ]); ?>
        <?php $error($form_id . '-age', 'age'); ?>
      </div>
    </div>

    <div class="flex flex-col gap-2">
      <?php $label($form_id . '-experience', __('Berufserfahrung', 'hengegroup-theme')); ?>
      <?php get_template_part('template-parts/base/native-select', null, [
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
      ]); ?>
      <?php $error($form_id . '-experience', 'experience'); ?>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
      <div class="flex flex-col gap-2">
        <?php $label($form_id . '-email', __('E-Mail', 'hengegroup-theme'), true); ?>
        <?php get_template_part('template-parts/base/input', null, [
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
        ]); ?>
        <?php $error($form_id . '-email', 'email'); ?>
      </div>
      <div class="flex flex-col gap-2">
        <?php $label($form_id . '-phone', __('Telefon', 'hengegroup-theme')); ?>
        <?php get_template_part('template-parts/base/input', null, [
            'config' => [
                'id' => $form_id . '-phone',
                'type' => 'tel',
                'name' => 'phone',
                'value' => $value('phone'),
                'autocomplete' => 'tel',
                'aria_invalid' => isset($errors['phone']),
                'attributes' => $control_attributes($form_id . '-phone', 'phone'),
            ],
        ]); ?>
        <?php $error($form_id . '-phone', 'phone'); ?>
      </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
      <div class="flex flex-col gap-2">
        <?php $label(
            $form_id . '-contact-method',
            __('Wie dürfen wir dich kontaktieren?', 'hengegroup-theme'),
        ); ?>
        <?php get_template_part('template-parts/base/native-select', null, [
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
        ]); ?>
        <?php $error($form_id . '-contact-method', 'contact_method'); ?>
      </div>
      <div class="flex flex-col gap-2">
        <?php $label(
            $form_id . '-contact-time',
            __('Wann dürfen wir dich kontaktieren?', 'hengegroup-theme'),
        ); ?>
        <?php get_template_part('template-parts/base/native-select', null, [
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
        ]); ?>
        <?php $error($form_id . '-contact-time', 'contact_time'); ?>
      </div>
    </div>

    <div class="grid gap-5 pt-1 sm:grid-cols-2">
      <div class="flex flex-col gap-2">
        <?php $file_field($form_id . '-cv', 'cv', $cv_label, false); ?>
        <?php $error($form_id . '-cv', 'cv'); ?>
      </div>
      <div class="flex flex-col gap-2">
        <?php $file_field(
            $form_id . '-certificates',
            'certificates[]',
            $certificates_label,
            true,
        ); ?>
        <?php $error($form_id . '-certificates', 'certificates'); ?>
      </div>
    </div>

    <div class="flex flex-col gap-2">
      <?php $label(
          $form_id . '-message',
          __('Deine Nachricht / Fragen an uns', 'hengegroup-theme'),
      ); ?>
      <?php get_template_part('template-parts/base/textarea', null, [
          'config' => [
              'id' => $form_id . '-message',
              'name' => 'message',
              'value' => $value('message'),
              'rows' => 4,
              'maxlength' => '5000',
              'aria_invalid' => isset($errors['message']),
              'attributes' => $control_attributes($form_id . '-message', 'message'),
          ],
      ]); ?>
      <?php $error($form_id . '-message', 'message'); ?>
    </div>

    <div class="flex flex-col gap-2">
      <div class="flex items-start gap-3">
        <?php get_template_part('template-parts/base/checkbox', null, [
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
        ]); ?>
        <label for="<?php echo esc_attr(
            $form_id . '-privacy',
        ); ?>" class="text-sm leading-normal text-grey-light">
          <?php echo wp_kses_post($privacy_label); ?>
        </label>
      </div>
      <?php $error($form_id . '-privacy', 'privacy'); ?>
    </div>

    <div class="flex flex-col-reverse items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
      <p class="text-sm text-grey-light/70"><?php esc_html_e(
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
