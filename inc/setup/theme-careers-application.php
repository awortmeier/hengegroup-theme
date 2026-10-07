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
// Die Bewerbung geht per E-Mail mit Anhaengen an den Ansprechpartner der Stelle (Unternehmen bzw.
// Karriere > Einstellungen), der Bewerber bekommt eine Eingangsbestaetigung. Bewusst KEINE
// Speicherung in WordPress (keine Bewerberdaten/Dateien in Datenbank oder Mediathek) -- weniger
// Datenschutz-Aufwand (Loeschfristen, Zugriffsrechte); hochgeladene Dateien liegen nur waehrend des
// Versands im temporaeren Verzeichnis. Siehe docs/entscheidungen.md "Bewerbungsformular".
//
// Spam-Schutz ohne Captcha/Drittanbieter: Nonce, Honeypot-Feld und Mindest-Ausfuellzeit.

const HENGEGROUP_THEME_APPLICATION_ACTION = 'hengegroup_theme_job_application';
const HENGEGROUP_THEME_APPLICATION_MAX_FILE_BYTES = 5 * 1024 * 1024;
const HENGEGROUP_THEME_APPLICATION_MAX_CERTIFICATES = 5;

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
            'Vielen Dank! Deine Bewerbung ist bei uns eingegangen. Du erhältst in Kürze eine Bestätigung per E-Mail.',
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
            'Deine Bewerbung konnte gerade nicht versendet werden. Bitte versuche es später erneut oder schreib uns direkt per E-Mail.',
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
    if ($honeypot !== '' || $started <= 0 || time() - $started < 3) {
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
        $token = wp_generate_password(20, false);
        set_transient(
            'hg_application_' . $token,
            ['values' => $values, 'errors' => $errors],
            15 * MINUTE_IN_SECONDS,
        );
        hengegroup_theme_redirect_after_application($job_id, [
            'bewerbung' => 'fehler',
            'eingabe' => $token,
        ]);
    }

    $sent = hengegroup_theme_send_job_application(
        hengegroup_theme_get_job_data($job_id),
        $values,
        array_merge($cv_files, $certificate_files),
    );

    hengegroup_theme_redirect_after_application($job_id, [
        'bewerbung' => $sent ? 'gesendet' : 'versand',
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
 * Versendet die Bewerbung an den Ansprechpartner der Stelle (Fallback: Admin-E-Mail) und eine
 * Eingangsbestaetigung an den Bewerber. Dateien werden unter ihrem Originalnamen in ein eigenes
 * temporaeres Verzeichnis kopiert (sonst kaemen sie als "phpXYZ.tmp" an) und danach geloescht.
 */
function hengegroup_theme_send_job_application(array $job, array $values, array $files): bool
{
    $options = hengegroup_theme_get_job_application_options();
    $recipient =
        $job['contact']['email'] !== '' ? $job['contact']['email'] : get_option('admin_email');
    $temp_dir = trailingslashit(get_temp_dir()) . 'hg-bewerbung-' . wp_generate_password(12, false);
    $attachments = [];

    if ($files !== [] && wp_mkdir_p($temp_dir)) {
        foreach ($files as $file) {
            $target = trailingslashit($temp_dir) . wp_unique_filename($temp_dir, $file['name']);

            if (move_uploaded_file($file['tmp_name'], $target)) {
                $attachments[] = $target;
            }
        }
    }

    $label = static fn(string $field, string $value): string => $options[$field][$value] ?? '—';
    $lines = [
        sprintf(__('Stelle: %s', 'hengegroup-theme'), $job['title']),
        sprintf(__('Referenz: %s', 'hengegroup-theme'), $job['reference']),
        sprintf(__('Link: %s', 'hengegroup-theme'), $job['url']),
        '',
        sprintf(__('Name: %s', 'hengegroup-theme'), $values['name']),
        sprintf(__('Alter: %s', 'hengegroup-theme'), $values['age']),
        sprintf(
            __('Berufserfahrung: %s', 'hengegroup-theme'),
            $label('experience', $values['experience']),
        ),
        sprintf(__('E-Mail: %s', 'hengegroup-theme'), $values['email']),
        sprintf(
            __('Telefon: %s', 'hengegroup-theme'),
            $values['phone'] !== '' ? $values['phone'] : '—',
        ),
        sprintf(
            __('Kontakt bevorzugt per: %s', 'hengegroup-theme'),
            $label('contact_method', $values['contact_method']),
        ),
        sprintf(
            __('Erreichbar: %s', 'hengegroup-theme'),
            $label('contact_time', $values['contact_time']),
        ),
        '',
        __('Nachricht:', 'hengegroup-theme'),
        $values['message'] !== '' ? $values['message'] : '—',
        '',
        sprintf(
            /* translators: %d: number of attached files. */
            __('Anhänge: %d', 'hengegroup-theme'),
            count($attachments),
        ),
    ];

    $sent = wp_mail(
        $recipient,
        sprintf(
            /* translators: 1: job title, 2: applicant name. */
            __('Bewerbung: %1$s – %2$s', 'hengegroup-theme'),
            $job['title'],
            $values['name'],
        ),
        implode("\n", $lines),
        // Anzeigename in Anfuehrungszeichen: "Name, Vorname" enthaelt ein Komma, das den Header
        // sonst in zwei Adressen zerlegen wuerde.
        [
            'Reply-To: "' .
            str_replace(['"', "\r", "\n"], '', $values['name']) .
            '" <' .
            $values['email'] .
            '>',
        ],
        $attachments,
    );

    foreach ($attachments as $attachment) {
        wp_delete_file($attachment);
    }

    if (is_dir($temp_dir)) {
        rmdir($temp_dir); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
    }

    if ($sent) {
        wp_mail(
            $values['email'],
            sprintf(
                /* translators: %s: job title. */
                __('Deine Bewerbung: %s', 'hengegroup-theme'),
                $job['title'],
            ),
            implode("\n", [
                __('Hallo,', 'hengegroup-theme'),
                '',
                sprintf(
                    /* translators: %s: job title. */
                    __(
                        'vielen Dank für deine Bewerbung als %s. Sie ist bei uns eingegangen, wir melden uns so bald wie möglich bei dir.',
                        'hengegroup-theme',
                    ),
                    $job['title'],
                ),
                '',
                __('Viele Grüße', 'hengegroup-theme'),
                $job['contact']['name'] !== '' ? $job['contact']['name'] : get_bloginfo('name'),
            ]),
            $job['contact']['email'] !== '' ? ['Reply-To: ' . $job['contact']['email']] : [],
        );
    }

    return $sent;
}

/**
 * Status und ggf. zwischengespeicherte Eingaben fuer das Formular nach dem Redirect. Der Transient
 * wird beim Lesen geloescht (einmalig).
 */
function hengegroup_theme_get_application_state(): array
{
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- nur Anzeige-Status, keine Aktion.
    $status = isset($_GET['bewerbung']) ? sanitize_key(wp_unslash($_GET['bewerbung'])) : '';
    $token = isset($_GET['eingabe']) ? sanitize_key(wp_unslash($_GET['eingabe'])) : '';
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    $stored = $token !== '' ? get_transient('hg_application_' . $token) : false;

    if ($token !== '') {
        delete_transient('hg_application_' . $token);
    }

    $messages = hengegroup_theme_get_application_status_messages();

    return [
        'status' => isset($messages[$status]) ? $status : '',
        'message' => $messages[$status] ?? '',
        'values' => is_array($stored) ? (array) ($stored['values'] ?? []) : [],
        'errors' => is_array($stored) ? (array) ($stored['errors'] ?? []) : [],
    ];
}
