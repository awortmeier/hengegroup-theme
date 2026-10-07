<?php

declare(strict_types=1);

// Backend-Felder des Produktbereichs (Daten-/Render-Helfer: inc/template-parts/products.php):
//   - Produkt-Editor: Tab "Technische Daten" in WooCommerce' Produktdaten-Box (Recycling-Hinweis,
//     chemische Analyse als freie Zeilen, Downloads aus der Mediathek) und die Box "Anwendungen"
//     (Taxonomie `produkt_anwendung`, Checkboxen nach Gruppen sortiert).
//   - Anwendungen (Produkte > Anwendungen): Felder fuer Gruppen (Kicker/Farbe/Ueberschrift) und
//     Anwendungen (Kurztext/Bild/Icon), Reihenfolge fuer beide; schreibgeschuetzte Liste der
//     zugeordneten Produkte. Zuordnung bewusst nur am Produkt (explizite Vorgabe), zusaetzlich per
//     Quick Edit/Massenbearbeitung und Filter in der Produktliste.
//   - Produktkategorien: Kicker, Farbe, Ueberschrift, Ansprechpartner (Term-Meta).
//   - Produkte > Einstellungen: Uebersichtsseite und Standard-Ansprechpartner.
//   - WooCommerce' "Up-Sells" heissen im Produkt-Editor "Verwandte Produkte" (das Feld steuert die
//     manuell gesetzten verwandten Produkte der Detailseite, siehe
//     hengegroup_theme_get_related_product_ids()).
//
// Klassische Metaboxen/Term-Formularfelder wie bei Karriere/SEO/Badge (kein ACF, kein eigenes
// Gutenberg-Sidebar-Bundle -- der Produkt-Editor ist ohnehin der klassische WooCommerce-Editor).
// Die wiederholbaren Zeilen (Analyse/Downloads) und die Datei-/Fotoauswahl stellt
// assets/js/admin/theme-products.js bereit.

/**
 * Bindet die Mediathek-Auswahl (SEO-Bildpicker fuer das Ansprechpartner-Foto) und das Skript fuer
 * wiederholbare Zeilen/Dateiauswahl auf den betroffenen Backend-Seiten ein.
 */
function hengegroup_theme_action_admin_enqueue_scripts_products(string $hook_suffix): void
{
    $screen = get_current_screen();
    $is_product_editor =
        in_array($hook_suffix, ['post.php', 'post-new.php'], true) &&
        $screen instanceof WP_Screen &&
        $screen->post_type === 'product';
    $is_category_screen =
        in_array($hook_suffix, ['edit-tags.php', 'term.php'], true) &&
        $screen instanceof WP_Screen &&
        in_array($screen->taxonomy, ['product_cat', HENGEGROUP_THEME_ANWENDUNG_TAXONOMY], true);
    $is_settings_page = $hook_suffix === 'product_page_hengegroup-theme-product-settings';

    if (!$is_product_editor && !$is_category_screen && !$is_settings_page) {
        return;
    }

    hengegroup_theme_enqueue_seo_media_picker();

    if (!$is_product_editor) {
        return;
    }

    $script_path = get_template_directory() . '/assets/js/admin/theme-products.js';

    wp_enqueue_script(
        'hengegroup-theme-products-admin',
        get_template_directory_uri() . '/assets/js/admin/theme-products.js',
        ['media-editor'],
        file_exists($script_path) ? (string) filemtime($script_path) : false,
        true,
    );
}
add_action('admin_enqueue_scripts', 'hengegroup_theme_action_admin_enqueue_scripts_products');

/**
 * Tab "Technische Daten" in WooCommerce' Produktdaten-Box.
 */
function hengegroup_theme_filter_product_data_tabs(array $tabs): array
{
    $tabs['hengegroup_theme_technical'] = [
        'label' => __('Technische Daten', 'hengegroup-theme'),
        'target' => 'hengegroup_theme_technical_data',
        'class' => [],
        'priority' => 65,
    ];

    return $tabs;
}
add_filter('woocommerce_product_data_tabs', 'hengegroup_theme_filter_product_data_tabs');

/**
 * Eine Zeile der chemischen Analyse (auch als leere Vorlage fuer "Zeile hinzufuegen").
 */
function hengegroup_theme_render_analysis_row(string $label, string $value): void
{
    printf(
        '<tr data-hengegroup-row><td><input type="text" name="hengegroup_theme_analysis_label[]" value="%1$s" class="widefat" placeholder="%3$s"></td><td><input type="text" name="hengegroup_theme_analysis_value[]" value="%2$s" class="widefat" placeholder="%4$s"></td><td><button type="button" class="button-link button-link-delete" data-hengegroup-remove-row>%5$s</button></td></tr>',
        esc_attr($label),
        esc_attr($value),
        esc_attr__('z. B. Al₂O₃', 'hengegroup-theme'),
        esc_attr__('z. B. 93,5 – 95 %', 'hengegroup-theme'),
        esc_html__('Entfernen', 'hengegroup-theme'),
    );
}

/**
 * Ein Download-Eintrag (auch als leere Vorlage).
 */
function hengegroup_theme_render_download_row(array $download): void
{
    $attachment_id = (int) ($download['attachment_id'] ?? 0);
    $file_name = $attachment_id > 0 ? wp_basename((string) get_attached_file($attachment_id)) : '';

    printf(
        '<tr data-hengegroup-row><td><input type="hidden" name="hengegroup_theme_download_file[]" value="%1$s" data-hengegroup-file-input><button type="button" class="button" data-hengegroup-select-file>%2$s</button> <span data-hengegroup-file-name>%3$s</span></td><td><input type="text" name="hengegroup_theme_download_title[]" value="%4$s" class="widefat" placeholder="%5$s"><textarea name="hengegroup_theme_download_description[]" rows="2" class="widefat" placeholder="%6$s">%7$s</textarea><input type="text" name="hengegroup_theme_download_cta[]" value="%8$s" class="widefat" placeholder="%9$s"></td><td><button type="button" class="button-link button-link-delete" data-hengegroup-remove-row>%10$s</button></td></tr>',
        esc_attr($attachment_id > 0 ? (string) $attachment_id : ''),
        esc_html__('Datei wählen', 'hengegroup-theme'),
        esc_html($file_name),
        esc_attr((string) ($download['title'] ?? '')),
        esc_attr__('Titel, z. B. Produktdatenblatt (leer = Dateititel)', 'hengegroup-theme'),
        esc_attr__('Kurzbeschreibung', 'hengegroup-theme'),
        esc_textarea((string) ($download['description'] ?? '')),
        esc_attr((string) ($download['cta'] ?? '')),
        esc_attr__('Button-Text (leer = "Herunterladen")', 'hengegroup-theme'),
        esc_html__('Entfernen', 'hengegroup-theme'),
    );
}

/**
 * Wiederholbare Tabelle mit Vorlage-Zeile fuer assets/js/admin/theme-products.js.
 */
function hengegroup_theme_render_repeatable_table(
    string $key,
    array $headings,
    array $rows,
    callable $render_row,
    array $empty_row,
    string $add_label,
): void {
    printf(
        '<table class="widefat striped" data-hengegroup-repeatable="%s"><thead><tr>',
        esc_attr($key),
    );

    foreach ($headings as $heading) {
        printf('<th>%s</th>', esc_html($heading));
    }

    echo '<th></th></tr></thead><tbody>';

    foreach ($rows as $row) {
        $render_row(...$row);
    }

    echo '</tbody></table>';

    echo '<template data-hengegroup-template="' . esc_attr($key) . '">';
    $render_row(...$empty_row);
    echo '</template>';

    printf(
        '<p><button type="button" class="button" data-hengegroup-add-row="%1$s">%2$s</button></p>',
        esc_attr($key),
        esc_html($add_label),
    );
}

function hengegroup_theme_action_product_data_panels_technical(): void
{
    global $post;

    $post_id = $post instanceof WP_Post ? (int) $post->ID : 0;
    $keys = hengegroup_theme_get_product_meta_keys();
    $analysis = get_post_meta($post_id, $keys['analysis'], true);
    $downloads = get_post_meta($post_id, $keys['downloads'], true);
    ?>
    <div id="hengegroup_theme_technical_data" class="panel woocommerce_options_panel hidden">
        <div class="options_group">
            <?php woocommerce_wp_textarea_input([
                'id' => 'hengegroup_theme_recycling_text',
                'label' => __('Recycling-Hinweis', 'hengegroup-theme'),
                'value' => (string) get_post_meta($post_id, $keys['recycling'], true),
                'desc_tip' => true,
                'description' => __(
                    'Optional. Erscheint als grüner Hinweis unter der Produktbeschreibung, z. B. "Wir können Ihren Normalkorund wieder aufbereiten! Sprechen Sie uns an."',
                    'hengegroup-theme',
                ),
            ]); ?>
        </div>

        <div class="options_group" style="padding: 0 12px 12px;">
            <h4><?php esc_html_e('Chemische Analyse (typisch)', 'hengegroup-theme'); ?></h4>
            <?php hengegroup_theme_render_repeatable_table(
                'analysis',
                [__('Bezeichnung', 'hengegroup-theme'), __('Wert', 'hengegroup-theme')],
                array_map(
                    static fn(array $row): array => [
                        (string) ($row['label'] ?? ''),
                        (string) ($row['value'] ?? ''),
                    ],
                    is_array($analysis) ? $analysis : [],
                ),
                'hengegroup_theme_render_analysis_row',
                ['', ''],
                __('Zeile hinzufügen', 'hengegroup-theme'),
            ); ?>
            <p class="description"><?php esc_html_e(
                'Die lieferbaren Körnungen werden im Tab "Eigenschaften" über das Attribut "Körnung" gepflegt.',
                'hengegroup-theme',
            ); ?></p>
        </div>

        <div class="options_group" style="padding: 0 12px 12px;">
            <h4><?php esc_html_e('Downloads', 'hengegroup-theme'); ?></h4>
            <?php hengegroup_theme_render_repeatable_table(
                'downloads',
                [__('Datei', 'hengegroup-theme'), __('Anzeige', 'hengegroup-theme')],
                array_map(
                    static fn(array $row): array => [$row],
                    is_array($downloads) ? $downloads : [],
                ),
                'hengegroup_theme_render_download_row',
                [[]],
                __('Download hinzufügen', 'hengegroup-theme'),
            ); ?>
        </div>
    </div>
    <?php
}
add_action(
    'woocommerce_product_data_panels',
    'hengegroup_theme_action_product_data_panels_technical',
);

/**
 * Speichert den Tab "Technische Daten". `woocommerce_process_product_meta` laeuft erst nach
 * WooCommerce' eigener Nonce-/Rechtepruefung der Produktdaten-Box.
 */
function hengegroup_theme_action_process_product_meta_technical(int $post_id): void
{
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- von WooCommerce vor diesem Hook geprueft (woocommerce_meta_nonce).
    $list = static fn(string $key): array => isset($_POST[$key]) && is_array($_POST[$key])
        ? array_map(
            static fn($value): string => sanitize_textarea_field((string) $value),
            wp_unslash($_POST[$key]), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- direkt darueber je Wert sanitisiert.
        )
        : [];
    $recycling = isset($_POST['hengegroup_theme_recycling_text'])
        ? sanitize_textarea_field(wp_unslash((string) $_POST['hengegroup_theme_recycling_text']))
        : '';
    // phpcs:enable WordPress.Security.NonceVerification.Missing

    $keys = hengegroup_theme_get_product_meta_keys();
    $analysis = hengegroup_theme_normalize_analysis_rows(
        $list('hengegroup_theme_analysis_label'),
        $list('hengegroup_theme_analysis_value'),
    );

    $files = $list('hengegroup_theme_download_file');
    $titles = $list('hengegroup_theme_download_title');
    $descriptions = $list('hengegroup_theme_download_description');
    $ctas = $list('hengegroup_theme_download_cta');
    $downloads = [];

    foreach ($files as $index => $file) {
        $attachment_id = absint($file);

        if ($attachment_id <= 0 || get_post_type($attachment_id) !== 'attachment') {
            continue;
        }

        $downloads[] = [
            'attachment_id' => $attachment_id,
            'title' => trim($titles[$index] ?? ''),
            'description' => trim($descriptions[$index] ?? ''),
            'cta' => trim($ctas[$index] ?? ''),
        ];
    }

    foreach (
        ['recycling' => trim($recycling), 'analysis' => $analysis, 'downloads' => $downloads]
        as $field => $value
    ) {
        if ($value === '' || $value === []) {
            delete_post_meta($post_id, $keys[$field]);
        } else {
            update_post_meta($post_id, $keys[$field], $value);
        }
    }
}
add_action(
    'woocommerce_process_product_meta',
    'hengegroup_theme_action_process_product_meta_technical',
);

/**
 * Box "Anwendungen" im Produkt-Editor (`meta_box_cb` der Taxonomie, siehe
 * inc/setup/theme-products.php): Anwendungen als Checkboxen unter ihrer Gruppe; Gruppen selbst sind
 * nicht anwaehlbar. Feldname `tax_input[produkt_anwendung][]` -- gespeichert von WordPress selbst
 * (wie die Kategorien-Box), das versteckte `0` sorgt dafuer, dass "alles abgewaehlt" auch
 * gespeichert wird.
 */
function hengegroup_theme_render_product_anwendungen_meta_box(WP_Post $post): void
{
    $taxonomy = HENGEGROUP_THEME_ANWENDUNG_TAXONOMY;
    $selected = wp_get_object_terms($post->ID, $taxonomy, ['fields' => 'ids']);
    $selected = is_array($selected) ? array_map('intval', $selected) : [];
    $groups = get_terms(['taxonomy' => $taxonomy, 'parent' => 0, 'hide_empty' => false]);
    $groups = hengegroup_theme_sort_anwendung_terms(is_array($groups) ? $groups : []);

    printf('<input type="hidden" name="tax_input[%s][]" value="0">', esc_attr($taxonomy));

    if ($groups === []) {
        printf(
            '<p>%1$s <a href="%2$s">%3$s</a></p>',
            esc_html__('Noch keine Anwendungen angelegt.', 'hengegroup-theme'),
            esc_url(admin_url('edit-tags.php?taxonomy=' . $taxonomy . '&post_type=product')),
            esc_html__('Anwendungen verwalten', 'hengegroup-theme'),
        );

        return;
    }

    echo '<div style="max-height:320px;overflow:auto">';

    foreach ($groups as $group) {
        $children = hengegroup_theme_get_group_anwendungen((int) $group->term_id);

        printf('<p style="margin:10px 0 4px"><strong>%s</strong></p>', esc_html($group->name));

        if ($children === []) {
            printf(
                '<p class="description">%s</p>',
                esc_html__('— keine Anwendungen —', 'hengegroup-theme'),
            );
            continue;
        }

        echo '<ul style="margin:0">';

        foreach ($children as $child) {
            printf(
                '<li><label><input type="checkbox" name="tax_input[%1$s][]" value="%2$d"%3$s> %4$s</label></li>',
                esc_attr($taxonomy),
                (int) $child->term_id,
                checked(in_array((int) $child->term_id, $selected, true), true, false),
                esc_html($child->name),
            );
        }

        echo '</ul>';
    }

    echo '</div>';
    printf(
        '<p class="description">%s</p>',
        esc_html__(
            'Erscheinen in der Produktbox und als "Anwendungsbereiche" auf der Produktseite (ohne Link). Auf der Seite "Anwendungen" erscheint dieses Produkt umgekehrt bei jeder gewählten Anwendung.',
            'hengegroup-theme',
        ),
    );
}

/**
 * Filter "Nach Anwendung" in der Produktliste.
 */
function hengegroup_theme_action_restrict_manage_posts_anwendungen(string $post_type): void
{
    if ($post_type !== 'product') {
        return;
    }

    $taxonomy = HENGEGROUP_THEME_ANWENDUNG_TAXONOMY;
    $current = isset($_GET[$taxonomy]) ? sanitize_title(wp_unslash($_GET[$taxonomy])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reiner Listenfilter.

    wp_dropdown_categories([
        'taxonomy' => $taxonomy,
        'name' => $taxonomy,
        'value_field' => 'slug',
        'selected' => $current,
        'hierarchical' => true,
        'hide_empty' => false,
        'show_option_all' => __('Alle Anwendungen', 'hengegroup-theme'),
    ]);
}
add_action('restrict_manage_posts', 'hengegroup_theme_action_restrict_manage_posts_anwendungen');

/**
 * Wertet den Filter "Nach Anwendung" aus (die Taxonomie hat keine `query_var`, deshalb von Hand).
 */
function hengegroup_theme_action_pre_get_posts_anwendung_filter(WP_Query $query): void
{
    $taxonomy = HENGEGROUP_THEME_ANWENDUNG_TAXONOMY;

    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'product') {
        return;
    }

    $slug = isset($_GET[$taxonomy]) ? sanitize_title(wp_unslash($_GET[$taxonomy])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reiner Listenfilter.

    if ($slug === '' || $slug === '0') {
        return;
    }

    $query->set('tax_query', [
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
        ['taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $slug],
    ]);
}
add_action('pre_get_posts', 'hengegroup_theme_action_pre_get_posts_anwendung_filter');

/**
 * Zusatzfelder der Anwendungen: field => [Label, Typ, Beschreibung, Ebene]. Ebene: group (nur
 * Gruppen), item (nur Anwendungen), both. Beim Bearbeiten erscheinen nur die Felder der Ebene des
 * Terms; beim Anlegen blendet hengegroup_theme_action_admin_footer_anwendung_fields() je nach
 * gewaehlter Gruppe ("uebergeordnet") die unpassenden aus.
 */
function hengegroup_theme_get_anwendung_fields(): array
{
    return [
        'kicker' => [
            __('Kicker', 'hengegroup-theme'),
            'text',
            __('Kleine Pill über der Überschrift, z. B. "Kominex / Imexco".', 'hengegroup-theme'),
            'group',
        ],
        'variant' => [
            __('Farbe', 'hengegroup-theme'),
            'variant',
            __('Farbe der Kicker-Pill.', 'hengegroup-theme'),
            'group',
        ],
        'heading' => [
            __('Überschrift', 'hengegroup-theme'),
            'text',
            __(
                'Überschrift der Sektion auf der Seite "Anwendungen". Leer = Name. Der Text darunter ist die Beschreibung.',
                'hengegroup-theme',
            ),
            'group',
        ],
        'short' => [
            __('Kurztext', 'hengegroup-theme'),
            'textarea',
            __(
                'Text der Karte "Anwendungsbereiche" auf Produktseiten. Leer = gekürzte Beschreibung. Die Beschreibung selbst erscheint auf der Seite "Anwendungen".',
                'hengegroup-theme',
            ),
            'item',
        ],
        'image_id' => [
            __('Bild', 'hengegroup-theme'),
            'image',
            __('Bild der Karte auf der Seite "Anwendungen".', 'hengegroup-theme'),
            'item',
        ],
        'icon' => [
            __('Icon', 'hengegroup-theme'),
            'icon',
            __('Icon der Karte "Anwendungsbereiche" auf Produktseiten.', 'hengegroup-theme'),
            'item',
        ],
        'order' => [
            __('Reihenfolge', 'hengegroup-theme'),
            'number',
            __(
                'Kleinere Zahl = weiter oben (Gruppen untereinander bzw. Anwendungen innerhalb ihrer Gruppe).',
                'hengegroup-theme',
            ),
            'both',
        ],
    ];
}

/**
 * Steuerelement eines Anwendungs-Felds -- Text/Bild/Farbe wie bei den Karriere-Taxonomien
 * (hengegroup_theme_get_job_term_field_control()), zusaetzlich Icon-Auswahl und Zahl.
 */
function hengegroup_theme_get_anwendung_field_control(
    string $field,
    string $type,
    string $value,
): string {
    $id = 'hengegroup-theme-term-' . $field;
    $name = 'hengegroup_theme_term[' . $field . ']';

    if ($type === 'icon') {
        $options = ['' => __('— kein Icon —', 'hengegroup-theme')];

        foreach (hengegroup_theme_get_anwendung_icons() as $key => [$label]) {
            $options[$key] = $label;
        }

        return hengegroup_theme_get_job_admin_select($id, $name, $options, $value);
    }

    if ($type === 'number') {
        return sprintf(
            '<input type="number" id="%1$s" name="%2$s" value="%3$s" class="small-text" step="1">',
            esc_attr($id),
            esc_attr($name),
            esc_attr($value),
        );
    }

    return hengegroup_theme_get_job_term_field_control($field, $type, $value);
}

function hengegroup_theme_render_anwendung_add_fields(): void
{
    wp_nonce_field('hengegroup_theme_save_anwendung', 'hengegroup_theme_anwendung_nonce');

    foreach (
        hengegroup_theme_get_anwendung_fields()
        as $field => [$label, $type, $description, $scope]
    ) {
        printf(
            '<div class="form-field" data-anwendung-scope="%5$s"><label for="%1$s">%2$s</label>%3$s%4$s</div>',
            esc_attr('hengegroup-theme-term-' . $field),
            esc_html($label),
            hengegroup_theme_get_anwendung_field_control($field, $type, ''), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            '<p>' . esc_html($description) . '</p>',
            esc_attr($scope),
        );
    }
}

/**
 * True auf der Liste/dem Bearbeiten-Formular der Anwendungen (Produkte > Anwendungen).
 */
function hengegroup_theme_is_admin_anwendung_screen(): bool
{
    $screen = get_current_screen();

    return $screen instanceof WP_Screen &&
        in_array($screen->base, ['edit-tags', 'term'], true) &&
        $screen->taxonomy === HENGEGROUP_THEME_ANWENDUNG_TAXONOMY;
}

/**
 * Blendet auf Liste/Formular der Anwendungen das Feld "Titelform" (Slug) aus -- entsteht automatisch
 * aus dem Namen und dient nur als Sprunganker auf /anwendungen/, Redakteure brauchen es nicht
 * (explizite Nachfrage 2026-10-07: "alle Felder entfernen, die nicht benoetigt werden"). WordPress
 * bietet dafuer keinen Filter, das Feld ist in edit-tags.php/edit-tag-form.php fest verdrahtet --
 * deshalb rohes CSS statt Tailwind als Ausnahme (CLAUDE.md Regel 1), gleiche Begruendung wie
 * hengegroup_theme_action_admin_head_hide_woocommerce_virtual_downloadable() (theme-admin-woocommerce.php):
 * reines Backend-Feld-Ausblenden, kein Tailwind-Build in wp-admin. Ein leerer Slug wird beim
 * Speichern wie gewohnt aus dem Namen erzeugt.
 */
function hengegroup_theme_action_admin_head_anwendung_fields(): void
{
    if (!hengegroup_theme_is_admin_anwendung_screen()) {
        return;
    }

    echo '<style>.term-slug-wrap{display:none !important;}</style>';
}
add_action('admin_head', 'hengegroup_theme_action_admin_head_anwendung_fields');

/**
 * Beim Anlegen steht die Ebene erst mit der Auswahl "Gruppe" (uebergeordnet, `#parent`) fest:
 * ohne Gruppe = neue Gruppe (nur Gruppen-Felder), mit Gruppe = Anwendung (nur Anwendungs-Felder).
 * Kleines Inline-Skript statt eigener Datei -- betrifft nur dieses eine Formular.
 */
function hengegroup_theme_action_admin_footer_anwendung_fields(): void
{
    $screen = get_current_screen();

    if (!hengegroup_theme_is_admin_anwendung_screen() || $screen->base !== 'edit-tags') {
        return;
    }

    echo "<script>(function(){var parent=document.getElementById('parent');if(!parent){return;}function update(){var level=parent.value==='-1'||parent.value===''?'group':'item';document.querySelectorAll('[data-anwendung-scope]').forEach(function(row){var scope=row.getAttribute('data-anwendung-scope');row.style.display=scope==='both'||scope===level?'':'none';});}parent.addEventListener('change',update);update();})();</script>";
}
add_action('admin_footer', 'hengegroup_theme_action_admin_footer_anwendung_fields');

/**
 * Spalte "Titelform" in der Liste der Anwendungen ausblenden (siehe oben).
 */
function hengegroup_theme_filter_manage_anwendung_columns(array $columns): array
{
    unset($columns['slug']);

    return $columns;
}
add_filter(
    'manage_edit-' . HENGEGROUP_THEME_ANWENDUNG_TAXONOMY . '_columns',
    'hengegroup_theme_filter_manage_anwendung_columns',
);
add_action(
    HENGEGROUP_THEME_ANWENDUNG_TAXONOMY . '_add_form_fields',
    'hengegroup_theme_render_anwendung_add_fields',
);

function hengegroup_theme_render_anwendung_edit_fields(WP_Term $term): void
{
    wp_nonce_field('hengegroup_theme_save_anwendung', 'hengegroup_theme_anwendung_nonce');

    $level = $term->parent > 0 ? 'item' : 'group';

    foreach (
        hengegroup_theme_get_anwendung_fields()
        as $field => [$label, $type, $description, $scope]
    ) {
        if ($scope !== 'both' && $scope !== $level) {
            continue;
        }

        printf(
            '<tr class="form-field"><th scope="row"><label for="%1$s">%2$s</label></th><td>%3$s%4$s</td></tr>',
            esc_attr('hengegroup-theme-term-' . $field),
            esc_html($label),
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- control markup is escaped field by field inside.
            hengegroup_theme_get_anwendung_field_control(
                $field,
                $type,
                (string) get_term_meta(
                    $term->term_id,
                    '_hengegroup_theme_anwendung_' . $field,
                    true,
                ),
            ),
            '<p class="description">' . esc_html($description) . '</p>',
        );
    }

    if ($level !== 'item') {
        return;
    }

    $links = array_map(
        static fn(int $product_id): string => sprintf(
            '<a href="%1$s">%2$s</a>',
            esc_url((string) get_edit_post_link($product_id)),
            esc_html(get_the_title($product_id)),
        ),
        hengegroup_theme_get_anwendung_product_ids((int) $term->term_id),
    );

    printf(
        '<tr class="form-field"><th scope="row">%1$s</th><td>%2$s<p class="description">%3$s</p></td></tr>',
        esc_html__('Zugeordnete Produkte', 'hengegroup-theme'),
        $links !== [] ? implode(', ', $links) : '—', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
        esc_html__(
            'Nur zur Ansicht. Die Zuordnung wird im jeweiligen Produkt gepflegt (Box "Anwendungen") oder per Quick Edit in der Produktliste.',
            'hengegroup-theme',
        ),
    );
}
add_action(
    HENGEGROUP_THEME_ANWENDUNG_TAXONOMY . '_edit_form_fields',
    'hengegroup_theme_render_anwendung_edit_fields',
);

function hengegroup_theme_action_save_anwendung(int $term_id): void
{
    if (
        !isset($_POST['hengegroup_theme_anwendung_nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['hengegroup_theme_anwendung_nonce'])),
            'hengegroup_theme_save_anwendung',
        ) ||
        !current_user_can('manage_product_terms')
    ) {
        return;
    }

    // Jeder Wert wird unten je Feldtyp einzeln sanitisiert. Felder, die auf der Bearbeitungsseite
    // fuer diese Ebene nicht angezeigt werden, fehlen im POST und bleiben unangetastet.
    $data =
        isset($_POST['hengegroup_theme_term']) && is_array($_POST['hengegroup_theme_term'])
            ? wp_unslash($_POST['hengegroup_theme_term']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            : [];

    foreach (hengegroup_theme_get_anwendung_fields() as $field => [, $type]) {
        if (!array_key_exists($field, $data)) {
            continue;
        }

        $raw = (string) $data[$field];
        $value = match ($type) {
            'textarea' => trim(sanitize_textarea_field($raw)),
            'image' => absint($raw) > 0 ? (string) absint($raw) : '',
            'variant' => in_array($raw, hengegroup_theme_get_badge_variants(), true) ? $raw : '',
            'icon' => isset(hengegroup_theme_get_anwendung_icons()[$raw]) ? $raw : '',
            'number' => is_numeric(trim($raw)) ? (string) (int) $raw : '',
            default => trim(sanitize_text_field($raw)),
        };

        if ($value === '') {
            delete_term_meta($term_id, '_hengegroup_theme_anwendung_' . $field);
        } else {
            update_term_meta($term_id, '_hengegroup_theme_anwendung_' . $field, $value);
        }
    }
}
add_action(
    'created_' . HENGEGROUP_THEME_ANWENDUNG_TAXONOMY,
    'hengegroup_theme_action_save_anwendung',
);
add_action(
    'edited_' . HENGEGROUP_THEME_ANWENDUNG_TAXONOMY,
    'hengegroup_theme_action_save_anwendung',
);

/**
 * Zusatzfelder der Produktkategorien: field => [Label, Typ, Beschreibung]. Typ: text | email |
 * image | variant (gleiche Steuerelemente wie die Karriere-Taxonomien,
 * hengegroup_theme_get_job_term_field_control()).
 */
function hengegroup_theme_get_product_category_fields(): array
{
    return [
        'kicker' => [
            __('Kicker', 'hengegroup-theme'),
            'text',
            __(
                'Kleine Pill über der Überschrift der Sektion auf der Produktübersicht, z. B. "Kominex / Imexco".',
                'hengegroup-theme',
            ),
        ],
        'variant' => [
            __('Farbe', 'hengegroup-theme'),
            'variant',
            __('Farbe der Kicker-Pill.', 'hengegroup-theme'),
        ],
        'heading' => [
            __('Überschrift', 'hengegroup-theme'),
            'text',
            __(
                'Überschrift der Sektion auf der Produktübersicht. Leer = Kategoriename. Der Text darunter ist die Beschreibung der Kategorie.',
                'hengegroup-theme',
            ),
        ],
        'contact_name' => [
            __('Ansprechpartner: Name', 'hengegroup-theme'),
            'text',
            __(
                'Nur ausfüllen, wenn abweichend vom Standard-Ansprechpartner (Produkte > Einstellungen). Gilt für alle Produkte dieser Kategorie.',
                'hengegroup-theme',
            ),
        ],
        'contact_role' => [__('Ansprechpartner: Funktion', 'hengegroup-theme'), 'text', ''],
        'contact_email' => [__('Ansprechpartner: E-Mail', 'hengegroup-theme'), 'email', ''],
        'contact_phone' => [__('Ansprechpartner: Telefon', 'hengegroup-theme'), 'text', ''],
        'contact_photo_id' => [__('Ansprechpartner: Foto', 'hengegroup-theme'), 'image', ''],
    ];
}

function hengegroup_theme_render_product_category_add_fields(): void
{
    wp_nonce_field(
        'hengegroup_theme_save_product_category',
        'hengegroup_theme_product_category_nonce',
    );

    foreach (
        hengegroup_theme_get_product_category_fields()
        as $field => [$label, $type, $description]
    ) {
        printf(
            '<div class="form-field"><label for="%1$s">%2$s</label>%3$s%4$s</div>',
            esc_attr('hengegroup-theme-term-' . $field),
            esc_html($label),
            hengegroup_theme_get_job_term_field_control($field, $type, ''), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $description !== '' ? '<p>' . esc_html($description) . '</p>' : '',
        );
    }
}
add_action('product_cat_add_form_fields', 'hengegroup_theme_render_product_category_add_fields');

function hengegroup_theme_render_product_category_edit_fields(WP_Term $term): void
{
    wp_nonce_field(
        'hengegroup_theme_save_product_category',
        'hengegroup_theme_product_category_nonce',
    );

    foreach (
        hengegroup_theme_get_product_category_fields()
        as $field => [$label, $type, $description]
    ) {
        printf(
            '<tr class="form-field"><th scope="row"><label for="%1$s">%2$s</label></th><td>%3$s%4$s</td></tr>',
            esc_attr('hengegroup-theme-term-' . $field),
            esc_html($label),
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- control markup is escaped field by field inside.
            hengegroup_theme_get_job_term_field_control(
                $field,
                $type,
                (string) get_term_meta(
                    $term->term_id,
                    '_hengegroup_theme_category_' . $field,
                    true,
                ),
            ),
            $description !== '' ? '<p class="description">' . esc_html($description) . '</p>' : '',
        );
    }
}
add_action('product_cat_edit_form_fields', 'hengegroup_theme_render_product_category_edit_fields');

function hengegroup_theme_action_save_product_category(int $term_id): void
{
    if (
        !isset($_POST['hengegroup_theme_product_category_nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['hengegroup_theme_product_category_nonce'])),
            'hengegroup_theme_save_product_category',
        ) ||
        !current_user_can('manage_product_terms')
    ) {
        return;
    }

    // Jeder Wert wird unten je Feldtyp einzeln sanitisiert.
    $data =
        isset($_POST['hengegroup_theme_term']) && is_array($_POST['hengegroup_theme_term'])
            ? wp_unslash($_POST['hengegroup_theme_term']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            : [];

    foreach (hengegroup_theme_get_product_category_fields() as $field => [, $type]) {
        $raw = (string) ($data[$field] ?? '');
        $value = match ($type) {
            'email' => sanitize_email($raw),
            'image' => absint($raw) > 0 ? (string) absint($raw) : '',
            'variant' => in_array($raw, hengegroup_theme_get_badge_variants(), true) ? $raw : '',
            default => trim(sanitize_text_field($raw)),
        };

        if ($value === '') {
            delete_term_meta($term_id, '_hengegroup_theme_category_' . $field);
        } else {
            update_term_meta($term_id, '_hengegroup_theme_category_' . $field, $value);
        }
    }
}
add_action('created_product_cat', 'hengegroup_theme_action_save_product_category');
add_action('edited_product_cat', 'hengegroup_theme_action_save_product_category');

/**
 * WooCommerce' "Up-Sells" steuern in diesem Projekt die manuell gesetzten verwandten Produkte der
 * Detailseite -- im Produkt-Editor entsprechend beschriftet, damit Redakteure das richtige Feld
 * finden (Cross-Sells bleiben unveraendert, sie gehoeren spaeter zum Warenkorb).
 */
function hengegroup_theme_filter_gettext_upsells(
    string $translation,
    string $text,
    string $domain,
): string {
    if ($domain !== 'woocommerce' || !is_admin()) {
        return $translation;
    }

    if ($text === 'Upsells') {
        return __('Verwandte Produkte', 'hengegroup-theme');
    }

    if (
        str_starts_with(
            $text,
            'Upsells are products which you recommend instead of the currently viewed product',
        )
    ) {
        return __(
            'Erscheinen auf der Produktseite unter "Verwandte Produkte" (bis zu 4). Sind es weniger, wird mit passenden Produkten derselben Kategorie aufgefüllt.',
            'hengegroup-theme',
        );
    }

    return $translation;
}
add_filter('gettext', 'hengegroup_theme_filter_gettext_upsells', 10, 3);

function hengegroup_theme_sanitize_product_options(mixed $input): array
{
    $input = is_array($input) ? $input : [];

    return [
        'overview_page_id' => absint($input['overview_page_id'] ?? 0),
        'contact_name' => sanitize_text_field((string) ($input['contact_name'] ?? '')),
        'contact_role' => sanitize_text_field((string) ($input['contact_role'] ?? '')),
        'contact_email' => sanitize_email((string) ($input['contact_email'] ?? '')),
        'contact_phone' => sanitize_text_field((string) ($input['contact_phone'] ?? '')),
        'contact_photo_id' => absint($input['contact_photo_id'] ?? 0),
    ];
}

function hengegroup_theme_action_admin_init_register_product_settings(): void
{
    register_setting('hengegroup_theme_product_options_group', HENGEGROUP_THEME_PRODUCT_OPTION, [
        'type' => 'array',
        'sanitize_callback' => 'hengegroup_theme_sanitize_product_options',
        'default' => [],
    ]);
}
add_action('admin_init', 'hengegroup_theme_action_admin_init_register_product_settings');

function hengegroup_theme_action_admin_menu_product_settings(): void
{
    add_submenu_page(
        'edit.php?post_type=product',
        __('Produkt-Einstellungen', 'hengegroup-theme'),
        __('Einstellungen', 'hengegroup-theme'),
        'manage_options',
        'hengegroup-theme-product-settings',
        'hengegroup_theme_render_product_settings_page',
    );
}
add_action('admin_menu', 'hengegroup_theme_action_admin_menu_product_settings');

function hengegroup_theme_render_product_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $options = hengegroup_theme_get_product_options();
    $option = HENGEGROUP_THEME_PRODUCT_OPTION;
    $text_fields = [
        'contact_name' => [__('Name', 'hengegroup-theme'), 'text'],
        'contact_role' => [__('Funktion', 'hengegroup-theme'), 'text'],
        'contact_email' => [__('E-Mail', 'hengegroup-theme'), 'email'],
        'contact_phone' => [__('Telefon', 'hengegroup-theme'), 'text'],
    ];
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Produkt-Einstellungen', 'hengegroup-theme'); ?></h1>
        <form action="options.php" method="post">
            <?php settings_fields('hengegroup_theme_product_options_group'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="hengegroup-theme-products-page"><?php esc_html_e(
                                'Produktübersicht',
                                'hengegroup-theme',
                            ); ?></label>
                        </th>
                        <td>
                            <?php wp_dropdown_pages([
                                'name' => esc_attr($option . '[overview_page_id]'),
                                'id' => 'hengegroup-theme-products-page',
                                'selected' => (int) $options['overview_page_id'],
                                'show_option_none' => esc_html__(
                                    '— Seite mit Slug "produkte" —',
                                    'hengegroup-theme',
                                ),
                                'option_none_value' => '0',
                            ]); ?>
                            <p class="description"><?php esc_html_e(
                                'Ziel von "Alle Produkte"-Links und der Weiterleitungen von Kategorie-Adressen.',
                                'hengegroup-theme',
                            ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" colspan="2">
                            <h2 style="margin:0"><?php esc_html_e(
                                'Standard-Ansprechpartner (Produktseiten)',
                                'hengegroup-theme',
                            ); ?></h2>
                            <p class="description" style="font-weight:normal"><?php esc_html_e(
                                'Gilt für alle Produkte, deren Kategorie keinen eigenen Ansprechpartner hat.',
                                'hengegroup-theme',
                            ); ?></p>
                        </th>
                    </tr>
                    <?php foreach ($text_fields as $field => [$label, $type]): ?>
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr(
                                    'hengegroup-theme-product-' . $field,
                                ); ?>"><?php echo esc_html($label); ?></label>
                            </th>
                            <td>
                                <input
                                    type="<?php echo esc_attr($type); ?>"
                                    id="<?php echo esc_attr(
                                        'hengegroup-theme-product-' . $field,
                                    ); ?>"
                                    name="<?php echo esc_attr($option . '[' . $field . ']'); ?>"
                                    value="<?php echo esc_attr((string) $options[$field]); ?>"
                                    class="regular-text"
                                >
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <th scope="row"><?php esc_html_e('Foto', 'hengegroup-theme'); ?></th>
                        <td>
                            <?php hengegroup_theme_render_seo_image_picker_field(
                                'hengegroup-theme-product-contact-photo',
                                $option . '[contact_photo_id]',
                                (int) $options['contact_photo_id'],
                            ); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
