<?php

declare(strict_types=1);

// Einzelseite einer Stelle. Inhalt in 8 von 12 Spalten, Ansprechpartner-Karte ab lg rechts
// (row-span-2, damit der Text direkt unter der Faktenleiste beginnt). Daten aus
// hengegroup_theme_get_job_data(), wie das JSON-LD; Google verlangt die Fakten auch sichtbar.

defined('ABSPATH') || exit();

get_header();

while (have_posts()):

    the_post();
    $job = hengegroup_theme_get_job_data((int) get_the_ID());
    $location_label = implode(' · ', array_column($job['locations'], 'label'));
    $employment_labels = array_intersect_key(
        hengegroup_theme_get_job_employment_types(),
        array_flip($job['employment_types']),
    );
    $salary_label = hengegroup_theme_format_job_salary(
        $job['salary']['min'],
        $job['salary']['max'],
        $job['salary']['unit'],
    );

    if ($job['start_immediately']) {
        $start_label = __('ab sofort', 'hengegroup-theme');
    } elseif ($job['start_date'] !== '') {
        $start_label = sprintf(
            /* translators: %s: start date. */
            __('ab %s', 'hengegroup-theme'),
            date_i18n(get_option('date_format'), strtotime($job['start_date'])),
        );
    } else {
        $start_label = '';
    }

    // Literale Icon-Namen, damit find-lucide-icons.php sie findet.
    $facts = [
        [
            'label' => __('Standort', 'hengegroup-theme'),
            'value' => $location_label,
            'icon' => ['name' => 'map-pin', 'set' => 'lucide', 'class' => 'size-5 shrink-0'],
        ],
        [
            'label' => __('Anstellungsart', 'hengegroup-theme'),
            'value' => implode(', ', $employment_labels),
            'icon' => [
                'name' => 'briefcase-business',
                'set' => 'lucide',
                'class' => 'size-5 shrink-0',
            ],
        ],
        [
            'label' => __('Arbeitsmodell', 'hengegroup-theme'),
            'value' => hengegroup_theme_get_job_work_models()[$job['work_model']],
            'icon' => ['name' => 'laptop', 'set' => 'lucide', 'class' => 'size-5 shrink-0'],
        ],
        [
            'label' => __('Gehalt', 'hengegroup-theme'),
            'value' => $salary_label,
            'icon' => ['name' => 'banknote', 'set' => 'lucide', 'class' => 'size-5 shrink-0'],
        ],
        [
            'label' => __('Eintritt', 'hengegroup-theme'),
            'value' => $start_label,
            'icon' => ['name' => 'calendar', 'set' => 'lucide', 'class' => 'size-5 shrink-0'],
        ],
        [
            'label' => __('Veröffentlicht', 'hengegroup-theme'),
            'value' => (string) get_the_date(),
            'icon' => [
                'name' => 'calendar-clock',
                'set' => 'lucide',
                'class' => 'size-5 shrink-0',
            ],
            'datetime' => $job['date_posted'],
        ],
    ];
    $facts = array_filter($facts, static fn(array $fact): bool => $fact['value'] !== '');
    $application_email = $job['contact']['email'];
    ?>
  <article <?php post_class('header-offset'); ?> data-slot="job-posting">
    <header class="wrapper pb-10 section-spacing-top">
      <div class="col-span-12 flex flex-col gap-6 lg:col-span-8">
        <?php if (hengegroup_theme_is_job_expired((int) get_the_ID())): ?>
          <p class="rounded-xl border border-henge-blue/30 bg-white px-5 py-4 text-base text-grey-dark" role="status">
            <?php esc_html_e(
                'Nur für Redakteure sichtbar: Diese Stelle ist abgelaufen bzw. besetzt. Besucher werden auf die Karriereseite weitergeleitet, Google erhält keine Stellendaten mehr.',
                'hengegroup-theme',
            ); ?>
          </p>
        <?php endif; ?>

        <?php
        $location_meta =
            $location_label !== ''
                ? hengegroup_theme_render_icon([
                        'name' => 'map-pin',
                        'set' => 'lucide',
                        'class' => 'size-4 shrink-0',
                    ]) . esc_html($location_label)
                : '';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo hengegroup_theme_render_section_header([
            'kicker' =>
                $job['company'] !== null
                    ? hengegroup_theme_render_label_badge(
                        $job['company']['name'],
                        $job['company']['variant'],
                        'px-3.5 tracking-[1.5px]',
                    )
                    : '',
            'title' => get_the_title(),
            'title_tag' => 'h1',
            'meta' => $location_meta,
        ]);
        ?>
      </div>
    </header>

    <div class="wrapper gap-y-10 section-spacing-bottom">
      <?php if ($facts !== []): ?>
        <?php ob_start(); ?>
        <dl class="grid content-start gap-4 sm:grid-cols-2 xl:grid-cols-3">
          <?php foreach ($facts as $fact): ?>
            <div class="flex items-start gap-3">
              <span class="mt-0.5 text-henge-green">
                <?php printf(
                    '%s',
                    hengegroup_theme_render_icon($fact['icon']), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                ); ?>
              </span>
              <div>
                <dt class="text-sm text-muted-foreground"><?php echo esc_html(
                    $fact['label'],
                ); ?></dt>
                <dd class="text-base font-semibold text-grey-dark">
                  <?php if (!empty($fact['datetime'])): ?>
                    <time datetime="<?php echo esc_attr($fact['datetime']); ?>"><?php echo esc_html(
    $fact['value'],
); ?></time>
                  <?php else: ?>
                    <?php echo esc_html($fact['value']); ?>
                  <?php endif; ?>
                </dd>
              </div>
            </div>
          <?php endforeach; ?>
        </dl>
        <?php
        $facts_markup = (string) ob_get_clean();
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo hengegroup_theme_render_card([
            'content' => $facts_markup,
            'class' => 'col-span-12 self-start lg:col-span-8',
        ]);
        ?>
      <?php endif; ?>

      <?php $contact_card = hengegroup_theme_render_contact_card($job['contact'], [
          'variant' => 'filled',
      ]); ?>
      <?php if ($contact_card !== ''): ?>
        <aside class="col-span-12 self-start lg:col-span-4 lg:col-start-9 lg:row-span-2 lg:row-start-1" aria-label="<?php esc_attr_e(
            'Ansprechpartner',
            'hengegroup-theme',
        ); ?>">
          <?php printf(
              '%s',
              $contact_card, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          ); ?>
        </aside>
      <?php endif; ?>

      <div class="col-span-12 text-base leading-relaxed text-grey-dark lg:col-span-8 rich-text">
        <?php the_content(); ?>

      </div>
    </div>
  </article>

  <section id="bewerbung" class="bg-neutral-200 section-spacing" aria-labelledby="bewerbung-titel">
      <div class="wrapper-small">
        <div class="col-span-12 flex flex-col gap-10">
          <?php $application_header = hengegroup_theme_render_section_header([
              'title' => __('Bewerbungsformular', 'hengegroup-theme'),
              'title_id' => 'bewerbung-titel',
              'description' => sprintf(
                  /* translators: %s: job title. */
                  __('Bewerbung für: %s', 'hengegroup-theme'),
                  get_the_title(),
              ),
          ]); ?>
          <?php printf(
              '%s',
              $application_header, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          ); ?>
          <?php get_template_part('template-parts/components/job-application-form', null, [
              'job' => $job,
          ]); ?>
          <?php if ($application_email !== ''): ?>
          <p class="text-sm text-grey-dark/70">
            <?php printf(
                /* translators: %s: application e-mail address (link). */
                esc_html__('Lieber per E-Mail? Schick deine Unterlagen an %s.', 'hengegroup-theme'),
                '<a class="text-grey-dark underline underline-offset-4" href="' .
                    esc_url(
                        'mailto:' .
                            $application_email .
                            '?subject=' .
                            rawurlencode(
                                sprintf(
                                    /* translators: 1: job title, 2: reference number. */
                                    __('Bewerbung: %1$s (%2$s)', 'hengegroup-theme'),
                                    get_the_title(),
                                    $job['reference'],
                                ),
                            ),
                    ) .
                    '">' .
                    esc_html(antispambot($application_email)) .
                    '</a>',
            ); ?>
          </p>
          <?php endif; ?>
        </div>
      </div>
    </section>

<?php
endwhile;
get_footer();
