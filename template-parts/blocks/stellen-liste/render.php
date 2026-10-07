<?php

declare(strict_types=1);

// Rendert template-parts/blocks/stellen-liste/block.json -- eine der drei Listen einer
// Stellenanzeige ("Wir bieten dir"/"Dein Profil"/"Deine Aufgaben", Ueberschrift fest je Typ aus
// hengegroup_theme_get_job_list_types()), darin eine normale Gutenberg-Liste (`$content`, fertig
// gerendert). Explizite Nachfrage 2026-10-07: Listen im Editor statt als Textfelder, aber mit
// Struktur -- der Typ sagt hengegroup_theme_extract_job_lists() (inc/template-parts/careers.php),
// welches schema.org-Feld die Eintraege im JSON-LD fuellen.
//
// Eine leere "Wir bieten dir"-Liste zeigt die Standard-Benefits des zugeordneten Unternehmens
// (Karriere > Unternehmen) -- so muss nicht jede Stelle dieselben Benefits wiederholen. Andere leere
// Listen werden gar nicht ausgegeben. Listen-Optik (Aufzaehlungszeichen, Einzug) kommt hier aus
// dem Block selbst, damit er auch ausserhalb der Stellen-Einzelseite korrekt aussieht.

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
    '<section class="my-6 text-base text-grey-dark [&_li]:leading-[1.7] [&_ul]:list-disc [&_ul]:pl-5" data-slot="job-list" data-type="%1$s">%2$s%3$s</section>',
    esc_attr($type),
    $heading_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $list_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
