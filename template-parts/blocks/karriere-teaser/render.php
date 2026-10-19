<?php

declare(strict_types=1);

// Block "Karriere-Teaser": Text + Button links, neueste Stellen rechts (companyId > 0: nur dieses
// Unternehmen, ohne Stellen dann ganz ausgeblendet). Ohne Button-URL geht der Button auf die
// Karriereseite (#stellen).

if (!is_array($attributes ?? null) || !function_exists('hengegroup_theme_render_job_teaser_list')) {
    return;
}

$heading = trim((string) ($attributes['heading'] ?? ''));
$heading_tag = strtolower(trim((string) ($attributes['headingTag'] ?? 'h2')));

if (!in_array($heading_tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'], true)) {
    $heading_tag = 'h2';
}

$text = trim((string) ($attributes['text'] ?? ''));
$button_text = trim((string) ($attributes['buttonText'] ?? ''));
$button_url = trim((string) ($attributes['buttonUrl'] ?? ''));
$limit = (int) ($attributes['limit'] ?? 4);
$company_id = (int) ($attributes['companyId'] ?? 0);
$empty_text = trim((string) ($attributes['emptyText'] ?? ''));
// Der Hintergrund heisst hier aus Kompatibilitaet mit bestehenden Inhalten `variant`.
$background = hengegroup_theme_get_section_background((string) ($attributes['variant'] ?? ''));
$spacing_class = hengegroup_theme_get_section_spacing_class(
    (string) ($attributes['spacing'] ?? ''),
);
$is_dark = $background['dark'];
$list_markup = hengegroup_theme_render_job_teaser_list($limit, $company_id);

if ($list_markup === '' && $company_id > 0) {
    return;
}

$actions = '';

if ($button_text !== '') {
    ob_start();
    get_template_part('template-parts/base/button', null, [
        'config' => [
            'text' => $button_text,
            'href' =>
                $button_url !== ''
                    ? $button_url
                    : hengegroup_theme_get_career_page_url() . '#stellen',
            'variant' => $is_dark ? 'grey-light' : 'grey-dark',
            'size' => 'lg',
        ],
    ]);
    $actions = (string) ob_get_clean();
}

printf(
    '<section class="%1$s" data-block="karriere-teaser" data-background="%2$s">',
    esc_attr(trim($spacing_class . ' ' . $background['class'])),
    esc_attr($background['name']),
);
echo '<div class="wrapper items-start gap-y-10">';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo hengegroup_theme_render_section_header([
    'size' => 'base',
    'color' => $background['color'],
    'title' => $heading,
    'title_tag' => $heading_tag,
    'description' => $text,
    'actions' => $actions,
    'class' => 'col-span-12 lg:col-span-6 lg:pr-8',
]);

echo '<div class="col-span-12 lg:col-span-6">';

if ($list_markup !== '') {
    echo $list_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} elseif ($empty_text !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => 'body-base',
            'color' => $is_dark ? 'light' : 'neutral',
            'text' => $empty_text,
        ],
    ]);
}

echo '</div>';
echo '</div>';
echo '</section>';
