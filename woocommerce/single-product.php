<?php

declare(strict_types=1);

// Produktdetailseite (ersetzt das WC-Layout komplett, noch keine Bestellfunktion). Abschnitte nur
// mit Inhalt: Intro, Technische Daten, Anwendungen (ohne Link), Downloads, Ansprechpartner +
// Anfrage (#kontakt), verwandte Produkte. Daten aus hengegroup_theme_get_product_data().

defined('ABSPATH') || exit();

get_header('shop');

while (have_posts()):

    the_post();
    $product = wc_get_product(get_the_ID());

    if (!($product instanceof WC_Product)) {
        continue;
    }

    $data = hengegroup_theme_get_product_data($product);
    $badge_markup = hengegroup_theme_render_product_badge($data['id']);
    $section_heading = static function (string $text, string $color = 'default'): void {
        get_template_part('template-parts/base/typography', null, [
            'config' => [
                'variant' => 'headline-sm',
                'tag' => 'h2',
                'text' => $text,
                'color' => $color,
                'class' => 'mb-10',
            ],
        ]);
    };
    ?>
  <article <?php wc_product_class('header-offset', $product); ?> data-slot="product-detail">
    <section class="section-spacing" aria-labelledby="produkt-titel">
      <div class="wrapper items-start gap-y-10">
        <div class="<?php echo esc_attr(
            $data['image_id'] > 0 ? 'col-span-12 lg:col-span-8' : 'col-span-12 lg:col-span-9',
        ); ?>">
          <?php if ($badge_markup !== ''): ?>
            <div class="mb-4.5">
              <?php printf(
                  '%s',
                  $badge_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
              ); ?>
            </div>
          <?php endif; ?>

          <?php get_template_part('template-parts/base/typography', null, [
              'config' => [
                  'variant' => 'headline-sm',
                  'tag' => 'h1',
                  'text' => $data['name'],
                  'class' => 'mb-5 text-balance lg:text-[44px] lg:leading-[1.1]',
                  'attributes' => ['id' => 'produkt-titel'],
              ],
          ]); ?>

          <?php if (trim($data['description']) !== ''): ?>
            <div class="mb-7 text-lg leading-relaxed text-grey-dark lg:text-[19px] rich-text">
              <?php echo wp_kses_post(wc_format_content($data['description'])); ?>
            </div>
          <?php endif; ?>

          <?php if ($data['recycling'] !== ''): ?>
            <p class="inline-flex max-w-full items-center gap-3 rounded-[14px] bg-henge-green/10 px-5 py-4 text-[15px] leading-normal text-grey-dark">
              <?php $recycling_icon = hengegroup_theme_render_icon([
                  'name' => 'recycle',
                  'set' => 'lucide',
                  'class' => 'size-6 shrink-0 text-henge-green',
              ]); ?>
              <?php printf(
                  '%s',
                  $recycling_icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
              ); ?>
              <span><?php echo esc_html($data['recycling']); ?></span>
            </p>
          <?php endif; ?>
        </div>

        <?php if ($data['image_id'] > 0): ?>
          <div class="col-span-12 lg:col-span-4">
            <?php $product_image = hengegroup_theme_render_image([
                'attachment_id' => $data['image_id'],
                'size' => 'large',
                'alt' => $data['name'],
                'class' => 'h-80 w-full rounded-[20px] object-cover lg:h-105',
            ]); ?>
            <?php printf(
                '%s',
                $product_image, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ); ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <?php if ($data['analysis'] !== [] || $data['grain_sizes'] !== []): ?>
      <section
        class="section-dark section-spacing"
        aria-label="<?php esc_attr_e('Technische Daten', 'hengegroup-theme'); ?>"
      >
        <div class="wrapper gap-y-12">
          <?php if ($data['analysis'] !== []): ?>
            <div class="<?php echo esc_attr(
                $data['grain_sizes'] !== []
                    ? 'col-span-12 lg:col-span-6'
                    : 'col-span-12 lg:col-span-8',
            ); ?>">
              <?php get_template_part('template-parts/base/typography', null, [
                  'config' => [
                      'variant' => 'body-lg',
                      'tag' => 'h2',
                      'text' => __('Chemische Analyse (typisch)', 'hengegroup-theme'),
                      'color' => 'light',
                      'class' => 'mb-6 font-bold',
                  ],
              ]); ?>
              <dl>
                <?php foreach ($data['analysis'] as $row): ?>
                  <div class="flex justify-between gap-6 border-b border-grey-light/15 py-3.5 text-[17px] text-grey-light">
                    <dt><?php echo esc_html((string) $row['label']); ?></dt>
                    <dd class="text-right font-semibold"><?php echo esc_html(
                        (string) $row['value'],
                    ); ?></dd>
                  </div>
                <?php endforeach; ?>
              </dl>
            </div>
          <?php endif; ?>

          <?php if ($data['grain_sizes'] !== []): ?>
            <div class="<?php echo esc_attr(
                $data['analysis'] !== []
                    ? 'col-span-12 lg:col-span-6'
                    : 'col-span-12 lg:col-span-8',
            ); ?>">
              <?php get_template_part('template-parts/base/typography', null, [
                  'config' => [
                      'variant' => 'body-lg',
                      'tag' => 'h2',
                      'text' => __('Lieferbare Körnungen', 'hengegroup-theme'),
                      'color' => 'light',
                      'class' => 'mb-6 font-bold',
                  ],
              ]); ?>
              <ul>
                <?php foreach ($data['grain_sizes'] as $grain_size): ?>
                  <li class="border-b border-grey-light/15 py-3.5 text-[17px] text-grey-light"><?php echo esc_html(
                      $grain_size,
                  ); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($data['anwendungen'] !== []): ?>
      <section class="section-spacing">
        <div class="wrapper">
          <div class="col-span-12">
            <?php $section_heading(__('Anwendungsbereiche', 'hengegroup-theme')); ?>
            <ul class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
              <?php foreach ($data['anwendungen'] as $anwendung): ?>
                <?php printf(
                    '%s',
                    hengegroup_theme_render_anwendung_card($anwendung, $data['accent']), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                ); ?>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($data['downloads'] !== []): ?>
      <section id="downloads" class="bg-neutral-200 section-spacing">
        <div class="wrapper">
          <div class="col-span-12">
            <?php $section_heading(__('Downloads', 'hengegroup-theme')); ?>
            <ul class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
              <?php foreach ($data['downloads'] as $download): ?>
                <?php
                $download_cta =
                    $download['cta'] !== ''
                        ? $download['cta']
                        : sprintf(
                            /* translators: %s: download title. */
                            __('%s herunterladen', 'hengegroup-theme'),
                            $download['title'],
                        );

                ob_start();
                get_template_part('template-parts/base/button', null, [
                    'config' => [
                        'href' => $download['url'],
                        'text' => $download_cta,
                        'variant' => 'grey-dark',
                        'size' => 'lg',
                        'full_width' => true,
                        'attributes' => ['download' => true],
                    ],
                ]);
                $download_action = (string) ob_get_clean();
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo hengegroup_theme_render_card([
                    'tag' => 'li',
                    'title' => $download['title'],
                    'title_variant' => 'body-base',
                    'description' => $download['meta'],
                    'icon' => hengegroup_theme_render_icon([
                        'name' => 'file',
                        'set' => 'lucide',
                    ]),
                    'content' =>
                        $download['description'] !== ''
                            ? hengegroup_theme_render_typography([
                                'variant' => 'body-sm',
                                'text' => $download['description'],
                            ])
                            : '',
                    'footer' => $download_action,
                ]);
                ?>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php $contact_card = hengegroup_theme_render_contact_card(
        $data['contact'] + ['address' => $data['address']],
        ['variant' => 'split', 'accent' => $data['accent']],
    ); ?>
    <section id="kontakt" class="scroll-mt-24 bg-grey-dark section-spacing" aria-labelledby="kontakt-titel">
      <div class="wrapper gap-y-12">
        <div class="col-span-12 max-w-3xl">
          <?php get_template_part('template-parts/base/typography', null, [
              'config' => [
                  'variant' => 'headline-sm',
                  'tag' => 'h2',
                  'text' => __('Ihr Ansprechpartner im Vertrieb', 'hengegroup-theme'),
                  'color' => 'light',
                  'class' => 'mb-5',
                  'attributes' => ['id' => 'kontakt-titel'],
              ],
          ]); ?>
          <?php get_template_part('template-parts/base/typography', null, [
              'config' => [
                  'variant' => 'body-base',
                  'text' => sprintf(
                      /* translators: %s: product name. */
                      __(
                          'Sie haben Fragen zu %s oder wünschen ein individuelles Angebot? Kontaktieren Sie uns über das Formular oder direkt telefonisch.',
                          'hengegroup-theme',
                      ),
                      $data['name'],
                  ),
                  'color' => 'light',
              ],
          ]); ?>
        </div>

        <?php if ($contact_card !== ''): ?>
          <aside class="col-span-12 self-start sm:col-span-6 lg:col-span-3" aria-label="<?php esc_attr_e(
              'Ansprechpartner',
              'hengegroup-theme',
          ); ?>">
            <?php printf(
                '%s',
                $contact_card, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ); ?>
          </aside>
        <?php endif; ?>

        <div class="col-span-12 lg:col-span-9">
          <?php get_template_part('template-parts/components/inquiry-form', null, [
              'product_id' => $data['id'],
              'with_location' => false,
              'tone' => 'dark',
              'accent' => $data['accent'],
              'message_label' => sprintf(
                  /* translators: %s: product name. */
                  __('Nachricht zu %s', 'hengegroup-theme'),
                  $data['name'],
              ),
          ]); ?>
        </div>
      </div>
    </section>

    <?php $related_markup = hengegroup_theme_render_product_cards(
        $data['related_ids'],
        'minimal',
    ); ?>
    <?php if ($related_markup !== ''): ?>
      <section class="bg-neutral-50 section-spacing" aria-labelledby="verwandte-titel">
        <div class="wrapper gap-y-6">
          <div class="col-span-12">
            <?php get_template_part('template-parts/base/typography', null, [
                'config' => [
                    'variant' => 'headline-sm',
                    'tag' => 'h2',
                    'text' => __('Verwandte Produkte', 'hengegroup-theme'),
                    'class' => 'mb-4',
                    'attributes' => ['id' => 'verwandte-titel'],
                ],
            ]); ?>
          </div>
          <?php printf(
              '%s',
              $related_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          ); ?>
        </div>
      </section>
    <?php endif; ?>
  </article>
<?php
endwhile;

get_footer('shop');
