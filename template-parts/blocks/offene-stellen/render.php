<?php

declare(strict_types=1);

// Block "Offene Stellen": aktive Stellen nach Unternehmen, rechts der Ansprechpartner.
// `id="stellen"` ist Sprungziel, daher `supports.multiple: false`.

if (!is_array($attributes ?? null) || !function_exists('hengegroup_theme_render_open_jobs_list')) {
    return;
}

$background = hengegroup_theme_get_section_background((string) ($attributes['background'] ?? ''));
$spacing_class = hengegroup_theme_get_section_spacing_class(
    (string) ($attributes['spacing'] ?? ''),
);
$show_contact = !empty($attributes['showContact'] ?? true);
$contact_heading = trim((string) ($attributes['contactHeading'] ?? ''));
$list_markup = hengegroup_theme_render_open_jobs_list(
    (string) ($attributes['emptyText'] ?? ''),
    $background['dark'],
);
$contact_markup = $show_contact
    ? hengegroup_theme_render_contact_card(hengegroup_theme_get_open_jobs_contact($attributes), [
        'variant' => $background['dark'] ? 'default' : 'filled',
    ])
    : '';
$list_span = $contact_markup !== '' ? 'lg:col-span-8' : '';

printf(
    '<section id="stellen" class="%1$s" data-background="%2$s">',
    esc_attr(trim('scroll-mt-24 ' . $spacing_class . ' ' . $background['class'])),
    esc_attr($background['name']),
);
echo '<div class="wrapper items-start gap-y-12">';
printf('<div class="col-span-12 %s">', esc_attr($list_span));
echo $list_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</div>';

if ($contact_markup !== '') {
    echo '<div class="col-span-12 flex flex-col gap-5 lg:col-span-4">';

    if ($contact_heading !== '') {
        get_template_part('template-parts/base/typography', null, [
            'config' => [
                'variant' => 'headline-xs',
                'tag' => 'h2',
                'text' => $contact_heading,
                'accent_markup' => true,
            ],
        ]);
    }

    echo $contact_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '</div>';
}

echo '</div>';
echo '</section>';
