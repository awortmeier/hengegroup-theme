<?php

declare(strict_types=1);

// Eingaenge aus Website-Formularen als Backend-Eintraege statt E-Mails (explizite Vorgabe
// 2026-10-07: "keine echten E-Mails, sondern Eintraege im Backend"):
//   - `produktanfrage` (Hauptmenuepunkt "Produktanfragen"): Kontaktformulare der Produktuebersicht
//     (Block "Kontakt") und der Produktdetailseite, Handler inc/setup/theme-product-inquiries.php.
//   - `bewerbung` (Hauptmenuepunkt "Bewerbungen"): Bewerbungsformular der Stellenseite, Handler
//     inc/setup/theme-careers-application.php, Dateien geschuetzt ausserhalb der Mediathek.
//
// Beide Typen teilen sich hier Registrierung, Status (neu / in Bearbeitung / erledigt), Spalten,
// die schreibgeschuetzte Detailansicht und den Zaehler neuer Eintraege am Menuepunkt (es kommt ja
// keine E-Mail mehr, die auf neue Eingaenge hinweist). Beide sind eigene Hauptmenuepunkte direkt
// unter "Dashboard" statt Unterpunkte von Produkte/Karriere (explizite Nachfrage 2026-10-07) --
// dort sieht man sie nach dem Login zuerst; der Zaehler steht direkt am Menuepunkt. Eintraege werden nie automatisch geloescht
// (explizite Vorgabe); sichtbar fuer alle, die fremde Beitraege bearbeiten duerfen (Redakteure und
// Administratoren -- explizite Vorgabe "jeder darf sie sehen"). Neu anlegen kann man sie im Backend
// nicht (`create_posts => do_not_allow`), sie entstehen nur ueber die Formulare.
//
// Spam-Schutz und Fehler-Zwischenspeicher (Post/Redirect/Get) teilen sich beide Formulare ueber
// hengegroup_theme_is_form_bot()/hengegroup_theme_store_form_state()/
// hengegroup_theme_read_form_state() unten. Begruendung: docs/entscheidungen.md "Formulare:
// Eintraege im Backend statt E-Mail".

const HENGEGROUP_THEME_INQUIRY_POST_TYPE = 'produktanfrage';
const HENGEGROUP_THEME_APPLICATION_POST_TYPE = 'bewerbung';
const HENGEGROUP_THEME_REQUEST_META_PREFIX = '_hengegroup_theme_request_';
const HENGEGROUP_THEME_REQUESTS_MENU_SEPARATOR = 'separator-hengegroup-theme-requests';

/**
 * Status eines Eingangs.
 */
function hengegroup_theme_get_request_statuses(): array
{
    return [
        'neu' => __('Neu', 'hengegroup-theme'),
        'in_bearbeitung' => __('In Bearbeitung', 'hengegroup-theme'),
        'erledigt' => __('Erledigt', 'hengegroup-theme'),
    ];
}

/**
 * Felder je Eingangs-Typ in Anzeige-Reihenfolge: Feld => Label. Gespeichert als Post-Meta
 * HENGEGROUP_THEME_REQUEST_META_PREFIX . Feld (Auswahlfelder bereits als lesbarer Text).
 */
function hengegroup_theme_get_request_fields(string $post_type): array
{
    if ($post_type === HENGEGROUP_THEME_INQUIRY_POST_TYPE) {
        return [
            'product_id' => __('Produkt', 'hengegroup-theme'),
            'company' => __('Firma', 'hengegroup-theme'),
            'name' => __('Name', 'hengegroup-theme'),
            'postal_code' => __('PLZ', 'hengegroup-theme'),
            'city' => __('Ort', 'hengegroup-theme'),
            'email' => __('E-Mail', 'hengegroup-theme'),
            'phone' => __('Telefon', 'hengegroup-theme'),
            'message' => __('Nachricht', 'hengegroup-theme'),
            'source_url' => __('Gesendet von', 'hengegroup-theme'),
        ];
    }

    return [
        'job_id' => __('Stelle', 'hengegroup-theme'),
        'name' => __('Name', 'hengegroup-theme'),
        'age' => __('Alter', 'hengegroup-theme'),
        'experience' => __('Berufserfahrung', 'hengegroup-theme'),
        'email' => __('E-Mail', 'hengegroup-theme'),
        'phone' => __('Telefon', 'hengegroup-theme'),
        'contact_method' => __('Kontakt bevorzugt per', 'hengegroup-theme'),
        'contact_time' => __('Erreichbar', 'hengegroup-theme'),
        'message' => __('Nachricht', 'hengegroup-theme'),
        'files' => __('Unterlagen', 'hengegroup-theme'),
    ];
}

function hengegroup_theme_register_request_post_types(): void
{
    $shared = [
        'public' => false,
        'show_ui' => true,
        'show_in_rest' => false,
        'show_in_nav_menus' => false,
        'show_in_admin_bar' => false,
        'exclude_from_search' => true,
        'publicly_queryable' => false,
        'query_var' => false,
        'rewrite' => false,
        'supports' => ['title'],
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'capabilities' => ['create_posts' => 'do_not_allow'],
    ];

    register_post_type(
        HENGEGROUP_THEME_INQUIRY_POST_TYPE,
        array_merge($shared, [
            'labels' => [
                'name' => __('Produktanfragen', 'hengegroup-theme'),
                'singular_name' => __('Produktanfrage', 'hengegroup-theme'),
                'edit_item' => __('Produktanfrage', 'hengegroup-theme'),
                'search_items' => __('Produktanfragen durchsuchen', 'hengegroup-theme'),
                'not_found' => __('Keine Produktanfragen gefunden', 'hengegroup-theme'),
                'not_found_in_trash' => __(
                    'Keine Produktanfragen im Papierkorb',
                    'hengegroup-theme',
                ),
                'all_items' => __('Produktanfragen', 'hengegroup-theme'),
                'menu_name' => __('Produktanfragen', 'hengegroup-theme'),
            ],
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-email-alt',
        ]),
    );

    register_post_type(
        HENGEGROUP_THEME_APPLICATION_POST_TYPE,
        array_merge($shared, [
            'labels' => [
                'name' => __('Bewerbungen', 'hengegroup-theme'),
                'singular_name' => __('Bewerbung', 'hengegroup-theme'),
                'edit_item' => __('Bewerbung', 'hengegroup-theme'),
                'search_items' => __('Bewerbungen durchsuchen', 'hengegroup-theme'),
                'not_found' => __('Keine Bewerbungen gefunden', 'hengegroup-theme'),
                'not_found_in_trash' => __('Keine Bewerbungen im Papierkorb', 'hengegroup-theme'),
                'all_items' => __('Bewerbungen', 'hengegroup-theme'),
                'menu_name' => __('Bewerbungen', 'hengegroup-theme'),
            ],
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-id-alt',
        ]),
    );
}
add_action('init', 'hengegroup_theme_register_request_post_types');

/**
 * Legt einen Eingang an. `$fields` = Feld => Wert (siehe hengegroup_theme_get_request_fields()).
 * Rueckgabe: Post-ID oder 0 bei Fehler.
 */
function hengegroup_theme_create_request(string $post_type, string $title, array $fields): int
{
    $post_id = wp_insert_post(
        [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'post_title' => $title,
            'post_author' => 0,
        ],
        true,
    );

    if (is_wp_error($post_id) || $post_id <= 0) {
        return 0;
    }

    foreach ($fields as $field => $value) {
        if ($value === '' || $value === [] || $value === 0) {
            continue;
        }

        update_post_meta($post_id, HENGEGROUP_THEME_REQUEST_META_PREFIX . $field, $value);
    }

    update_post_meta($post_id, HENGEGROUP_THEME_REQUEST_META_PREFIX . 'status', 'neu');

    return (int) $post_id;
}

function hengegroup_theme_get_request_status(int $post_id): string
{
    $status = (string) get_post_meta(
        $post_id,
        HENGEGROUP_THEME_REQUEST_META_PREFIX . 'status',
        true,
    );

    return isset(hengegroup_theme_get_request_statuses()[$status]) ? $status : 'neu';
}

/**
 * Spam-Pruefung beider Formulare: Honeypot-Feld ausgefuellt oder in unter 3 Sekunden abgeschickt.
 * Erkannte Bots bekommen beim Aufrufer dieselbe Erfolgsmeldung wie Menschen.
 */
function hengegroup_theme_is_form_bot(string $honeypot, int $started): bool
{
    return $honeypot !== '' || $started <= 0 || time() - $started < 3;
}

/**
 * Haelt Eingaben + Fehler 15 Minuten fuer die Anzeige nach dem Redirect; Rueckgabe: Schluessel fuer
 * den Query-Parameter `eingabe`.
 */
function hengegroup_theme_store_form_state(string $prefix, array $values, array $errors): string
{
    $token = wp_generate_password(20, false);
    set_transient(
        $prefix . $token,
        ['values' => $values, 'errors' => $errors],
        15 * MINUTE_IN_SECONDS,
    );

    return $token;
}

/**
 * Status (Query-Parameter `$status_key`) und ggf. zwischengespeicherte Eingaben nach dem Redirect.
 * Der Transient wird beim Lesen geloescht (einmalig).
 */
function hengegroup_theme_read_form_state(
    string $prefix,
    string $status_key,
    array $messages,
): array {
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- nur Anzeige-Status, keine Aktion.
    $status = isset($_GET[$status_key]) ? sanitize_key(wp_unslash($_GET[$status_key])) : '';
    $token = isset($_GET['eingabe']) ? sanitize_key(wp_unslash($_GET['eingabe'])) : '';
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    $stored = $token !== '' ? get_transient($prefix . $token) : false;

    if ($token !== '') {
        delete_transient($prefix . $token);
    }

    return [
        'status' => isset($messages[$status]) ? $status : '',
        'message' => $messages[$status] ?? '',
        'values' => is_array($stored) ? (array) ($stored['values'] ?? []) : [],
        'errors' => is_array($stored) ? (array) ($stored['errors'] ?? []) : [],
    ];
}

/**
 * Detailansicht und Status-Box im Editor eines Eingangs. Titel bleibt sichtbar (Kurzbezeichnung),
 * die Formulardaten selbst sind schreibgeschuetzt.
 */
function hengegroup_theme_action_add_meta_boxes_requests(): void
{
    foreach (
        [HENGEGROUP_THEME_INQUIRY_POST_TYPE, HENGEGROUP_THEME_APPLICATION_POST_TYPE]
        as $post_type
    ) {
        add_meta_box(
            'hengegroup-theme-request-details',
            __('Angaben', 'hengegroup-theme'),
            'hengegroup_theme_render_request_details_meta_box',
            $post_type,
            'normal',
            'high',
        );
        add_meta_box(
            'hengegroup-theme-request-status',
            __('Status', 'hengegroup-theme'),
            'hengegroup_theme_render_request_status_meta_box',
            $post_type,
            'side',
            'high',
        );
    }
}
add_action('add_meta_boxes', 'hengegroup_theme_action_add_meta_boxes_requests');

/**
 * Anzeige eines gespeicherten Feldwerts im Backend (verlinkt, wo sinnvoll).
 */
function hengegroup_theme_format_request_value(int $post_id, string $field): string
{
    $value = get_post_meta($post_id, HENGEGROUP_THEME_REQUEST_META_PREFIX . $field, true);

    if ($field === 'files') {
        return hengegroup_theme_render_application_file_links(
            $post_id,
            is_array($value) ? $value : [],
        );
    }

    if (in_array($field, ['product_id', 'job_id'], true)) {
        $linked_id = (int) $value;

        if ($linked_id <= 0) {
            return $field === 'product_id'
                ? esc_html__('Allgemeine Anfrage', 'hengegroup-theme')
                : '—';
        }

        $edit_link = get_edit_post_link($linked_id);

        return sprintf(
            '%1$s%2$s',
            esc_html(get_the_title($linked_id)),
            $edit_link !== null
                ? ' (<a href="' .
                    esc_url($edit_link) .
                    '">' .
                    esc_html__('bearbeiten', 'hengegroup-theme') .
                    '</a>)'
                : '',
        );
    }

    $value = trim((string) $value);

    if ($value === '') {
        return '—';
    }

    return match ($field) {
        'email' => '<a href="' .
            esc_url('mailto:' . $value, ['mailto']) .
            '">' .
            esc_html($value) .
            '</a>',
        'phone' => '<a href="' .
            esc_url(hengegroup_theme_phone_href($value), ['tel']) .
            '">' .
            esc_html($value) .
            '</a>',
        'source_url' => '<a href="' .
            esc_url($value) .
            '" target="_blank" rel="noopener">' .
            esc_html($value) .
            '</a>',
        default => nl2br(esc_html($value)),
    };
}

function hengegroup_theme_render_request_details_meta_box(WP_Post $post): void
{
    echo '<table class="form-table" role="presentation"><tbody>';

    foreach (hengegroup_theme_get_request_fields($post->post_type) as $field => $label) {
        printf(
            '<tr><th scope="row">%1$s</th><td>%2$s</td></tr>',
            esc_html($label),
            hengegroup_theme_format_request_value($post->ID, $field), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
        );
    }

    printf(
        '<tr><th scope="row">%1$s</th><td>%2$s</td></tr>',
        esc_html__('Eingegangen', 'hengegroup-theme'),
        esc_html(get_the_date(get_option('date_format') . ' ' . get_option('time_format'), $post)),
    );

    echo '</tbody></table>';
}

function hengegroup_theme_render_request_status_meta_box(WP_Post $post): void
{
    wp_nonce_field('hengegroup_theme_save_request_status', 'hengegroup_theme_request_status_nonce');

    $current = hengegroup_theme_get_request_status($post->ID);

    echo '<select name="hengegroup_theme_request_status" class="widefat">';

    foreach (hengegroup_theme_get_request_statuses() as $status => $label) {
        printf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr($status),
            selected($current, $status, false),
            esc_html($label),
        );
    }

    echo '</select>';
}

function hengegroup_theme_action_save_post_request_status(int $post_id): void
{
    if (
        !isset($_POST['hengegroup_theme_request_status_nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['hengegroup_theme_request_status_nonce'])),
            'hengegroup_theme_save_request_status',
        ) ||
        (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
        !current_user_can('edit_post', $post_id)
    ) {
        return;
    }

    $status = isset($_POST['hengegroup_theme_request_status'])
        ? sanitize_key(wp_unslash($_POST['hengegroup_theme_request_status']))
        : '';

    if (isset(hengegroup_theme_get_request_statuses()[$status])) {
        update_post_meta($post_id, HENGEGROUP_THEME_REQUEST_META_PREFIX . 'status', $status);
    }
}
add_action(
    'save_post_' . HENGEGROUP_THEME_INQUIRY_POST_TYPE,
    'hengegroup_theme_action_save_post_request_status',
);
add_action(
    'save_post_' . HENGEGROUP_THEME_APPLICATION_POST_TYPE,
    'hengegroup_theme_action_save_post_request_status',
);

/**
 * Spalten der Listen: Status + die wichtigsten Angaben je Typ.
 */
function hengegroup_theme_filter_manage_request_columns(array $columns): array
{
    $screen = get_current_screen();
    $post_type = $screen instanceof WP_Screen ? $screen->post_type : '';
    $date = $columns['date'] ?? null;
    unset($columns['date']);

    $columns['hengegroup_theme_request_status'] = __('Status', 'hengegroup-theme');

    if ($post_type === HENGEGROUP_THEME_INQUIRY_POST_TYPE) {
        $columns['hengegroup_theme_request_product_id'] = __('Produkt', 'hengegroup-theme');
    } else {
        $columns['hengegroup_theme_request_job_id'] = __('Stelle', 'hengegroup-theme');
    }

    $columns['hengegroup_theme_request_email'] = __('E-Mail', 'hengegroup-theme');

    if ($date !== null) {
        $columns['date'] = __('Eingegangen', 'hengegroup-theme');
    }

    return $columns;
}
add_filter(
    'manage_' . HENGEGROUP_THEME_INQUIRY_POST_TYPE . '_posts_columns',
    'hengegroup_theme_filter_manage_request_columns',
);
add_filter(
    'manage_' . HENGEGROUP_THEME_APPLICATION_POST_TYPE . '_posts_columns',
    'hengegroup_theme_filter_manage_request_columns',
);

function hengegroup_theme_action_manage_request_custom_column(string $column, int $post_id): void
{
    if ($column === 'hengegroup_theme_request_status') {
        $status = hengegroup_theme_get_request_status($post_id);
        $label = hengegroup_theme_get_request_statuses()[$status];

        echo $status === 'neu' ? '<strong>' . esc_html($label) . '</strong>' : esc_html($label);

        return;
    }

    if (!str_starts_with($column, 'hengegroup_theme_request_')) {
        return;
    }

    $value = hengegroup_theme_format_request_value(
        $post_id,
        substr($column, strlen('hengegroup_theme_request_')),
    );

    echo $value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside hengegroup_theme_format_request_value().
}
add_action(
    'manage_' . HENGEGROUP_THEME_INQUIRY_POST_TYPE . '_posts_custom_column',
    'hengegroup_theme_action_manage_request_custom_column',
    10,
    2,
);
add_action(
    'manage_' . HENGEGROUP_THEME_APPLICATION_POST_TYPE . '_posts_custom_column',
    'hengegroup_theme_action_manage_request_custom_column',
    10,
    2,
);

/**
 * Anzahl Eingaenge mit Status "neu".
 */
function hengegroup_theme_count_new_requests(string $post_type): int
{
    $query = new WP_Query([
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => HENGEGROUP_THEME_REQUEST_META_PREFIX . 'status', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        'meta_value' => 'neu', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
    ]);

    return (int) $query->found_posts;
}

/**
 * Badge-Markup im Stil von WordPress' eigenen Zaehlern (Kommentare, Updates).
 */
function hengegroup_theme_get_admin_count_bubble(int $count): string
{
    return sprintf(
        ' <span class="awaiting-mod count-%1$d"><span class="pending-count">%1$d</span></span>',
        $count,
    );
}

/**
 * Zaehler neuer Eingaenge (Status "neu") direkt an den Hauptmenuepunkten "Produktanfragen"/
 * "Bewerbungen" -- ersetzt die E-Mail-Benachrichtigung. Ohne neue Eingaenge erscheint kein Badge.
 */
function hengegroup_theme_action_admin_menu_request_counts(): void
{
    global $menu;

    if (!current_user_can('edit_others_posts')) {
        return;
    }

    foreach (
        [HENGEGROUP_THEME_INQUIRY_POST_TYPE, HENGEGROUP_THEME_APPLICATION_POST_TYPE]
        as $post_type
    ) {
        $count = hengegroup_theme_count_new_requests($post_type);

        if ($count <= 0) {
            continue;
        }

        foreach ($menu as $index => $item) {
            if (($item[2] ?? '') === 'edit.php?post_type=' . $post_type) {
                $menu[$index][0] .= hengegroup_theme_get_admin_count_bubble($count); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
            }
        }
    }
}
add_action('admin_menu', 'hengegroup_theme_action_admin_menu_request_counts', 1000);

/**
 * Eigener Menue-Trenner zwischen "Dashboard" und den Eingaengen (explizite Nachfrage 2026-10-07:
 * gleicher Abstand wie zwischen "Bewerbungen" und "Medien", das ist WordPress' `separator1`).
 * Gleiche Struktur wie WordPress' eigene Trenner in wp-admin/menu.php; die Position setzt
 * hengegroup_theme_filter_menu_order_requests().
 */
function hengegroup_theme_action_admin_menu_requests_separator(): void
{
    global $menu;

    $menu[] = ['', 'read', HENGEGROUP_THEME_REQUESTS_MENU_SEPARATOR, '', 'wp-menu-separator']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}
add_action('admin_menu', 'hengegroup_theme_action_admin_menu_requests_separator', 1000);

/**
 * Setzt "Produktanfragen" und "Bewerbungen" direkt hinter "Dashboard", abgesetzt durch den eigenen
 * Trenner davor (siehe hengegroup_theme_action_admin_menu_requests_separator()); danach folgt
 * WordPress' `separator1`. Ueber `menu_order` statt `menu_position`, weil zwischen Dashboard (2) und
 * dem ersten Trenner (4) nur Platz fuer einen Eintrag mit ganzzahliger Position ist -- ein zweiter
 * wuerde von WordPress hinter "Beitraege" geschoben.
 */
function hengegroup_theme_filter_menu_order_requests(array $menu_order): array
{
    $requests = [
        HENGEGROUP_THEME_REQUESTS_MENU_SEPARATOR,
        'edit.php?post_type=' . HENGEGROUP_THEME_INQUIRY_POST_TYPE,
        'edit.php?post_type=' . HENGEGROUP_THEME_APPLICATION_POST_TYPE,
    ];
    $menu_order = array_values(array_diff($menu_order, $requests));
    $position = array_search('index.php', $menu_order, true);

    array_splice($menu_order, $position === false ? 0 : (int) $position + 1, 0, $requests);

    return $menu_order;
}
add_filter('custom_menu_order', '__return_true');
add_filter('menu_order', 'hengegroup_theme_filter_menu_order_requests');
