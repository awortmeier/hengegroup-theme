<?php

declare(strict_types=1);

// Shared render helpers for template parts.

/**
 * ['class' => 'a', 'disabled' => true] -> ' class="a" disabled'. null/false are omitted.
 */
function hengegroup_theme_render_attributes(array $attributes): string
{
    $parts = [];

    foreach ($attributes as $name => $value) {
        $attribute_name = trim((string) $name);

        if ($attribute_name === '' || $value === null || $value === false) {
            continue;
        }

        if ($value === true) {
            $parts[] = $attribute_name;
            continue;
        }

        $parts[] = sprintf('%s="%s"', $attribute_name, esc_attr((string) $value));
    }

    return $parts === [] ? '' : ' ' . implode(' ', $parts);
}

/**
 * Merges `data_attributes` (keys without `data-`) into an attributes array.
 */
function hengegroup_theme_merge_data_attributes(array $attributes, array $data_attributes): array
{
    foreach ($data_attributes as $attribute_key => $attribute_value) {
        $data_name = trim((string) $attribute_key);

        if ($data_name === '') {
            continue;
        }

        $attributes['data-' . $data_name] = $attribute_value;
    }

    return $attributes;
}

/**
 * Escapes $content and wraps the given words in <span class="font-accent">, longest word first.
 */
function hengegroup_theme_render_accent_text(string $content, array $highlighted_words): string
{
    $needles = [];

    foreach ($highlighted_words as $word) {
        $word = trim((string) $word);

        if ($word === '') {
            continue;
        }

        $needles[$word] = true;
    }

    if ($needles === []) {
        return esc_html($content);
    }

    $needle_values = array_keys($needles);

    usort(
        $needle_values,
        static fn(string $left, string $right): int => strlen($right) <=> strlen($left),
    );

    $pattern =
        '/(' .
        implode(
            '|',
            array_map(static fn(string $word): string => preg_quote($word, '/'), $needle_values),
        ) .
        ')/';
    $parts = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE);

    if (!is_array($parts)) {
        return esc_html($content);
    }

    $output = '';

    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }

        if (isset($needles[$part])) {
            $output .= '<span class="font-accent">' . esc_html($part) . '</span>';
            continue;
        }

        $output .= esc_html($part);
    }

    return $output;
}

/**
 * RichText HTML with the "Akzent" format -> escaped text in which only <span class="font-accent">
 * survives. Not wp_kses(): it would allow any class on the span.
 */
function hengegroup_theme_render_accent_markup(string $html): string
{
    $open_tag = '<span class="font-accent">';
    $parts = preg_split(
        '#(' . preg_quote($open_tag, '#') . '|</span>)#',
        $html,
        -1,
        PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY,
    );

    if (!is_array($parts)) {
        return '';
    }

    $output = '';
    $depth = 0;

    foreach ($parts as $part) {
        if ($part === $open_tag) {
            $output .= $depth === 0 ? $open_tag : '';
            $depth++;
            continue;
        }

        if ($part === '</span>') {
            if ($depth > 0) {
                $depth--;
                $output .= $depth === 0 ? '</span>' : '';
            }
            continue;
        }

        $output .= esc_html(html_entity_decode(strip_tags($part), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    return $depth > 0 ? $output . '</span>' : $output;
}

function hengegroup_theme_render_icon(array $icon_config): string
{
    ob_start();
    get_template_part('template-parts/base/icon', null, ['config' => $icon_config]);

    return (string) ob_get_clean();
}

function hengegroup_theme_render_typography(array $typography_config): string
{
    ob_start();
    get_template_part('template-parts/base/typography', null, ['config' => $typography_config]);

    return (string) ob_get_clean();
}

function hengegroup_theme_render_card(array $card_config): string
{
    ob_start();
    get_template_part('template-parts/base/card', null, ['config' => $card_config]);

    return (string) ob_get_clean();
}

/**
 * Class lists of card.php, shared with the block editor (assets/js/blocks/shared.jsx).
 */
function hengegroup_theme_get_card_classes(): array
{
    static $classes = null;

    if ($classes === null) {
        $json = (string) file_get_contents(
            get_template_directory() . '/template-parts/base/card-classes.json',
        );
        $classes = (array) json_decode($json, true);
    }

    return $classes;
}

/**
 * Button variant that stays readable on the surface of a card variant.
 */
function hengegroup_theme_get_card_button_variant(string $card_variant): string
{
    $classes = hengegroup_theme_get_card_classes();

    return in_array($card_variant, (array) $classes['darkVariants'], true)
        ? (string) $classes['button']['dark']
        : (string) $classes['button']['light'];
}

/**
 * Subheading inside a card (e.g. "Adresse"), same look as a body-base card title.
 */
function hengegroup_theme_render_card_subheading(string $text): string
{
    return hengegroup_theme_render_typography([
        'variant' => 'body-base',
        'tag' => 'p',
        'text' => $text,
        'class' => 'font-extrabold',
    ]);
}

/**
 * target/rel for links to other hosts, which open in a new tab.
 */
function hengegroup_theme_get_link_attributes(string $url): array
{
    $host = $url !== '' ? (string) wp_parse_url($url, PHP_URL_HOST) : '';

    return $host !== '' && $host !== (string) wp_parse_url(home_url('/'), PHP_URL_HOST)
        ? ['target' => '_blank', 'rel' => 'noopener']
        : [];
}

/**
 * Badge in Versalien mit Akzentschrift.
 */
function hengegroup_theme_render_label_badge(
    string $text,
    string $variant,
    string $class = '',
): string {
    ob_start();
    get_template_part('template-parts/base/badge', null, [
        'config' => [
            'text' => $text,
            'variant' => $variant,
            'font' => 'accent',
            'class' => trim('uppercase tracking-wide ' . $class),
        ],
    ]);

    return (string) ob_get_clean();
}

/**
 * Empty string when image.php renders nothing (e.g. missing file).
 */
function hengegroup_theme_render_image(array $image_config): string
{
    ob_start();
    get_template_part('template-parts/base/image', null, ['config' => $image_config]);

    return (string) ob_get_clean();
}

/**
 * 'email' -> 'email-description'; deterministic so the control and the description match.
 */
function hengegroup_theme_field_description_id(string $control_id): string
{
    return $control_id . '-description';
}

function hengegroup_theme_field_error_id(string $control_id): string
{
    return $control_id . '-error';
}

/**
 * aria-describedby value, e.g. 'email-description email-error'. Pass false for parts that are not
 * rendered: an id pointing at nothing is worse than none.
 */
function hengegroup_theme_field_describedby(
    string $control_id,
    bool $has_description = true,
    bool $has_error = true,
): string {
    $ids = [];

    if ($has_description) {
        $ids[] = hengegroup_theme_field_description_id($control_id);
    }

    if ($has_error) {
        $ids[] = hengegroup_theme_field_error_id($control_id);
    }

    return implode(' ', $ids);
}

/**
 * Field family wrapper: field.php + field-label.php + control + field-error.php (when `error`).
 * aria_invalid/aria-describedby on the control stay the caller's job
 * (hengegroup_theme_field_error_id()).
 *
 * $field: control_id, control (HTML), label ('' if the control brings its own), error,
 * label_class, error_class, class.
 */
function hengegroup_theme_render_form_field(array $field): string
{
    $control_id = (string) ($field['control_id'] ?? '');
    $label = trim((string) ($field['label'] ?? ''));
    $error = trim((string) ($field['error'] ?? ''));

    ob_start();

    if ($label !== '') {
        get_template_part('template-parts/base/field/field-label', null, [
            'config' => [
                'for' => $control_id,
                'text' => $label,
                'class' => (string) ($field['label_class'] ?? ''),
            ],
        ]);
    }

    echo (string) ($field['control'] ?? ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-rendered by base components.

    if ($error !== '') {
        get_template_part('template-parts/base/field/field-error', null, [
            'config' => [
                'for' => $control_id,
                'text' => $error,
                'class' => (string) ($field['error_class'] ?? ''),
            ],
        ]);
    }

    $content = (string) ob_get_clean();

    ob_start();
    get_template_part('template-parts/base/field/field', null, [
        'config' => [
            'content' => $content,
            'invalid' => $error !== '',
            'class' => (string) ($field['class'] ?? ''),
        ],
    ]);

    return (string) ob_get_clean();
}

/**
 * WP_DEBUG hint for icon-only components without aria_label (_doing_it_wrong() is a no-op otherwise).
 */
function hengegroup_theme_warn_missing_aria_label(
    string $component,
    bool $is_icon_only,
    string $aria_label,
): void {
    if (!$is_icon_only || $aria_label !== '') {
        return;
    }

    _doing_it_wrong(
        esc_html($component),
        esc_html(
            sprintf(
                'Icon-only %s is missing a non-empty `aria_label` config value -- screen readers ' .
                    'have no accessible name for it.',
                $component,
            ),
        ),
        'hengegroup-theme 1.0.0',
    );
}

/**
 * Static position classes for a floating panel, e.g. bottom/start -> 'top-[calc(100%+10px)] left-0'.
 * Only the panel itself; arrows have their own per-component classes.
 */
function hengegroup_theme_floating_position_classes(
    string $side,
    string $align = 'center',
    int $gap_px = 10,
): string {
    if (!in_array($side, ['top', 'right', 'bottom', 'left'], true)) {
        $side = 'bottom';
    }

    $primary_axis_class = match ($side) {
        'top' => "bottom-[calc(100%+{$gap_px}px)]",
        'left' => "right-[calc(100%+{$gap_px}px)]",
        'right' => "left-[calc(100%+{$gap_px}px)]",
        default => "top-[calc(100%+{$gap_px}px)]", // 'bottom'
    };

    $cross_axis_class = in_array($side, ['top', 'bottom'], true)
        ? match ($align) {
            'start' => 'left-0',
            'end' => 'right-0',
            default => 'left-1/2 -translate-x-1/2', // 'center'
        }
        : match ($align) {
            'start' => 'top-0',
            'end' => 'bottom-0',
            default => 'top-1/2 -translate-y-1/2', // 'center'
        };

    return $primary_axis_class . ' ' . $cross_axis_class;
}

/**
 * Bento shapes for a 3-column grid that ends flush for any count: every 8 images form a 3x4 block,
 * the rest also fills whole rows. 'square' | 'wide' (2x1) | 'tall' (1x2) | 'full' (3x1).
 * Must match assets/js/blocks/galerie/layout.js.
 */
function hengegroup_theme_get_bento_tile_shapes(int $count): array
{
    $block = ['tall', 'square', 'square', 'wide', 'square', 'square', 'tall', 'wide'];
    $tails = [
        0 => [],
        1 => ['full'],
        2 => ['wide', 'square'],
        3 => ['square', 'square', 'square'],
        4 => ['tall', 'square', 'square', 'wide'],
        5 => ['tall', 'square', 'square', 'wide', 'full'],
        6 => ['tall', 'square', 'square', 'wide', 'wide', 'square'],
        7 => ['tall', 'square', 'square', 'wide', 'square', 'square', 'square'],
    ];

    if ($count <= 0) {
        return [];
    }

    $shapes = [];

    for ($i = 0; $i < intdiv($count, 8); $i++) {
        array_push($shapes, ...$block);
    }

    return array_merge($shapes, $tails[$count % 8]);
}

/**
 * contact-card.php as a string ('' when nothing to show).
 */
function hengegroup_theme_render_contact_card(array $contact, array $args = []): string
{
    ob_start();
    get_template_part(
        'template-parts/components/contact-card',
        null,
        [
            'contact' => $contact,
        ] + $args,
    );

    return trim((string) ob_get_clean());
}

/**
 * Spaltenklassen eines Kachel-Rasters: `full` verteilt die Kacheln auf die ganze Zeile (Spalten =
 * Anzahl, hoechstens 4), `grid-4` ist ein festes 4er-Raster, sonst gelten die Block-Klassen.
 */
function hengegroup_theme_get_tile_grid_class(
    string $layout,
    int $count,
    string $default_class,
): string {
    if ($layout === 'grid-4') {
        return 'sm:grid-cols-2 lg:grid-cols-4';
    }

    if ($layout !== 'full') {
        return $default_class;
    }

    return match (true) {
        $count <= 1 => '',
        $count === 2 => 'sm:grid-cols-2',
        $count === 3 => 'sm:grid-cols-2 lg:grid-cols-3',
        default => 'sm:grid-cols-2 lg:grid-cols-4',
    };
}

/**
 * Phone number -> tel: URI. The German trunk prefix "(0)" after the country code is dropped.
 */
function hengegroup_theme_phone_href(string $phone): string
{
    $phone = preg_replace('/\(0\)/', '', $phone) ?? $phone;
    $digits = preg_replace('/[^0-9+]/', '', $phone) ?? '';

    return $digits === '' ? '' : 'tel:' . $digits;
}

/**
 * Contact rows (e-mail, phone, fax) for contact-card.php and footer.php. The e-mail goes through
 * antispambot() (entities): output href with esc_attr(), text with esc_html() -- esc_url() would
 * double-encode. tel: is already esc_url()-ed; fax has no link.
 *
 * @return list<array{icon: string, label: string, text: string, href: ?string}>
 */
function hengegroup_theme_get_contact_rows(string $email, string $phone, string $fax): array
{
    $email = trim($email);
    $phone = trim($phone);
    $fax = trim($fax);
    $rows = [];

    if ($email !== '') {
        $obfuscated = antispambot($email);
        $rows[] = [
            'icon' => 'mail',
            'label' => __('E-Mail:', 'hengegroup-theme'),
            'text' => $obfuscated,
            'href' => 'mailto:' . $obfuscated,
        ];
    }

    $phone_href = $phone !== '' ? hengegroup_theme_phone_href($phone) : '';
    if ($phone_href !== '') {
        $rows[] = [
            'icon' => 'phone',
            'label' => __('Telefon:', 'hengegroup-theme'),
            'text' => $phone,
            'href' => esc_url($phone_href, ['tel']),
        ];
    }

    if ($fax !== '') {
        $rows[] = [
            'icon' => 'printer',
            'label' => __('Fax:', 'hengegroup-theme'),
            'text' => $fax,
            'href' => null,
        ];
    }

    return $rows;
}

/**
 * Background/border/text classes per brand color, as literals for the Tailwind scanner. Unknown
 * colors fall back to $fallback.
 *
 * @return array{bg: string, border: string, fg: string}
 */
function hengegroup_theme_get_accent_classes(string $color, string $fallback = 'grey-dark'): array
{
    $classes = [
        'henge-blue' => [
            'bg' => 'bg-henge-blue',
            'border' => 'border-henge-blue',
            'fg' => 'text-henge-blue-foreground',
        ],
        'henge-green' => [
            'bg' => 'bg-henge-green',
            'border' => 'border-henge-green',
            'fg' => 'text-henge-green-foreground',
        ],
        'henge-grey' => [
            'bg' => 'bg-henge-grey',
            'border' => 'border-henge-grey',
            'fg' => 'text-henge-grey-foreground',
        ],
        'grey-dark' => [
            'bg' => 'bg-grey-dark',
            'border' => 'border-grey-dark',
            'fg' => 'text-grey-dark-foreground',
        ],
    ];

    return $classes[$color] ?? ($classes[$fallback] ?? $classes['grey-dark']);
}

/**
 * Card variant of a card block. The allowed values are the `enum` of its `variant` attribute in
 * block.json (shared with the editor); without a valid value $fallback, else the first one.
 */
function hengegroup_theme_get_block_card_variant(
    array $attributes,
    ?WP_Block $block,
    string $fallback = '',
): string {
    $allowed =
        $block !== null && $block->block_type !== null
            ? array_values((array) ($block->block_type->attributes['variant']['enum'] ?? []))
            : [];

    return hengegroup_theme_pick_card_variant(
        (string) ($attributes['variant'] ?? ''),
        $allowed,
        $fallback,
    );
}

/**
 * Base config of a card block's card (<li>, variant from block.json, accent attribute); $config
 * adds to or overrides it.
 */
function hengegroup_theme_get_block_card_config(
    array $attributes,
    ?WP_Block $block,
    array $config = [],
    string $variant_fallback = '',
): array {
    return $config + [
        'tag' => 'li',
        'variant' => hengegroup_theme_get_block_card_variant(
            $attributes,
            $block,
            $variant_fallback,
        ),
        'accent' => (string) ($attributes['accent'] ?? ''),
    ];
}

function hengegroup_theme_pick_card_variant(
    string $variant,
    array $allowed,
    string $fallback = '',
): string {
    if (in_array($variant, $allowed, true)) {
        return $variant;
    }

    if (in_array($fallback, $allowed, true)) {
        return $fallback;
    }

    return (string) ($allowed[0] ?? 'default');
}
