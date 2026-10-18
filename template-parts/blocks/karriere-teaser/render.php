<?php

declare(strict_types=1);

// Block "Karriere-Teaser": Text + Button links, neueste Stellen rechts (companyId > 0: nur dieses
// Unternehmen). Ohne Button-URL geht der Button auf die Karriereseite (#stellen).

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
$is_dark = ($attributes['variant'] ?? 'light') === 'dark';
$color = $is_dark ? 'light' : 'default';
$list_markup = hengegroup_theme_render_job_teaser_list($limit, $company_id);

printf(
    '<section class="%1$s" data-block="karriere-teaser" data-variant="%2$s">',
    esc_attr($is_dark ? 'section-dark section-spacing' : 'section-spacing'),
    esc_attr($is_dark ? 'dark' : 'light'),
);
echo '<div class="wrapper items-start gap-y-10">';
echo '<div class="col-span-12 lg:col-span-6 lg:pr-8">';

if ($heading !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'text' => $heading,
            'accent_markup' => true,
            'variant' => 'headline-base',
            'tag' => $heading_tag,
            'color' => $color,
            'class' => 'mb-5',
        ],
    ]);
}

if ($text !== '') {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'text' => $text,
            'variant' => 'body-lg',
            'color' => $color,
            'class' => $button_text !== '' ? 'mb-8' : '',
        ],
    ]);
}

if ($button_text !== '') {
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
}

echo '</div>';
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
