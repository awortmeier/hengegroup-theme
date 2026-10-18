<?php

declare(strict_types=1);

// Bewerbungsformular einer Stelle, verarbeitet von inc/setup/theme-careers-application.php.
// Im HTML-Teil keine PHP-Bloecke, die nur aus einem Kommentar bestehen: @prettier/plugin-php
// vervielfacht sie. Datei-Inputs liegen sr-only in einem als Button gestalteten <label>;
// job-application.js zeigt Dateinamen an und prueft die Groesse vorab.
//
// Args:
//   job   array   hengegroup_theme_get_job_data() (Pflicht)

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

// Leerer `value` wuerde den Platzhalter abwaehlen und die erste Option vorbelegen.
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

ob_start();
?>
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
      <?php
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

<?php get_template_part('template-parts/components/request-form', null, [
    'config' => [
        'id' => $form_id,
        'action' => HENGEGROUP_THEME_APPLICATION_ACTION,
        'nonce_action' => HENGEGROUP_THEME_APPLICATION_ACTION . '_' . (int) $job['id'],
        'nonce_name' => '_hengegroup_theme_application_nonce',
        'state' => $state,
        'content' => (string) ob_get_clean(),
        'hidden' => ['job_id' => (string) $job['id']],
        'consent_purpose' => __('zur Bearbeitung meiner Bewerbung', 'hengegroup-theme'),
        'submit_text' => __('Bewerbung senden', 'hengegroup-theme'),
        'multipart' => true,
        'accent' => (string) ($job['company']['variant'] ?? ''),
        'data_attributes' => ['form' => 'application'],
    ],
]); ?>
