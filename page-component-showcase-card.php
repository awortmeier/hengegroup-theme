<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Card
 *
 * Dev-Seite zur visuellen Pruefung der Komponente (Seite mit noindex anlegen).
 */

get_header();

$placeholder_image = static function (string $fill, string $class): array {
    return [
        'src' =>
            "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 200'%3E" .
            "%3Crect width='320' height='200' fill='%23{$fill}'/%3E%3C/svg%3E",
        'alt' => '',
        'decorative' => true,
        'class' => $class,
    ];
};

$icon = static fn(string $name): string => hengegroup_theme_render_icon([
    'name' => $name,
    'set' => 'lucide',
]);

$text = static fn(string $value, string $class = ''): string => hengegroup_theme_render_typography([
    'variant' => 'body-sm',
    'text' => $value,
    'class' => trim('text-pretty ' . $class),
]);

$button = static function (array $config): string {
    ob_start();
    get_template_part('template-parts/base/button', null, ['config' => $config]);

    return (string) ob_get_clean();
};

$contact_rows = static function (string $text_class): string {
    $rows = '';

    foreach (
        hengegroup_theme_get_contact_rows('info@hengegroup.com', '+49 2302 1234-0', '')
        as $row
    ) {
        $rows .= sprintf(
            '<li class="flex items-center gap-2.5 %1$s">%2$s<a class="underline-offset-4 hover:underline" href="%3$s">%4$s</a></li>',
            esc_attr($text_class),
            hengegroup_theme_render_icon([
                'name' => $row['icon'],
                'set' => 'lucide',
                'class' => 'size-4 shrink-0',
            ]),
            esc_attr((string) $row['href']),
            esc_html($row['text']),
        );
    }

    return '<ul class="flex flex-col gap-1 text-base">' . $rows . '</ul>';
};

$address =
    '<p class="text-lg font-extrabold">Adresse</p><address class="text-base not-italic">Hengegroup GmbH<br>Musterstraße 1<br>58453 Witten</address>';

$card = static function (string $caption, array $config): void {
    echo '<figure class="flex flex-col gap-3">';
    echo hengegroup_theme_render_card($config); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    printf(
        '<figcaption class="text-sm text-neutral-500">%s</figcaption>',
        wp_kses($caption, ['code' => []]),
    );
    echo '</figure>';
};
?>

<div class="mx-auto max-w-6xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Card — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        <code>template-parts/base/card.php</code>. Dev-only, nicht für Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Flächen (<code>variant</code>)</h2>
        <div class="grid grid-cols-1 items-start gap-6 md:grid-cols-3">
            <?php
            $card('<code>default</code>', [
                'title' => 'Default',
                'description' => 'Weiße Fläche mit Schatten.',
                'content' => $text('Standard für alle Karten.'),
            ]);
            $card('<code>default</code> mit Icon', [
                'title' => 'Mit Icon',
                'description' => 'Subline neben dem Icon.',
                'icon' => $icon('leaf'),
                'content' => $text('Icon-Box links neben Titel und Subline.'),
            ]);
            $card('<code>default</code> + <code>accent_border</code>', [
                'accent_border' => true,
                'accent' => 'henge-green',
                'title' => 'Mit Farbrand',
                'content' => $text('Linker Rand in der Akzentfarbe.'),
            ]);

            foreach (['grey-dark', 'henge-grey', 'henge-blue', 'henge-green'] as $accent) {
                $card("<code>filled</code> {$accent}", [
                    'variant' => 'filled',
                    'accent' => $accent,
                    'title' => 'Einfarbig',
                    'description' => 'Subline in gedämpfter Schrift.',
                    'content' => $text('Fläche in der Akzentfarbe, helle Schrift.'),
                ]);
            }

            $card('<code>split</code>: oben Akzent, <code>footer</code> unten weiß', [
                'variant' => 'split',
                'accent' => 'henge-blue',
                'title' => 'Split',
                'description' => 'Oberer Bereich',
                'footer' => $text('Unterer Bereich auf Weiß.'),
            ]);
            ?>
        </div>
        <div class="mt-6 grid grid-cols-1 items-start gap-6 rounded-2xl section-dark p-8 md:grid-cols-2">
            <?php
            $card('<code>ghost</code>: keine Fläche, kein Padding, erbt die Textfarbe', [
                'variant' => 'ghost',
                'title' => 'Ghost',
                'icon' => $icon('coins'),
                'icon_class' => 'bg-linear-135 brand-gradient text-grey-light',
                'content' => $text('Für Inhalte direkt auf dunklen Flächen.', 'opacity-85'),
            ]);
            $card('<code>glass</code> + <code>accent_border</code>', [
                'variant' => 'glass',
                'accent_border' => true,
                'accent' => 'henge-green',
                'title' => 'Glass',
                'content' => $text('Halbtransparent mit Blur, z. B. über Bildern.'),
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Einsatz auf der Website</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Jede Karte der Seite als Kombination aus Variante und Slots.
        </p>
        <div class="grid grid-cols-1 items-start gap-6 md:grid-cols-2 lg:grid-cols-3">
            <?php
            $card('Produkte / Produktdetail / Anwendung – Split (Kontakt)', [
                'variant' => 'split',
                'accent' => 'henge-green',
                'title' => 'Kontakt',
                'content' => $contact_rows('text-grey-light'),
                'footer' => $address,
            ]);
            $card('Produktdetail – Einfarbig mit Icon', [
                'variant' => 'filled',
                'accent' => 'henge-blue',
                'title' => 'Strahltechnik',
                'title_variant' => 'body-base',
                'icon' => $icon('spray-can'),
            ]);
            $card('Produktdetail – Einfarbig mit Icon und Subline', [
                'variant' => 'filled',
                'accent' => 'henge-blue',
                'title' => 'Strahltechnik',
                'title_variant' => 'body-base',
                'icon' => $icon('spray-can'),
                'content' => $text(
                    'Entzundern, Entrosten und Aufrauen von Oberflächen.',
                    'text-grey-light/80',
                ),
            ]);
            $card('Karriere / Karrieredetail – Einfarbig', [
                'variant' => 'filled',
                'title' => 'Anna Muster',
                'description' => 'Personalreferentin',
                'content' => $contact_rows('text-grey-light'),
            ]);
            $card('Karriere – Einfarbig mit Bild (<code>orientation: horizontal</code>)', [
                'variant' => 'filled',
                'orientation' => 'horizontal',
                'image' => $placeholder_image('9aa3a6', 'absolute inset-0 size-full object-cover'),
                'title' => 'Anna Muster',
                'description' => 'Personalreferentin',
                'content' => $contact_rows('text-grey-light'),
            ]);
            $card('Karrieredetail – Fact Sheet', [
                'title' => 'Auf einen Blick',
                'content' =>
                    '<dl class="grid grid-cols-2 gap-4 text-base"><div><dt class="text-sm text-muted-foreground">Standort</dt><dd class="font-semibold">Witten</dd></div><div><dt class="text-sm text-muted-foreground">Arbeitszeit</dt><dd class="font-semibold">Vollzeit</dd></div><div><dt class="text-sm text-muted-foreground">Eintritt</dt><dd class="font-semibold">ab sofort</dd></div><div><dt class="text-sm text-muted-foreground">Bereich</dt><dd class="font-semibold">Produktion</dd></div></dl>',
            ]);
            $card('Hengegroup – Historie', [
                'title' => '1998 – Gründung',
                'title_variant' => 'body-base',
                'content' => $text('Start mit drei Mitarbeitenden in Witten.'),
            ]);
            $card('Hengegroup – Evolution mit Icon', [
                'title' => 'Nachhaltigkeit',
                'title_variant' => 'body-base',
                'icon' => $icon('leaf'),
                'icon_class' => 'bg-henge-green text-henge-green-foreground',
                'content' => $text('Kreislaufwirtschaft und Recycling von Strahlmitteln.'),
            ]);
            $card('Hengegroup – Leitung, Bild oben', [
                'image' => $placeholder_image('646a6c', 'aspect-4/3 w-full object-cover'),
                'title' => 'Max Muster',
                'title_variant' => 'body-base',
                'description' => 'Geschäftsführer',
            ]);
            $card('Hengegroup – Unternehmen der Gruppe, Rand links, ganze Karte als Link', [
                'accent_border' => true,
                'accent' => 'henge-blue',
                'href' => '#',
                'content' => $text('Kominex liefert Mineralien für Industrie und Bau.'),
            ]);
            $card('Mit Button: <code>href</code> wird ignoriert, nur der Button ist klickbar', [
                'accent_border' => true,
                'accent' => 'henge-blue',
                'href' => '#',
                'content' => $text('Kominex liefert Mineralien für Industrie und Bau.'),
                'footer' => $button([
                    'variant' => 'outline',
                    'size' => 'sm',
                    'text' => 'Zum Unternehmen',
                    'href' => '#',
                ]),
            ]);
            $card('Standort – Split mit Zeiten', [
                'variant' => 'split',
                'title' => 'Werk Witten',
                'content' =>
                    '<address class="text-base not-italic">Musterstraße 1<br>58453 Witten</address>',
                'footer' =>
                    '<p class="text-lg font-extrabold">Ladezeiten</p><p class="text-base">Mo–Fr 7–15 Uhr</p>',
            ]);
            $card('Ansprechpartner – Split mit Bild', [
                'variant' => 'split',
                'accent' => 'henge-blue',
                'orientation' => 'horizontal',
                'image' => $placeholder_image(
                    '9aa3a6',
                    'absolute inset-0 size-full object-cover object-top',
                ),
                'title' => 'Max Muster',
                'description' => 'Vertrieb',
                'footer' => $contact_rows('text-grey-dark'),
            ]);
            ?>
        </div>
        <div class="mt-6 grid grid-cols-1 items-start gap-6 rounded-2xl section-dark p-8 md:grid-cols-2">
            <?php
            $card('Karriere – Benefit transparent', [
                'variant' => 'ghost',
                'title' => '30 Tage Urlaub',
                'title_variant' => 'body-base',
                'icon' => $icon('tree-palm'),
                'icon_class' => 'bg-linear-135 brand-gradient text-grey-light',
                'content' => $text('Plus Sonderurlaub zu besonderen Anlässen.', 'opacity-85'),
            ]);
            $card('Startseite – Bühne als Glass-Karte', [
                'variant' => 'glass',
                'accent_border' => true,
                'accent' => 'henge-green',
                'title' => 'Rohstoffe für die Industrie',
                'title_tag' => 'h2',
                'content' => $text('Strahlmittel, Schleifmittel und Feuerfest aus einer Hand.'),
                'footer' => $button([
                    'variant' => 'henge-green',
                    'size' => 'sm',
                    'text' => 'Produkte',
                    'href' => '#',
                ]),
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-2 text-xl font-semibold">Links und Hover (<code>href</code>)</h2>
        <p class="mb-6 text-sm text-neutral-500">
            Ohne Links/Buttons im Inhalt wird die ganze Karte zum <code>&lt;a&gt;</code> mit Hover,
            sonst sind nur die Buttons klickbar und die Karte hat keinen Hover.
        </p>
        <div class="grid grid-cols-1 items-start gap-6 md:grid-cols-3">
            <?php
            $card('<code>default</code>, ganze Karte als Link', [
                'href' => '#',
                'title' => 'Stelle: Industriemechaniker (m/w/d)',
                'title_variant' => 'body-base',
                'description' => 'Witten · Vollzeit',
            ]);
            $card('<code>filled</code> als Link', [
                'variant' => 'filled',
                'accent' => 'henge-green',
                'href' => '#',
                'title' => 'Schleifmittel',
                'icon' => $icon('arrow-right'),
            ]);
            $card('<code>default</code> + Bild links, als Link', [
                'orientation' => 'horizontal',
                'href' => '#',
                'image' => $placeholder_image('3b875e', 'absolute inset-0 size-full object-cover'),
                'title' => 'Strahltechnik',
                'content' => $text('Anwendung mit Bild links.'),
            ]);
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">
            Leading und Kicker (<code>leading</code>, <code>kicker</code>)
        </h2>
        <div class="grid grid-cols-1 items-start gap-6 md:grid-cols-2">
            <?php
            $card('<code>leading</code>: Siegel/Logo links neben Titel und Subline', [
                'leading' =>
                    '<span class="flex size-19 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-semibold">ISO</span>',
                'title' => 'ISO-Zertifizierung',
                'title_variant' => 'body-base',
                'description' => 'seit 2015',
                'content' => $text('Zertifiziert nach ISO 9001:2015.'),
                'footer' => $button([
                    'variant' => 'outline',
                    'text' => 'Zertifikat (PDF)',
                    'href' => '#',
                    'class' => 'ml-auto',
                ]),
            ]);
            ob_start();
            get_template_part('template-parts/base/badge', null, [
                'config' => ['text' => 'IMEXCO', 'variant' => 'henge-blue', 'font' => 'accent'],
            ]);
            $badge = (string) ob_get_clean();
            ?>
            <div class="rounded-2xl section-dark p-8">
                <?php $card('<code>kicker</code> über einem <code>headline-sm</code>-Titel', [
                    'variant' => 'glass',
                    'accent_border' => true,
                    'accent' => 'henge-blue',
                    'kicker' => $badge,
                    'title' => 'Qualität für Industrie',
                    'title_tag' => 'h2',
                    'title_variant' => 'headline-sm',
                    'content' => $text('Industriemineralien und maßgeschneiderte Lösungen.'),
                ]); ?>
            </div>
        </div>
    </section>
</div>

<?php get_footer(); ?>
