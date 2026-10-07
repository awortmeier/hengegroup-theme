<?php

declare(strict_types=1);

// Daten- und Render-Helfer fuer Stellenangebote (Custom Post Type `stellenangebote`, siehe
// inc/setup/theme-careers.php fuer Post-Type/Taxonomien/Routing, inc/setup/theme-careers-admin.php
// fuer die Backend-Felder, inc/setup/theme-careers-seo.php fuer JobPosting-JSON-LD/Title/
// Description). Gleiche Aufteilung wie inc/template-parts/woocommerce-product-card.php: diese Datei
// definiert nur Funktionen (keine add_action()/WP-Aufrufe beim Einbinden), damit die reinen
// Logik-Helfer (Formatierung, Ablaufpruefung, JobPosting-Aufbau) per Brain Monkey testbar bleiben
// (tests/Unit/CareersTest.php).
//
// Eine Stelle wird ueberall als EIN normalisiertes Array gelesen
// (hengegroup_theme_get_job_data()) -- Einzelseite, Listen-Bloecke und JSON-LD rendern aus
// derselben Quelle, damit sichtbarer Inhalt und strukturierte Daten nicht auseinanderlaufen (Google
// verlangt, dass alles im JobPosting auch sichtbar auf der Seite steht).
//
// Siehe docs/entscheidungen.md "Stellenangebote: Datenmodell, Google-Jobs-JSON-LD und Ablauf".

const HENGEGROUP_THEME_JOB_POST_TYPE = 'stellenangebote';
const HENGEGROUP_THEME_JOB_COMPANY_TAXONOMY = 'stellen_unternehmen';
const HENGEGROUP_THEME_JOB_LOCATION_TAXONOMY = 'stellen_standort';
const HENGEGROUP_THEME_JOB_CATEGORY_TAXONOMY = 'stellen_bereich';
const HENGEGROUP_THEME_CAREER_OPTION = 'hengegroup_theme_career_options';

/**
 * Post-Meta-Keys je Stelle. Leere Werte werden geloescht statt als '' gespeichert (siehe
 * hengegroup_theme_action_save_post_job_details()), damit die "NOT EXISTS"-Zweige der
 * Ablauf-Abfrage (hengegroup_theme_get_active_job_meta_query()) greifen.
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
 * Google-JobPosting-`employmentType`-Werte (feste englische Enum-Werte laut
 * developers.google.com/search/docs/appearance/structured-data/job-posting) mit deutschen Labels
 * fuers Backend und die sichtbare Faktenzeile. Ausbildung/Minijob haben kein eigenes Google-Enum
 * und laufen ueber OTHER/PART_TIME -- das sichtbare Label sagt trotzdem, was gemeint ist, sobald
 * die Redaktion es im Stellentitel/Text nennt.
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
 * schema.org-`unitText`-Werte fuer `baseSalary` (Google akzeptiert genau diese fuenf).
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
 * Arbeitsmodell. `remote` wird im JSON-LD zu `jobLocationType: TELECOMMUTE` (plus
 * `applicantLocationRequirements`), `hybrid` bleibt bei Google ein normaler Vor-Ort-Job mit
 * Standort -- Google kennt kein eigenes Hybrid-Enum, das sichtbare Label nennt es trotzdem.
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
 * Bildungsabschluss -> schema.org `EducationalOccupationalCredential.credentialCategory` (die von
 * Google dokumentierten Werte). `none` wird zu Googles Sonderwert "no requirements".
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
 * Berufserfahrung in Monaten (schema.org `OccupationalExperienceRequirements.monthsOfExperience`).
 * 0 = ausdruecklich keine Erfahrung noetig (Google-Sonderwert "no requirements"); "nicht
 * angegeben" ist kein Eintrag hier, sondern das Fehlen des Meta-Werts.
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
 * Die drei Listen einer Stellenanzeige (Block "Stellen-Liste", template-parts/blocks/stellen-liste)
 * mit ihrer sichtbaren Ueberschrift, in Design-Reihenfolge. Der Typ bestimmt auch das
 * schema.org-Feld im JSON-LD (jobBenefits/qualifications/responsibilities) und dass leere
 * "Wir bieten dir"-Listen die Standard-Benefits des Unternehmens zeigen.
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
 * Liest die Eintraege aller "Stellen-Liste"-Bloecke aus bereits geparsten Bloecken
 * (parse_blocks()) -- reine Funktion, damit die Zuordnung unit-getestet ist. Unterstuetzt
 * core/list mit core/list-item-Kindbloecken (WordPress >= 6.1) und das aeltere Format ohne
 * Kindbloecke (nur <li> im HTML). Verschachtelte Unterlisten werden mit eingesammelt.
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
 * Zerlegt ein mehrzeiliges Backend-Textfeld (ein Punkt pro Zeile) in eine Liste. Fuehrende
 * Aufzaehlungszeichen ("-", "*", "•", "–") werden entfernt, weil Redakteure Listen oft aus Word/
 * der alten Seite inklusive Bullets einfuegen.
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
 * Sichtbare Standortzeile im Stil des Designs ("D-06463 Falkenstein/Harz OT Ermsleben"):
 * Laenderkennzeichen + PLZ + Ort. Deutschland bekommt das gewohnte "D", andere Laender ihren
 * ISO-Code. Faellt auf den Term-Namen zurueck, wenn PLZ/Ort fehlen.
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
 * Deutsches Zahlenformat ohne WordPress-Abhaengigkeit (Testbarkeit): Tausenderpunkt, Nachkommastellen
 * nur wenn vorhanden (15,50 statt 15,5; 3.200 statt 3.200,00).
 */
function hengegroup_theme_format_job_amount(float $amount): string
{
    $decimals = abs($amount - round($amount)) > 0.001 ? 2 : 0;

    return number_format($amount, $decimals, ',', '.');
}

/**
 * Sichtbare Gehaltsangabe, z. B. "3.200 – 3.800 € brutto pro Monat", "ab 3.200 € …",
 * "bis 3.800 € …". Leerer String, wenn weder Min noch Max gepflegt ist.
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
 * Ablauf-Regel als reine Funktion (Testbarkeit): besetzt ODER `validThrough` liegt vor `$today`
 * (beide 'Y-m-d', String-Vergleich ist bei diesem Format chronologisch korrekt). Ein leeres
 * `validThrough` laeuft nie automatisch ab.
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
 * Welche fuer Google Jobs relevanten Angaben fehlen noch -- fuer den Hinweis in der
 * "Stellendetails"-Box im Backend. `title`/`description`/`datePosted` ergeben sich immer aus dem
 * Beitrag selbst, deshalb prueft das nur die Felder, die die Redaktion vergessen kann.
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
 * Baut das schema.org-JobPosting aus dem normalisierten Stellen-Array
 * (hengegroup_theme_get_job_data()) -- als reine Funktion ohne WP-Aufrufe, damit die
 * Google-relevante Struktur unit-getestet ist (tests/Unit/CareersTest.php). Alles, was WordPress
 * liefern muss (Beschreibungs-HTML, Logo-URL, Website-Name, Zeitzone), kommt ueber `$context`:
 *   description_html  string  sichtbarer Inhalt als HTML (Google erlaubt <p>, <ul>, <li>, <br>,
 *                             <h1>-<h6>, <strong>, <em>)
 *   site_name         string  Name der Dachmarke (parentOrganization)
 *   site_url          string
 *   timezone_offset   string  z. B. "+02:00", fuer `validThrough` (Ende des Tages, Ortszeit)
 *   logo_url          string  Unternehmenslogo (optional)
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

        if ($company['website'] !== '') {
            $organization['sameAs'] = $company['website'];
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

    // true, sobald man sich direkt auf der Stellenseite bewerben kann (Bewerbungsformular).
    $schema['directApply'] = !empty($context['direct_apply']);

    return $schema;
}

/**
 * Plugin-/Theme-weite Karriere-Einstellungen (Backend: Stellenangebote > Einstellungen, siehe
 * inc/setup/theme-careers-admin.php), mit festen Defaults fuer jeden Key.
 */
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
 * URL der Karriereseite (eine normale WordPress-Seite mit dem "Offene Stellen"-Block, siehe
 * docs/entscheidungen.md) -- Ziel fuer abgelaufene Stellen, Breadcrumbs und die Teaser-Buttons.
 * Faellt auf eine Seite mit Slug "karriere" und zuletzt auf die Startseite zurueck.
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
        'website' => trim(
            (string) get_term_meta($term->term_id, '_hengegroup_theme_company_website', true),
        ),
        'logo_id' => (int) get_term_meta($term->term_id, '_hengegroup_theme_company_logo_id', true),
        'variant' => in_array($variant, $variants, true) ? $variant : $variants[0],
        'benefits' => hengegroup_theme_parse_job_list_lines(
            (string) get_term_meta($term->term_id, '_hengegroup_theme_company_benefits', true),
        ),
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
 * Ansprechpartner fuer eine Stelle: der beim Unternehmen hinterlegte (falls Name ODER E-Mail
 * gepflegt), sonst der globale Standard aus den Karriere-Einstellungen -- aktuell gibt es einen
 * fuer alle Unternehmen, pro Unternehmen ueberschreibbar, wenn sich das aendert.
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
 * Normalisiertes Daten-Array einer Stelle -- siehe Dateikopf. Benefits fallen auf die
 * Standard-Benefits des Unternehmens zurueck, wenn die Stelle selbst keine eigenen hat.
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
 * meta_query fuer "nur aktive Stellen" (nicht besetzt, `validThrough` leer oder heute/spaeter) --
 * geteilt zwischen den Listen-Bloecken, der XML-Sitemap und allen anderen Frontend-Abfragen, damit
 * abgelaufene Stellen nirgends mehr verlinkt werden.
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

/**
 * IDs aller aktiven Stellen, neueste zuerst. `$limit` -1 = alle.
 */
function hengegroup_theme_get_active_job_ids(int $limit = -1): array
{
    $query = new WP_Query([
        'post_type' => HENGEGROUP_THEME_JOB_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => $limit > 0 ? $limit : -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'fields' => 'ids',
        'no_found_rows' => true,
        'meta_query' => hengegroup_theme_get_active_job_meta_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
    ]);

    return array_map('intval', $query->posts);
}

/**
 * Firmen-Pill (badge.php) in der Unternehmensfarbe -- gleiche Optik wie die Produkt-Badges
 * (Crillee, Grossbuchstaben), siehe hengegroup_theme_render_product_badge().
 */
function hengegroup_theme_render_job_company_badge(array $company, string $class = ''): string
{
    ob_start();
    get_template_part('template-parts/base/badge', null, [
        'config' => [
            'text' => $company['name'],
            'variant' => $company['variant'],
            'font' => 'accent',
            'class' => trim('uppercase tracking-wide ' . $class),
        ],
    ]);

    return (string) ob_get_clean();
}

/**
 * Textfarbe passend zur Badge-Variante des Unternehmens (Gruppen-Ueberschrift auf der
 * Karriereseite). Literale Klassen, damit Tailwind sie beim Scannen findet.
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
 * Eine klickbare Stellen-Zeile (weisse Karte, Titel, Pfeil) als `<li>` -- Design "Startseite"/
 * "Karriereseite". Mit `$with_company_badge` steht die Firmen-Pill vor dem Titel (Startseite); auf
 * der Karriereseite gruppiert die Ueberschrift bereits nach Unternehmen.
 *
 * Die Textfarbe sitzt zusaetzlich am inneren <span> (auch in der Kontaktkarte): Im Block-Editor
 * gewinnt sonst die Link-Farbe aus theme.json (`styles.elements.link`) gegen die Klasse am <a>.
 */
function hengegroup_theme_render_job_row(array $job, bool $with_company_badge): string
{
    $badge =
        $with_company_badge && $job['company'] !== null
            ? hengegroup_theme_render_job_company_badge($job['company'], 'shrink-0')
            : '';

    $arrow = hengegroup_theme_render_icon([
        'name' => 'arrow-right',
        'set' => 'lucide',
        'class' => 'size-5 shrink-0 text-grey-dark transition-transform group-hover:translate-x-1',
    ]);

    return sprintf(
        '<li><a class="group flex items-center justify-between gap-4 rounded-2xl bg-white px-6 py-5 text-grey-dark no-underline shadow-[0_1px_3px_rgba(0,0,0,0.06)] transition-shadow hover:shadow-[0_4px_12px_rgba(0,0,0,0.1)] focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none" href="%1$s"><span class="flex min-w-0 flex-wrap items-center gap-x-4 gap-y-2">%2$s<span class="text-lg leading-snug font-semibold text-grey-dark">%3$s</span></span>%4$s</a></li>',
        esc_url($job['url']),
        $badge,
        esc_html($job['title']),
        $arrow,
    );
}

/**
 * Ansprechpartner-Karte (dunkel, Design "Karriereseite"/"Stellenangebot"). Leerer String, wenn
 * weder Name noch E-Mail gepflegt sind. `$label` setzt eine kleine Ueberschrift in die Karte (z. B.
 * auf der Stellen-Einzelseite, wo die Karte ohne eigene Abschnitts-Ueberschrift neben der
 * Faktenleiste steht), `$class` haengt Klassen an (z. B. `h-full`).
 */
function hengegroup_theme_render_job_contact_card(
    array $contact,
    string $label = '',
    string $class = '',
): string {
    if (trim($contact['name'] ?? '') === '' && trim($contact['email'] ?? '') === '') {
        return '';
    }

    $rows = '';
    $row_template =
        '<li class="flex items-center gap-2.5 text-base text-grey-light">%1$s<span>%2$s</span></li>';

    if (($contact['role'] ?? '') !== '') {
        $rows .= sprintf(
            $row_template,
            hengegroup_theme_render_icon([
                'name' => 'users',
                'set' => 'lucide',
                'class' => 'size-4 shrink-0',
            ]),
            esc_html($contact['role']),
        );
    }

    if (($contact['email'] ?? '') !== '') {
        $rows .= sprintf(
            $row_template,
            hengegroup_theme_render_icon([
                'name' => 'mail',
                'set' => 'lucide',
                'class' => 'size-4 shrink-0',
            ]),
            sprintf(
                '<a class="text-grey-light underline-offset-4 hover:underline" href="mailto:%1$s"><span class="text-grey-light">%2$s</span></a>',
                esc_attr(antispambot($contact['email'])),
                esc_html(antispambot($contact['email'])),
            ),
        );
    }

    if (($contact['phone'] ?? '') !== '') {
        $rows .= sprintf(
            $row_template,
            hengegroup_theme_render_icon([
                'name' => 'phone',
                'set' => 'lucide',
                'class' => 'size-4 shrink-0',
            ]),
            sprintf(
                '<a class="text-grey-light underline-offset-4 hover:underline" href="tel:%1$s"><span class="text-grey-light">%2$s</span></a>',
                esc_attr((string) preg_replace('/[^\d+]/', '', $contact['phone'])),
                esc_html($contact['phone']),
            ),
        );
    }

    return sprintf(
        '<div class="%3$s" data-slot="job-contact">%4$s<p class="mb-3.5 text-xl font-extrabold text-grey-light">%1$s</p><ul class="flex flex-col gap-2.5">%2$s</ul></div>',
        esc_html($contact['name'] ?? ''),
        $rows,
        esc_attr(
            trim(
                'rounded-[20px] bg-grey-dark px-8 py-7 shadow-[0_8px_24px_rgba(0,0,0,0.12)] ' .
                    $class,
            ),
        ),
        $label !== ''
            ? '<p class="mb-2 text-sm text-grey-light/60">' . esc_html($label) . '</p>'
            : '',
    );
}

/**
 * Aktive Stellen gruppiert nach Unternehmen (Design "Karriereseite"): Unternehmensname in
 * Firmenfarbe + Standort(e) der Gruppe, darunter die Stellen-Zeilen. Stellen ohne Unternehmen
 * landen in einer Gruppe unter dem Seitennamen.
 */
function hengegroup_theme_render_jobs_grouped(): string
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
        $text_class = hengegroup_theme_get_job_company_text_class(
            $group['company']['variant'] ?? 'grey-dark',
        );
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
                    '<p class="flex items-center gap-1.5 text-sm text-grey-dark/60">%1$s%2$s</p>',
                    hengegroup_theme_render_icon([
                        'name' => 'map-pin',
                        'set' => 'lucide',
                        'class' => 'size-3.5 shrink-0',
                    ]),
                    esc_html($location_label),
                )
                : '';

        $markup .= sprintf(
            '<div class="mb-12 last:mb-0" data-slot="job-group"><div class="mb-4.5 flex flex-wrap items-center gap-x-3.5 gap-y-1"><h2 class="font-accent text-[22px] font-bold %1$s">%2$s</h2>%3$s</div><ul class="flex flex-col gap-3">%4$s</ul></div>',
            esc_attr($text_class),
            esc_html($name),
            $location_markup,
            $rows,
        );
    }

    return $markup;
}

/**
 * Kompakte Liste der neuesten aktiven Stellen mit Firmen-Pill (Design "Startseite", Block
 * "Karriere-Teaser"). Leerer String, wenn keine Stelle aktiv ist.
 */
function hengegroup_theme_render_job_teaser_list(int $limit): string
{
    $rows = implode(
        '',
        array_map(
            static fn(int $job_id): string => hengegroup_theme_render_job_row(
                hengegroup_theme_get_job_data($job_id),
                true,
            ),
            hengegroup_theme_get_active_job_ids(max(1, $limit)),
        ),
    );

    return $rows !== '' ? '<ul class="flex flex-col gap-3">' . $rows . '</ul>' : '';
}

/**
 * Icon-Auswahl fuer den Benefits-Block (template-parts/blocks/benefits): Schluessel => Label +
 * icon.php-Konfiguration. Literale Konfigurationen, damit scripts/find-lucide-icons.php sie beim
 * Build findet. Die Labels gehen per Inline-Script an den Block-Editor (inc/setup/theme-blocks.php)
 * -- eine Quelle fuer Frontend und Editor-Auswahl.
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
    ];
}

/**
 * Linke Spalte des "Offene Stellen"-Blocks: gruppierte Stellen oder -- wenn keine aktiv ist -- ein
 * Hinweis mit Initiativbewerbungs-Adresse. Geteilt zwischen offene-stellen/render.php (Frontend)
 * und dem Editor-Vorschau-Zwilling offene-stellen-vorschau/render.php.
 */
function hengegroup_theme_render_open_jobs_list(): string
{
    $groups_markup = hengegroup_theme_render_jobs_grouped();

    if ($groups_markup !== '') {
        return $groups_markup;
    }

    $contact = hengegroup_theme_get_job_contact(null);

    ob_start();
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => 'body-base',
            'text' =>
                $contact['email'] !== ''
                    ? sprintf(
                        /* translators: %s: application e-mail address. */
                        __(
                            'Aktuell sind keine Stellen ausgeschrieben. Initiativbewerbungen sind jederzeit willkommen: %s',
                            'hengegroup-theme',
                        ),
                        $contact['email'],
                    )
                    : __('Aktuell sind keine Stellen ausgeschrieben.', 'hengegroup-theme'),
        ],
    ]);

    return (string) ob_get_clean();
}

/**
 * SVG eines Benefit-Icons in der Kachel-Groesse/-Farbe des Designs -- geteilt zwischen
 * benefit/render.php (Frontend) und der Icon-Vorschau im Block-Editor (inc/setup/theme-blocks.php
 * reicht die fertigen SVGs per Inline-Script an benefits/edit.jsx).
 */
function hengegroup_theme_render_benefit_icon(string $key): string
{
    $icons = hengegroup_theme_get_benefit_icons();
    $icon_config = $icons[$key][1] ?? reset($icons)[1];
    $icon_config['class'] = 'size-5.5 text-grey-light';

    return hengegroup_theme_render_icon($icon_config);
}

/**
 * Auswahlwerte des Bewerbungsformulars (Design "Stellenangebot einzelseite"): Wert => Label.
 * Geteilt zwischen Formular (template-parts/components/job-application-form.php), Pruefung
 * (hengegroup_theme_validate_job_application()) und Bewerbungs-E-Mail.
 */
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
 * Prueft die (bereits sanitisierten) Textfelder einer Bewerbung -- reine Funktion, unit-getestet
 * (tests/Unit/CareersTest.php). Dateien prueft der Formular-Handler separat
 * (inc/setup/theme-careers-application.php), weil das WordPress' Dateityp-Erkennung braucht.
 * Rueckgabe: Feldname => Fehlermeldung, leer = alles gueltig.
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
