<?php

declare(strict_types=1);

// Kontaktkarte. Zeilendaten aus hengegroup_theme_get_contact_rows() (wie footer.php). Rendert nichts
// ohne Name, E-Mail und (bei split) Adresse. Aufruf als String: hengegroup_theme_render_contact_card().
//
// Args:
//   contact   array    Pflicht. { name, role, email, phone, fax, address (mehrzeilig) }
//   variant   string   light (default) | dark | split (oben henge-grey, darunter Adresse)
//   label     string   kleine Ueberschrift ueber dem Namen
//   class     string   an den Wrapper angehaengt

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

foreach (hengegroup_theme_get_contact_rows($email, $phone, $fax) as $contact_row) {
    $rows .= sprintf(
        $row_template,
        $icon($contact_row['icon']),
        '<span class="sr-only">' .
            esc_html($contact_row['label']) .
            ' </span>' .
            ($contact_row['href'] !== null
                ? sprintf(
                    '<a class="%1$s" href="%2$s">%3$s</a>',
                    esc_attr($link_class),
                    esc_attr($contact_row['href']),
                    esc_html($contact_row['text']),
                )
                : esc_html($contact_row['text'])),
    );
}

$wrapper_class = trim(
    'overflow-hidden rounded-card-raised shadow-card-raised ' .
        ($variant === 'split' ? '' : $surface_class . ' ') .
        $class_name,
);
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
