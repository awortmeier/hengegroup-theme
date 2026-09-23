<?php

declare(strict_types=1);

function hengegroup_theme_get_woocommerce_submenu_pages_to_remove(): array
{
    return [
        [
            'parent' => 'woocommerce',
            'slug' => 'coupons-moved',
        ],
        [
            'parent' => 'woocommerce',
            'slug' => 'wc-reports',
        ],
        [
            'parent' => 'wc-admin&path=/analytics/overview',
            'slug' => 'wc-admin&path=/analytics/downloads',
        ],
        [
            'parent' => 'woocommerce-marketing',
            'slug' => 'admin.php?page=wc-admin&path=/marketing',
        ],
        // Rezensionen und Marken bleiben als Feature/Taxonomie aktiv (Frontend unangetastet), nur
        // die Backend-Verwaltungsseiten unter "Produkte" verschwinden (explizite Nachfrage
        // 2026-09-23, siehe docs/entscheidungen.md).
        [
            'parent' => 'edit.php?post_type=product',
            'slug' => 'product-reviews',
        ],
        [
            'parent' => 'edit.php?post_type=product',
            'slug' => 'edit-tags.php?taxonomy=product_brand&post_type=product',
        ],
    ];
}

/**
 * "Gruppiert" und "Extern/angegliedert" werden in diesem Projekt nicht genutzt -- raus aus dem
 * Produkttyp-Dropdown, damit sie gar nicht erst waehlbar sind (explizite Nachfrage 2026-09-23,
 * siehe docs/entscheidungen.md).
 */
function hengegroup_theme_filter_product_type_selector_remove_unused_types(array $types): array
{
    unset($types['grouped'], $types['external']);

    return $types;
}

/**
 * "Virtuell"/"Herunterladbar" (allgemeiner Produkt-Tab) werden in diesem Projekt nicht genutzt.
 * WooCommerce bietet dafuer keinen Filter -- die beiden Checkboxen sind in
 * html-product-data-general.php fest verdrahtet -- deshalb rohes CSS statt Tailwind als Ausnahme
 * (siehe CLAUDE.md Regel 1): reines Backend-Feld-Ausblenden, kein Teil der Tailwind-gestylten
 * Theme-Oberflaeche, fuer die es ueberhaupt keinen Tailwind-Build in wp-admin gibt.
 * `label[for="_virtual"]`/`label[for="_downloadable"]` (statt der urspruenglich angenommenen
 * `_virtual_field`/`_downloadable_field`-Wrapper-Klassen aus woocommerce_wp_checkbox()) --
 * explizite Nachfrage/Korrektur 2026-09-23, siehe docs/entscheidungen.md: die installierte
 * WooCommerce-Version rendert diese beiden Checkboxen ueber ein eigenes, neueres Markup (Checkbox
 * + Label direkt, ohne den `<p class="{id}_field">`-Wrapper, den woocommerce_wp_checkbox() fuer
 * alle anderen Checkbox-Felder noch nutzt) -- die alten Klassen trafen dadurch nichts. `for`/`id`
 * sind WCs eigene, stabile Feld-IDs (`_virtual`/`_downloadable`, identisch mit dem
 * Post-Meta-Key), unabhaengig vom Markup drumherum.
 */
function hengegroup_theme_action_admin_head_hide_woocommerce_virtual_downloadable(): void
{
    $screen = get_current_screen();

    if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'product') {
        return;
    }

    echo '<style>label[for="_virtual"],label[for="_downloadable"]{display:none !important;}</style>';
}

/**
 * True auf dem einzelnen Produkt-Editor (`post.php`/`post-new.php` fuer `post_type=product`) --
 * Basis fuer die beiden folgenden Editor-Filter, die nur dort greifen sollen, nicht auf jedem
 * anderen Post-/Seiten-Editor in wp-admin.
 */
function hengegroup_theme_is_admin_product_edit_screen(): bool
{
    $screen = get_current_screen();

    return $screen instanceof WP_Screen &&
        $screen->base === 'post' &&
        $screen->post_type === 'product';
}

/**
 * Produktbeschreibung (`content`-Editor) und Produktkurzbeschreibung (`excerpt`-Editor) bekommen
 * auf explizite Nachfrage (2026-09-23, siehe docs/entscheidungen.md) nur noch den Visual-Editor --
 * kein Visual/Text-Umschalter mehr -- und keinen "Medien hinzufuegen"-Button. `wp_editor_settings`
 * greift fuer jeden `wp_editor()`-Aufruf (WordPress-Core-Filter, unabhaengig davon ob WordPress
 * selbst oder WooCommerce ihn aufruft). `quicktags => false` unterdrueckt sowohl die "Text"-Tab-
 * Schaltflaeche als auch die HTML-Editor-Umschaltung komplett, `media_buttons => false` den
 * separaten "Medien hinzufuegen"-Button oberhalb der Toolbar (kein Teil von `mce_buttons`/
 * `teeny_mce_buttons`, eigener `wp_editor()`-Settings-Key).
 */
function hengegroup_theme_filter_wp_editor_settings_product_description_visual_only(
    array $settings,
    string $editor_id,
): array {
    if (
        !in_array($editor_id, ['content', 'excerpt'], true) ||
        !hengegroup_theme_is_admin_product_edit_screen()
    ) {
        return $settings;
    }

    $settings['quicktags'] = false;
    $settings['media_buttons'] = false;

    return $settings;
}

/**
 * Entfernt aus der Produktbeschreibungs-Toolbar (auf explizite Nachfrage, 2026-09-23, siehe
 * docs/entscheidungen.md): das "Absatz"-Format-Dropdown (Ueberschriften/Absatz/... --
 * `formatselect`), die "Weiterlesen"-Tag-Schaltflaeche (`wp_more`), Blockzitat (`blockquote`) und
 * die "Werkzeugleiste umschalten"-Schaltflaeche fuer die erweiterte zweite Toolbar-Zeile
 * (`wp_adv`) -- Letzteres macht die zweite Zeile mangels Umschalter unerreichbar. Die
 * Kurzbeschreibung laeuft bereits im "teeny"-Modus, dessen eigene, kleinere Default-Toolbar
 * (siehe hengegroup_theme_filter_teeny_mce_buttons_product_short_description_remove_buttons())
 * weder das Format-Dropdown noch `wp_more`/`wp_adv` enthaelt -- der Filter greift dort trotzdem
 * mit, falls WooCommerce das irgendwann aendert.
 */
function hengegroup_theme_filter_mce_buttons_product_description_remove_buttons(
    array $buttons,
    string $editor_id,
): array {
    if (
        !in_array($editor_id, ['content', 'excerpt'], true) ||
        !hengegroup_theme_is_admin_product_edit_screen()
    ) {
        return $buttons;
    }

    return array_values(array_diff($buttons, ['formatselect', 'wp_more', 'blockquote', 'wp_adv']));
}

/**
 * Entfernt aus der Produktkurzbeschreibungs-Toolbar (TinyMCEs "teeny"-Modus, auf explizite
 * Nachfrage, 2026-09-23, siehe docs/entscheidungen.md): Blockzitat (`blockquote`) und den
 * "Vollbild"-Button (`fullscreen`) -- beide sind Teil von WordPress' Teeny-Default-Toolbar (anders
 * als bei der Hauptbeschreibung, siehe
 * hengegroup_theme_filter_mce_buttons_product_description_remove_buttons()), aber ueber
 * `mce_buttons` nicht erreichbar, weil Teeny-Editoren einen eigenen Core-Filter (`teeny_mce_buttons`)
 * statt `mce_buttons` durchlaufen.
 */
function hengegroup_theme_filter_teeny_mce_buttons_product_short_description_remove_buttons(
    array $buttons,
    string $editor_id,
): array {
    if ($editor_id !== 'excerpt' || !hengegroup_theme_is_admin_product_edit_screen()) {
        return $buttons;
    }

    return array_values(array_diff($buttons, ['blockquote', 'fullscreen']));
}

/**
 * Letzte, garantiert wirksame Absicherung fuer die Kurzbeschreibungs-Toolbar (explizite Nachfrage
 * 2026-09-23, siehe docs/entscheidungen.md: "Vollbild" blieb trotz
 * `hengegroup_theme_filter_teeny_mce_buttons_product_short_description_remove_buttons()` sichtbar).
 * WooCommerce uebergibt fuer den `excerpt`-Editor einen eigenen `tinymce`-Settings-Teilarray
 * (vermutlich inkl. eigenem `toolbar1`-String), den `wp_editor()` per `array_merge()` ueber
 * WordPress' aus `teeny_mce_buttons` gebauten Default-Toolbar draufbuegelt -- unser Filter oben
 * greift dadurch ins Leere, weil das Ergebnis danach ueberschrieben wird. `tiny_mce_before_init`
 * ist der letzte Filter vor der JSON-Kodierung des kompletten TinyMCE-Init-Arrays, gewinnt also
 * garantiert gegen jeden vorherigen `array_merge()` -- entfernt `blockquote`/`fullscreen` direkt
 * aus den fertig zusammengebauten `toolbar*`-Strings statt aus dem (in diesem Fall zu frueh
 * gefilterten) Buttons-Array.
 */
function hengegroup_theme_filter_tiny_mce_before_init_product_short_description_remove_buttons(
    array $mce_init,
    string $editor_id,
): array {
    if ($editor_id !== 'excerpt' || !hengegroup_theme_is_admin_product_edit_screen()) {
        return $mce_init;
    }

    foreach (['toolbar1', 'toolbar2', 'toolbar3', 'toolbar4'] as $toolbar_key) {
        if (empty($mce_init[$toolbar_key]) || !is_string($mce_init[$toolbar_key])) {
            continue;
        }

        $buttons = array_diff(explode(',', $mce_init[$toolbar_key]), ['blockquote', 'fullscreen']);
        $mce_init[$toolbar_key] = implode(',', $buttons);
    }

    return $mce_init;
}

function hengegroup_theme_action_admin_menu_cleanup_woocommerce(): void
{
    foreach (hengegroup_theme_get_woocommerce_submenu_pages_to_remove() as $submenu_page) {
        $parent = $submenu_page['parent'] ?? '';
        $slug = $submenu_page['slug'] ?? '';

        if (!is_string($parent) || !is_string($slug) || $parent === '' || $slug === '') {
            continue;
        }

        remove_submenu_page($parent, $slug);
    }
}

function hengegroup_theme_action_admin_bar_menu_cleanup_woocommerce(): void
{
    global $wp_admin_bar;
    $wp_admin_bar->remove_node('woocommerce-site-visibility-badge');
}

/**
 * WordPress registriert fuer jede an den Post-Type "product" gebundene Taxonomie automatisch eine
 * eigene Sidebar-Metabox im Produkt-Editor (`add_meta_boxes` mit dem Taxonomie-Namen als Box-ID,
 * siehe `register_taxonomy()`s `show_ui`) -- unabhaengig von der Backend-Menue-Sichtbarkeit. Die
 * entfernte "Marken"-Verwaltungsseite (siehe hengegroup_theme_get_woocommerce_submenu_pages_to_remove())
 * liess die "Produktmarken"-Box im einzelnen Produkt-Editor deshalb unveraendert sichtbar
 * (explizite Nachfrage 2026-09-23, siehe docs/entscheidungen.md). `product_branddiv` ist WCs
 * eigene, feste Box-ID fuer die `product_brand`-Taxonomie (gleiches `<taxonomy>div`-Namensschema
 * wie WordPress' eigene `categorydiv`/`tagsdiv-*`-Boxen). Taxonomie/Frontend bleiben unangetastet,
 * nur diese eine Backend-Box verschwindet.
 */
function hengegroup_theme_action_add_meta_boxes_remove_product_brand_metabox(): void
{
    remove_meta_box('product_branddiv', 'product', 'side');
}

function hengegroup_theme_register_admin_woocommerce_hooks(): void
{
    add_filter(
        'wp_editor_settings',
        'hengegroup_theme_filter_wp_editor_settings_product_description_visual_only',
        10,
        2,
    );
    add_filter(
        'mce_buttons',
        'hengegroup_theme_filter_mce_buttons_product_description_remove_buttons',
        10,
        2,
    );
    add_filter(
        'teeny_mce_buttons',
        'hengegroup_theme_filter_teeny_mce_buttons_product_short_description_remove_buttons',
        10,
        2,
    );
    add_filter(
        'tiny_mce_before_init',
        'hengegroup_theme_filter_tiny_mce_before_init_product_short_description_remove_buttons',
        10,
        2,
    );
    add_action('admin_menu', 'hengegroup_theme_action_admin_menu_cleanup_woocommerce', 999);
    add_action('admin_bar_menu', 'hengegroup_theme_action_admin_bar_menu_cleanup_woocommerce', 999);
    add_filter(
        'product_type_selector',
        'hengegroup_theme_filter_product_type_selector_remove_unused_types',
    );
    add_action(
        'admin_head',
        'hengegroup_theme_action_admin_head_hide_woocommerce_virtual_downloadable',
    );
    add_action(
        'add_meta_boxes_product',
        'hengegroup_theme_action_add_meta_boxes_remove_product_brand_metabox',
        999,
    );
}

hengegroup_theme_register_admin_woocommerce_hooks();
