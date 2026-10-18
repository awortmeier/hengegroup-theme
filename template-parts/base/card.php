<?php

declare(strict_types=1);

// Card. Class lists come from card-classes.json (shared with the block editor). Renders nothing
// when everything is empty. Spacing inside comes from gaps only: pass siblings without margins.
//
// Config:
//   variant          string   default (white) | filled (accent surface) | split (accent top, white
//                             footer) | ghost (no surface, no padding) | glass (translucent dark)
//   accent           string   henge-grey | henge-blue | henge-green | grey-dark
//                             (default: grey-dark for filled, henge-grey otherwise)
//   accent_border    bool     4px accent border on the left
//   orientation      string   vertical (default) | horizontal (media as left column)
//   kicker           string   pre-rendered, above the title (e.g. badge.php)
//   title            string   via typography.php
//   title_tag        string   h2-h6 (default h3)
//   title_variant    string   body-lg (default) | body-base | body-sm | headline-sm
//   description      string   subline below the title
//   icon             string   pre-rendered icon in a 44px box left of title/subline
//   icon_class       string   classes of that box (default depends on variant)
//   leading          string   pre-rendered visual left of title/subline instead of the icon box
//                             (logo, seal); also without a title
//   image            array    image.php config; horizontal: the image must fill the column
//   content          string   pre-rendered body, laid out as a column with the card gap
//   footer           string   pre-rendered footer, pinned to the bottom; split: the white lower part
//   href             string   whole card as <a> with hover; ignored when the card already contains
//                             links/buttons (then only those are clickable)
//   link_attributes  array    extra attributes of that <a>, e.g. target/rel
//   tag              string   div (default) | article | section | li (with href: <li> around the <a>)
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];
$c = hengegroup_theme_get_card_classes();

$variant = trim((string) ($config['variant'] ?? 'default'));
$accent = trim((string) ($config['accent'] ?? ''));
$accent_border = !empty($config['accent_border']);
$kicker = trim((string) ($config['kicker'] ?? ''));
$title = trim((string) ($config['title'] ?? ''));
$title_tag = strtolower(trim((string) ($config['title_tag'] ?? 'h3')));
$title_variant = trim((string) ($config['title_variant'] ?? 'body-lg'));
$description = trim((string) ($config['description'] ?? ''));
$icon = trim((string) ($config['icon'] ?? ''));
$icon_class = trim((string) ($config['icon_class'] ?? ''));
$leading = trim((string) ($config['leading'] ?? ''));
$image_config = is_array($config['image'] ?? null) ? $config['image'] : null;
$content = trim((string) ($config['content'] ?? ''));
$footer = trim((string) ($config['footer'] ?? ''));
$href = trim((string) ($config['href'] ?? ''));
$link_attributes = is_array($config['link_attributes'] ?? null) ? $config['link_attributes'] : [];
$tag = strtolower(trim((string) ($config['tag'] ?? 'div')));
$orientation = trim((string) ($config['orientation'] ?? 'vertical'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (!in_array($variant, $c['variants'], true)) {
    $variant = 'default';
}

$accent_classes = hengegroup_theme_get_accent_classes(
    $accent,
    $c['defaultAccent'][$variant] ?? $c['defaultAccent']['other'],
);

if (!in_array($title_tag, ['h2', 'h3', 'h4', 'h5', 'h6'], true)) {
    $title_tag = 'h3';
}

if (!in_array($title_variant, ['body-lg', 'body-base', 'body-sm', 'headline-sm'], true)) {
    $title_variant = 'body-lg';
}

if (!in_array($tag, ['div', 'article', 'section', 'li'], true)) {
    $tag = 'div';
}

if (!in_array($orientation, ['vertical', 'horizontal'], true)) {
    $orientation = 'vertical';
}

$is_split = $variant === 'split';
$shape = in_array($variant, $c['plainVariants'], true) ? 'plain' : 'raised';
$on_dark = in_array($variant, $c['darkVariants'], true);
$padding = $variant === 'ghost' ? '' : $c['padding'];

$media_markup = '';

if ($image_config !== null) {
    $image_markup = hengegroup_theme_render_image($image_config);

    if (trim($image_markup) !== '') {
        $media_markup = sprintf(
            '<div class="%1$s" data-slot="card-media">%2$s</div>',
            esc_attr($c['media'][$orientation]),
            $image_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    }
}

$horizontal = $orientation === 'horizontal' && $media_markup !== '';

$heading_markup =
    ($title !== ''
        ? hengegroup_theme_render_typography([
            'variant' => $title_variant,
            'tag' => $title_tag,
            'text' => $title,
            'data_slot' => 'card-title',
            'color' => $on_dark ? 'light' : 'default',
            // Headlines bring their own weight.
            'class' => str_starts_with($title_variant, 'body-') ? $c['title'] : '',
        ])
        : '') .
    ($description !== ''
        ? hengegroup_theme_render_typography([
            'variant' => 'body-sm',
            'text' => $description,
            'data_slot' => 'card-description',
            'class' =>
                $c['description'][$on_dark ? 'dark' : ($variant === 'ghost' ? 'ghost' : 'light')],
        ])
        : '');

if ($icon !== '') {
    $leading = sprintf(
        '<span class="%1$s" data-slot="card-icon">%2$s</span>',
        esc_attr(
            $c['iconBox'] .
                ' ' .
                ($icon_class !== '' ? $icon_class : $c['icon'][$on_dark ? 'dark' : 'light']),
        ),
        $icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

// The subline sits next to the leading visual, under the title.
if ($leading !== '') {
    $heading_markup = sprintf(
        '<div class="%1$s" data-slot="card-title-row">%2$s%3$s</div>',
        esc_attr($c['titleRow']),
        $leading, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $heading_markup !== '' ? '<div class="min-w-0 flex-1">' . $heading_markup . '</div>' : '',
    );
}

$top_markup =
    ($kicker !== '' ? '<div data-slot="card-kicker">' . $kicker . '</div>' : '') .
    ($heading_markup !== '' ? '<div data-slot="card-header">' . $heading_markup . '</div>' : '') .
    ($content !== ''
        ? sprintf(
            '<div class="%1$s" data-slot="card-content">%2$s</div>',
            esc_attr($c['content']),
            $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        )
        : '');

$footer_markup =
    $footer !== ''
        ? sprintf(
            '<div class="%1$s" data-slot="card-footer">%2$s</div>',
            esc_attr(
                $is_split
                    ? $c['splitFooter'] .
                        ' ' .
                        ($top_markup !== '' ? $c['splitFooterPadding'] : $padding)
                    : $c['footer'],
            ),
            $footer, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        )
        : '';

if ($media_markup . $top_markup . $footer_markup === '') {
    return;
}

if ($is_split) {
    $body_class = $c['splitBody'];
    $body_html =
        ($top_markup !== ''
            ? sprintf(
                '<div class="%1$s" data-slot="card-split">%2$s</div>',
                esc_attr(
                    "{$c['splitTop']} {$accent_classes['bg']} " .
                        ($footer_markup !== '' ? $c['splitTopPadding'] : $padding),
                ),
                $top_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            )
            : '') . $footer_markup;
} else {
    $body_class = trim("{$c['body']} {$padding}");
    $body_html = $top_markup . $footer_markup;
}

$body_markup =
    $body_html !== ''
        ? sprintf(
            '<div class="%1$s" data-slot="card-body">%2$s</div>',
            esc_attr($body_class),
            $body_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        )
        : '';

// Links/buttons inside cannot be nested in an <a>, and two click targets would compete.
if (
    $href !== '' &&
    preg_match('/<(a|button|input|select|textarea)\b/i', $kicker . $content . $footer) === 1
) {
    $href = '';
}

$computed_class = implode(' ', [
    $c['surface'][$variant],
    $variant === 'filled' ? $accent_classes['bg'] : '',
    $c['root'],
    $c['shape'][$shape],
    $horizontal ? $c['horizontal'] : '',
    // Media and split bands bleed to the edge; without them overflow stays visible for popovers.
    $media_markup !== '' || $is_split ? $c['clip'] : '',
    $accent_border ? "{$c['accentBorder']} {$accent_classes['border']}" : '',
    $href !== '' ? $c['link'] . ' ' . ($c['hover'][$variant] ?? $c['hover']['raised']) : '',
    $class_name,
]);

$element_attributes = $href !== '' ? $link_attributes + $attributes : $attributes;
$element_attributes['class'] = trim((string) preg_replace('/\s+/', ' ', $computed_class));
$element_attributes['data-slot'] = 'card';
$element_attributes['data-variant'] = $variant;
$element_attributes['data-orientation'] = $horizontal ? 'horizontal' : 'vertical';

if ($href !== '') {
    $element_attributes['href'] = $href;
}

$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);

$card_markup = sprintf(
    '<%1$s%2$s>%3$s%4$s</%1$s>',
    esc_html($href !== '' ? 'a' : $tag),
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $media_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $body_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

// The <li> stretches the link to the grid row height like an unlinked card.
if ($href !== '' && $tag === 'li') {
    $card_markup = '<li class="flex flex-col *:flex-1">' . $card_markup . '</li>';
}

echo $card_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
