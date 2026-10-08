<?php

declare(strict_types=1);

// Kontaktkarte als Komponente -- fuehrt die fruehere Produkt-Kontaktkarte (Produktdetailseite),
// die Job-Kontaktkarte (Karriereseite/Stellenangebot) und die Firmen-Kontaktkarte (Block "Kontakt")
// zusammen (Audit 2026-10-08 Punkt 7, Vorgaben 2026-10-08):
//   - Aufbau, Masse und Abstaende von der Produkt-Kontaktkarte: Ueberschrift (Name) in 19px
//     extrabold, Rolle als Unterzeile, `size-4`-Icons, `px-7 py-6`, Karten-Stufe `raised` aus tokens.css
//     (`rounded-card-raised`/`shadow-card-raised`). Kein Foto (explizite Vorgabe).
//   - Kontaktzeilen und Adresse in typography.php's `body-sm` (`text-base leading-normal`) -- eine
//     Stufe groesser als die Produktkarte (`text-sm`), Vorgabe 2026-10-08.
//   - `variant` waehlt die Flaechen:
//       light  ein Bereich, weiss, dunkle Schrift
//       dark   ein Bereich, grey-dark, helle Schrift
//       split  zwei Bereiche (aus der Firmen-Kontaktkarte): oben henge-grey mit Name/Rolle/
//              Kontaktzeilen, darunter weiss mit "Adresse" -- der zweite Bereich nur, wenn eine
//              Adresse gepflegt ist
//   - `fax` als Zeile ohne Link (kein sinnvolles Linkziel), "Fax:" fuer Screenreader.
//   - `address` wird nur in `split` ausgegeben (die einteiligen Varianten haben keinen Platz fuer
//     einen eigenen Adressblock).
//   - `label`: kleine Ueberschrift ueber dem Namen, wenn die Karte ohne eigene
//     Abschnitts-Ueberschrift steht. Aktuell nirgends genutzt (Stellen-Einzelseite zeigt seit
//     2026-10-08 auf Wunsch keine Ueberschrift mehr).
// Telefon-Link ueber hengegroup_theme_phone_href() + esc_url(), E-Mail ueber antispambot().
// Rendert nichts, wenn weder Name, E-Mail noch (bei `split`) Adresse gepflegt sind.
//
// Aufruf als String ueber hengegroup_theme_render_contact_card() (inc/template-parts/helpers.php).
//
// Supported args:
//   contact   array    Pflicht. { name, role, email, phone, fax, address } -- `name` ist die
//                      Ueberschrift des (oberen) Bereichs, bei der Firmenkarte z. B. "Kontakt";
//                      `address` mehrzeilig (Zeilenumbrueche). Weitere Schluessel (z. B. photo_id)
//                      werden ignoriert
//   variant   string   light | dark | split (default: light)
//   label     string   optionale kleine Ueberschrift ueber dem Namen
//   class     string   an den Wrapper angehaengt (z. B. `h-full`)

$contact = is_array($args['contact'] ?? null) ? $args['contact'] : [];
$name = trim((string) ($contact['name'] ?? ''));
$role = trim((string) ($contact['role'] ?? ''));
$email = trim((string) ($contact['email'] ?? ''));
$phone = trim((string) ($contact['phone'] ?? ''));
$fax = trim((string) ($contact['fax'] ?? ''));

$variant = (string) ($args['variant'] ?? 'light');
$variant = in_array($variant, ['light', 'dark', 'split'], true) ? $variant : 'light';
$label = trim((string) ($args['label'] ?? ''));
$class_name = trim((string) ($args['class'] ?? ''));

$address_lines =
    $variant === 'split'
        ? array_values(
            array_filter(
                array_map('trim', explode("\n", (string) ($contact['address'] ?? ''))),
                static fn(string $line): bool => $line !== '',
            ),
        )
        : [];

if ($name === '' && $email === '' && $address_lines === []) {
    return;
}

// Flaeche, Schrift, gedaempfte Schrift je Bereich.
$tones = [
    'light' => ['bg-white', 'text-grey-dark', 'text-grey-dark/60'],
    'dark' => ['bg-grey-dark', 'text-grey-light', 'text-grey-light/60'],
    'henge-grey' => ['bg-henge-grey', 'text-grey-light', 'text-grey-light/60'],
];
[$surface_class, $text_class, $muted_class] = $tones[
    $variant === 'split' ? 'henge-grey' : $variant
];

$icon = static fn(string $name, string $extra = ''): string => hengegroup_theme_render_icon([
    'name' => $name,
    'set' => 'lucide',
    'class' => trim('size-4 shrink-0 ' . $extra),
]);
$row_template = sprintf(
    '<li class="flex min-w-0 items-center gap-2.5 text-base leading-normal %1$s">%%1$s<span class="min-w-0 break-all">%%2$s</span></li>',
    esc_attr($text_class),
);
$link_class = $text_class . ' underline-offset-4 hover:underline';
$rows = '';

if ($email !== '') {
    $rows .= sprintf(
        $row_template,
        $icon('mail'),
        sprintf(
            '<a class="%1$s" href="mailto:%2$s">%3$s</a>',
            esc_attr($link_class),
            esc_attr(antispambot($email)),
            esc_html(antispambot($email)),
        ),
    );
}

$phone_href = $phone !== '' ? hengegroup_theme_phone_href($phone) : '';

if ($phone_href !== '') {
    $rows .= sprintf(
        $row_template,
        $icon('phone'),
        sprintf(
            '<a class="%1$s" href="%2$s">%3$s</a>',
            esc_attr($link_class),
            esc_url($phone_href, ['tel']),
            esc_html($phone),
        ),
    );
}

if ($fax !== '') {
    $rows .= sprintf(
        $row_template,
        $icon('printer'),
        '<span class="sr-only">' .
            esc_html__('Fax:', 'hengegroup-theme') .
            ' </span>' .
            esc_html($fax),
    );
}

$wrapper_class = trim(
    'overflow-hidden rounded-card-raised shadow-card-raised ' .
        ($variant === 'split' ? '' : $surface_class . ' ') .
        $class_name,
);
// Icon + Adresszeilen (einzeln escaped, mit <br> verbunden).
$address_markup =
    $icon('map-pin', 'mt-0.5') .
    '<span>' .
    implode('<br>', array_map('esc_html', $address_lines)) .
    '</span>';
$main_class = trim('px-7 py-6 ' . ($variant === 'split' ? $surface_class : ''));
?>
<div class="<?php echo esc_attr(
    $wrapper_class,
); ?>" data-slot="contact-card" data-variant="<?php echo esc_attr($variant); ?>">
  <?php if ($name !== '' || $label !== '' || $rows !== ''): ?>
    <div class="<?php echo esc_attr($main_class); ?>" data-slot="contact-card-main">
      <?php if ($label !== ''): ?>
        <p class="<?php echo esc_attr('mb-2 text-sm ' . $muted_class); ?>">
          <?php echo esc_html($label); ?>
        </p>
      <?php endif; ?>
      <?php if ($name !== ''): ?>
        <p class="<?php echo esc_attr('mb-1 text-[19px] font-extrabold ' . $text_class); ?>">
          <?php echo esc_html($name); ?>
        </p>
      <?php endif; ?>
      <?php if ($role !== ''): ?>
        <p class="<?php echo esc_attr('mb-4.5 text-sm ' . $muted_class); ?>">
          <?php echo esc_html($role); ?>
        </p>
      <?php else: ?>
        <div class="mb-3.5"></div>
      <?php endif; ?>
      <?php if ($rows !== ''): ?>
        <ul class="flex flex-col gap-2.5">
          <?php printf(
              '%s',
              $rows, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          ); ?>
        </ul>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <?php if ($address_lines !== []): ?>
    <div class="bg-white px-7 py-6" data-slot="contact-card-address">
      <p class="mb-3.5 text-[19px] font-extrabold text-grey-dark">
        <?php esc_html_e('Adresse', 'hengegroup-theme'); ?>
      </p>
      <address class="flex items-start gap-2.5 text-base leading-normal text-grey-dark not-italic">
        <?php printf(
            '%s',
            $address_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        ); ?>
      </address>
    </div>
  <?php endif; ?>
</div>
