<?php

declare(strict_types=1);

// Verarbeitung des Bewerbungsformulars: POST an admin-post.php, Redirect zurueck (#bewerbung),
// Textfelder bei Fehlern 15 min per Transient. Speichert einen Eintrag "Bewerbung" (keine E-Mail);
// Dateien ausserhalb der Mediathek mit Zufallsnamen, Download nur mit Rechte- und Nonce-Pruefung.

const HENGEGROUP_THEME_APPLICATION_ACTION = 'hengegroup_theme_job_application';
const HENGEGROUP_THEME_APPLICATION_MAX_FILE_BYTES = 5 * 1024 * 1024;
const HENGEGROUP_THEME_APPLICATION_MAX_CERTIFICATES = 5;
const HENGEGROUP_THEME_APPLICATION_FILE_ACTION = 'hengegroup_theme_application_file';

/**
 * 5 MB, aber nie mehr als der Server annimmt (wp_max_upload_size()).
 */
function hengegroup_theme_get_application_max_file_bytes(): int
{
    return (int) min(HENGEGROUP_THEME_APPLICATION_MAX_FILE_BYTES, wp_max_upload_size());
}

function hengegroup_theme_get_application_mime_types(): array
{
    return [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg|jpeg' => 'image/jpeg',
    ];
}

function hengegroup_theme_get_application_status_messages(): array
{
    return [
        'gesendet' => __(
            'Vielen Dank! Deine Bewerbung ist bei uns eingegangen. Wir melden uns so bald wie möglich bei dir.',
            'hengegroup-theme',
        ),
        'fehler' => __('Bitte prüfe die markierten Angaben.', 'hengegroup-theme'),
        'abgelaufen' => __(
            'Das Formular war zu lange geöffnet. Bitte sende deine Bewerbung noch einmal ab.',
            'hengegroup-theme',
        ),
        'zugross' => __(
            'Deine Dateien sind zusammen zu groß. Bitte lade weniger oder kleinere Dateien hoch (z. B. als ein PDF) oder schick sie uns per E-Mail.',
            'hengegroup-theme',
        ),
        'versand' => __(
            'Deine Bewerbung konnte gerade nicht gespeichert werden. Bitte versuche es später erneut oder schreib uns direkt per E-Mail.',
            'hengegroup-theme',
        ),
    ];
}

/**
 * Sanitisierte Textfelder; die Nonce prueft der Aufrufer.
 */
function hengegroup_theme_get_application_input(): array
{
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- geprueft in hengegroup_theme_handle_job_application().
    $field = static fn(string $key): string => isset($_POST[$key])
        ? sanitize_text_field(wp_unslash((string) $_POST[$key]))
        : '';

    $values = [
        'name' => $field('name'),
        'age' => $field('age'),
        'email' => sanitize_email($field('email')),
        'phone' => $field('phone'),
        'experience' => $field('experience'),
        'contact_method' => $field('contact_method'),
        'contact_time' => $field('contact_time'),
        'message' => isset($_POST['message'])
            ? sanitize_textarea_field(wp_unslash((string) $_POST['message']))
            : '',
        'privacy' => !empty($_POST['privacy']) ? '1' : '',
    ];
    // phpcs:enable WordPress.Security.NonceVerification.Missing

    return $values;
}

/**
 * $_FILES-Eintrag (einzeln oder name[]) als Liste; leere Felder entfallen.
 */
function hengegroup_theme_get_application_files(string $key): array
{
    $entry = $_FILES[$key] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

    if (!is_array($entry) || !isset($entry['name'])) {
        return [];
    }

    $files = [];

    foreach ((array) $entry['name'] as $index => $name) {
        $error = (int) ((array) $entry['error'])[$index];

        if ($error === UPLOAD_ERR_NO_FILE || (string) $name === '') {
            continue;
        }

        $files[] = [
            'name' => sanitize_file_name((string) $name),
            'tmp_name' => (string) ((array) $entry['tmp_name'])[$index],
            'size' => (int) ((array) $entry['size'])[$index],
            'error' => $error,
        ];
    }

    return $files;
}

/**
 * Upload-Status, Groesse, echter Dateityp. '' = gueltig.
 */
function hengegroup_theme_validate_application_file(array $file): string
{
    if (
        $file['error'] === UPLOAD_ERR_INI_SIZE ||
        $file['size'] > hengegroup_theme_get_application_max_file_bytes()
    ) {
        return sprintf(
            /* translators: %s: maximum file size, e.g. "5 MB". */
            __('Die Datei ist größer als %s.', 'hengegroup-theme'),
            size_format(hengegroup_theme_get_application_max_file_bytes()),
        );
    }

    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return __('Die Datei konnte nicht hochgeladen werden.', 'hengegroup-theme');
    }

    $check = wp_check_filetype_and_ext(
        $file['tmp_name'],
        $file['name'],
        hengegroup_theme_get_application_mime_types(),
    );

    if (empty($check['ext']) || empty($check['type'])) {
        return __('Erlaubt sind nur PDF-, PNG- und JPG-Dateien.', 'hengegroup-theme');
    }

    return '';
}

function hengegroup_theme_redirect_after_application(int $job_id, array $args): void
{
    $target =
        $job_id > 0 ? (string) get_permalink($job_id) : hengegroup_theme_get_career_page_url();

    wp_safe_redirect(add_query_arg($args, $target) . '#bewerbung', 303);
    exit();
}

function hengegroup_theme_handle_job_application(): void
{
    // Bei Ueberschreitung von post_max_size sind $_POST/$_FILES leer; ohne diesen Zweig kaeme
    // "Formular abgelaufen". Die Stelle ist dann nur ueber den Referer bekannt.
    $content_length = isset($_SERVER['CONTENT_LENGTH']) ? absint($_SERVER['CONTENT_LENGTH']) : 0;

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nur Leer-Pruefung, kein Zugriff auf Werte.
    if ($_POST === [] && $content_length > 0) {
        $referer = wp_get_referer();
        $target = $referer !== false ? $referer : hengegroup_theme_get_career_page_url();

        wp_safe_redirect(
            add_query_arg(
                'bewerbung',
                'zugross',
                remove_query_arg(['bewerbung', 'eingabe'], $target),
            ) . '#bewerbung',
            303,
        );
        exit();
    }

    // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce wird direkt darunter geprueft.
    $job_id = isset($_POST['job_id']) ? absint($_POST['job_id']) : 0;
    $nonce = isset($_POST['_hengegroup_theme_application_nonce'])
        ? sanitize_text_field(wp_unslash($_POST['_hengegroup_theme_application_nonce']))
        : '';
    $honeypot = isset($_POST['website']) ? sanitize_text_field(wp_unslash($_POST['website'])) : '';
    $started = isset($_POST['started']) ? absint($_POST['started']) : 0;
    // phpcs:enable WordPress.Security.NonceVerification.Missing

    if (!wp_verify_nonce($nonce, HENGEGROUP_THEME_APPLICATION_ACTION . '_' . $job_id)) {
        hengegroup_theme_redirect_after_application($job_id, ['bewerbung' => 'abgelaufen']);
    }

    // Bots bekommen dieselbe Erfolgsmeldung, damit sie nicht lernen, was sie verraten hat.
    if (hengegroup_theme_is_form_bot($honeypot, $started)) {
        hengegroup_theme_redirect_after_application($job_id, ['bewerbung' => 'gesendet']);
    }

    if (
        get_post_type($job_id) !== HENGEGROUP_THEME_JOB_POST_TYPE ||
        get_post_status($job_id) !== 'publish' ||
        hengegroup_theme_is_job_expired($job_id)
    ) {
        hengegroup_theme_redirect_after_application(0, []);
    }

    $values = hengegroup_theme_get_application_input();
    $errors = hengegroup_theme_validate_job_application($values);
    $cv_files = array_slice(hengegroup_theme_get_application_files('cv'), 0, 1);
    $certificate_files = hengegroup_theme_get_application_files('certificates');

    if (count($certificate_files) > HENGEGROUP_THEME_APPLICATION_MAX_CERTIFICATES) {
        $errors['certificates'] = sprintf(
            /* translators: %d: maximum number of files. */
            __('Bitte lade höchstens %d Zeugnis-Dateien hoch.', 'hengegroup-theme'),
            HENGEGROUP_THEME_APPLICATION_MAX_CERTIFICATES,
        );
    }

    foreach (['cv' => $cv_files, 'certificates' => $certificate_files] as $field => $files) {
        foreach ($files as $file) {
            $file_error = hengegroup_theme_validate_application_file($file);

            if ($file_error !== '' && !isset($errors[$field])) {
                $errors[$field] = $file['name'] . ': ' . $file_error;
            }
        }
    }

    if ($errors !== []) {
        hengegroup_theme_redirect_after_application($job_id, [
            'bewerbung' => 'fehler',
            'eingabe' => hengegroup_theme_store_form_state('hg_application_', $values, $errors),
        ]);
    }

    $stored = hengegroup_theme_store_job_application(
        hengegroup_theme_get_job_data($job_id),
        $values,
        array_merge($cv_files, $certificate_files),
    );

    hengegroup_theme_redirect_after_application($job_id, [
        'bewerbung' => $stored ? 'gesendet' : 'versand',
    ]);
}
add_action(
    'admin_post_nopriv_' . HENGEGROUP_THEME_APPLICATION_ACTION,
    'hengegroup_theme_handle_job_application',
);
add_action(
    'admin_post_' . HENGEGROUP_THEME_APPLICATION_ACTION,
    'hengegroup_theme_handle_job_application',
);

/**
 * Ablageordner, bei Bedarf angelegt: bevorzugt neben dem Web-Root (.htaccess schuetzt unter nginx
 * nicht), sonst uploads/hengegroup-bewerbungen/ (z. B. bei open_basedir). Filter
 * `hengegroup_theme_application_storage_dir`. '' wenn nichts angelegt werden kann.
 */
function hengegroup_theme_get_application_storage_dir(): string
{
    if (!function_exists('get_home_path')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $private = hengegroup_theme_get_private_storage_dir(get_home_path());
    $directory =
        $private !== '' &&
        hengegroup_theme_is_path_within_open_basedir($private, (string) ini_get('open_basedir')) &&
        (is_dir($private) || wp_is_writable(dirname($private)))
            ? $private
            : hengegroup_theme_get_legacy_application_storage_dir();
    $directory = (string) apply_filters('hengegroup_theme_application_storage_dir', $directory);

    if ($directory === '' || !wp_mkdir_p($directory)) {
        return '';
    }

    $protect = [
        '.htaccess' =>
            "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n",
        'index.php' => "<?php\n// Silence is golden.\n",
    ];

    foreach ($protect as $file => $content) {
        if (!is_file($directory . '/' . $file)) {
            file_put_contents($directory . '/' . $file, $content); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        }
    }

    return $directory;
}

/**
 * Frueherer Ablageort; Rueckfall und Fundort alter Dateien.
 */
function hengegroup_theme_get_legacy_application_storage_dir(): string
{
    $uploads = wp_upload_dir(null, false);

    return trailingslashit((string) $uploads['basedir']) .
        HENGEGROUP_THEME_APPLICATION_STORAGE_FOLDER;
}

/**
 * Sucht im aktuellen und im frueheren Ablageort; '' wenn nicht gefunden.
 */
function hengegroup_theme_get_application_file_path(array $file): string
{
    $name = wp_basename((string) ($file['file'] ?? ''));

    if ($name === '') {
        return '';
    }

    $directories = array_unique([
        hengegroup_theme_get_application_storage_dir(),
        hengegroup_theme_get_legacy_application_storage_dir(),
    ]);

    foreach ($directories as $directory) {
        $path = trailingslashit($directory) . $name;

        if ($directory !== '' && is_file($path)) {
            return $path;
        }
    }

    return '';
}

function hengegroup_theme_store_job_application(array $job, array $values, array $files): bool
{
    $options = hengegroup_theme_get_job_application_options();
    $label = static fn(string $field, string $value): string => $options[$field][$value] ?? '';

    $post_id = hengegroup_theme_create_request(
        HENGEGROUP_THEME_APPLICATION_POST_TYPE,
        sprintf(
            /* translators: 1: applicant name, 2: job title. */
            __('%1$s – %2$s', 'hengegroup-theme'),
            $values['name'],
            $job['title'],
        ),
        [
            'job_id' => (int) $job['id'],
            'name' => $values['name'],
            'age' => $values['age'],
            'experience' => $label('experience', $values['experience']),
            'email' => $values['email'],
            'phone' => $values['phone'],
            'contact_method' => $label('contact_method', $values['contact_method']),
            'contact_time' => $label('contact_time', $values['contact_time']),
            'message' => $values['message'],
        ],
    );

    if ($post_id <= 0) {
        return false;
    }

    $directory = $files !== [] ? hengegroup_theme_get_application_storage_dir() : '';
    $stored_files = [];

    foreach ($directory !== '' ? $files : [] as $file) {
        $stored_name = $post_id . '-' . wp_generate_password(24, false) . '-' . $file['name'];
        $target = trailingslashit($directory) . $stored_name;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            $stored_files[] = [
                'name' => $file['name'],
                'file' => $stored_name,
                'size' => (int) $file['size'],
            ];
        }
    }

    if ($stored_files !== []) {
        update_post_meta($post_id, HENGEGROUP_THEME_REQUEST_META_PREFIX . 'files', $stored_files);
    }

    return true;
}

function hengegroup_theme_render_application_file_links(int $post_id, array $files): string
{
    if ($files === []) {
        return '—';
    }

    $links = [];

    foreach (array_values($files) as $index => $file) {
        $url = wp_nonce_url(
            add_query_arg(
                [
                    'action' => HENGEGROUP_THEME_APPLICATION_FILE_ACTION,
                    'post' => $post_id,
                    'file' => $index,
                ],
                admin_url('admin-post.php'),
            ),
            HENGEGROUP_THEME_APPLICATION_FILE_ACTION . '_' . $post_id,
        );

        $links[] = sprintf(
            '<a href="%1$s">%2$s</a> (%3$s)',
            esc_url($url),
            esc_html((string) ($file['name'] ?? '')),
            esc_html(size_format((int) ($file['size'] ?? 0))),
        );
    }

    return implode('<br>', $links);
}

function hengegroup_theme_handle_application_file_download(): void
{
    $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce direkt darunter.
    $index = isset($_GET['file']) ? absint($_GET['file']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    check_admin_referer(HENGEGROUP_THEME_APPLICATION_FILE_ACTION . '_' . $post_id);

    if (
        get_post_type($post_id) !== HENGEGROUP_THEME_APPLICATION_POST_TYPE ||
        !current_user_can('edit_post', $post_id)
    ) {
        wp_die(esc_html__('Keine Berechtigung.', 'hengegroup-theme'), '', ['response' => 403]);
    }

    $files = array_values(
        (array) get_post_meta($post_id, HENGEGROUP_THEME_REQUEST_META_PREFIX . 'files', true),
    );
    $file = $files[$index] ?? null;
    $path = is_array($file) ? hengegroup_theme_get_application_file_path($file) : '';

    if ($path === '') {
        wp_die(esc_html__('Datei nicht gefunden.', 'hengegroup-theme'), '', ['response' => 404]);
    }

    $type = wp_check_filetype($path, hengegroup_theme_get_application_mime_types());

    nocache_headers();
    header(
        'Content-Type: ' . ($type['type'] !== false ? $type['type'] : 'application/octet-stream'),
    );
    header(
        'Content-Disposition: attachment; filename="' .
            str_replace(['"', "\r", "\n"], '', (string) $file['name']) .
            '"',
    );
    header('Content-Length: ' . (string) filesize($path));
    header('X-Content-Type-Options: nosniff');
    readfile($path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
    exit();
}
add_action(
    'admin_post_' . HENGEGROUP_THEME_APPLICATION_FILE_ACTION,
    'hengegroup_theme_handle_application_file_download',
);

function hengegroup_theme_action_before_delete_post_application_files(int $post_id): void
{
    if (get_post_type($post_id) !== HENGEGROUP_THEME_APPLICATION_POST_TYPE) {
        return;
    }

    foreach (
        (array) get_post_meta($post_id, HENGEGROUP_THEME_REQUEST_META_PREFIX . 'files', true)
        as $file
    ) {
        $path = is_array($file) ? hengegroup_theme_get_application_file_path($file) : '';

        if ($path !== '') {
            wp_delete_file($path);
        }
    }
}
add_action('before_delete_post', 'hengegroup_theme_action_before_delete_post_application_files');

function hengegroup_theme_get_application_state(): array
{
    return hengegroup_theme_read_form_state(
        'hg_application_',
        'bewerbung',
        hengegroup_theme_get_application_status_messages(),
    );
}
