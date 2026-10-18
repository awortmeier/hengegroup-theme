<?php

declare(strict_types=1);

// Kontaktkarte auf Basis von card.php. Zeilendaten aus hengegroup_theme_get_contact_rows() (wie
// footer.php). Rendert nichts ohne Name, E-Mail und Adresse. Aufruf als String:
// hengegroup_theme_render_contact_card().
//
// Args:
//   contact   array    Pflicht. { name, role, email, phone, fax, address (mehrzeilig) }
//   variant   string   filled (default) | split | default
//   accent    string   henge-grey | henge-blue | henge-green | grey-dark (card.php-Default)
//   lower     string   address (default bei split) | contact (oben nur Name/Funktion, unten
//                      E-Mail/Telefon/Fax) | hours (oben Kontakt + Adresse, unten Zeiten);
//                      bei split der weisse untere Teil, sonst darunter. Ohne: nur Kontaktzeilen.
//   hours     array    nur bei lower = hours: { label, lines }, z. B. Ladezeiten
//   image     int      Attachment-ID, Porträt links (dekorativ, Name folgt direkt)
//   tag       string   wie card.php (default div)
//   class     string   an die Karte angehaengt

$contact = is_array($args['contact'] ?? null) ? $args['contact'] : [];
$name = trim((string) ($contact['name'] ?? ''));
$role = trim((string) ($contact['role'] ?? ''));
$email = trim((string) ($contact['email'] ?? ''));
$phone = trim((string) ($contact['phone'] ?? ''));
$fax = trim((string) ($contact['fax'] ?? ''));

$variant = (string) ($args['variant'] ?? 'filled');
$variant = in_array($variant, ['filled', 'split', 'default'], true) ? $variant : 'filled';
$is_split = $variant === 'split';
$lower = (string) ($args['lower'] ?? ($is_split ? 'address' : ''));
$lower = in_array($lower, ['address', 'contact', 'hours'], true) ? $lower : '';
$image_id = (int) ($args['image'] ?? 0);

$address_lines = array_values(
    array_filter(
        array_map('trim', explode("\n", (string) ($contact['address'] ?? ''))),
        static fn(string $line): bool => $line !== '',
    ),
);

if ($lower === '' || $lower === 'contact') {
    $address_lines = [];
}

if ($name === '' && $email === '' && $address_lines === []) {
    return;
}

$hours = is_array($args['hours'] ?? null) ? $args['hours'] : [];
$hours_label = trim((string) ($hours['label'] ?? ''));
$hours_lines =
    $lower === 'hours'
        ? array_values(
            array_filter(
                array_map('trim', array_map('strval', (array) ($hours['lines'] ?? []))),
                static fn(string $line): bool => $line !== '',
            ),
        )
        : [];

$icon = static fn(string $name): string => hengegroup_theme_render_icon([
    'name' => $name,
    'set' => 'lucide',
    'class' => 'mt-1 size-4 shrink-0',
]);

$rows = '';

foreach (hengegroup_theme_get_contact_rows($email, $phone, $fax) as $contact_row) {
    // text-current: theme.json colours links via :where(), the card's text colour wins.
    $rows .= sprintf(
        '<li class="flex min-w-0 items-start gap-2.5">%1$s<span class="min-w-0 break-all"><span class="sr-only">%2$s </span>%3$s</span></li>',
        $icon($contact_row['icon']),
        esc_html($contact_row['label']),
        $contact_row['href'] !== null
            ? sprintf(
                '<a class="text-current underline-offset-4 hover:underline" href="%1$s">%2$s</a>',
                esc_attr($contact_row['href']),
                esc_html($contact_row['text']),
            )
            : esc_html($contact_row['text']),
    );
}

$rows_markup =
    $rows !== ''
        ? '<ul class="flex flex-col gap-1 text-base leading-normal">' . $rows . '</ul>'
        : '';
$address_markup =
    $address_lines !== []
        ? '<address class="flex items-start gap-2.5 text-base leading-normal not-italic">' .
            $icon('map-pin') .
            '<span>' .
            implode('<br>', array_map('esc_html', $address_lines)) .
            '</span></address>'
        : '';
$hours_markup =
    $hours_lines !== []
        ? ($hours_label !== '' ? hengegroup_theme_render_card_subheading($hours_label) : '') .
            '<p class="flex items-start gap-2.5 text-base leading-normal">' .
            $icon('clock') .
            '<span>' .
            implode('<br>', array_map('esc_html', $hours_lines)) .
            '</span></p>'
        : '';

switch ($lower) {
    case 'contact':
        $upper = '';
        $lower_markup = $rows_markup;
        break;
    case 'hours':
        $upper = $rows_markup . $address_markup;
        $lower_markup = $hours_markup;
        break;
    case 'address':
        $upper = $rows_markup;
        $lower_markup =
            $address_markup !== ''
                ? hengegroup_theme_render_card_subheading(__('Adresse', 'hengegroup-theme')) .
                    $address_markup
                : '';
        break;
    default:
        $upper = $rows_markup;
        $lower_markup = '';
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo hengegroup_theme_render_card([
    'variant' => $variant,
    'accent' => (string) ($args['accent'] ?? ''),
    'tag' => (string) ($args['tag'] ?? 'div'),
    'orientation' => 'horizontal',
    'image' =>
        $image_id > 0
            ? [
                'attachment_id' => $image_id,
                'size' => 'large',
                'decorative' => true,
                'loading' => 'lazy',
                'class' => 'absolute inset-0 size-full object-cover object-top',
            ]
            : null,
    'title' => $name,
    'title_variant' => 'body-base',
    'description' => $role,
    'content' => $is_split ? $upper : $upper . $lower_markup,
    'footer' => $is_split ? $lower_markup : '',
    'class' => trim((string) ($args['class'] ?? '')),
    'data_attributes' => ['component' => 'contact-card'],
]);
