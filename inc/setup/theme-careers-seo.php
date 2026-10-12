<?php

declare(strict_types=1);

// SEO fuer Stellen ueber die bestehende SEO-Ausgabe: JobPosting + BreadcrumbList, automatischer
// Titel und Description. Abgelaufene Stellen bekommen kein JobPosting (Google-Richtlinie).

/**
 * Sichtbarer Inhalt (Einleitung + Listen inkl. Standard-Benefits) mit den von Google erlaubten Tags.
 */
function hengegroup_theme_get_job_description_html(array $job): string
{
    $content = (string) apply_filters(
        'the_content',
        (string) get_post_field('post_content', $job['id']),
    );
    $html = wp_kses($content, [
        'p' => [],
        'br' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
    ]);

    return trim((string) preg_replace('/\s+/', ' ', $html));
}

function hengegroup_theme_get_job_posting_schema_for_post(int $post_id): array
{
    $job = hengegroup_theme_get_job_data($post_id);
    $logo_url = '';

    if ($job['company'] !== null && $job['company']['logo_id'] > 0) {
        $logo_url = (string) wp_get_attachment_image_url($job['company']['logo_id'], 'full');
    }

    return hengegroup_theme_build_job_posting_schema($job, [
        'description_html' => hengegroup_theme_get_job_description_html($job),
        'site_name' => get_bloginfo('name'),
        'site_url' => home_url('/'),
        'timezone_offset' => wp_date('P'),
        'logo_url' => $logo_url,
        'direct_apply' => true,
    ]);
}

function hengegroup_theme_filter_seo_structured_data_jobs(array $schemas, int $post_id): array
{
    if ($post_id <= 0 || get_post_type($post_id) !== HENGEGROUP_THEME_JOB_POST_TYPE) {
        return $schemas;
    }

    $schemas[] = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => get_bloginfo('name'),
                'item' => home_url('/'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => __('Karriere', 'hengegroup-theme'),
                'item' => hengegroup_theme_get_career_page_url(),
            ],
            [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => get_the_title($post_id),
                'item' => (string) get_permalink($post_id),
            ],
        ],
    ];

    if (!hengegroup_theme_is_job_expired($post_id)) {
        $schemas[] = hengegroup_theme_get_job_posting_schema_for_post($post_id);
    }

    return $schemas;
}
add_filter(
    'hengegroup_theme_seo_structured_data',
    'hengegroup_theme_filter_seo_structured_data_jobs',
    10,
    2,
);

/**
 * "Jobtitel in Ort – Unternehmen | Seitenname", nur ohne eigenen Titel in der SEO-Box.
 */
function hengegroup_theme_filter_pre_get_document_title_jobs(string $title): string
{
    if (!is_singular(HENGEGROUP_THEME_JOB_POST_TYPE)) {
        return $title;
    }

    $post_id = (int) get_queried_object_id();

    if (trim((string) get_post_meta($post_id, '_hengegroup_theme_seo_title', true)) !== '') {
        return $title;
    }

    $job = hengegroup_theme_get_job_data($post_id);
    $job_title = $job['title'];

    if ($job['locations'] !== [] && $job['locations'][0]['locality'] !== '') {
        $job_title = sprintf(
            /* translators: 1: job title, 2: city. */
            __('%1$s in %2$s', 'hengegroup-theme'),
            $job_title,
            $job['locations'][0]['locality'],
        );
    }

    if ($job['company'] !== null) {
        $job_title .= ' – ' . $job['company']['name'];
    }

    $options = hengegroup_theme_get_seo_options();

    return $job_title . ' ' . $options['title_separator'] . ' ' . get_bloginfo('name');
}
add_filter('pre_get_document_title', 'hengegroup_theme_filter_pre_get_document_title_jobs', 25);

/**
 * Description aus den Fakten der Stelle, solange SEO-Box und Auszug leer sind.
 */
function hengegroup_theme_filter_seo_description_jobs(string $description, int $post_id): string
{
    if ($post_id <= 0 || get_post_type($post_id) !== HENGEGROUP_THEME_JOB_POST_TYPE) {
        return $description;
    }

    if (trim((string) get_post_meta($post_id, '_hengegroup_theme_seo_description', true)) !== '') {
        return $description;
    }

    // Fuer Stellen hat der Auszug Vorrang vor der site-weiten Standard-Beschreibung.
    if (has_excerpt($post_id)) {
        return trim(wp_strip_all_tags(get_the_excerpt($post_id)));
    }

    $job = hengegroup_theme_get_job_data($post_id);
    $employment_labels = array_intersect_key(
        hengegroup_theme_get_job_employment_types(),
        array_flip($job['employment_types']),
    );
    $facts = array_filter([
        $job['locations'] !== [] ? $job['locations'][0]['label'] : '',
        implode(' / ', $employment_labels),
        hengegroup_theme_format_job_salary(
            $job['salary']['min'],
            $job['salary']['max'],
            $job['salary']['unit'],
        ),
    ]);

    $lead =
        $job['company'] !== null
            ? sprintf(
                /* translators: 1: job title, 2: company name. */
                __('Jetzt bewerben: %1$s bei %2$s.', 'hengegroup-theme'),
                $job['title'],
                $job['company']['legal_name'] !== ''
                    ? $job['company']['legal_name']
                    : $job['company']['name'],
            )
            : sprintf(
                /* translators: %s: job title. */
                __('Jetzt bewerben: %s.', 'hengegroup-theme'),
                $job['title'],
            );

    return trim($lead . ($facts !== [] ? ' ' . implode(' · ', $facts) . '.' : ''));
}
add_filter(
    'hengegroup_theme_seo_description',
    'hengegroup_theme_filter_seo_description_jobs',
    10,
    2,
);
