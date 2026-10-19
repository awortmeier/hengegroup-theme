<?php

declare(strict_types=1);

// Block "Instagram": Kopf mit Button zum Profil, darunter die neuesten Beitraege aus
// Einstellungen > Instagram. Ohne Beitraege wird die Sektion ausgeblendet.

if (!is_array($attributes ?? null) || !function_exists('hengegroup_theme_get_instagram_posts')) {
    return;
}

$heading = trim((string) ($attributes['heading'] ?? ''));
$heading_tag = strtolower(trim((string) ($attributes['headingTag'] ?? 'h2')));

if (!in_array($heading_tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'], true)) {
    $heading_tag = 'h2';
}

$text = trim((string) ($attributes['text'] ?? ''));
$button_text = trim((string) ($attributes['buttonText'] ?? ''));
// Volle Reihen im 3er-Raster.
$count = (int) max(3, min(HENGEGROUP_THEME_INSTAGRAM_MAX_POSTS, (int) ($attributes['count'] ?? 3)));
$count -= $count % 3;
$posts = hengegroup_theme_get_instagram_posts($count);

if ($posts === []) {
    return;
}

$background = hengegroup_theme_get_section_background(
    (string) ($attributes['background'] ?? 'dark'),
);
$spacing_class = hengegroup_theme_get_section_spacing_class(
    (string) ($attributes['spacing'] ?? ''),
);
$profile_url = hengegroup_theme_get_instagram_profile_url();
$actions = '';

if ($button_text !== '' && $profile_url !== '') {
    ob_start();
    get_template_part('template-parts/base/button', null, [
        'config' => [
            'text' => $button_text,
            'href' => $profile_url,
            'variant' => $background['dark'] ? 'grey-light' : 'grey-dark',
            'size' => 'lg',
            'attributes' => hengegroup_theme_get_link_attributes($profile_url),
        ],
    ]);
    $actions = (string) ob_get_clean();
}

printf(
    '<section class="%1$s" data-block="instagram" data-background="%2$s">',
    esc_attr(trim($spacing_class . ' ' . $background['class'])),
    esc_attr($background['name']),
);
echo '<div class="wrapper gap-y-10">';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo hengegroup_theme_render_section_header([
    'color' => $background['color'],
    'title' => $heading,
    'title_tag' => $heading_tag,
    'description' => $text,
    'actions' => $actions,
    'class' => 'col-span-12 lg:col-span-6',
]);

echo '<ul class="col-span-12 grid grid-cols-1 gap-5 sm:grid-cols-3">';

foreach ($posts as $post) {
    $image_markup = hengegroup_theme_render_image([
        'src' => $post['src'],
        'width' => $post['width'] > 0 ? (string) $post['width'] : '',
        'height' => $post['height'] > 0 ? (string) $post['height'] : '',
        'alt' => $post['alt'],
        'loading' => 'lazy',
        'class' =>
            'aspect-[4/3] w-full object-cover transition-transform duration-300 group-hover:scale-105 sm:aspect-video',
    ]);

    printf(
        '<li><a href="%1$s" target="_blank" rel="noopener" class="group block overflow-hidden rounded-2xl shadow-raised focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current" data-slot="instagram-post">%2$s</a></li>',
        esc_url($post['permalink']),
        $image_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

echo '</ul>';
echo '</div>';
echo '</section>';
