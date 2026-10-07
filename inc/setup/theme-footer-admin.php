<?php

declare(strict_types=1);

// Settings > Footer: editorially maintained company contact data + social profile URLs rendered
// by footer.php (Claude-Design "Hengegroup" footer reference). An own options page rather than
// the Customizer, because theme-admin.php actively hides the Customizer (see docs/to-do.md
// Phase 3 note); same register_setting()/add_options_page() idiom as theme-seo-admin.php. The
// footer's "Links" column is NOT stored here -- it is the already registered `footer`
// wp_nav_menu() location (theme-setup.php), same "editorial nav belongs in the menu editor"
// reasoning as the header (see docs/entscheidungen.md).
//
// Every field is optional: footer.php skips an empty contact line, an empty social URL's icon,
// and a whole column once all of its entries are empty -- no "#" placeholder links on the live
// site. Contact defaults are the reference design's values so a fresh install already matches
// it; social URLs default to empty (the reference only has "#" placeholders there).

const HENGEGROUP_THEME_FOOTER_OPTION = 'hengegroup_theme_footer_options';

/**
 * Social profile fields in render order: option key => [label, Tabler icon set, Tabler icon name].
 * Icon names are string literals on purpose so scripts/find-tabler-icons.php picks them up.
 */
function hengegroup_theme_get_footer_social_networks(): array
{
    return [
        'facebook_url' => [
            'label' => __('Facebook', 'hengegroup-theme'),
            'icon' => ['name' => 'brand-facebook', 'set' => 'tabler/filled'],
        ],
        'instagram_url' => [
            'label' => __('Instagram', 'hengegroup-theme'),
            'icon' => ['name' => 'brand-instagram', 'set' => 'tabler/outline'],
        ],
        'linkedin_url' => [
            'label' => __('LinkedIn', 'hengegroup-theme'),
            'icon' => ['name' => 'brand-linkedin', 'set' => 'tabler/filled'],
        ],
    ];
}

/**
 * Returns the footer options, merged over the defaults so every key is always present
 * (e.g. right after theme activation, before the settings page was ever saved).
 */
function hengegroup_theme_get_footer_options(): array
{
    $defaults = [
        'address' => "HENGE Services GmbH\nInterpark 23\nD-76877 Offenbach (Pfalz)",
        'email' => 'info@hengegroup.com',
        'phone' => '+49 (0) 63 48 / 98 38-0',
        'fax' => '+49 (0) 63 48 / 98 38-50',
        'facebook_url' => '',
        'instagram_url' => '',
        'linkedin_url' => '',
    ];

    $stored = get_option(HENGEGROUP_THEME_FOOTER_OPTION, []);

    return array_merge($defaults, is_array($stored) ? $stored : []);
}

function hengegroup_theme_sanitize_footer_options($input): array
{
    $input = is_array($input) ? $input : [];

    $sanitized = [
        'address' => sanitize_textarea_field((string) ($input['address'] ?? '')),
        'email' => sanitize_email((string) ($input['email'] ?? '')),
        'phone' => sanitize_text_field((string) ($input['phone'] ?? '')),
        'fax' => sanitize_text_field((string) ($input['fax'] ?? '')),
    ];

    foreach (array_keys(hengegroup_theme_get_footer_social_networks()) as $key) {
        $sanitized[$key] = esc_url_raw((string) ($input[$key] ?? ''), ['http', 'https']);
    }

    return $sanitized;
}

function hengegroup_theme_action_admin_init_register_footer_settings(): void
{
    register_setting('hengegroup_theme_footer_options_group', HENGEGROUP_THEME_FOOTER_OPTION, [
        'type' => 'array',
        'sanitize_callback' => 'hengegroup_theme_sanitize_footer_options',
        'default' => [],
    ]);
}
add_action('admin_init', 'hengegroup_theme_action_admin_init_register_footer_settings');

function hengegroup_theme_action_admin_menu_footer_settings(): void
{
    add_options_page(
        __('Footer', 'hengegroup-theme'),
        __('Footer', 'hengegroup-theme'),
        'manage_options',
        'hengegroup-theme-footer',
        'hengegroup_theme_render_footer_settings_page',
    );
}
add_action('admin_menu', 'hengegroup_theme_action_admin_menu_footer_settings');

/**
 * One form-table row with a single-line input -- the settings page below has six nearly
 * identical rows, this keeps them from being seven copies of the same markup.
 */
function hengegroup_theme_render_footer_settings_input_row(
    string $key,
    string $label,
    string $value,
    string $type = 'text',
): void {
    $field_id = HENGEGROUP_THEME_FOOTER_OPTION . '_' . $key; ?>
    <tr>
        <th scope="row">
            <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($label); ?></label>
        </th>
        <td>
            <input
                type="<?php echo esc_attr($type); ?>"
                id="<?php echo esc_attr($field_id); ?>"
                name="<?php echo esc_attr(HENGEGROUP_THEME_FOOTER_OPTION . '[' . $key . ']'); ?>"
                value="<?php echo esc_attr($value); ?>"
                class="regular-text"
            />
        </td>
    </tr>
    <?php
}

function hengegroup_theme_render_footer_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $options = hengegroup_theme_get_footer_options();
    $address_id = HENGEGROUP_THEME_FOOTER_OPTION . '_address';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Footer', 'hengegroup-theme'); ?></h1>
        <p>
            <?php esc_html_e(
                'Kontaktdaten und Social-Media-Profile im Seiten-Footer. Leere Felder werden im ' .
                    'Footer ausgeblendet. Die Spalte "Links" wird unter Design > Menues ' .
                    '(Position "Footermenü") gepflegt.',
                'hengegroup-theme',
            ); ?>
        </p>
        <form action="options.php" method="post">
            <?php settings_fields('hengegroup_theme_footer_options_group'); ?>
            <h2><?php esc_html_e('Kontakt', 'hengegroup-theme'); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($address_id); ?>">
                                <?php esc_html_e('Adresse', 'hengegroup-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <textarea
                                id="<?php echo esc_attr($address_id); ?>"
                                name="<?php echo esc_attr(
                                    HENGEGROUP_THEME_FOOTER_OPTION,
                                ); ?>[address]"
                                rows="3"
                                class="large-text"
                            ><?php echo esc_textarea($options['address']); ?></textarea>
                            <p class="description">
                                <?php esc_html_e(
                                    'Eine Zeile pro Adresszeile.',
                                    'hengegroup-theme',
                                ); ?>
                            </p>
                        </td>
                    </tr>
                    <?php
                    hengegroup_theme_render_footer_settings_input_row(
                        'email',
                        __('E-Mail', 'hengegroup-theme'),
                        (string) $options['email'],
                        'email',
                    );
                    hengegroup_theme_render_footer_settings_input_row(
                        'phone',
                        __('Telefon', 'hengegroup-theme'),
                        (string) $options['phone'],
                    );
                    hengegroup_theme_render_footer_settings_input_row(
                        'fax',
                        __('Fax', 'hengegroup-theme'),
                        (string) $options['fax'],
                    );
                    ?>
                </tbody>
            </table>
            <h2><?php esc_html_e('Social Media', 'hengegroup-theme'); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                    <?php foreach (
                        hengegroup_theme_get_footer_social_networks()
                        as $key => $network
                    ) {
                        hengegroup_theme_render_footer_settings_input_row(
                            $key,
                            $network['label'],
                            (string) $options[$key],
                            'url',
                        );
                    } ?>
                </tbody>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
