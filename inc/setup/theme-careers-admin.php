<?php

declare(strict_types=1);

// Backend fuer Stellenangebote (siehe inc/setup/theme-careers.php fuer Post-Type/Taxonomien):
//   - "Stellendetails"-Box im Stellen-Editor: Unternehmen (Einzelauswahl), Standorte,
//     Taetigkeitsbereich, Anstellungsart, Gueltig bis, Eintritt, Gehalt, Arbeitsmodell,
//     Erfahrung/Abschluss, Aufgaben/Profil/Benefits (ein Punkt pro Zeile), "Stelle besetzt", alte
//     Stellen-ID. Oben ein Hinweis, welche fuer Google Jobs wichtigen Angaben noch fehlen.
//   - Zusatzfelder an den Taxonomie-Termen: Unternehmen (rechtlicher Name, Website, Logo, Farbe,
//     Standard-Benefits, optionaler eigener Ansprechpartner), Standort (Adresse + Koordinaten).
//   - "Karriere > Einstellungen": Karriereseite + Standard-Ansprechpartner (aktuell einer fuer alle
//     Unternehmen, pro Unternehmen ueberschreibbar).
// Klassische add_meta_box()-/Term-Formular-Felder wie die bestehende SEO-/Badge-Box
// (theme-seo-admin.php, theme-woocommerce-products.php) -- funktioniert im Block-Editor als
// Metabox unter dem Inhalt, ohne eigenes JS-Bundle. Inline-`style`-Attribute sind wp-admin-UI,
// ausserhalb von CLAUDE.md Regel 1 (gleiche Abgrenzung wie theme-seo-admin.php).

function hengegroup_theme_action_add_meta_boxes_job_details(): void
{
    add_meta_box(
        'hengegroup-theme-job-details',
        __('Stellendetails', 'hengegroup-theme'),
        'hengegroup_theme_render_job_details_meta_box',
        HENGEGROUP_THEME_JOB_POST_TYPE,
        'normal',
        'high',
    );
}
add_action('add_meta_boxes', 'hengegroup_theme_action_add_meta_boxes_job_details');

/**
 * Eine Tabellenzeile im WordPress-Formular-Stil (`form-table`), `$control` ist bereits escapetes
 * HTML.
 */
function hengegroup_theme_render_job_admin_row(
    string $label,
    string $control,
    string $description = '',
    string $for = '',
): void {
    printf(
        '<tr><th scope="row">%1$s</th><td>%2$s%3$s</td></tr>',
        $for !== ''
            ? sprintf('<label for="%s">%s</label>', esc_attr($for), esc_html($label))
            : esc_html($label),
        $control, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $description !== '' ? '<p class="description">' . esc_html($description) . '</p>' : '',
    );
}

/**
 * <select> aus einer value => label-Liste. `$empty_label` erzeugt eine erste, leere Option.
 */
function hengegroup_theme_get_job_admin_select(
    string $id,
    string $name,
    array $options,
    string $selected,
    string $empty_label = '',
): string {
    $markup = sprintf('<select id="%s" name="%s">', esc_attr($id), esc_attr($name));

    if ($empty_label !== '') {
        $markup .= sprintf(
            '<option value=""%s>%s</option>',
            selected($selected, '', false),
            esc_html($empty_label),
        );
    }

    foreach ($options as $value => $label) {
        $markup .= sprintf(
            '<option value="%s"%s>%s</option>',
            esc_attr((string) $value),
            selected($selected, (string) $value, false),
            esc_html($label),
        );
    }

    return $markup . '</select>';
}

function hengegroup_theme_render_job_details_meta_box(WP_Post $post): void
{
    wp_nonce_field('hengegroup_theme_save_job_details', 'hengegroup_theme_job_details_nonce');

    $job = hengegroup_theme_get_job_data($post->ID);
    $missing = hengegroup_theme_get_job_posting_missing_fields($job);
    $name = static fn(string $field): string => 'hengegroup_theme_job[' . $field . ']';

    if ($missing !== []) {
        printf(
            '<div class="notice notice-warning inline"><p>%s</p></div>',
            esc_html(
                sprintf(
                    /* translators: %s: comma-separated list of missing field labels. */
                    __(
                        'Für eine vollständige Anzeige in der Google-Jobsuche fehlt noch: %s.',
                        'hengegroup-theme',
                    ),
                    implode(', ', $missing),
                ),
            ),
        );
    }

    if (hengegroup_theme_is_job_expired($post->ID)) {
        printf(
            '<div class="notice notice-info inline"><p>%s</p></div>',
            esc_html__(
                'Diese Stelle ist abgelaufen bzw. besetzt: sie erscheint in keiner Liste mehr und ' .
                    'Besucher werden auf die Karriereseite weitergeleitet.',
                'hengegroup-theme',
            ),
        );
    }

    echo '<table class="form-table" role="presentation"><tbody>';

    $companies = get_terms([
        'taxonomy' => HENGEGROUP_THEME_JOB_COMPANY_TAXONOMY,
        'hide_empty' => false,
    ]);
    $company_options = [];

    foreach (is_array($companies) ? $companies : [] as $term) {
        $company_options[(string) $term->term_id] = $term->name;
    }

    hengegroup_theme_render_job_admin_row(
        __('Unternehmen', 'hengegroup-theme'),
        hengegroup_theme_get_job_admin_select(
            'hengegroup-theme-job-company',
            $name('company'),
            $company_options,
            $job['company'] !== null ? (string) $job['company']['term_id'] : '',
            __('— Unternehmen wählen —', 'hengegroup-theme'),
        ),
        __(
            'Logo, Farbe, Website und Ansprechpartner kommen vom Unternehmen (Karriere > Unternehmen).',
            'hengegroup-theme',
        ),
        'hengegroup-theme-job-company',
    );

    $locations = get_terms([
        'taxonomy' => HENGEGROUP_THEME_JOB_LOCATION_TAXONOMY,
        'hide_empty' => false,
    ]);
    $selected_locations = array_column($job['locations'], 'term_id');
    $location_markup = '';

    foreach (is_array($locations) ? $locations : [] as $term) {
        $location_markup .= sprintf(
            '<label style="display:block;margin-bottom:4px"><input type="checkbox" name="%s[]" value="%d"%s> %s</label>',
            esc_attr($name('locations')),
            (int) $term->term_id,
            checked(in_array((int) $term->term_id, $selected_locations, true), true, false),
            esc_html(hengegroup_theme_get_job_location($term)['label']),
        );
    }

    hengegroup_theme_render_job_admin_row(
        __('Standort(e)', 'hengegroup-theme'),
        $location_markup !== ''
            ? $location_markup
            : esc_html__(
                'Noch keine Standorte angelegt (Karriere > Standorte).',
                'hengegroup-theme',
            ),
    );

    $categories = get_terms([
        'taxonomy' => HENGEGROUP_THEME_JOB_CATEGORY_TAXONOMY,
        'hide_empty' => false,
    ]);
    $category_options = [];
    $selected_category = '';

    foreach (is_array($categories) ? $categories : [] as $term) {
        $category_options[(string) $term->term_id] = $term->name;

        if ($term->name === $job['category']) {
            $selected_category = (string) $term->term_id;
        }
    }

    hengegroup_theme_render_job_admin_row(
        __('Tätigkeitsbereich', 'hengegroup-theme'),
        hengegroup_theme_get_job_admin_select(
            'hengegroup-theme-job-category',
            $name('category'),
            $category_options,
            $selected_category,
            __('— keiner —', 'hengegroup-theme'),
        ),
        '',
        'hengegroup-theme-job-category',
    );

    $employment_markup = '';

    foreach (hengegroup_theme_get_job_employment_types() as $value => $label) {
        $employment_markup .= sprintf(
            '<label style="display:inline-block;margin:0 16px 4px 0"><input type="checkbox" name="%s[]" value="%s"%s> %s</label>',
            esc_attr($name('employment_types')),
            esc_attr($value),
            checked(in_array($value, $job['employment_types'], true), true, false),
            esc_html($label),
        );
    }

    hengegroup_theme_render_job_admin_row(
        __('Anstellungsart', 'hengegroup-theme'),
        $employment_markup,
    );

    hengegroup_theme_render_job_admin_row(
        __('Gültig bis', 'hengegroup-theme'),
        sprintf(
            '<input type="date" id="hengegroup-theme-job-valid-through" name="%s" value="%s">',
            esc_attr($name('valid_through')),
            esc_attr($job['valid_through']),
        ),
        __(
            'Nach diesem Tag verschwindet die Stelle automatisch aus allen Listen und leitet auf die ' .
                'Karriereseite weiter. Google verlangt für Stellenanzeigen ein Ablaufdatum.',
            'hengegroup-theme',
        ),
        'hengegroup-theme-job-valid-through',
    );

    hengegroup_theme_render_job_admin_row(
        __('Eintritt', 'hengegroup-theme'),
        sprintf(
            '<label style="margin-right:16px"><input type="checkbox" name="%1$s" value="1"%2$s> %3$s</label><input type="date" id="hengegroup-theme-job-start-date" name="%4$s" value="%5$s" aria-label="%6$s">',
            esc_attr($name('start_immediately')),
            checked($job['start_immediately'], true, false),
            esc_html__('ab sofort', 'hengegroup-theme'),
            esc_attr($name('start_date')),
            esc_attr($job['start_date']),
            esc_attr__('Eintrittsdatum', 'hengegroup-theme'),
        ),
        __('Datum nur, wenn nicht "ab sofort".', 'hengegroup-theme'),
    );

    hengegroup_theme_render_job_admin_row(
        __('Gehalt (brutto, EUR)', 'hengegroup-theme'),
        sprintf(
            '<input type="number" min="0" step="0.01" name="%1$s" value="%2$s" placeholder="%3$s" style="width:120px" aria-label="%3$s"> – <input type="number" min="0" step="0.01" name="%4$s" value="%5$s" placeholder="%6$s" style="width:120px" aria-label="%6$s"> %7$s %8$s',
            esc_attr($name('salary_min')),
            esc_attr($job['salary']['min'] !== null ? (string) $job['salary']['min'] : ''),
            esc_attr__('von', 'hengegroup-theme'),
            esc_attr($name('salary_max')),
            esc_attr($job['salary']['max'] !== null ? (string) $job['salary']['max'] : ''),
            esc_attr__('bis', 'hengegroup-theme'),
            esc_html__('pro', 'hengegroup-theme'),
            hengegroup_theme_get_job_admin_select(
                'hengegroup-theme-job-salary-unit',
                $name('salary_unit'),
                hengegroup_theme_get_job_salary_units(),
                $job['salary']['unit'],
            ),
        ),
        __(
            'Ein Wert genügt für ein festes Gehalt ("ab …" bzw. "bis …"). Wird auf der Seite und ' .
                'in der Google-Jobsuche angezeigt.',
            'hengegroup-theme',
        ),
    );

    hengegroup_theme_render_job_admin_row(
        __('Arbeitsmodell', 'hengegroup-theme'),
        hengegroup_theme_get_job_admin_select(
            'hengegroup-theme-job-work-model',
            $name('work_model'),
            hengegroup_theme_get_job_work_models(),
            $job['work_model'],
        ),
        '',
        'hengegroup-theme-job-work-model',
    );

    $experience_options = [];

    foreach (hengegroup_theme_get_job_experience_options() as $months => $label) {
        $experience_options[(string) $months] = $label;
    }

    hengegroup_theme_render_job_admin_row(
        __('Berufserfahrung', 'hengegroup-theme'),
        hengegroup_theme_get_job_admin_select(
            'hengegroup-theme-job-experience',
            $name('experience_months'),
            $experience_options,
            $job['experience_months'] !== null ? (string) $job['experience_months'] : '',
            __('— nicht angegeben —', 'hengegroup-theme'),
        ),
        '',
        'hengegroup-theme-job-experience',
    );

    hengegroup_theme_render_job_admin_row(
        __('Abschluss', 'hengegroup-theme'),
        hengegroup_theme_get_job_admin_select(
            'hengegroup-theme-job-education',
            $name('education'),
            hengegroup_theme_get_job_education_levels(),
            $job['education'],
            __('— nicht angegeben —', 'hengegroup-theme'),
        ),
        '',
        'hengegroup-theme-job-education',
    );

    $keys = hengegroup_theme_get_job_meta_keys();
    $textareas = [
        'benefits' => [
            __('Wir bieten dir', 'hengegroup-theme'),
            __(
                'Ein Punkt pro Zeile. Leer = Standard-Benefits des Unternehmens.',
                'hengegroup-theme',
            ),
        ],
        'profile' => [
            __('Dein Profil', 'hengegroup-theme'),
            __('Ein Punkt pro Zeile.', 'hengegroup-theme'),
        ],
        'tasks' => [
            __('Deine Aufgaben', 'hengegroup-theme'),
            __('Ein Punkt pro Zeile.', 'hengegroup-theme'),
        ],
    ];

    foreach ($textareas as $field => [$label, $description]) {
        hengegroup_theme_render_job_admin_row(
            $label,
            sprintf(
                '<textarea id="hengegroup-theme-job-%1$s" name="%2$s" rows="6" class="large-text">%3$s</textarea>',
                esc_attr($field),
                esc_attr($name($field)),
                esc_textarea((string) get_post_meta($post->ID, $keys[$field], true)),
            ),
            $description,
            'hengegroup-theme-job-' . $field,
        );
    }

    hengegroup_theme_render_job_admin_row(
        __('Status', 'hengegroup-theme'),
        sprintf(
            '<label><input type="checkbox" name="%s" value="1"%s> %s</label>',
            esc_attr($name('filled')),
            checked($job['filled'], true, false),
            esc_html__('Stelle ist besetzt (sofort aus allen Listen nehmen)', 'hengegroup-theme'),
        ),
    );

    hengegroup_theme_render_job_admin_row(
        __('Alte Stellen-ID', 'hengegroup-theme'),
        sprintf(
            '<input type="text" id="hengegroup-theme-job-legacy-id" name="%s" value="%s" class="regular-text" placeholder="job-67">',
            esc_attr($name('legacy_id')),
            esc_attr($job['legacy_id']),
        ),
        __(
            'Nur für übernommene Anzeigen der alten Website: /karriere/?job=<ID> wird dauerhaft ' .
                'hierher weitergeleitet. Dient zugleich als Referenznummer.',
            'hengegroup-theme',
        ),
        'hengegroup-theme-job-legacy-id',
    );

    echo '</tbody></table>';
}

function hengegroup_theme_sanitize_job_amount(mixed $value): string
{
    $value = str_replace(',', '.', trim((string) $value));

    return is_numeric($value) && (float) $value >= 0 ? (string) (float) $value : '';
}

function hengegroup_theme_action_save_post_job_details(int $post_id): void
{
    if (
        !isset($_POST['hengegroup_theme_job_details_nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['hengegroup_theme_job_details_nonce'])),
            'hengegroup_theme_save_job_details',
        )
    ) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    // Jeder Wert wird unten einzeln gegen eine Allow-Liste geprueft bzw. sanitisiert, bevor er
    // gespeichert wird -- phpcs sieht diese nachgelagerte Pruefung von dieser Zeile aus nicht.
    $data =
        isset($_POST['hengegroup_theme_job']) && is_array($_POST['hengegroup_theme_job'])
            ? wp_unslash($_POST['hengegroup_theme_job']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            : [];
    $keys = hengegroup_theme_get_job_meta_keys();

    $company_id = absint($data['company'] ?? 0);
    wp_set_object_terms(
        $post_id,
        $company_id > 0 ? [$company_id] : [],
        HENGEGROUP_THEME_JOB_COMPANY_TAXONOMY,
    );

    $location_ids = array_values(
        array_filter(array_map('absint', (array) ($data['locations'] ?? []))),
    );
    wp_set_object_terms($post_id, $location_ids, HENGEGROUP_THEME_JOB_LOCATION_TAXONOMY);

    $category_id = absint($data['category'] ?? 0);
    wp_set_object_terms(
        $post_id,
        $category_id > 0 ? [$category_id] : [],
        HENGEGROUP_THEME_JOB_CATEGORY_TAXONOMY,
    );

    $employment_types = array_values(
        array_intersect(
            array_map('sanitize_text_field', (array) ($data['employment_types'] ?? [])),
            array_keys(hengegroup_theme_get_job_employment_types()),
        ),
    );

    if ($employment_types !== []) {
        update_post_meta($post_id, $keys['employment_types'], $employment_types);
    } else {
        delete_post_meta($post_id, $keys['employment_types']);
    }

    foreach (['valid_through', 'start_date'] as $field) {
        $date = sanitize_text_field((string) ($data[$field] ?? ''));
        hengegroup_theme_update_or_delete_post_meta(
            $post_id,
            $keys[$field],
            hengegroup_theme_is_valid_job_date($date) ? $date : '',
        );
    }

    hengegroup_theme_update_or_delete_post_meta(
        $post_id,
        $keys['start_immediately'],
        !empty($data['start_immediately']) ? '1' : '',
    );
    hengegroup_theme_update_or_delete_post_meta(
        $post_id,
        $keys['salary_min'],
        hengegroup_theme_sanitize_job_amount($data['salary_min'] ?? ''),
    );
    hengegroup_theme_update_or_delete_post_meta(
        $post_id,
        $keys['salary_max'],
        hengegroup_theme_sanitize_job_amount($data['salary_max'] ?? ''),
    );

    $allow_listed = [
        'salary_unit' => array_keys(hengegroup_theme_get_job_salary_units()),
        'work_model' => array_keys(hengegroup_theme_get_job_work_models()),
        'education' => array_keys(hengegroup_theme_get_job_education_levels()),
        'experience_months' => array_map(
            'strval',
            array_keys(hengegroup_theme_get_job_experience_options()),
        ),
    ];

    foreach ($allow_listed as $field => $allowed) {
        $value = sanitize_text_field((string) ($data[$field] ?? ''));
        hengegroup_theme_update_or_delete_post_meta(
            $post_id,
            $keys[$field],
            in_array($value, $allowed, true) ? $value : '',
        );
    }

    foreach (['tasks', 'profile', 'benefits'] as $field) {
        hengegroup_theme_update_or_delete_post_meta(
            $post_id,
            $keys[$field],
            trim(sanitize_textarea_field((string) ($data[$field] ?? ''))),
        );
    }

    hengegroup_theme_update_or_delete_post_meta(
        $post_id,
        $keys['filled'],
        !empty($data['filled']) ? '1' : '',
    );
    hengegroup_theme_update_or_delete_post_meta(
        $post_id,
        $keys['legacy_id'],
        sanitize_title((string) ($data['legacy_id'] ?? '')),
    );
}
add_action(
    'save_post_' . HENGEGROUP_THEME_JOB_POST_TYPE,
    'hengegroup_theme_action_save_post_job_details',
);

/**
 * Zusatzfelder je Taxonomie: field => [Label, Typ, Beschreibung]. Typ: text | url | email |
 * textarea | image | variant.
 */
function hengegroup_theme_get_job_term_fields(string $taxonomy): array
{
    if ($taxonomy === HENGEGROUP_THEME_JOB_COMPANY_TAXONOMY) {
        return [
            'legal_name' => [
                __('Rechtlicher Name', 'hengegroup-theme'),
                'text',
                __(
                    'z. B. "KOMINEX Minerals + Processing GmbH & Co. KG" -- erscheint als Arbeitgeber in der Google-Jobsuche.',
                    'hengegroup-theme',
                ),
            ],
            'website' => [__('Website', 'hengegroup-theme'), 'url', ''],
            'logo_id' => [__('Logo', 'hengegroup-theme'), 'image', ''],
            'variant' => [
                __('Farbe', 'hengegroup-theme'),
                'variant',
                __(
                    'Farbe der Unternehmens-Pill und der Überschrift auf der Karriereseite.',
                    'hengegroup-theme',
                ),
            ],
            'benefits' => [
                __('Standard-Benefits', 'hengegroup-theme'),
                'textarea',
                __(
                    'Ein Punkt pro Zeile. Gilt für jede Stelle dieses Unternehmens ohne eigene Benefits.',
                    'hengegroup-theme',
                ),
            ],
            'contact_name' => [
                __('Ansprechpartner: Name', 'hengegroup-theme'),
                'text',
                __(
                    'Nur ausfüllen, wenn abweichend vom Standard-Ansprechpartner (Karriere > Einstellungen).',
                    'hengegroup-theme',
                ),
            ],
            'contact_role' => [__('Ansprechpartner: Funktion', 'hengegroup-theme'), 'text', ''],
            'contact_email' => [__('Ansprechpartner: E-Mail', 'hengegroup-theme'), 'email', ''],
            'contact_phone' => [__('Ansprechpartner: Telefon', 'hengegroup-theme'), 'text', ''],
        ];
    }

    if ($taxonomy === HENGEGROUP_THEME_JOB_LOCATION_TAXONOMY) {
        return [
            'street' => [__('Straße und Hausnummer', 'hengegroup-theme'), 'text', ''],
            'postal_code' => [__('PLZ', 'hengegroup-theme'), 'text', ''],
            'locality' => [
                __('Ort', 'hengegroup-theme'),
                'text',
                __('z. B. "Falkenstein/Harz OT Ermsleben"', 'hengegroup-theme'),
            ],
            'region' => [__('Bundesland', 'hengegroup-theme'), 'text', ''],
            'country' => [
                __('Land (ISO-Code)', 'hengegroup-theme'),
                'text',
                __('Zweistellig, z. B. "DE". Leer = DE.', 'hengegroup-theme'),
            ],
            'latitude' => [
                __('Breitengrad', 'hengegroup-theme'),
                'text',
                __(
                    'Optional, z. B. 51.7342 -- verbessert die Standortzuordnung.',
                    'hengegroup-theme',
                ),
            ],
            'longitude' => [__('Längengrad', 'hengegroup-theme'), 'text', ''],
        ];
    }

    return [];
}

function hengegroup_theme_get_job_term_meta_prefix(string $taxonomy): string
{
    return $taxonomy === HENGEGROUP_THEME_JOB_COMPANY_TAXONOMY
        ? '_hengegroup_theme_company_'
        : '_hengegroup_theme_location_';
}

function hengegroup_theme_get_job_term_field_control(
    string $field,
    string $type,
    string $value,
): string {
    $id = 'hengegroup-theme-term-' . $field;
    $name = 'hengegroup_theme_term[' . $field . ']';

    if ($type === 'textarea') {
        return sprintf(
            '<textarea id="%s" name="%s" rows="5" class="large-text">%s</textarea>',
            esc_attr($id),
            esc_attr($name),
            esc_textarea($value),
        );
    }

    if ($type === 'image') {
        ob_start();
        hengegroup_theme_render_seo_image_picker_field($id, $name, (int) $value);

        return (string) ob_get_clean();
    }

    if ($type === 'variant') {
        $variants = hengegroup_theme_get_badge_variants();

        return hengegroup_theme_get_job_admin_select(
            $id,
            $name,
            array_combine($variants, $variants),
            in_array($value, $variants, true) ? $value : $variants[0],
        );
    }

    return sprintf(
        '<input type="%s" id="%s" name="%s" value="%s" class="regular-text">',
        esc_attr($type),
        esc_attr($id),
        esc_attr($name),
        esc_attr($value),
    );
}

function hengegroup_theme_render_job_term_add_fields(string $taxonomy): void
{
    wp_nonce_field('hengegroup_theme_save_job_term', 'hengegroup_theme_job_term_nonce');

    foreach (
        hengegroup_theme_get_job_term_fields($taxonomy)
        as $field => [$label, $type, $description]
    ) {
        printf(
            '<div class="form-field"><label for="%1$s">%2$s</label>%3$s%4$s</div>',
            esc_attr('hengegroup-theme-term-' . $field),
            esc_html($label),
            hengegroup_theme_get_job_term_field_control($field, $type, ''), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $description !== '' ? '<p>' . esc_html($description) . '</p>' : '',
        );
    }
}

function hengegroup_theme_render_job_term_edit_fields(WP_Term $term, string $taxonomy): void
{
    wp_nonce_field('hengegroup_theme_save_job_term', 'hengegroup_theme_job_term_nonce');
    $prefix = hengegroup_theme_get_job_term_meta_prefix($taxonomy);

    foreach (
        hengegroup_theme_get_job_term_fields($taxonomy)
        as $field => [$label, $type, $description]
    ) {
        printf(
            '<tr class="form-field"><th scope="row"><label for="%1$s">%2$s</label></th><td>%3$s%4$s</td></tr>',
            esc_attr('hengegroup-theme-term-' . $field),
            esc_html($label),
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- control markup is escaped field by field inside.
            hengegroup_theme_get_job_term_field_control(
                $field,
                $type,
                (string) get_term_meta($term->term_id, $prefix . $field, true),
            ),
            $description !== '' ? '<p class="description">' . esc_html($description) . '</p>' : '',
        );
    }
}

/**
 * Haengt an den generischen `created_term`/`edited_term` (die als 3. Argument den Taxonomie-Namen
 * uebergeben) statt an `created_{$taxonomy}` -- Letzteres uebergibt dort die Term-Argumente.
 */
function hengegroup_theme_action_save_job_term(int $term_id, int $tt_id, string $taxonomy): void
{
    if (
        !in_array(
            $taxonomy,
            [HENGEGROUP_THEME_JOB_COMPANY_TAXONOMY, HENGEGROUP_THEME_JOB_LOCATION_TAXONOMY],
            true,
        )
    ) {
        return;
    }

    if (
        !isset($_POST['hengegroup_theme_job_term_nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['hengegroup_theme_job_term_nonce'])),
            'hengegroup_theme_save_job_term',
        ) ||
        !current_user_can('manage_categories')
    ) {
        return;
    }

    // Jeder Wert wird unten je Feldtyp einzeln sanitisiert -- siehe Kommentar in
    // hengegroup_theme_action_save_post_job_details().
    $data =
        isset($_POST['hengegroup_theme_term']) && is_array($_POST['hengegroup_theme_term'])
            ? wp_unslash($_POST['hengegroup_theme_term']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            : [];
    $prefix = hengegroup_theme_get_job_term_meta_prefix($taxonomy);

    foreach (hengegroup_theme_get_job_term_fields($taxonomy) as $field => [, $type]) {
        $raw = (string) ($data[$field] ?? '');
        $value = match ($type) {
            'textarea' => trim(sanitize_textarea_field($raw)),
            'url' => esc_url_raw(trim($raw)),
            'email' => sanitize_email($raw),
            'image' => absint($raw) > 0 ? (string) absint($raw) : '',
            'variant' => in_array($raw, hengegroup_theme_get_badge_variants(), true) ? $raw : '',
            default => trim(sanitize_text_field($raw)),
        };

        if (in_array($field, ['latitude', 'longitude'], true)) {
            $value = str_replace(',', '.', $value);
            $value = is_numeric($value) ? $value : '';
        }

        if ($field === 'country') {
            $value = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $value) ?? '', 0, 2));
        }

        if ($value === '') {
            delete_term_meta($term_id, $prefix . $field);
        } else {
            update_term_meta($term_id, $prefix . $field, $value);
        }
    }
}

function hengegroup_theme_register_job_term_field_hooks(): void
{
    foreach (
        [HENGEGROUP_THEME_JOB_COMPANY_TAXONOMY, HENGEGROUP_THEME_JOB_LOCATION_TAXONOMY]
        as $taxonomy
    ) {
        add_action($taxonomy . '_add_form_fields', 'hengegroup_theme_render_job_term_add_fields');
        add_action(
            $taxonomy . '_edit_form_fields',
            'hengegroup_theme_render_job_term_edit_fields',
            10,
            2,
        );
    }
}
hengegroup_theme_register_job_term_field_hooks();
add_action('created_term', 'hengegroup_theme_action_save_job_term', 10, 3);
add_action('edited_term', 'hengegroup_theme_action_save_job_term', 10, 3);

/**
 * Logo-Feld der Unternehmen nutzt dasselbe wp.media()-Bildauswahl-Widget wie die SEO-Box.
 */
function hengegroup_theme_action_admin_enqueue_scripts_job_terms(string $hook_suffix): void
{
    if (!in_array($hook_suffix, ['edit-tags.php', 'term.php'], true)) {
        return;
    }

    $screen = get_current_screen();

    if (
        $screen instanceof WP_Screen &&
        $screen->taxonomy === HENGEGROUP_THEME_JOB_COMPANY_TAXONOMY
    ) {
        hengegroup_theme_enqueue_seo_media_picker();
    }
}
add_action('admin_enqueue_scripts', 'hengegroup_theme_action_admin_enqueue_scripts_job_terms');

/**
 * Spalten "Gültig bis"/Status in der Stellenliste -- abgelaufene Stellen sind so auf einen Blick
 * erkennbar, ohne jede einzeln zu oeffnen.
 */
function hengegroup_theme_filter_manage_job_columns(array $columns): array
{
    $date = $columns['date'] ?? null;
    unset($columns['date']);
    $columns['hengegroup_theme_job_valid'] = __('Gültig bis', 'hengegroup-theme');

    if ($date !== null) {
        $columns['date'] = $date;
    }

    return $columns;
}
add_filter(
    'manage_' . HENGEGROUP_THEME_JOB_POST_TYPE . '_posts_columns',
    'hengegroup_theme_filter_manage_job_columns',
);

function hengegroup_theme_action_manage_job_custom_column(string $column, int $post_id): void
{
    if ($column !== 'hengegroup_theme_job_valid') {
        return;
    }

    $valid_through = (string) get_post_meta(
        $post_id,
        hengegroup_theme_get_job_meta_keys()['valid_through'],
        true,
    );
    $label =
        $valid_through !== ''
            ? date_i18n(get_option('date_format'), strtotime($valid_through))
            : '—';

    if (hengegroup_theme_is_job_expired($post_id)) {
        $label .= ' · ' . __('abgelaufen/besetzt', 'hengegroup-theme');
    }

    echo esc_html($label);
}
add_action(
    'manage_' . HENGEGROUP_THEME_JOB_POST_TYPE . '_posts_custom_column',
    'hengegroup_theme_action_manage_job_custom_column',
    10,
    2,
);

function hengegroup_theme_sanitize_career_options(mixed $input): array
{
    $input = is_array($input) ? $input : [];

    return [
        'career_page_id' => absint($input['career_page_id'] ?? 0),
        'contact_name' => sanitize_text_field((string) ($input['contact_name'] ?? '')),
        'contact_role' => sanitize_text_field((string) ($input['contact_role'] ?? '')),
        'contact_email' => sanitize_email((string) ($input['contact_email'] ?? '')),
        'contact_phone' => sanitize_text_field((string) ($input['contact_phone'] ?? '')),
    ];
}

function hengegroup_theme_action_admin_init_register_career_settings(): void
{
    register_setting('hengegroup_theme_career_options_group', HENGEGROUP_THEME_CAREER_OPTION, [
        'type' => 'array',
        'sanitize_callback' => 'hengegroup_theme_sanitize_career_options',
        'default' => [],
    ]);
}
add_action('admin_init', 'hengegroup_theme_action_admin_init_register_career_settings');

function hengegroup_theme_action_admin_menu_career_settings(): void
{
    add_submenu_page(
        'edit.php?post_type=' . HENGEGROUP_THEME_JOB_POST_TYPE,
        __('Karriere-Einstellungen', 'hengegroup-theme'),
        __('Einstellungen', 'hengegroup-theme'),
        'manage_options',
        'hengegroup-theme-career-settings',
        'hengegroup_theme_render_career_settings_page',
    );
}
add_action('admin_menu', 'hengegroup_theme_action_admin_menu_career_settings');

function hengegroup_theme_render_career_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $options = hengegroup_theme_get_career_options();
    $option = HENGEGROUP_THEME_CAREER_OPTION;
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Karriere-Einstellungen', 'hengegroup-theme'); ?></h1>
        <form action="options.php" method="post">
            <?php settings_fields('hengegroup_theme_career_options_group'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="hengegroup-theme-career-page">
                                <?php esc_html_e('Karriereseite', 'hengegroup-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <?php wp_dropdown_pages([
                                'name' => esc_attr($option . '[career_page_id]'),
                                'id' => 'hengegroup-theme-career-page',
                                'selected' => (int) $options['career_page_id'],
                                'show_option_none' => esc_html__(
                                    '— Seite mit Slug "karriere" —',
                                    'hengegroup-theme',
                                ),
                                'option_none_value' => '0',
                            ]); ?>
                            <p class="description">
                                <?php esc_html_e(
                                    'Die Seite mit dem Block "Offene Stellen". Ziel für abgelaufene Stellen, ' .
                                        'Breadcrumbs und den "Offene Stellen"-Button auf der Startseite. Sie ' .
                                        'sollte unter /karriere/ erreichbar sein.',
                                    'hengegroup-theme',
                                ); ?>
                            </p>
                        </td>
                    </tr>
                    <?php foreach (
                        [
                            'contact_name' => [
                                __('Ansprechpartner: Name', 'hengegroup-theme'),
                                'text',
                            ],
                            'contact_role' => [
                                __('Ansprechpartner: Funktion', 'hengegroup-theme'),
                                'text',
                            ],
                            'contact_email' => [
                                __('Ansprechpartner: E-Mail', 'hengegroup-theme'),
                                'email',
                            ],
                            'contact_phone' => [
                                __('Ansprechpartner: Telefon', 'hengegroup-theme'),
                                'text',
                            ],
                        ]
                        as $field => [$label, $type]
                    ): ?>
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr(
                                    'hengegroup-theme-career-' . $field,
                                ); ?>">
                                    <?php echo esc_html($label); ?>
                                </label>
                            </th>
                            <td>
                                <input
                                    type="<?php echo esc_attr($type); ?>"
                                    id="<?php echo esc_attr(
                                        'hengegroup-theme-career-' . $field,
                                    ); ?>"
                                    name="<?php echo esc_attr($option . '[' . $field . ']'); ?>"
                                    value="<?php echo esc_attr((string) $options[$field]); ?>"
                                    class="regular-text"
                                />
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="description">
                <?php esc_html_e(
                    'Der Ansprechpartner gilt für alle Unternehmen, solange beim Unternehmen kein eigener ' .
                        'hinterlegt ist. Bewerbungen gehen an diese E-Mail-Adresse.',
                    'hengegroup-theme',
                ); ?>
            </p>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
