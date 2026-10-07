<?php declare(strict_types=1) ?>
</main>

<?php
// Dark four-column site footer after the Claude-Design "Hengegroup" footer reference: logo +
// address, contact, social media, links -- then the same brand-gradient rule the header uses on
// its top edge, and the copyright line. Contact data and social URLs come from Settings > Footer
// (inc/setup/theme-footer-admin.php), the "Links" column from the `footer` wp_nav_menu() location;
// every entry/column is skipped when empty, so no "#" placeholder ends up on the live site.
//
// Social-Media-Links are button.php icon buttons (variant `ghost`, size `icon-lg`) with a
// translucent resting background added via `class` (ghost itself has none, so nothing competes);
// the hover state is ghost's own (light fill, dark icon) instead of the reference's green fill --
// button.php has no variant for that, and a footer-only variant isn't worth extending its API.
//
// `hover:text-[#8fd6ab]` is the reference's own light-green link hover -- a Tailwind ARBITRARY
// VALUE (CLAUDE.md Regel 1 allows these), since no brand token matches it and henge-green itself
// is too dark for readable text on grey-dark.
$footer_options = hengegroup_theme_get_footer_options();

$footer_address_lines = array_filter(
    array_map('trim', explode("\n", (string) $footer_options['address'])),
    static fn(string $line): bool => $line !== '',
);
$footer_email = (string) $footer_options['email'];
$footer_phone = (string) $footer_options['phone'];
$footer_fax = (string) $footer_options['fax'];

// Contact rows in render order -- `href` null renders plain text (fax has nothing to link to).
// `label` is a screen-reader-only prefix, since the visual cue is the icon alone.
$footer_contact_rows = [];
if ($footer_email !== '') {
    $footer_contact_rows[] = [
        'icon' => ['name' => 'mail', 'set' => 'lucide'],
        'label' => __('E-Mail:', 'hengegroup-theme'),
        'text' => $footer_email,
        'href' => esc_url('mailto:' . $footer_email, ['mailto']),
    ];
}
if ($footer_phone !== '') {
    $footer_contact_rows[] = [
        'icon' => ['name' => 'phone', 'set' => 'lucide'],
        'label' => __('Telefon:', 'hengegroup-theme'),
        'text' => $footer_phone,
        'href' => esc_url(hengegroup_theme_phone_href($footer_phone), ['tel']),
    ];
}
if ($footer_fax !== '') {
    $footer_contact_rows[] = [
        'icon' => ['name' => 'printer', 'set' => 'lucide'],
        'label' => __('Fax:', 'hengegroup-theme'),
        'text' => $footer_fax,
        'href' => null,
    ];
}

$footer_social_links = [];
foreach (hengegroup_theme_get_footer_social_networks() as $key => $network) {
    $url = esc_url_raw((string) ($footer_options[$key] ?? ''), ['http', 'https']);
    if ($url !== '') {
        $footer_social_links[] = [
            'url' => $url,
            'label' => $network['label'],
            'icon' => $network['icon'] + ['class' => 'size-[17px]'],
        ];
    }
}

$footer_menu = wp_nav_menu([
    'theme_location' => 'footer',
    'container' => false,
    'menu_class' => 'flex flex-col gap-2.5',
    'depth' => 1,
    'fallback_cb' => false,
    'echo' => false,
]);

$footer_address_icon = hengegroup_theme_render_icon([
    'name' => 'map-pin',
    'set' => 'lucide',
    'class' => 'mt-0.5 size-4 shrink-0',
]);

$footer_heading = static function (string $text, array $attributes = []): void {
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => 'body-xs',
            'tag' => 'h2',
            'text' => $text,
            'color' => 'light',
            'class' => 'mb-4 font-bold',
            'attributes' => $attributes,
        ],
    ]);
};
$footer_link_class = 'text-grey-light no-underline transition-colors hover:text-[#8fd6ab]';
?>

<footer
    class="bg-grey-dark px-6 py-14 text-grey-light shadow-[inset_0_40px_40px_-40px_rgb(0_0_0/0.5)] sm:px-12"
>
    <div class="mx-auto grid max-w-[1400px] grid-cols-1 gap-12 pb-8 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
        <div>
            <div class="mb-4">
                <?php if (has_custom_logo()): ?>
                    <div class="[&_img]:h-7 [&_img]:w-auto">
                        <?php the_custom_logo(); ?>
                    </div>
                <?php else: ?>
                    <a
                        class="text-xl font-semibold text-grey-light no-underline"
                        href="<?php echo esc_url(home_url('/')); ?>"
                    ><?php bloginfo('name'); ?></a>
                <?php endif; ?>
            </div>

            <?php if ($footer_address_lines !== []): ?>
                <address class="flex items-start gap-2.5 text-sm/[1.7] not-italic">
                    <?php printf(
                        '%s',
                        $footer_address_icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ); ?>
                    <span>
                        <?php foreach ($footer_address_lines as $address_line): ?>
                            <?php echo esc_html($address_line); ?><br>
                        <?php endforeach; ?>
                    </span>
                </address>
            <?php endif; ?>
        </div>

        <?php if ($footer_contact_rows !== []): ?>
            <div>
                <?php $footer_heading(__('Kontakt', 'hengegroup-theme')); ?>
                <ul class="flex flex-col gap-2.5">
                    <?php foreach ($footer_contact_rows as $contact_row): ?>
                        <?php $contact_icon = hengegroup_theme_render_icon(
                            $contact_row['icon'] + ['class' => 'size-4 shrink-0'],
                        ); ?>
                        <li class="flex items-center gap-2.5 text-sm">
                            <?php printf(
                                '%s',
                                $contact_icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            ); ?>
                            <span class="sr-only"><?php echo esc_html(
                                $contact_row['label'],
                            ); ?></span>
                            <?php if ($contact_row['href']): ?>
                                <a
                                    class="<?php echo esc_attr($footer_link_class); ?>"
                                    href="<?php echo esc_attr($contact_row['href']); ?>"
                                ><?php echo esc_html($contact_row['text']); ?></a>
                            <?php else: ?>
                                <span><?php echo esc_html($contact_row['text']); ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($footer_social_links !== []): ?>
            <div>
                <?php $footer_heading(__('Social Media', 'hengegroup-theme')); ?>
                <ul class="flex gap-3.5">
                    <?php foreach ($footer_social_links as $social_link): ?>
                        <li>
                            <?php get_template_part('template-parts/base/button', null, [
                                'config' => [
                                    'href' => $social_link['url'],
                                    'icon' => $social_link['icon'],
                                    'aria_label' => $social_link['label'],
                                    'variant' => 'ghost',
                                    'size' => 'icon-lg',
                                    'class' => 'bg-grey-light/10 text-grey-light',
                                    'attributes' => [
                                        'target' => '_blank',
                                        'rel' => 'noopener noreferrer',
                                    ],
                                ],
                            ]); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($footer_menu): ?>
            <nav aria-labelledby="footer-links-heading">
                <?php $footer_heading(__('Links', 'hengegroup-theme'), [
                    'id' => 'footer-links-heading',
                ]); ?>
                <div class="text-sm [&_a]:text-grey-light [&_a]:no-underline [&_a]:transition-colors [&_a:hover]:text-[#8fd6ab]">
                    <?php printf(
                        '%s',
                        $footer_menu, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ); ?>
                </div>
            </nav>
        <?php endif; ?>
    </div>

    <div
        class="mx-auto h-0.5 max-w-[1400px] bg-linear-to-r from-henge-grey via-henge-green to-henge-blue"
        aria-hidden="true"
    ></div>

    <p class="mx-auto max-w-[1400px] pt-6 text-[13px]">
        &copy; <?php echo esc_html(wp_date('Y')); ?> <?php echo esc_html(get_bloginfo('name')); ?>
    </p>
</footer>

</div>
<?php wp_footer(); ?>
</body>
</html>
