<?php

declare(strict_types=1);

// Daten- und Render-Helfer fuer den Produktbereich: Produktdetailseite
// (woocommerce/single-product.php), Anwendungen (Taxonomie `produkt_anwendung`, Block
// "Anwendungsgruppe"), Block "Produktkategorie" und die Ansprechpartner-Karte. Post-Type/Routing:
// inc/setup/theme-products.php, Backend-Felder: inc/setup/theme-products-admin.php.
//
// Gleiche Aufteilung wie inc/template-parts/careers.php: nur Funktionen, keine Hooks beim Einbinden,
// damit die reinen Logik-Helfer (Analyse-Zeilen, Auffuellen der verwandten Produkte) per Brain
// Monkey testbar bleiben (tests/Unit/ProductsTest.php).
//
// Datenmodell (siehe docs/entscheidungen.md "Produktbereich: Datenmodell"):
//   - Anwendungen: hierarchische, nicht oeffentliche Taxonomie `produkt_anwendung` am Produkt --
//     Ebene 1 = Gruppen (Sektionen der Seite /anwendungen/, Kicker/Farbe/Ueberschrift wie bei den
//     Produktkategorien), Ebene 2 = Anwendungen (Beschreibung, Bild, Icon, Reihenfolge). Produkte
//     bekommen nur Anwendungen der Ebene 2; die Verknuepfung liegt einmal in WordPress' eigener
//     Term-Zuordnung und ist in beide Richtungen abfragbar (Produkt -> Anwendungen per
//     get_the_terms(), Anwendung -> Produkte per tax_query). Keine Einzelseiten (explizite Vorgabe
//     2026-10-07: Anwendungen haben nur eine Uebersichtsseite).
//   - Produktkategorien (`product_cat`): Sektionen der Produktuebersicht, mit Kicker/Farbe/
//     Ueberschrift/Ansprechpartner als Term-Meta.
//   - Koernungen: globales WooCommerce-Attribut `pa_koernung` -- wird spaeter, sobald Produkte
//     bestellbar sind, zur Variantenauswahl ("Fuer Variationen verwenden").
//   - Chemische Analyse/Downloads/Recycling-Hinweis: Produkt-Meta aus dem Tab "Technische Daten".

const HENGEGROUP_THEME_ANWENDUNG_TAXONOMY = 'produkt_anwendung';
const HENGEGROUP_THEME_PRODUCT_OPTION = 'hengegroup_theme_product_options';
const HENGEGROUP_THEME_GRAIN_ATTRIBUTE = 'koernung';
const HENGEGROUP_THEME_RELATED_PRODUCTS_LIMIT = 4;

/**
 * Produkt-Meta-Keys des Tabs "Technische Daten".
 */
function hengegroup_theme_get_product_meta_keys(): array
{
    return [
        'recycling' => '_hengegroup_theme_recycling_text',
        'analysis' => '_hengegroup_theme_analysis',
        'downloads' => '_hengegroup_theme_downloads',
    ];
}

/**
 * Icon-Auswahl fuer Anwendungen (Karten "Anwendungsbereiche" auf der Produktdetailseite). Literale Konfigurationen, damit scripts/find-lucide-icons.php sie beim
 * Build findet.
 */
function hengegroup_theme_get_anwendung_icons(): array
{
    return [
        'disc' => [
            __('Scheibe / Schleifen', 'hengegroup-theme'),
            ['name' => 'disc', 'set' => 'lucide'],
        ],
        'flame' => [
            __('Flamme / Hitze', 'hengegroup-theme'),
            ['name' => 'flame', 'set' => 'lucide'],
        ],
        'spray-can' => [
            __('Strahlen / Spruehen', 'hengegroup-theme'),
            ['name' => 'spray-can', 'set' => 'lucide'],
        ],
        'droplets' => [
            __('Wasser / Schneiden', 'hengegroup-theme'),
            ['name' => 'droplets', 'set' => 'lucide'],
        ],
        'gem' => [__('Praezision', 'hengegroup-theme'), ['name' => 'gem', 'set' => 'lucide']],
        'factory' => [
            __('Industrie', 'hengegroup-theme'),
            ['name' => 'factory', 'set' => 'lucide'],
        ],
        'hammer' => [
            __('Werkzeug / Bearbeitung', 'hengegroup-theme'),
            ['name' => 'hammer', 'set' => 'lucide'],
        ],
        'building' => [__('Bau', 'hengegroup-theme'), ['name' => 'building-2', 'set' => 'lucide']],
        'recycle' => [
            __('Recycling', 'hengegroup-theme'),
            ['name' => 'recycle', 'set' => 'lucide'],
        ],
        'flask' => [
            __('Labor / Analyse', 'hengegroup-theme'),
            ['name' => 'flask-conical', 'set' => 'lucide'],
        ],
        'truck' => [__('Logistik', 'hengegroup-theme'), ['name' => 'truck', 'set' => 'lucide']],
        'layers' => [
            __('Oberflaeche', 'hengegroup-theme'),
            ['name' => 'layers', 'set' => 'lucide'],
        ],
    ];
}

/**
 * Hintergrundklasse je Badge-Farbe (hengegroup_theme_get_badge_variants()) -- fuer Flaechen in
 * Produktfarbe (Icon-Kacheln der Anwendungsbereiche).
 */
function hengegroup_theme_get_variant_background_class(string $variant): string
{
    return [
        'henge-blue' => 'bg-henge-blue text-henge-blue-foreground',
        'henge-green' => 'bg-henge-green text-henge-green-foreground',
        'henge-grey' => 'bg-henge-grey text-henge-grey-foreground',
        'grey-dark' => 'bg-grey-dark text-grey-dark-foreground',
    ][$variant] ?? 'bg-grey-dark text-grey-dark-foreground';
}

/**
 * Bereinigt die Zeilen der chemischen Analyse aus den parallelen Formular-Arrays (Bezeichnung[] /
 * Wert[]) -- Zeilen ohne Bezeichnung UND Wert fallen weg, Reihenfolge bleibt erhalten.
 *
 * @param array<int, mixed> $labels
 * @param array<int, mixed> $values
 * @return list<array{label: string, value: string}>
 */
function hengegroup_theme_normalize_analysis_rows(array $labels, array $values): array
{
    $rows = [];

    foreach (array_values($labels) as $index => $label) {
        $label = trim((string) $label);
        $value = trim((string) (array_values($values)[$index] ?? ''));

        if ($label === '' && $value === '') {
            continue;
        }

        $rows[] = ['label' => $label, 'value' => $value];
    }

    return $rows;
}

/**
 * Verwandte Produkte: zuerst die manuell gesetzten (WooCommerce "Up-Sells", im Backend als
 * "Verwandte Produkte" beschriftet), dann mit passenden Zufallsprodukten auf `$limit` aufgefuellt.
 * Ohne Duplikate und ohne das Produkt selbst.
 *
 * @param int[] $manual_ids
 * @param int[] $fallback_ids
 * @return int[]
 */
function hengegroup_theme_merge_related_product_ids(
    array $manual_ids,
    array $fallback_ids,
    int $self_id,
    int $limit,
): array {
    $ids = [];

    foreach (array_merge($manual_ids, $fallback_ids) as $id) {
        $id = (int) $id;

        if ($id <= 0 || $id === $self_id || in_array($id, $ids, true)) {
            continue;
        }

        $ids[] = $id;

        if (count($ids) >= $limit) {
            break;
        }
    }

    return $ids;
}

/**
 * IDs der verwandten Produkte (siehe hengegroup_theme_merge_related_product_ids()). Die Auffuellung
 * kommt zuerst aus WooCommerce' eigener Zufallsauswahl `wc_get_related_products()` (gleiche
 * Produktkategorie/-schlagwoerter, gecacht und gemischt); reicht das nicht fuer 4 (z. B. kleine
 * Kategorie), wird mit zufaelligen anderen veroeffentlichten Produkten aufgefuellt -- explizite
 * Vorgabe: bei weniger als 4 gesetzten immer auf 4 auffuellen.
 *
 * @return int[]
 */
function hengegroup_theme_get_related_product_ids(WC_Product $product): array
{
    $limit = HENGEGROUP_THEME_RELATED_PRODUCTS_LIMIT;
    $self_id = $product->get_id();
    $manual = array_values(
        array_filter(
            array_map('intval', $product->get_upsell_ids()),
            static fn(int $id): bool => get_post_status($id) === 'publish',
        ),
    );
    $fallback =
        count($manual) < $limit && function_exists('wc_get_related_products')
            ? array_map(
                'intval',
                wc_get_related_products($self_id, $limit, array_merge($manual, [0])),
            )
            : [];
    $ids = hengegroup_theme_merge_related_product_ids($manual, $fallback, $self_id, $limit);

    if (count($ids) < $limit) {
        $random = get_posts([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'fields' => 'ids',
            'orderby' => 'rand',
            'no_found_rows' => true,
            'post__not_in' => array_merge($ids, [$self_id]), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
        ]);
        $ids = hengegroup_theme_merge_related_product_ids(
            $ids,
            array_map('intval', $random),
            $self_id,
            $limit,
        );
    }

    return $ids;
}

/**
 * Sortiert Anwendungen (Arrays mit `group_order`, `order`, `name`) nach Gruppe, dann Reihenfolge,
 * dann Name -- reine Funktion, unit-getestet (tests/Unit/ProductsTest.php).
 *
 * @param list<array{group_order: int, order: int, name: string}> $items
 * @return list<array{group_order: int, order: int, name: string}>
 */
function hengegroup_theme_sort_anwendung_items(array $items): array
{
    usort(
        $items,
        static fn(array $a, array $b): int => [$a['group_order'], $a['order']] <=> [
            $b['group_order'],
            $b['order'],
        ] ?:
        strnatcasecmp($a['name'], $b['name']),
    );

    return $items;
}

/**
 * Reihenfolge-Feld eines Anwendungs-Terms (Gruppe oder Anwendung), 0 wenn leer.
 */
function hengegroup_theme_get_anwendung_order(int $term_id): int
{
    return (int) get_term_meta($term_id, '_hengegroup_theme_anwendung_order', true);
}

/**
 * Normalisierte Daten einer Anwendung (Term der Ebene 2). `short` = Kurztext fuer die Karte auf der
 * Produktdetailseite, faellt auf die gekuerzte Beschreibung zurueck.
 */
function hengegroup_theme_get_anwendung_data(WP_Term $term): array
{
    $meta = static fn(string $field): string => trim(
        (string) get_term_meta($term->term_id, '_hengegroup_theme_anwendung_' . $field, true),
    );
    $description = trim(wp_strip_all_tags((string) $term->description));
    $short = $meta('short');

    return [
        'term_id' => (int) $term->term_id,
        'slug' => $term->slug,
        'name' => $term->name,
        'description' => $description,
        'short' => $short !== '' ? $short : wp_trim_words($description, 20),
        'image_id' => (int) $meta('image_id'),
        'icon' => $meta('icon'),
        'order' => (int) $meta('order'),
    ];
}

/**
 * Normalisierte Daten einer Anwendungsgruppe (Term der Ebene 1) -- gleiche Felder wie
 * hengegroup_theme_get_product_category_data().
 */
function hengegroup_theme_get_anwendung_group_data(WP_Term $term): array
{
    $meta = static fn(string $field): string => trim(
        (string) get_term_meta($term->term_id, '_hengegroup_theme_anwendung_' . $field, true),
    );
    $variant = $meta('variant');
    $variants = hengegroup_theme_get_badge_variants();
    $heading = $meta('heading');

    return [
        'term_id' => (int) $term->term_id,
        'slug' => $term->slug,
        'name' => $term->name,
        'kicker' => $meta('kicker'),
        'variant' => in_array($variant, $variants, true) ? $variant : $variants[0],
        'heading' => $heading !== '' ? $heading : $term->name,
        'description' => trim(wp_strip_all_tags((string) $term->description)),
    ];
}

/**
 * Anwendungen (Ebene 2) einer Gruppe, sortiert nach Reihenfolge, dann Name.
 *
 * @return WP_Term[]
 */
function hengegroup_theme_get_group_anwendungen(int $group_id): array
{
    $terms = get_terms([
        'taxonomy' => HENGEGROUP_THEME_ANWENDUNG_TAXONOMY,
        'parent' => $group_id,
        'hide_empty' => false,
    ]);

    return hengegroup_theme_sort_anwendung_terms(is_array($terms) ? $terms : []);
}

/**
 * Sortiert Anwendungs-Terms nach Gruppe (deren Reihenfolge), eigener Reihenfolge, Name.
 *
 * @param WP_Term[] $terms
 * @return WP_Term[]
 */
function hengegroup_theme_sort_anwendung_terms(array $terms): array
{
    $items = array_map(
        static fn(WP_Term $term): array => [
            'term' => $term,
            'group_order' =>
                $term->parent > 0 ? hengegroup_theme_get_anwendung_order((int) $term->parent) : 0,
            'order' => hengegroup_theme_get_anwendung_order((int) $term->term_id),
            'name' => $term->name,
        ],
        $terms,
    );

    return array_column(hengegroup_theme_sort_anwendung_items($items), 'term');
}

/**
 * Anwendungen eines Produkts (nur Ebene 2 -- versehentlich zugeordnete Gruppen fallen weg),
 * sortiert wie auf der Seite /anwendungen/.
 *
 * @return WP_Term[]
 */
function hengegroup_theme_get_product_anwendungen(int $product_id): array
{
    $terms = get_the_terms($product_id, HENGEGROUP_THEME_ANWENDUNG_TAXONOMY);

    if (!is_array($terms)) {
        return [];
    }

    return hengegroup_theme_sort_anwendung_terms(
        array_values(array_filter($terms, static fn(WP_Term $term): bool => $term->parent > 0)),
    );
}

/**
 * Veroeffentlichte Produkte einer Anwendung (Gegenrichtung der Zuordnung am Produkt), in der
 * Reihenfolge der Produktuebersicht.
 *
 * @return int[]
 */
function hengegroup_theme_get_anwendung_product_ids(int $term_id): array
{
    return array_map(
        'intval',
        get_posts([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
            'no_found_rows' => true,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            'tax_query' => [
                [
                    'taxonomy' => HENGEGROUP_THEME_ANWENDUNG_TAXONOMY,
                    'field' => 'term_id',
                    'terms' => $term_id,
                    'include_children' => false,
                ],
            ],
        ]),
    );
}

/**
 * Produkte einer Kategorie (inkl. Unterkategorien) in der Reihenfolge "Menue-Reihenfolge, dann
 * Name" -- wie WooCommerce' eigene Standardsortierung.
 *
 * @return int[]
 */
function hengegroup_theme_get_category_product_ids(int $term_id): array
{
    return array_map(
        'intval',
        get_posts([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
            'no_found_rows' => true,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $term_id,
                    'include_children' => true,
                ],
            ],
        ]),
    );
}

/**
 * Zusatzfelder einer Produktkategorie (Term-Meta, gepflegt unter Produkte > Kategorien). `heading`
 * faellt auf den Kategorienamen zurueck, `description` ist WordPress' eigene Term-Beschreibung.
 */
function hengegroup_theme_get_product_category_data(WP_Term $term): array
{
    $meta = static fn(string $field): string => trim(
        (string) get_term_meta($term->term_id, '_hengegroup_theme_category_' . $field, true),
    );
    $variant = $meta('variant');
    $variants = hengegroup_theme_get_badge_variants();
    $heading = $meta('heading');

    return [
        'term_id' => (int) $term->term_id,
        'slug' => $term->slug,
        'name' => $term->name,
        'kicker' => $meta('kicker'),
        'variant' => in_array($variant, $variants, true) ? $variant : $variants[0],
        'heading' => $heading !== '' ? $heading : $term->name,
        'description' => trim(wp_strip_all_tags((string) $term->description)),
        'contact' => [
            'name' => $meta('contact_name'),
            'role' => $meta('contact_role'),
            'email' => $meta('contact_email'),
            'phone' => $meta('contact_phone'),
            'photo_id' => (int) $meta('contact_photo_id'),
        ],
    ];
}

/**
 * Einstellungen unter Produkte > Einstellungen, ueber die Standardwerte gemischt.
 */
function hengegroup_theme_get_product_options(): array
{
    $stored = get_option(HENGEGROUP_THEME_PRODUCT_OPTION, []);

    return array_merge(
        [
            'overview_page_id' => 0,
            'contact_name' => '',
            'contact_role' => '',
            'contact_email' => '',
            'contact_phone' => '',
            'contact_photo_id' => 0,
        ],
        is_array($stored) ? $stored : [],
    );
}

/**
 * URL der Produktuebersicht: die unter Produkte > Einstellungen gewaehlte Seite, sonst die Seite mit
 * Slug "produkte", sonst die Startseite.
 */
function hengegroup_theme_get_products_page_url(): string
{
    $page_id = (int) hengegroup_theme_get_product_options()['overview_page_id'];

    if ($page_id <= 0) {
        $page = get_page_by_path('produkte');
        $page_id = $page instanceof WP_Post ? (int) $page->ID : 0;
    }

    if ($page_id > 0 && get_post_status($page_id) === 'publish') {
        return (string) get_permalink($page_id);
    }

    return home_url('/');
}

/**
 * Ansprechpartner eines Produkts: der bei seiner (ersten) Kategorie hinterlegte, falls Name ODER
 * E-Mail gepflegt sind, sonst der Standard aus Produkte > Einstellungen -- gleiches Muster wie
 * hengegroup_theme_get_job_contact().
 */
function hengegroup_theme_get_product_contact(int $product_id): array
{
    $terms = get_the_terms($product_id, 'product_cat');
    $default_term_id = (int) get_option('default_product_cat', 0);

    foreach (is_array($terms) ? $terms : [] as $term) {
        if ($term->term_id === $default_term_id) {
            continue;
        }

        $contact = hengegroup_theme_get_product_category_data($term)['contact'];

        if ($contact['name'] !== '' || $contact['email'] !== '') {
            return $contact;
        }
    }

    $options = hengegroup_theme_get_product_options();

    return [
        'name' => trim((string) $options['contact_name']),
        'role' => trim((string) $options['contact_role']),
        'email' => trim((string) $options['contact_email']),
        'phone' => trim((string) $options['contact_phone']),
        'photo_id' => (int) $options['contact_photo_id'],
    ];
}

/**
 * Normalisierte Produktdaten fuer die Detailseite -- eine Quelle fuer alle Abschnitte.
 */
function hengegroup_theme_get_product_data(WC_Product $product): array
{
    $id = $product->get_id();
    $keys = hengegroup_theme_get_product_meta_keys();
    $analysis = get_post_meta($id, $keys['analysis'], true);
    $downloads = [];

    foreach ((array) get_post_meta($id, $keys['downloads'], true) as $download) {
        $attachment_id = (int) ($download['attachment_id'] ?? 0);
        $url = $attachment_id > 0 ? wp_get_attachment_url($attachment_id) : false;

        if ($url === false) {
            continue;
        }

        $file = get_attached_file($attachment_id);
        $extension = strtoupper((string) pathinfo((string) $file, PATHINFO_EXTENSION));
        $size = is_string($file) && is_file($file) ? (int) filesize($file) : 0;
        $title = trim((string) ($download['title'] ?? ''));

        $downloads[] = [
            'url' => (string) $url,
            'title' => $title !== '' ? $title : get_the_title($attachment_id),
            'description' => trim((string) ($download['description'] ?? '')),
            'cta' => trim((string) ($download['cta'] ?? '')),
            'meta' => implode(
                ' · ',
                array_filter([
                    $extension,
                    $size > 0 ? size_format($size, $size >= KB_IN_BYTES ? 1 : 0) : '',
                ]),
            ),
        ];
    }

    $grain_sizes = taxonomy_exists('pa_' . HENGEGROUP_THEME_GRAIN_ATTRIBUTE)
        ? wc_get_product_terms($id, 'pa_' . HENGEGROUP_THEME_GRAIN_ATTRIBUTE, [
            'fields' => 'names',
        ])
        : [];

    return [
        'id' => $id,
        'name' => $product->get_name(),
        'description' => (string) $product->get_description(),
        'image_id' => (int) $product->get_image_id(),
        'badge_variant' => in_array(
            (string) get_post_meta($id, '_badge_variant', true),
            hengegroup_theme_get_badge_variants(),
            true,
        )
            ? (string) get_post_meta($id, '_badge_variant', true)
            : hengegroup_theme_get_badge_variants()[0],
        'recycling' => trim((string) get_post_meta($id, $keys['recycling'], true)),
        'analysis' => is_array($analysis) ? $analysis : [],
        'grain_sizes' => array_values(array_map('strval', (array) $grain_sizes)),
        'downloads' => $downloads,
        'anwendungen' => hengegroup_theme_get_product_anwendungen($id),
        'contact' => hengegroup_theme_get_product_contact($id),
        'related_ids' => hengegroup_theme_get_related_product_ids($product),
    ];
}

/**
 * Icon einer Anwendung als SVG-Markup (leer, wenn keins gewaehlt ist).
 */
function hengegroup_theme_render_anwendung_icon(string $key, string $class): string
{
    $icons = hengegroup_theme_get_anwendung_icons();

    return isset($icons[$key])
        ? hengegroup_theme_render_icon($icons[$key][1] + ['class' => $class])
        : '';
}

/**
 * Karte "Anwendungsbereich" (Design "Produktdetailseite"): template-parts/base/card.php (flach,
 * `lg`) mit Icon-Kachel in Produktfarbe, Titel, Kurztext. Bewusst OHNE Link -- Produkte zaehlen
 * Anwendungen nur auf (explizite Vorgabe).
 */
function hengegroup_theme_render_anwendung_card(WP_Term $term, string $variant): string
{
    $anwendung = hengegroup_theme_get_anwendung_data($term);
    $icon = hengegroup_theme_render_anwendung_icon($anwendung['icon'], 'size-[22px]');

    return hengegroup_theme_render_card([
        'tag' => 'li',
        'size' => 'lg',
        'title' => $anwendung['name'],
        'title_variant' => 'body-base',
        'icon' => $icon,
        'icon_class' => hengegroup_theme_get_variant_background_class($variant),
        'content' =>
            $anwendung['short'] !== ''
                ? hengegroup_theme_render_typography([
                    'variant' => 'body-sm',
                    'text' => $anwendung['short'],
                ])
                : '',
    ]);
}

/**
 * Querkarte einer Anwendung auf der Seite /anwendungen/ (Design "Anwendungen"):
 * template-parts/base/card.php (`raised`, `horizontal`, `lg`) -- Bild links (240 px, mobil oben), rechts Titel, Beschreibung und die zugeordneten Produkte als Chips (badge.php,
 * Variante `outline` mit `href` -- gleiche Optik wie die Anwendungs-Badges der Produktbox). Die
 * Chips verlinken auf die Produktseiten (Design) -- die Gegenrichtung (Produkt -> Anwendung) bleibt
 * ohne Link. `id` = Slug der Anwendung als Sprungziel.
 */
function hengegroup_theme_render_anwendung_overview_card(WP_Term $term): string
{
    $anwendung = hengegroup_theme_get_anwendung_data($term);
    $image_config = [
        'alt' => $anwendung['name'],
        'class' => 'absolute inset-0 size-full object-cover',
    ];
    $image_config +=
        $anwendung['image_id'] > 0
            ? ['attachment_id' => $anwendung['image_id'], 'size' => 'medium_large']
            : [
                'src' => function_exists('wc_placeholder_img_src')
                    ? wc_placeholder_img_src('medium')
                    : '',
            ];

    $chips = '';

    foreach (hengegroup_theme_get_anwendung_product_ids($anwendung['term_id']) as $product_id) {
        ob_start();
        get_template_part('template-parts/base/badge', null, [
            'config' => [
                'text' => get_the_title($product_id),
                'href' => (string) get_permalink($product_id),
                'variant' => 'outline',
                'class' => 'max-w-full',
            ],
        ]);
        $chips .= '<li class="max-w-full">' . (string) ob_get_clean() . '</li>';
    }

    $products =
        $chips !== ''
            ? sprintf(
                '%1$s<ul class="flex flex-wrap gap-2">%2$s</ul>',
                hengegroup_theme_render_typography([
                    'variant' => 'body-xs',
                    'tag' => 'p',
                    'text' => __('Produkte', 'hengegroup-theme'),
                    'class' => 'mb-2.5 font-medium tracking-wider uppercase',
                ]),
                $chips,
            )
            : '';

    return hengegroup_theme_render_card([
        'tag' => 'li',
        'elevation' => 'raised',
        'orientation' => 'horizontal',
        'size' => 'lg',
        'image' => $image_config,
        'title' => $anwendung['name'],
        'content' =>
            ($anwendung['description'] !== ''
                ? hengegroup_theme_render_typography([
                    'variant' => 'body-sm',
                    'text' => $anwendung['description'],
                    'class' => 'mb-4.5',
                ])
                : '') . $products,
        'class' => 'scroll-mt-24',
        'attributes' => ['id' => $anwendung['slug']],
    ]);
}

/**
 * Firmen-Kontaktkarte (Block "Kontakt"): template-parts/components/contact-card.php in der Variante
 * `split` (oben E-Mail/Telefon/Fax, darunter Adresse) -- Daten aus Einstellungen > Footer, dieselbe
 * Quelle wie footer.php.
 */
function hengegroup_theme_render_company_contact_card(): string
{
    $options = hengegroup_theme_get_footer_options();

    return hengegroup_theme_render_contact_card(
        [
            'name' => __('Kontakt', 'hengegroup-theme'),
            'email' => (string) $options['email'],
            'phone' => (string) $options['phone'],
            'fax' => (string) $options['fax'],
            'address' => (string) $options['address'],
        ],
        ['variant' => 'split'],
    );
}

/**
 * Produktkarten als `<ul class="contents">` (Kinder greifen direkt ins umgebende `.wrapper`-Grid),
 * in der Variante `default` oder `minimal` (template-parts/components/product-card.php). Leerer
 * String, wenn keine ID zu einem veroeffentlichten Produkt gehoert.
 *
 * @param int[] $product_ids
 */
function hengegroup_theme_render_product_cards(
    array $product_ids,
    string $variant = 'default',
): string {
    $markup = '';

    foreach ($product_ids as $product_id) {
        $product = function_exists('wc_get_product') ? wc_get_product((int) $product_id) : null;

        if (!($product instanceof WC_Product) || !$product->is_visible()) {
            continue;
        }

        ob_start();
        echo '<li class="col-span-12 sm:col-span-6 lg:col-span-3">';
        get_template_part('template-parts/components/product-card', null, [
            'product' => $product,
            'variant' => $variant,
        ]);
        echo '</li>';
        $markup .= (string) ob_get_clean();
    }

    return $markup !== '' ? '<ul class="contents">' . $markup . '</ul>' : '';
}

/**
 * Prueft die (bereits sanitisierten) Felder einer Produktanfrage -- reine Funktion, unit-getestet
 * (tests/Unit/ProductsTest.php). `$with_location`: PLZ/Ort sind Pflicht (Formular der
 * Produktuebersicht), auf der Produktdetailseite gibt es die Felder nicht.
 * Rueckgabe: Feldname => Fehlermeldung, leer = alles gueltig.
 */
function hengegroup_theme_validate_product_inquiry(array $values, bool $with_location): array
{
    $errors = [];
    $required = [
        'company' => __('Bitte geben Sie Ihre Firma an.', 'hengegroup-theme'),
        'name' => __('Bitte geben Sie Ihren Namen an.', 'hengegroup-theme'),
    ];

    if ($with_location) {
        $required['postal_code'] = __('Bitte geben Sie Ihre Postleitzahl an.', 'hengegroup-theme');
        $required['city'] = __('Bitte geben Sie Ihren Ort an.', 'hengegroup-theme');
    }

    foreach ($required as $field => $message) {
        if (trim((string) ($values[$field] ?? '')) === '') {
            $errors[$field] = $message;
        }
    }

    if (filter_var(trim((string) ($values['email'] ?? '')), FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = __(
            'Bitte geben Sie eine gültige E-Mail-Adresse an.',
            'hengegroup-theme',
        );
    }

    $phone = trim((string) ($values['phone'] ?? ''));

    if ($phone !== '' && preg_match('/^[0-9 +()\/\-]{5,30}$/', $phone) !== 1) {
        $errors['phone'] = __('Bitte geben Sie eine gültige Telefonnummer an.', 'hengegroup-theme');
    }

    if (($values['privacy'] ?? '') !== '1') {
        $errors['privacy'] = __(
            'Bitte bestätigen Sie die Datenschutzhinweise.',
            'hengegroup-theme',
        );
    }

    return $errors;
}

/**
 * Zeile "Kontaktkarte + Anfrageformular" des Blocks "Kontakt" (zwei Rasterspalten fuer ein
 * umgebendes `.wrapper`) -- geteilt mit dessen Editor-Vorschau-Zwilling kontakt-vorschau.
 */
function hengegroup_theme_render_company_contact_row(): string
{
    $card = hengegroup_theme_render_company_contact_card();

    ob_start();
    get_template_part('template-parts/components/inquiry-form', null, [
        'product_id' => 0,
        'with_location' => true,
        'tone' => 'light',
    ]);
    $form = (string) ob_get_clean();

    return ($card !== ''
        ? '<div class="col-span-12 self-start sm:col-span-6 lg:col-span-3">' . $card . '</div>'
        : '') .
        '<div class="col-span-12 lg:col-span-9">' .
        $form .
        '</div>';
}
