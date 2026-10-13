<?php

declare(strict_types=1);

// Block "Stellen-Liste": Ueberschrift je Typ + Gutenberg-Liste. Der Typ bestimmt das JSON-LD-Feld
// (hengegroup_theme_extract_job_lists()). Leere "Wir bieten dir"-Liste = Standard-Benefits des
// Unternehmens, andere leere Listen entfallen.

if (!is_array($attributes ?? null) || !function_exists('hengegroup_theme_get_job_list_types')) {
    return;
}

$types = hengegroup_theme_get_job_list_types();
$type = (string) ($attributes['type'] ?? 'benefits');

if (!isset($types[$type])) {
    $type = 'benefits';
}

$list_markup = trim((string) ($content ?? ''));
$has_items = trim(wp_strip_all_tags($list_markup)) !== '';

if (!$has_items && $type === 'benefits') {
    $post_id =
        (int) (isset($block) && $block instanceof WP_Block ? $block->context['postId'] ?? 0 : 0);
    $post_id = $post_id > 0 ? $post_id : (int) get_the_ID();
    $company_terms =
        $post_id > 0 ? get_the_terms($post_id, HENGEGROUP_THEME_JOB_COMPANY_TAXONOMY) : false;
    $company =
        is_array($company_terms) && $company_terms !== []
            ? hengegroup_theme_get_job_company((int) $company_terms[0]->term_id)
            : null;
    $benefits = $company['benefits'] ?? [];

    if ($benefits !== []) {
        $list_markup =
            '<ul class="wp-block-list">' .
            implode(
                '',
                array_map(
                    static fn(string $benefit): string => '<li>' . esc_html($benefit) . '</li>',
                    $benefits,
                ),
            ) .
            '</ul>';
        $has_items = true;
    }
}

if (!$has_items) {
    return;
}

$heading_markup = hengegroup_theme_render_typography([
    'variant' => 'body-lg',
    'tag' => 'h2',
    'text' => $types[$type],
    'class' => 'mb-3 font-bold',
]);

printf(
    '<section class="my-6 text-base text-grey-dark rich-text" data-slot="job-list" data-type="%1$s">%2$s%3$s</section>',
    esc_attr($type),
    $heading_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $list_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
