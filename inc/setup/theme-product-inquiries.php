<?php

declare(strict_types=1);

// Verarbeitung der Produkt-Kontaktformulare (Formular: template-parts/components/inquiry-form.php)
// -- Block "Kontakt" auf der Produktuebersicht (allgemeine Anfrage, mit PLZ/Ort) und Abschnitt
// "Ihr Ansprechpartner im Vertrieb" der Produktdetailseite (Anfrage zu genau diesem Produkt).
//
// Gleicher Ablauf wie das Bewerbungsformular (inc/setup/theme-careers-application.php): klassisches
// POST an admin-post.php, danach Redirect zurueck auf die Seite (#kontakt) mit Status
// (Post/Redirect/Get), bei Fehlern Eingaben 15 Minuten per Transient; Spam-Schutz ueber Nonce,
// Honeypot und Mindest-Ausfuellzeit. Die Anfrage wird als Eintrag im Menuepunkt "Produktanfragen"
// gespeichert (inc/setup/theme-requests.php) -- es wird KEINE E-Mail versendet (explizite Vorgabe).

const HENGEGROUP_THEME_INQUIRY_ACTION = 'hengegroup_theme_product_inquiry';

/**
 * Status-Meldungen nach dem Redirect (Query-Parameter `anfrage`).
 */
function hengegroup_theme_get_inquiry_status_messages(): array
{
    return [
        'gesendet' => __(
            'Vielen Dank für Ihre Anfrage! Wir melden uns so bald wie möglich bei Ihnen.',
            'hengegroup-theme',
        ),
        'fehler' => __('Bitte prüfen Sie die markierten Angaben.', 'hengegroup-theme'),
        'abgelaufen' => __(
            'Das Formular war zu lange geöffnet. Bitte senden Sie Ihre Anfrage noch einmal ab.',
            'hengegroup-theme',
        ),
        'versand' => __(
            'Ihre Anfrage konnte gerade nicht gespeichert werden. Bitte versuchen Sie es später erneut oder rufen Sie uns an.',
            'hengegroup-theme',
        ),
    ];
}

function hengegroup_theme_get_inquiry_state(): array
{
    return hengegroup_theme_read_form_state(
        'hg_inquiry_',
        'anfrage',
        hengegroup_theme_get_inquiry_status_messages(),
    );
}

/**
 * Redirect zurueck auf die Seite, von der das Formular kam (veroeffentlichter Beitrag/Seite/
 * Produkt), sonst auf die Produktuebersicht.
 */
function hengegroup_theme_redirect_after_inquiry(int $return_id, array $args): void
{
    $target =
        $return_id > 0 && get_post_status($return_id) === 'publish'
            ? (string) get_permalink($return_id)
            : hengegroup_theme_get_products_page_url();

    wp_safe_redirect(add_query_arg($args, $target) . '#kontakt', 303);
    exit();
}

function hengegroup_theme_handle_product_inquiry(): void
{
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce wird direkt darunter geprueft.
    $return_id = isset($_POST['return_id']) ? absint($_POST['return_id']) : 0;
    $nonce = isset($_POST['_hengegroup_theme_inquiry_nonce'])
        ? sanitize_text_field(wp_unslash($_POST['_hengegroup_theme_inquiry_nonce']))
        : '';
    $honeypot = isset($_POST['website']) ? sanitize_text_field(wp_unslash($_POST['website'])) : '';
    $started = isset($_POST['started']) ? absint($_POST['started']) : 0;
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $with_location = !empty($_POST['with_location']);

    $field = static fn(string $key): string => isset($_POST[$key])
        ? sanitize_text_field(wp_unslash((string) $_POST[$key]))
        : '';
    $values = [
        'company' => $field('company'),
        'name' => $field('name'),
        'postal_code' => $with_location ? $field('postal_code') : '',
        'city' => $with_location ? $field('city') : '',
        'email' => sanitize_email($field('email')),
        'phone' => $field('phone'),
        'message' => isset($_POST['message'])
            ? sanitize_textarea_field(wp_unslash((string) $_POST['message']))
            : '',
        'privacy' => !empty($_POST['privacy']) ? '1' : '',
    ];
    // phpcs:enable WordPress.Security.NonceVerification.Missing

    if (!wp_verify_nonce($nonce, HENGEGROUP_THEME_INQUIRY_ACTION)) {
        hengegroup_theme_redirect_after_inquiry($return_id, ['anfrage' => 'abgelaufen']);
    }

    if (hengegroup_theme_is_form_bot($honeypot, $started)) {
        hengegroup_theme_redirect_after_inquiry($return_id, ['anfrage' => 'gesendet']);
    }

    if (
        $product_id > 0 &&
        (get_post_type($product_id) !== 'product' || get_post_status($product_id) !== 'publish')
    ) {
        $product_id = 0;
    }

    $errors = hengegroup_theme_validate_product_inquiry($values, $with_location);

    if ($errors !== []) {
        hengegroup_theme_redirect_after_inquiry($return_id, [
            'anfrage' => 'fehler',
            'eingabe' => hengegroup_theme_store_form_state('hg_inquiry_', $values, $errors),
        ]);
    }

    $title = implode(
        ' – ',
        array_filter([
            $values['company'],
            $values['name'],
            $product_id > 0 ? get_the_title($product_id) : '',
        ]),
    );

    $post_id = hengegroup_theme_create_request(HENGEGROUP_THEME_INQUIRY_POST_TYPE, $title, [
        'product_id' => $product_id,
        'company' => $values['company'],
        'name' => $values['name'],
        'postal_code' => $values['postal_code'],
        'city' => $values['city'],
        'email' => $values['email'],
        'phone' => $values['phone'],
        'message' => $values['message'],
        'source_url' => $return_id > 0 ? (string) get_permalink($return_id) : '',
    ]);

    hengegroup_theme_redirect_after_inquiry($return_id, [
        'anfrage' => $post_id > 0 ? 'gesendet' : 'versand',
    ]);
}
add_action(
    'admin_post_nopriv_' . HENGEGROUP_THEME_INQUIRY_ACTION,
    'hengegroup_theme_handle_product_inquiry',
);
add_action(
    'admin_post_' . HENGEGROUP_THEME_INQUIRY_ACTION,
    'hengegroup_theme_handle_product_inquiry',
);
