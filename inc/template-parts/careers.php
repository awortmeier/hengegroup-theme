<?php

declare(strict_types=1);

// Daten- und Render-Helfer fuer Stellenangebote. Nur Funktionen (keine Hooks), damit die Logik per
// Brain Monkey testbar bleibt. Seite, Bloecke und JSON-LD lesen dasselbe Array
// (hengegroup_theme_get_job_data()): Google verlangt, dass alles im JobPosting auch sichtbar ist.

const HENGEGROUP_THEME_JOB_POST_TYPE = 'stellenangebote';
const HENGEGROUP_THEME_JOB_COMPANY_TAXONOMY = 'stellen_unternehmen';
const HENGEGROUP_THEME_JOB_LOCATION_TAXONOMY = 'stellen_standort';
const HENGEGROUP_THEME_JOB_CATEGORY_TAXONOMY = 'stellen_bereich';
const HENGEGROUP_THEME_CAREER_OPTION = 'hengegroup_theme_career_options';
const HENGEGROUP_THEME_APPLICATION_STORAGE_FOLDER = 'hengegroup-bewerbungen';

/**
 * Leere Werte werden geloescht statt gespeichert, damit die NOT-EXISTS-Zweige der Ablaufabfrage greifen.
 */
function hengegroup_theme_get_job_meta_keys(): array
{
    return [
        'employment_types' => '_hengegroup_theme_job_employment_types',
        'valid_through' => '_hengegroup_theme_job_valid_through',
        'start_date' => '_hengegroup_theme_job_start_date',
        'start_immediately' => '_hengegroup_theme_job_start_immediately',
        'salary_min' => '_hengegroup_theme_job_salary_min',
        'salary_max' => '_hengegroup_theme_job_salary_max',
        'salary_unit' => '_hengegroup_theme_job_salary_unit',
        'work_model' => '_hengegroup_theme_job_work_model',
        'experience_months' => '_hengegroup_theme_job_experience_months',
        'education' => '_hengegroup_theme_job_education',
        'filled' => '_hengegroup_theme_job_filled',
        'legacy_id' => '_hengegroup_theme_job_legacy_id',
    ];
}

/**
 * Google-employmentType-Enums mit deutschen Labels. Ausbildung/Minijob laufen ueber OTHER/PART_TIME.
 */
function hengegroup_theme_get_job_employment_types(): array
{
    return [
        'FULL_TIME' => __('Vollzeit', 'hengegroup-theme'),
        'PART_TIME' => __('Teilzeit', 'hengegroup-theme'),
        'TEMPORARY' => __('Befristet', 'hengegroup-theme'),
        'CONTRACTOR' => __('Freie Mitarbeit', 'hengegroup-theme'),
        'INTERN' => __('Praktikum', 'hengegroup-theme'),
        'PER_DIEM' => __('Tageweise', 'hengegroup-theme'),
        'VOLUNTEER' => __('Ehrenamt', 'hengegroup-theme'),
        'OTHER' => __('Sonstiges (z. B. Ausbildung)', 'hengegroup-theme'),
    ];
}

/**
 * baseSalary.unitText (Google akzeptiert genau diese fuenf).
 */
function hengegroup_theme_get_job_salary_units(): array
{
    return [
        'HOUR' => __('Stunde', 'hengegroup-theme'),
        'DAY' => __('Tag', 'hengegroup-theme'),
        'WEEK' => __('Woche', 'hengegroup-theme'),
        'MONTH' => __('Monat', 'hengegroup-theme'),
        'YEAR' => __('Jahr', 'hengegroup-theme'),
    ];
}

/**
 * `remote` -> jobLocationType TELECOMMUTE; `hybrid` bleibt fuer Google ein Vor-Ort-Job (kein Enum).
 */
function hengegroup_theme_get_job_work_models(): array
{
    return [
        'onsite' => __('Vor Ort', 'hengegroup-theme'),
        'hybrid' => __('Hybrid (vor Ort + mobil)', 'hengegroup-theme'),
        'remote' => __('Vollständig remote', 'hengegroup-theme'),
    ];
}

/**
 * -> credentialCategory; `none` wird Googles "no requirements".
 */
function hengegroup_theme_get_job_education_levels(): array
{
    return [
        'none' => __('Keine Voraussetzung', 'hengegroup-theme'),
        'high school' => __('Schulabschluss', 'hengegroup-theme'),
        'professional certificate' => __('Abgeschlossene Berufsausbildung', 'hengegroup-theme'),
        'associate degree' => __('Fachwirt/Techniker o. Ä.', 'hengegroup-theme'),
        'bachelor degree' => __('Bachelor', 'hengegroup-theme'),
        'postgraduate degree' => __('Master/Diplom', 'hengegroup-theme'),
    ];
}

/**
 * Monate Berufserfahrung; 0 = ausdruecklich keine (Google "no requirements"), fehlend = nicht angegeben.
 */
function hengegroup_theme_get_job_experience_options(): array
{
    return [
        0 => __('Keine Berufserfahrung nötig', 'hengegroup-theme'),
        12 => __('Mind. 1 Jahr', 'hengegroup-theme'),
        24 => __('Mind. 2 Jahre', 'hengegroup-theme'),
        36 => __('Mind. 3 Jahre', 'hengegroup-theme'),
        60 => __('Mind. 5 Jahre', 'hengegroup-theme'),
    ];
}

/**
 * Typen des Blocks "Stellen-Liste" mit Ueberschrift; der Typ bestimmt das schema.org-Feld.
 */
function hengegroup_theme_get_job_list_types(): array
{
    return [
        'benefits' => __('Wir bieten dir:', 'hengegroup-theme'),
        'profile' => __('Dein Profil:', 'hengegroup-theme'),
        'tasks' => __('Deine Aufgaben:', 'hengegroup-theme'),
    ];
}

/**
 * Eintraege aller "Stellen-Liste"-Bloecke aus parse_blocks(). Unterstuetzt core/list mit und ohne
 * list-item-Kindbloecke; Unterlisten werden mit eingesammelt.
 */
function hengegroup_theme_extract_job_lists(array $blocks): array
{
    $lists = ['benefits' => [], 'profile' => [], 'tasks' => []];

    foreach ($blocks as $block) {
        if (!is_array($block)) {
            continue;
        }

        $inner_blocks = is_array($block['innerBlocks'] ?? null) ? $block['innerBlocks'] : [];

        if (($block['blockName'] ?? '') === 'hengegroup-theme/stellen-liste') {
            $type = (string) ($block['attrs']['type'] ?? 'benefits');

            if (isset($lists[$type])) {
                $lists[$type] = array_merge(
                    $lists[$type],
                    hengegroup_theme_collect_list_item_texts($inner_blocks),
                );
            }

            continue;
        }

        foreach (hengegroup_theme_extract_job_lists($inner_blocks) as $type => $items) {
            $lists[$type] = array_merge($lists[$type], $items);
        }
    }

    return $lists;
}

function hengegroup_theme_collect_list_item_texts(array $blocks): array
{
    $items = [];
    $to_text = static fn(string $html): string => trim(
        (string) preg_replace(
            '/\s+/u',
            ' ',
            html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ),
    );

    foreach ($blocks as $block) {
        if (!is_array($block)) {
            continue;
        }

        $name = $block['blockName'] ?? '';
        $inner_blocks = is_array($block['innerBlocks'] ?? null) ? $block['innerBlocks'] : [];

        if ($name === 'core/list-item') {
            $text = $to_text((string) ($block['innerHTML'] ?? ''));

            if ($text !== '') {
                $items[] = $text;
            }
        } elseif ($name === 'core/list' && $inner_blocks === []) {
            preg_match_all(
                '/<li[^>]*>(.*?)<\/li>/is',
                (string) ($block['innerHTML'] ?? ''),
                $matches,
            );

            foreach ($matches[1] as $html) {
                $text = $to_text($html);

                if ($text !== '') {
                    $items[] = $text;
                }
            }
        }

        $items = array_merge($items, hengegroup_theme_collect_list_item_texts($inner_blocks));
    }

    return $items;
}

/**
 * Ein Punkt pro Zeile; fuehrende Bullets (aus Word kopiert) werden entfernt.
 */
function hengegroup_theme_parse_job_list_lines(string $text): array
{
    $items = [];

    foreach (preg_split('/\R/u', $text) ?: [] as $line) {
        $line = trim((string) preg_replace('/^\s*(?:[-*•–]\s*)+/u', '', $line));

        if ($line !== '') {
            $items[] = $line;
        }
    }

    return $items;
}

/**
 * "D-06463 Falkenstein/Harz"; andere Laender mit ISO-Code, sonst Term-Name.
 */
function hengegroup_theme_format_job_location_label(array $location): string
{
    $postal_code = trim((string) ($location['postal_code'] ?? ''));
    $locality = trim((string) ($location['locality'] ?? ''));
    $country = strtoupper(trim((string) ($location['country'] ?? '')));

    if ($postal_code === '' && $locality === '') {
        return trim((string) ($location['name'] ?? ''));
    }

    $prefix = $country === 'DE' || $country === '' ? 'D' : $country;
    $place = trim($postal_code . ' ' . $locality);

    return $postal_code !== '' ? $prefix . '-' . $place : $place;
}

/**
 * 15,50 / 3.200 (Nachkommastellen nur wenn vorhanden), ohne WP-Abhaengigkeit.
 */
function hengegroup_theme_format_job_amount(float $amount): string
{
    $decimals = abs($amount - round($amount)) > 0.001 ? 2 : 0;

    return number_format($amount, $decimals, ',', '.');
}

/**
 * "3.200 – 3.800 € brutto pro Monat", "ab …", "bis …"; '' ohne Min und Max.
 */
function hengegroup_theme_format_job_salary(?float $min, ?float $max, string $unit): string
{
    $units = hengegroup_theme_get_job_salary_units();
    $unit_label = $units[$unit] ?? $units['MONTH'];

    if ($min !== null && $max !== null && $max < $min) {
        [$min, $max] = [$max, $min];
    }

    if ($min !== null && $max !== null && abs($max - $min) > 0.001) {
        $amount = sprintf(
            '%s – %s €',
            hengegroup_theme_format_job_amount($min),
            hengegroup_theme_format_job_amount($max),
        );
    } elseif ($min !== null) {
        $amount =
            $max !== null
                ? hengegroup_theme_format_job_amount($min) . ' €'
                : sprintf(
                    /* translators: %s: formatted minimum salary amount. */
                    __('ab %s', 'hengegroup-theme'),
                    hengegroup_theme_format_job_amount($min) . ' €',
                );
    } elseif ($max !== null) {
        $amount = sprintf(
            /* translators: %s: formatted maximum salary amount. */
            __('bis %s', 'hengegroup-theme'),
            hengegroup_theme_format_job_amount($max) . ' €',
        );
    } else {
        return '';
    }

    return sprintf(
        /* translators: 1: salary amount/range incl. currency, 2: pay period (e.g. "Monat"). */
        __('%1$s brutto pro %2$s', 'hengegroup-theme'),
        $amount,
        $unit_label,
    );
}

/**
 * Besetzt oder validThrough < $today (Y-m-d, String-Vergleich reicht). Leeres validThrough laeuft nie ab.
 */
function hengegroup_theme_is_job_expired_on(
    string $valid_through,
    bool $filled,
    string $today,
): bool {
    if ($filled) {
        return true;
    }

    return $valid_through !== '' && $valid_through < $today;
}

function hengegroup_theme_is_valid_job_date(string $date): bool
{
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches) !== 1) {
        return false;
    }

    return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]);
}

/**
 * Fuer Google Jobs fehlende Angaben (Hinweis in der Stellendetails-Box).
 */
function hengegroup_theme_get_job_posting_missing_fields(array $job): array
{
    $missing = [];

    if (empty($job['company'])) {
        $missing[] = __('Unternehmen', 'hengegroup-theme');
    }

    if (empty($job['locations']) && ($job['work_model'] ?? '') !== 'remote') {
        $missing[] = __('Standort', 'hengegroup-theme');
    }

    if (empty($job['employment_types'])) {
        $missing[] = __('Anstellungsart', 'hengegroup-theme');
    }

    if (($job['valid_through'] ?? '') === '') {
        $missing[] = __('Gültig bis', 'hengegroup-theme');
    }

    if (($job['salary']['min'] ?? null) === null && ($job['salary']['max'] ?? null) === null) {
        $missing[] = __('Gehalt', 'hengegroup-theme');
    }

    return $missing;
}

/**
 * schema.org-JobPosting, ohne WP-Aufrufe (unit-getestet). $context: description_html, site_name,
 * site_url, timezone_offset (z. B. "+02:00" fuer validThrough), logo_url, direct_apply.
 */
function hengegroup_theme_build_job_posting_schema(array $job, array $context): array
{
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'JobPosting',
        'title' => (string) $job['title'],
        'description' => (string) ($context['description_html'] ?? ''),
        'datePosted' => (string) $job['date_posted'],
        'url' => (string) $job['url'],
    ];

    $timezone_offset = (string) ($context['timezone_offset'] ?? '+00:00');

    if (($job['valid_through'] ?? '') !== '') {
        $schema['validThrough'] = $job['valid_through'] . 'T23:59:59' . $timezone_offset;
    }

    if (!empty($job['employment_types'])) {
        $schema['employmentType'] =
            count($job['employment_types']) === 1
                ? $job['employment_types'][0]
                : array_values($job['employment_types']);
    }

    $site_name = (string) ($context['site_name'] ?? '');
    $site_url = (string) ($context['site_url'] ?? '');
    $company = is_array($job['company'] ?? null) ? $job['company'] : null;

    if ($company !== null) {
        $organization = [
            '@type' => 'Organization',
            'name' => $company['legal_name'] !== '' ? $company['legal_name'] : $company['name'],
        ];

        // Unternehmensseite als `url`, nicht `sameAs` (das ist fuer fremde Auftritte).
        if (($company['url'] ?? '') !== '') {
            $organization['url'] = $company['url'];
        }

        if (($context['logo_url'] ?? '') !== '') {
            $organization['logo'] = $context['logo_url'];
        }

        if ($site_name !== '' && $site_name !== $organization['name']) {
            $organization['parentOrganization'] = array_filter([
                '@type' => 'Organization',
                'name' => $site_name,
                'url' => $site_url,
            ]);
        }

        $schema['hiringOrganization'] = $organization;
    } elseif ($site_name !== '') {
        $schema['hiringOrganization'] = array_filter([
            '@type' => 'Organization',
            'name' => $site_name,
            'sameAs' => $site_url,
        ]);
    }

    $places = [];

    foreach ($job['locations'] ?? [] as $location) {
        $address = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $location['street'] ?? '',
            'postalCode' => $location['postal_code'] ?? '',
            'addressLocality' => $location['locality'] ?? '',
            'addressRegion' => $location['region'] ?? '',
            'addressCountry' => ($location['country'] ?? '') !== '' ? $location['country'] : 'DE',
        ]);

        $place = ['@type' => 'Place', 'address' => $address];

        if (($location['latitude'] ?? '') !== '' && ($location['longitude'] ?? '') !== '') {
            $place['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $location['latitude'],
                'longitude' => (float) $location['longitude'],
            ];
        }

        $places[] = $place;
    }

    if ($places !== []) {
        $schema['jobLocation'] = count($places) === 1 ? $places[0] : $places;
    }

    if (($job['work_model'] ?? '') === 'remote') {
        $schema['jobLocationType'] = 'TELECOMMUTE';
        $schema['applicantLocationRequirements'] = [
            '@type' => 'Country',
            'name' =>
                ($job['locations'][0]['country'] ?? '') !== ''
                    ? $job['locations'][0]['country']
                    : 'DE',
        ];
    }

    $salary_min = $job['salary']['min'] ?? null;
    $salary_max = $job['salary']['max'] ?? null;

    if ($salary_min !== null || $salary_max !== null) {
        $value = ['@type' => 'QuantitativeValue', 'unitText' => $job['salary']['unit']];

        if (
            $salary_min !== null &&
            $salary_max !== null &&
            abs($salary_max - $salary_min) > 0.001
        ) {
            $value['minValue'] = min($salary_min, $salary_max);
            $value['maxValue'] = max($salary_min, $salary_max);
        } else {
            $value['value'] = $salary_min ?? $salary_max;
        }

        $schema['baseSalary'] = [
            '@type' => 'MonetaryAmount',
            'currency' => 'EUR',
            'value' => $value,
        ];
    }

    if (($job['reference'] ?? '') !== '') {
        $schema['identifier'] = [
            '@type' => 'PropertyValue',
            'name' => $schema['hiringOrganization']['name'] ?? $site_name,
            'value' => $job['reference'],
        ];
    }

    if (!empty($job['start_immediately'])) {
        $schema['jobStartDate'] = $job['date_posted'];
    } elseif (($job['start_date'] ?? '') !== '') {
        $schema['jobStartDate'] = $job['start_date'];
    }

    if (($job['experience_months'] ?? null) !== null) {
        $schema['experienceRequirements'] =
            (int) $job['experience_months'] === 0
                ? 'no requirements'
                : [
                    '@type' => 'OccupationalExperienceRequirements',
                    'monthsOfExperience' => (int) $job['experience_months'],
                ];
    }

    if (($job['education'] ?? '') !== '') {
        $schema['educationRequirements'] =
            $job['education'] === 'none'
                ? 'no requirements'
                : [
                    '@type' => 'EducationalOccupationalCredential',
                    'credentialCategory' => $job['education'],
                ];
    }

    if (($job['category'] ?? '') !== '') {
        $schema['occupationalCategory'] = $job['category'];
    }

    foreach (
        ['responsibilities' => 'tasks', 'qualifications' => 'profile', 'jobBenefits' => 'benefits']
        as $property => $key
    ) {
        if (!empty($job[$key])) {
            $schema[$property] = implode(', ', $job[$key]);
        }
    }

    $schema['directApply'] = !empty($context['direct_apply']);

    return $schema;
}

function hengegroup_theme_get_career_options(): array
{
    $defaults = [
        'career_page_id' => 0,
        'contact_name' => '',
        'contact_role' => '',
        'contact_email' => '',
        'contact_phone' => '',
    ];

    $stored = get_option(HENGEGROUP_THEME_CAREER_OPTION, []);

    return array_merge($defaults, is_array($stored) ? $stored : []);
}

/**
 * Karriereseite (Einstellung, sonst Slug "karriere", sonst Startseite).
 */
function hengegroup_theme_get_career_page_url(): string
{
    $page_id = (int) hengegroup_theme_get_career_options()['career_page_id'];

    if ($page_id <= 0) {
        $page = get_page_by_path('karriere');
        $page_id = $page instanceof WP_Post ? (int) $page->ID : 0;
    }

    if ($page_id > 0 && get_post_status($page_id) === 'publish') {
        return (string) get_permalink($page_id);
    }

    return home_url('/');
}

function hengegroup_theme_get_published_page_url(int $page_id): string
{
    return $page_id > 0 && get_post_status($page_id) === 'publish'
        ? (string) get_permalink($page_id)
        : '';
}

function hengegroup_theme_get_job_company(int $term_id): ?array
{
    $term = get_term($term_id, HENGEGROUP_THEME_JOB_COMPANY_TAXONOMY);

    if (!($term instanceof WP_Term)) {
        return null;
    }

    $variant = (string) get_term_meta($term->term_id, '_hengegroup_theme_company_variant', true);
    $variants = hengegroup_theme_get_badge_variants();

    return [
        'term_id' => (int) $term->term_id,
        'name' => $term->name,
        'legal_name' => trim(
            (string) get_term_meta($term->term_id, '_hengegroup_theme_company_legal_name', true),
        ),
        'url' => hengegroup_theme_get_published_page_url(
            (int) get_term_meta($term->term_id, '_hengegroup_theme_company_page_id', true),
        ),
        'benefits' => hengegroup_theme_parse_job_list_lines(
            (string) get_term_meta($term->term_id, '_hengegroup_theme_company_benefits', true),
        ),
        'logo_id' => (int) get_term_meta($term->term_id, '_hengegroup_theme_company_logo_id', true),
        'address' => trim(
            (string) get_term_meta($term->term_id, '_hengegroup_theme_company_address', true),
        ),
        'variant' => in_array($variant, $variants, true) ? $variant : $variants[0],
        'contact' => [
            'name' => (string) get_term_meta(
                $term->term_id,
                '_hengegroup_theme_company_contact_name',
                true,
            ),
            'role' => (string) get_term_meta(
                $term->term_id,
                '_hengegroup_theme_company_contact_role',
                true,
            ),
            'email' => (string) get_term_meta(
                $term->term_id,
                '_hengegroup_theme_company_contact_email',
                true,
            ),
            'phone' => (string) get_term_meta(
                $term->term_id,
                '_hengegroup_theme_company_contact_phone',
                true,
            ),
        ],
    ];
}

function hengegroup_theme_get_job_location(WP_Term $term): array
{
    $location = ['name' => $term->name, 'term_id' => (int) $term->term_id];

    foreach (
        ['street', 'postal_code', 'locality', 'region', 'country', 'latitude', 'longitude']
        as $field
    ) {
        $location[$field] = trim(
            (string) get_term_meta($term->term_id, '_hengegroup_theme_location_' . $field, true),
        );
    }

    $location['label'] = hengegroup_theme_format_job_location_label($location);

    return $location;
}

/**
 * Ansprechpartner des Unternehmens (wenn Name oder E-Mail gepflegt), sonst der Standard.
 */
function hengegroup_theme_get_job_contact(?array $company): array
{
    $company_contact = $company['contact'] ?? [];

    if (
        trim((string) ($company_contact['name'] ?? '')) !== '' ||
        trim((string) ($company_contact['email'] ?? '')) !== ''
    ) {
        return array_map('trim', $company_contact);
    }

    $options = hengegroup_theme_get_career_options();

    return [
        'name' => trim((string) $options['contact_name']),
        'role' => trim((string) $options['contact_role']),
        'email' => trim((string) $options['contact_email']),
        'phone' => trim((string) $options['contact_phone']),
    ];
}

/**
 * Normalisiertes Daten-Array einer Stelle; Benefits fallen auf die des Unternehmens zurueck.
 */
function hengegroup_theme_get_job_data(int $post_id): array
{
    $keys = hengegroup_theme_get_job_meta_keys();
    $meta = static fn(string $key): string => trim(
        (string) get_post_meta($post_id, $keys[$key], true),
    );

    $company_terms = get_the_terms($post_id, HENGEGROUP_THEME_JOB_COMPANY_TAXONOMY);
    $company =
        is_array($company_terms) && $company_terms !== []
            ? hengegroup_theme_get_job_company((int) $company_terms[0]->term_id)
            : null;

    $location_terms = get_the_terms($post_id, HENGEGROUP_THEME_JOB_LOCATION_TAXONOMY);
    $locations = is_array($location_terms)
        ? array_map('hengegroup_theme_get_job_location', $location_terms)
        : [];

    $category_terms = get_the_terms($post_id, HENGEGROUP_THEME_JOB_CATEGORY_TAXONOMY);

    $employment_types = get_post_meta($post_id, $keys['employment_types'], true);
    $employment_types = array_values(
        array_intersect(
            is_array($employment_types) ? $employment_types : [],
            array_keys(hengegroup_theme_get_job_employment_types()),
        ),
    );

    $salary_unit = $meta('salary_unit');
    $experience = $meta('experience_months');
    $legacy_id = $meta('legacy_id');
    $lists = hengegroup_theme_extract_job_lists(
        parse_blocks((string) get_post_field('post_content', $post_id)),
    );

    return [
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'url' => (string) get_permalink($post_id),
        'date_posted' => (string) get_the_date('Y-m-d', $post_id),
        'company' => $company,
        'locations' => $locations,
        'category' =>
            is_array($category_terms) && $category_terms !== [] ? $category_terms[0]->name : '',
        'employment_types' => $employment_types,
        'valid_through' => $meta('valid_through'),
        'start_date' => $meta('start_date'),
        'start_immediately' => $meta('start_immediately') === '1',
        'salary' => [
            'min' => $meta('salary_min') !== '' ? (float) $meta('salary_min') : null,
            'max' => $meta('salary_max') !== '' ? (float) $meta('salary_max') : null,
            'unit' => array_key_exists($salary_unit, hengegroup_theme_get_job_salary_units())
                ? $salary_unit
                : 'MONTH',
        ],
        'work_model' => array_key_exists(
            $meta('work_model'),
            hengegroup_theme_get_job_work_models(),
        )
            ? $meta('work_model')
            : 'onsite',
        'experience_months' => $experience !== '' ? (int) $experience : null,
        'education' => array_key_exists(
            $meta('education'),
            hengegroup_theme_get_job_education_levels(),
        )
            ? $meta('education')
            : '',
        'tasks' => $lists['tasks'],
        'profile' => $lists['profile'],
        'benefits' => $lists['benefits'] !== [] ? $lists['benefits'] : $company['benefits'] ?? [],
        'filled' => $meta('filled') === '1',
        'legacy_id' => $legacy_id,
        'reference' => $legacy_id !== '' ? $legacy_id : 'job-' . $post_id,
        'contact' => hengegroup_theme_get_job_contact($company),
    ];
}

function hengegroup_theme_is_job_expired(int $post_id): bool
{
    $keys = hengegroup_theme_get_job_meta_keys();

    return hengegroup_theme_is_job_expired_on(
        trim((string) get_post_meta($post_id, $keys['valid_through'], true)),
        get_post_meta($post_id, $keys['filled'], true) === '1',
        wp_date('Y-m-d'),
    );
}

/**
 * Nur aktive Stellen; fuer alle Frontend-Abfragen und die Sitemap.
 */
function hengegroup_theme_get_active_job_meta_query(): array
{
    $keys = hengegroup_theme_get_job_meta_keys();

    return [
        'relation' => 'AND',
        [
            'relation' => 'OR',
            ['key' => $keys['filled'], 'compare' => 'NOT EXISTS'],
            ['key' => $keys['filled'], 'value' => '1', 'compare' => '!='],
        ],
        [
            'relation' => 'OR',
            ['key' => $keys['valid_through'], 'compare' => 'NOT EXISTS'],
            [
                'key' => $keys['valid_through'],
                'value' => wp_date('Y-m-d'),
                'compare' => '>=',
                'type' => 'DATE',
            ],
        ],
    ];
}

function hengegroup_theme_get_active_job_ids(int $limit = -1, int $company_id = 0): array
{
    $query_args = [
        'post_type' => HENGEGROUP_THEME_JOB_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => $limit > 0 ? $limit : -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'fields' => 'ids',
        'no_found_rows' => true,
        'meta_query' => hengegroup_theme_get_active_job_meta_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
    ];

    if ($company_id > 0) {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
        $query_args['tax_query'] = [
            [
                'taxonomy' => HENGEGROUP_THEME_JOB_COMPANY_TAXONOMY,
                'field' => 'term_id',
                'terms' => $company_id,
            ],
        ];
    }

    $query = new WP_Query($query_args);

    return array_map('intval', $query->posts);
}

/**
 * Literale Klassen, damit Tailwind sie findet.
 */
function hengegroup_theme_get_job_company_text_class(string $variant): string
{
    $classes = [
        'henge-blue' => 'text-henge-blue',
        'henge-green' => 'text-henge-green',
        'henge-grey' => 'text-henge-grey',
        'grey-dark' => 'text-grey-dark',
    ];

    return $classes[$variant] ?? 'text-grey-dark';
}

/**
 * Stellen-Zeile als <li>. Die Textfarbe steht zusaetzlich am inneren <span>, weil im Editor sonst
 * die Link-Farbe aus theme.json gewinnt.
 */
function hengegroup_theme_render_job_row(array $job, bool $with_company_badge): string
{
    $badge =
        $with_company_badge && $job['company'] !== null
            ? hengegroup_theme_render_label_badge(
                $job['company']['name'],
                $job['company']['variant'],
                'shrink-0',
            )
            : '';

    $arrow = hengegroup_theme_render_icon([
        'name' => 'arrow-right',
        'set' => 'lucide',
        'class' => 'size-5 shrink-0 text-grey-dark transition-transform group-hover:translate-x-1',
    ]);

    return '<li>' .
        hengegroup_theme_render_card([
            'href' => $job['url'],
            'content' => sprintf(
                '<span class="flex items-center justify-between gap-4"><span class="flex min-w-0 flex-wrap items-center gap-x-4 gap-y-2">%1$s<span class="text-lg leading-snug font-semibold text-grey-dark">%2$s</span></span>%3$s</span>',
                $badge,
                esc_html($job['title']),
                $arrow,
            ),
            'class' => 'group text-grey-dark',
        ]) .
        '</li>';
}

/**
 * Aktive Stellen nach Unternehmen; ohne Unternehmen unter dem Seitennamen.
 */
function hengegroup_theme_render_jobs_grouped(bool $on_dark = false): string
{
    $groups = [];

    foreach (hengegroup_theme_get_active_job_ids() as $job_id) {
        $job = hengegroup_theme_get_job_data($job_id);
        $group_key = $job['company'] !== null ? (string) $job['company']['term_id'] : '0';

        if (!isset($groups[$group_key])) {
            $groups[$group_key] = ['company' => $job['company'], 'locations' => [], 'jobs' => []];
        }

        foreach ($job['locations'] as $location) {
            $groups[$group_key]['locations'][$location['label']] = true;
        }

        $groups[$group_key]['jobs'][] = $job;
    }

    if ($groups === []) {
        return '';
    }

    uasort(
        $groups,
        static fn(array $a, array $b): int => strcasecmp(
            $a['company']['name'] ?? '~',
            $b['company']['name'] ?? '~',
        ),
    );

    $markup = '';

    foreach ($groups as $group) {
        $name = $group['company']['name'] ?? get_bloginfo('name');
        $variant = $group['company']['variant'] ?? 'grey-dark';
        $text_class =
            $on_dark && $variant === 'grey-dark'
                ? 'text-grey-light'
                : hengegroup_theme_get_job_company_text_class($variant);
        $location_label = implode(' · ', array_keys($group['locations']));
        $rows = implode(
            '',
            array_map(
                static fn(array $job): string => hengegroup_theme_render_job_row($job, false),
                $group['jobs'],
            ),
        );

        $location_markup =
            $location_label !== ''
                ? sprintf(
                    '<p class="%1$s">%2$s%3$s</p>',
                    esc_attr(
                        'flex items-center gap-1.5 text-sm ' .
                            ($on_dark ? 'text-grey-light/60' : 'text-grey-dark/60'),
                    ),
                    hengegroup_theme_render_icon([
                        'name' => 'map-pin',
                        'set' => 'lucide',
                        'class' => 'size-3.5 shrink-0',
                    ]),
                    esc_html($location_label),
                )
                : '';

        $markup .= sprintf(
            '<div class="flex flex-col gap-4.5" data-slot="job-group"><div class="flex flex-wrap items-center gap-x-3.5 gap-y-1">%1$s%2$s</div><ul class="flex flex-col gap-3">%3$s</ul></div>',
            hengegroup_theme_render_typography([
                'variant' => 'body-lg',
                'tag' => 'h2',
                'text' => $name,
                'class' => 'font-accent font-bold ' . $text_class,
            ]),
            $location_markup,
            $rows,
        );
    }

    return '<div class="flex flex-col gap-12">' . $markup . '</div>';
}

/**
 * Mit $company_id nur dessen Stellen, dann ohne Unternehmens-Badge.
 */
function hengegroup_theme_render_job_teaser_list(int $limit, int $company_id = 0): string
{
    $rows = implode(
        '',
        array_map(
            static fn(int $job_id): string => hengegroup_theme_render_job_row(
                hengegroup_theme_get_job_data($job_id),
                $company_id <= 0,
            ),
            hengegroup_theme_get_active_job_ids(max(1, $limit), $company_id),
        ),
    );

    return $rows !== '' ? '<ul class="flex flex-col gap-3">' . $rows . '</ul>' : '';
}

/**
 * Literale Icon-Konfigurationen, damit find-lucide-icons.php sie findet. Die Labels gehen auch an
 * den Editor (theme-blocks.php).
 */
function hengegroup_theme_get_benefit_icons(): array
{
    return [
        'coins' => [
            __('Geld / Vorsorge', 'hengegroup-theme'),
            ['name' => 'coins', 'set' => 'lucide'],
        ],
        'euro' => [__('Euro', 'hengegroup-theme'), ['name' => 'euro', 'set' => 'lucide']],
        'sun' => [__('Sonne / Urlaub', 'hengegroup-theme'), ['name' => 'sun', 'set' => 'lucide']],
        'tree-palm' => [
            __('Palme / Urlaub', 'hengegroup-theme'),
            ['name' => 'tree-palm', 'set' => 'lucide'],
        ],
        'snowflake' => [
            __('Klima', 'hengegroup-theme'),
            ['name' => 'snowflake', 'set' => 'lucide'],
        ],
        'coffee' => [__('Kaffee', 'hengegroup-theme'), ['name' => 'coffee', 'set' => 'lucide']],
        'utensils' => [__('Essen', 'hengegroup-theme'), ['name' => 'utensils', 'set' => 'lucide']],
        'party-popper' => [
            __('Events', 'hengegroup-theme'),
            ['name' => 'party-popper', 'set' => 'lucide'],
        ],
        'graduation-cap' => [
            __('Weiterbildung', 'hengegroup-theme'),
            ['name' => 'graduation-cap', 'set' => 'lucide'],
        ],
        'heart-pulse' => [
            __('Gesundheit', 'hengegroup-theme'),
            ['name' => 'heart-pulse', 'set' => 'lucide'],
        ],
        'dumbbell' => [__('Sport', 'hengegroup-theme'), ['name' => 'dumbbell', 'set' => 'lucide']],
        'clipboard-check' => [
            __('Onboarding', 'hengegroup-theme'),
            ['name' => 'clipboard-check', 'set' => 'lucide'],
        ],
        'monitor' => [
            __('Arbeitsplatz', 'hengegroup-theme'),
            ['name' => 'monitor', 'set' => 'lucide'],
        ],
        'bike' => [__('Fahrrad', 'hengegroup-theme'), ['name' => 'bike', 'set' => 'lucide']],
        'car' => [__('Auto', 'hengegroup-theme'), ['name' => 'car', 'set' => 'lucide']],
        'clock' => [__('Arbeitszeit', 'hengegroup-theme'), ['name' => 'clock', 'set' => 'lucide']],
        'scale' => [
            __('Work-Life-Balance', 'hengegroup-theme'),
            ['name' => 'scale', 'set' => 'lucide'],
        ],
        'baby' => [__('Familie', 'hengegroup-theme'), ['name' => 'baby', 'set' => 'lucide']],
        'users' => [
            __('Team / Empfehlung', 'hengegroup-theme'),
            ['name' => 'users', 'set' => 'lucide'],
        ],
        'handshake' => [
            __('Zusammenarbeit', 'hengegroup-theme'),
            ['name' => 'handshake', 'set' => 'lucide'],
        ],
        'medal' => [
            __('Auszeichnung / Treue', 'hengegroup-theme'),
            ['name' => 'medal', 'set' => 'lucide'],
        ],
        'gift' => [
            __('Geschenk / Prämie', 'hengegroup-theme'),
            ['name' => 'gift', 'set' => 'lucide'],
        ],
        'trending-up' => [
            __('Entwicklung / Wachstum', 'hengegroup-theme'),
            ['name' => 'trending-up', 'set' => 'lucide'],
        ],
        'leaf' => [__('Nachhaltigkeit', 'hengegroup-theme'), ['name' => 'leaf', 'set' => 'lucide']],
    ];
}

/**
 * Gruppierte Stellen oder Hinweis auf Initiativbewerbung.
 */
function hengegroup_theme_render_open_jobs_list(string $empty_text, bool $on_dark = false): string
{
    $groups_markup = hengegroup_theme_render_jobs_grouped($on_dark);

    if ($groups_markup !== '' || trim($empty_text) === '') {
        return $groups_markup;
    }

    return hengegroup_theme_render_typography([
        'variant' => 'body-base',
        'text' => trim($empty_text),
    ]);
}

/**
 * Ansprechpartner des Blocks "Offene Stellen" (wenn Name oder E-Mail gepflegt), sonst der Standard.
 */
function hengegroup_theme_get_open_jobs_contact(array $attributes): array
{
    $contact = [
        'name' => trim((string) ($attributes['contactName'] ?? '')),
        'role' => trim((string) ($attributes['contactRole'] ?? '')),
        'email' => trim((string) ($attributes['contactEmail'] ?? '')),
        'phone' => trim((string) ($attributes['contactPhone'] ?? '')),
    ];

    return $contact['name'] !== '' || $contact['email'] !== ''
        ? $contact
        : hengegroup_theme_get_job_contact(null);
}

function hengegroup_theme_render_benefit_icon(string $key): string
{
    $icons = hengegroup_theme_get_benefit_icons();
    $icon_config = $icons[$key][1] ?? reset($icons)[1];
    $icon_config['class'] = 'size-5.5 text-grey-light';

    return hengegroup_theme_render_icon($icon_config);
}

function hengegroup_theme_get_job_application_options(): array
{
    return [
        'experience' => [
            'entry' => __('Berufseinsteiger*in', 'hengegroup-theme'),
            '1-3' => __('1–3 Jahre', 'hengegroup-theme'),
            '3-5' => __('3–5 Jahre', 'hengegroup-theme'),
            '5+' => __('Mehr als 5 Jahre', 'hengegroup-theme'),
        ],
        'contact_method' => [
            'phone' => __('Telefon', 'hengegroup-theme'),
            'email' => __('E-Mail', 'hengegroup-theme'),
        ],
        'contact_time' => [
            'morning' => __('Vormittags', 'hengegroup-theme'),
            'afternoon' => __('Nachmittags', 'hengegroup-theme'),
        ],
    ];
}

/**
 * Prueft die Textfelder einer Bewerbung (Dateien prueft der Handler). Feld => Fehlermeldung.
 */
function hengegroup_theme_validate_job_application(array $values): array
{
    $errors = [];
    $options = hengegroup_theme_get_job_application_options();

    if (trim((string) ($values['name'] ?? '')) === '') {
        $errors['name'] = __('Bitte gib deinen Namen an.', 'hengegroup-theme');
    }

    $age = trim((string) ($values['age'] ?? ''));

    if ($age === '' || !ctype_digit($age) || (int) $age < 14 || (int) $age > 99) {
        $errors['age'] = __(
            'Bitte gib dein Alter als Zahl zwischen 14 und 99 an.',
            'hengegroup-theme',
        );
    }

    if (filter_var(trim((string) ($values['email'] ?? '')), FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = __('Bitte gib eine gültige E-Mail-Adresse an.', 'hengegroup-theme');
    }

    $phone = trim((string) ($values['phone'] ?? ''));

    if ($phone !== '' && preg_match('/^[0-9 +()\/\-]{5,30}$/', $phone) !== 1) {
        $errors['phone'] = __('Bitte gib eine gültige Telefonnummer an.', 'hengegroup-theme');
    }

    foreach (['experience', 'contact_method', 'contact_time'] as $field) {
        $value = (string) ($values[$field] ?? '');

        if ($value !== '' && !isset($options[$field][$value])) {
            $errors[$field] = __('Bitte wähle einen Eintrag aus der Liste.', 'hengegroup-theme');
        }
    }

    if (($values['contact_method'] ?? '') === 'phone' && $phone === '') {
        $errors['phone'] = __(
            'Bitte gib eine Telefonnummer an, wenn wir dich telefonisch kontaktieren sollen.',
            'hengegroup-theme',
        );
    }

    if (mb_strlen((string) ($values['message'] ?? '')) > 5000) {
        $errors['message'] = __(
            'Die Nachricht darf höchstens 5.000 Zeichen lang sein.',
            'hengegroup-theme',
        );
    }

    if (empty($values['privacy'])) {
        $errors['privacy'] = __(
            'Bitte bestätige, dass du die Datenschutzhinweise gelesen hast.',
            'hengegroup-theme',
        );
    }

    return $errors;
}

/**
 * Ordner neben dem Website-Stammverzeichnis (.../httpdocs -> .../hengegroup-bewerbungen); '' wenn
 * es kein Elternverzeichnis gibt.
 */
function hengegroup_theme_get_private_storage_dir(string $site_root): string
{
    $site_root = rtrim($site_root, '/\\');

    if ($site_root === '') {
        return '';
    }

    return rtrim(dirname($site_root), '/\\') . '/' . HENGEGROUP_THEME_APPLICATION_STORAGE_FOLDER;
}

/**
 * Ob open_basedir (Praefix-Liste, leer = frei) $path erlaubt -- sonst gaebe es pro Aufruf eine Warnung.
 */
function hengegroup_theme_is_path_within_open_basedir(string $path, string $open_basedir): bool
{
    if (trim($open_basedir) === '') {
        return true;
    }

    foreach (explode(PATH_SEPARATOR, $open_basedir) as $allowed) {
        $allowed = trim($allowed);

        if ($allowed !== '' && str_starts_with($path, $allowed)) {
            return true;
        }
    }

    return false;
}
