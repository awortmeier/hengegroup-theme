<?php

declare(strict_types=1);

// Huelle fuer Formulare, die als Eingang landen (inc/setup/theme-requests.php): Status-Banner,
// Spam-Schutz, Datenschutz-Einwilligung, Pflichtfeld-Hinweis und Absenden. request-form.js
// fokussiert das Banner nach dem Redirect und verhindert doppeltes Absenden.
//
// Config:
//   id                string   Formular-ID, Praefix der Feld-IDs (Pflicht)
//   action            string   admin-post-Action (Pflicht)
//   nonce_action      string   (action)
//   nonce_name        string   (Pflicht)
//   state             array    hengegroup_theme_read_form_state()
//   content           string   vorab gerenderte Felder
//   hidden            array    name => value
//   consent_purpose   string   "zur Bearbeitung meiner Anfrage" (default)
//   tone              string   light (default) | dark
//   submit_text       string   "Senden" (default)
//   multipart         bool     enctype multipart/form-data
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];
$form_id = trim((string) ($config['id'] ?? ''));
$action = trim((string) ($config['action'] ?? ''));
$nonce_name = trim((string) ($config['nonce_name'] ?? ''));

if ($form_id === '' || $action === '' || $nonce_name === '') {
    return;
}

$nonce_action = trim((string) ($config['nonce_action'] ?? $action));
$state = is_array($config['state'] ?? null) ? $config['state'] : [];
$status = (string) ($state['status'] ?? '');
$message = (string) ($state['message'] ?? '');
$values = is_array($state['values'] ?? null) ? $state['values'] : [];
$errors = is_array($state['errors'] ?? null) ? $state['errors'] : [];
$content = (string) ($config['content'] ?? '');
$hidden = is_array($config['hidden'] ?? null) ? $config['hidden'] : [];
$consent_purpose = trim((string) ($config['consent_purpose'] ?? ''));
$consent_purpose =
    $consent_purpose !== ''
        ? $consent_purpose
        : __('zur Bearbeitung meiner Anfrage', 'hengegroup-theme');
$tone = in_array($config['tone'] ?? '', ['light', 'dark'], true) ? $config['tone'] : 'light';
$submit_text = trim((string) ($config['submit_text'] ?? ''));
$submit_text = $submit_text !== '' ? $submit_text : __('Senden', 'hengegroup-theme');
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$is_dark = $tone === 'dark';
$text_class = $is_dark ? 'text-grey-light' : 'text-grey-dark';
$muted_class = $is_dark ? 'text-grey-light/70' : 'text-grey-dark/70';
$privacy_id = $form_id . '-privacy';
$privacy_error = (string) ($errors['privacy'] ?? '');

if ($status === 'gesendet') {
    printf(
        '<div class="rounded-2xl bg-henge-green px-6 py-5 text-base text-henge-green-foreground" role="status" tabindex="-1" data-slot="request-form-status">%s</div>',
        esc_html($message),
    );

    return;
}

$form_attributes = hengegroup_theme_merge_data_attributes($attributes, $data_attributes);
$form_attributes['id'] = $form_id;
$form_attributes['class'] = trim('flex flex-col gap-5 ' . $class_name);
$form_attributes['action'] = admin_url('admin-post.php');
$form_attributes['method'] = 'post';
$form_attributes['enctype'] = !empty($config['multipart']) ? 'multipart/form-data' : null;
$form_attributes['data-slot'] = 'request-form';
$form_attributes['data-tone'] = $tone;

$privacy_url = get_privacy_policy_url();
$privacy_label =
    $privacy_url !== ''
        ? sprintf(
            /* translators: 1: link to the privacy policy, 2: purpose, e.g. "zur Bearbeitung meiner Anfrage". */
            esc_html__(
                'Ich habe die %1$s gelesen und bin mit der Verarbeitung meiner Angaben %2$s einverstanden. *',
                'hengegroup-theme',
            ),
            '<a class="' .
                esc_attr($text_class) .
                ' underline underline-offset-4" href="' .
                esc_url($privacy_url) .
                '" target="_blank" rel="noopener">' .
                esc_html__('Datenschutzhinweise', 'hengegroup-theme') .
                '</a>',
            esc_html($consent_purpose),
        )
        : sprintf(
            /* translators: %s: purpose, e.g. "zur Bearbeitung meiner Anfrage". */
            esc_html__(
                'Ich bin mit der Verarbeitung meiner Angaben %s einverstanden. *',
                'hengegroup-theme',
            ),
            esc_html($consent_purpose),
        );

ob_start();
get_template_part('template-parts/base/checkbox', null, [
    'config' => [
        'id' => $privacy_id,
        'name' => 'privacy',
        'value' => '1',
        'required' => true,
        'checked' => ($values['privacy'] ?? '') === '1',
        'aria_invalid' => $privacy_error !== '',
        'class' => 'mt-0.5',
        'attributes' =>
            $privacy_error !== ''
                ? ['aria-describedby' => hengegroup_theme_field_error_id($privacy_id)]
                : [],
    ],
]);
// Eigenes <label> statt label.php, weil das den Link escapen wuerde.
$privacy_field = hengegroup_theme_render_form_field([
    'control_id' => $privacy_id,
    'control' => sprintf(
        '<div class="flex items-start gap-3">%1$s<label for="%2$s" class="%3$s">%4$s</label></div>',
        (string) ob_get_clean(),
        esc_attr($privacy_id),
        esc_attr('text-sm leading-normal ' . $text_class),
        wp_kses_post($privacy_label),
    ),
    'error' => $privacy_error,
    'error_class' => $is_dark ? '!text-red-300' : '',
]);
?>
<form<?php printf(
    '%s',
    hengegroup_theme_render_attributes($form_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
); ?>>
  <?php if ($message !== ''): ?>
    <div class="rounded-2xl border border-destructive/40 bg-red-50 px-6 py-4 text-base text-grey-dark" role="alert" tabindex="-1" data-slot="request-form-status">
      <p class="font-semibold"><?php echo esc_html($message); ?></p>
      <?php if ($errors !== []): ?>
        <ul class="mt-2 list-disc pl-5 text-sm">
          <?php foreach ($errors as $error): ?>
            <li><?php echo esc_html((string) $error); ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>">
  <?php foreach ($hidden as $hidden_name => $hidden_value): ?>
    <input type="hidden" name="<?php echo esc_attr(
        (string) $hidden_name,
    ); ?>" value="<?php echo esc_attr((string) $hidden_value); ?>">
  <?php endforeach; ?>
  <input type="hidden" name="started" value="<?php echo esc_attr((string) time()); ?>">
  <?php wp_nonce_field($nonce_action, $nonce_name, false); ?>
  <div class="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
    <label for="<?php echo esc_attr($form_id . '-website'); ?>">Website</label>
    <input type="text" id="<?php echo esc_attr(
        $form_id . '-website',
    ); ?>" name="website" tabindex="-1" autocomplete="off">
  </div>

  <?php printf(
      '%s%s',
      $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered by base components.
      $privacy_field, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered by base components.
  ); ?>

  <div class="flex flex-col-reverse items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
    <p class="<?php echo esc_attr('text-sm ' . $muted_class); ?>"><?php esc_html_e(
    '* Pflichtfelder',
    'hengegroup-theme',
); ?></p>
    <?php get_template_part('template-parts/base/button', null, [
        'config' => [
            'text' => $submit_text,
            'type' => 'submit',
            'variant' => 'henge-green',
            'size' => 'lg',
            'data_attributes' => ['request-form-submit' => 'true'],
        ],
    ]); ?>
  </div>
</form>
