<?php

declare(strict_types=1);

// Verarbeitung des Bewerbungsformulars einer Stellenanzeige (Formular:
// template-parts/components/job-application-form.php, explizite Nachfrage 2026-10-07: "exklusiv
// fuer die Stellenanzeige, die man sich gerade ansieht" -- die Stelle steckt als verstecktes Feld im
// Formular und wird hier gegen eine veroeffentlichte, nicht abgelaufene Stelle geprueft).
//
// Ablauf: klassisches POST an admin-post.php (funktioniert ohne JavaScript), danach Redirect zurueck
// auf die Stelle (#bewerbung) mit Status -- Post/Redirect/Get, kein doppeltes Absenden beim Neuladen.
// Bei Fehlern werden die Textfelder 15 Minuten in einem Transient gehalten (Schluessel im Redirect),
// damit niemand alles neu tippen muss; Dateien muessen neu gewaehlt werden (Browser-Sicherheit).
//
// Die Bewerbung wird als Eintrag "Bewerbung" im Backend gespeichert (Dashboard > Bewerbungen, siehe
// inc/setup/theme-requests.php) -- es werden KEINE E-Mails versendet, weder an den Ansprechpartner
// noch als Eingangsbestaetigung (explizite Vorgabe 2026-10-07, loest die fruehere Entscheidung
// "Versand per E-Mail statt Speicherung" ab). Hochgeladene Dateien landen NICHT in der Mediathek
// (dort waeren sie oeffentlich abrufbar), sondern in einem eigenen, per .htaccess gesperrten
// Verzeichnis unter uploads/ mit zufaelligen Dateinamen; heruntergeladen werden sie nur ueber
// hengegroup_theme_handle_application_file_download() mit Rechte- und Nonce-Pruefung. Beim
// endgueltigen Loeschen einer Bewerbung werden ihre Dateien mitgeloescht; automatisch geloescht wird
// nichts (explizite Vorgabe). Siehe docs/entscheidungen.md "Formulare: Eintraege im Backend statt
// E-Mail".
//
// Spam-Schutz ohne Captcha/Drittanbieter: Nonce, Honeypot-Feld und Mindest-Ausfuellzeit.

const HENGEGROUP_THEME_APPLICATION_ACTION = 'hengegroup_theme_job_application';
const HENGEGROUP_THEME_APPLICATION_MAX_FILE_BYTES = 5 * 1024 * 1024;
const HENGEGROUP_THEME_APPLICATION_MAX_CERTIFICATES = 5;
const HENGEGROUP_THEME_APPLICATION_FILE_ACTION = 'hengegroup_theme_application_file';

/**
 * Tatsaechliche Obergrenze je Datei: 5 MB laut Design, aber nie mehr, als PHP auf dem Server
 * annimmt (`upload_max_filesize`/`post_max_size`, ueber wp_max_upload_size()) -- sonst wuerde das
 * Formular eine Groesse versprechen, die der Server stillschweigend verwirft.
 */
function hengegroup_theme_get_application_max_file_bytes(): int
{
    return (int) min(HENGEGROUP_THEME_APPLICATION_MAX_FILE_BYTES, wp_max_upload_size());
}

/**
 * Erlaubte Dateitypen fuer Lebenslauf/Zeugnisse (Design: pdf, png, jpg, jpeg).
 */
function hengegroup_theme_get_application_mime_types(): array
{
    return [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg|jpeg' => 'image/jpeg',
    ];
}

/**
 * Status-Meldungen nach dem Redirect (Query-Parameter `bewerbung`).
 */
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
 * Liest die Textfelder aus $_POST und sanitisiert sie. Nonce wird vom Aufrufer geprueft.
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
 * Normalisiert einen $_FILES-Eintrag (einzeln oder `name[]`) zu einer Liste hochgeladener Dateien;
 * leere Felder ("keine Datei gewaehlt") fallen weg.
 */
function hengegroup_theme_get_application_files(string $key): array
{
    // Nonce geprueft in hengegroup_theme_handle_job_application(); jeder Wert wird unten einzeln
    // behandelt (Name per sanitize_file_name(), Groessen/Fehler als int, tmp_name nur ueber
    // is_uploaded_file()/move_uploaded_file()).
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
 * Prueft eine hochgeladene Datei: Upload ok, Groesse, echter Dateityp (Inhalt, nicht nur Endung).
 * Leerer String = gueltig, sonst Fehlermeldung.
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

/**
 * Redirect zurueck auf die Stelle, Abschnitt Bewerbung.
 */
function hengegroup_theme_redirect_after_application(int $job_id, array $args): void
{
    $target =
        $job_id > 0 ? (string) get_permalink($job_id) : hengegroup_theme_get_career_page_url();

    wp_safe_redirect(add_query_arg($args, $target) . '#bewerbung', 303);
    exit();
}

function hengegroup_theme_handle_job_application(): void
{
    // Ueberschreitet die Anfrage `post_max_size`, verwirft PHP ALLE Felder ($_POST/$_FILES leer)
    // -- ohne diesen Zweig kaeme faelschlich "Formular abgelaufen" (Nonce fehlt). Die Stelle ist
    // dann nur noch ueber den Referer bekannt.
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

    // Bots fuellen das unsichtbare Feld aus oder schicken in unter 3 Sekunden ab -- sie bekommen
    // dieselbe Erfolgsmeldung wie echte Bewerber, damit sie nicht lernen, was sie verraten hat.
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
 * Geschuetztes Ablageverzeichnis fuer Bewerbungsunterlagen (uploads/hengegroup-bewerbungen/), wird
 * bei Bedarf angelegt und per .htaccess (Apache) gesperrt; index.php verhindert Verzeichnislisten.
 * Auf nginx greift .htaccess nicht -- dort schuetzen die zufaelligen Dateinamen, eine
 * Server-Regel fuer dieses Verzeichnis ist trotzdem empfehlenswert (siehe docs/to-do.md).
 * Leerer String, wenn das Verzeichnis nicht angelegt werden kann.
 */
function hengegroup_theme_get_application_storage_dir(): string
{
    $uploads = wp_upload_dir(null, false);
    $directory = trailingslashit((string) $uploads['basedir']) . 'hengegroup-bewerbungen';

    if (!wp_mkdir_p($directory)) {
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
 * Speichert die Bewerbung als Eintrag (Dashboard > Bewerbungen) und verschiebt die Dateien ins
 * geschuetzte Verzeichnis (zufaelliger Name, Originalname nur als Meta). Keine E-Mails.
 */
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

/**
 * Download-Links der Unterlagen einer Bewerbung fuer das Backend.
 */
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

/**
 * Liefert eine Bewerbungsdatei aus -- nur fuer angemeldete Nutzer, die die Bewerbung bearbeiten
 * duerfen, mit Nonce.
 */
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
    $directory = hengegroup_theme_get_application_storage_dir();
    $path =
        is_array($file) && $directory !== ''
            ? trailingslashit($directory) . wp_basename((string) ($file['file'] ?? ''))
            : '';

    if ($path === '' || !is_file($path)) {
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

/**
 * Loescht die Unterlagen mit, wenn eine Bewerbung endgueltig geloescht wird (Papierkorb leeren).
 */
function hengegroup_theme_action_before_delete_post_application_files(int $post_id): void
{
    if (get_post_type($post_id) !== HENGEGROUP_THEME_APPLICATION_POST_TYPE) {
        return;
    }

    $directory = hengegroup_theme_get_application_storage_dir();

    if ($directory === '') {
        return;
    }

    foreach (
        (array) get_post_meta($post_id, HENGEGROUP_THEME_REQUEST_META_PREFIX . 'files', true)
        as $file
    ) {
        $path = trailingslashit($directory) . wp_basename((string) ($file['file'] ?? ''));

        if (is_array($file) && is_file($path)) {
            wp_delete_file($path);
        }
    }
}
add_action('before_delete_post', 'hengegroup_theme_action_before_delete_post_application_files');

/**
 * Status und ggf. zwischengespeicherte Eingaben fuer das Formular nach dem Redirect. Der Transient
 * wird beim Lesen geloescht (einmalig).
 */
function hengegroup_theme_get_application_state(): array
{
    return hengegroup_theme_read_form_state(
        'hg_application_',
        'bewerbung',
        hengegroup_theme_get_application_status_messages(),
    );
}
