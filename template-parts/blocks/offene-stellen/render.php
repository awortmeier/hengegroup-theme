<?php

declare(strict_types=1);

// Block "Offene Stellen": aktive Stellen nach Unternehmen, rechts der Ansprechpartner.
// `id="stellen"` ist Sprungziel, daher `supports.multiple: false`.

if (!is_array($attributes ?? null) || !function_exists('hengegroup_theme_render_open_jobs_list')) {
    return;
}

$show_contact = !empty($attributes['showContact'] ?? true);
$contact_heading = trim((string) ($attributes['contactHeading'] ?? ''));
$list_markup = hengegroup_theme_render_open_jobs_list((string) ($attributes['emptyText'] ?? ''));
$contact_markup = $show_contact
    ? hengegroup_theme_render_contact_card(hengegroup_theme_get_open_jobs_contact($attributes), [
        'variant' => 'filled',
    ])
    : '';
$list_span = $contact_markup !== '' ? 'lg:col-span-8' : '';

echo '<section id="stellen" class="scroll-mt-24 section-spacing-bottom">';
echo '<div class="wrapper items-start gap-y-12">';
printf('<div class="col-span-12 %s">', esc_attr($list_span));
echo $list_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</div>';

if ($contact_markup !== '') {
    echo '<div class="col-span-12 lg:col-span-4">';

    if ($contact_heading !== '') {
        get_template_part('template-parts/base/typography', null, [
            'config' => [
                'variant' => 'headline-xs',
                'tag' => 'h2',
                'text' => $contact_heading,
                'accent_markup' => true,
                'class' => 'mb-5',
            ],
        ]);
    }

    echo $contact_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '</div>';
}

echo '</div>';
echo '</section>';
