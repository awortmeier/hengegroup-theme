<?php

declare(strict_types=1);

// Einzelseite einer Stelle unter /karriere/<slug>/ (Design "Stellenangebot einzelseite"):
// Firmen-Pill, H1, Standortzeile, Faktenleiste, Einleitung (Gutenberg-Inhalt), die drei Listen,
// Bewerbungs-Abschnitt, Ansprechpartner. Alle Daten aus hengegroup_theme_get_job_data()
// (inc/template-parts/careers.php) -- derselben Quelle wie das JobPosting-JSON-LD
// (inc/setup/theme-careers-seo.php), damit sichtbarer Inhalt und strukturierte Daten
// uebereinstimmen.
//
// Faktenleiste (Standort/Anstellungsart/Arbeitsmodell/Gehalt/Eintritt/Veroeffentlicht) ist eine
// Ergaenzung zum Design: Gehalt/Anstellungsart/Ablauf stehen im JSON-LD und muessen laut Google
// auch sichtbar sein; als echtes <dl> sind sie ausserdem fuer KI-Suchen sauber als Fakten lesbar.
//
// Bewerbung laeuft vorerst per E-Mail an den Ansprechpartner -- das Bewerbungsformular aus dem
// Design ist ein eigener, spaeterer Schritt (Versand, Datei-Upload, DSGVO), siehe docs/to-do.md.
//
// Der Header ist `fixed` und liegt ueber dem Inhalt (siehe header.php) -- daher der groessere
// obere Abstand des ersten Abschnitts.

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

    // Literale Icon-Konfigurationen (statt aus Variablen zusammengesetzt), damit
    // scripts/find-lucide-icons.php sie beim Build findet und die SVGs synchronisiert.
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
  <article <?php post_class('bg-grey-light'); ?> data-slot="job-posting">
    <header class="wrapper-small pt-28 pb-10 sm:pt-32 lg:pt-36">
      <div class="col-span-12">
        <?php if (hengegroup_theme_is_job_expired((int) get_the_ID())): ?>
          <p class="mb-6 rounded-xl border border-henge-blue/30 bg-white px-5 py-4 text-base text-grey-dark" role="status">
            <?php esc_html_e(
                'Nur für Redakteure sichtbar: Diese Stelle ist abgelaufen bzw. besetzt. Besucher werden auf die Karriereseite weitergeleitet, Google erhält keine Stellendaten mehr.',
                'hengegroup-theme',
            ); ?>
          </p>
        <?php endif; ?>

        <?php if ($job['company'] !== null): ?>
          <div class="mb-4.5">
            <?php $company_badge = hengegroup_theme_render_job_company_badge(
                $job['company'],
                'px-3.5 tracking-[1.5px]',
            ); ?>
            <?php printf(
                '%s',
                $company_badge, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ); ?>
          </div>
        <?php endif; ?>

        <?php get_template_part('template-parts/base/typography', null, [
            'config' => [
                'variant' => 'headline-sm',
                'tag' => 'h1',
                'text' => get_the_title(),
                'class' => 'mb-3.5 text-balance',
            ],
        ]); ?>

        <?php if ($location_label !== ''): ?>
          <p class="flex items-center gap-2 text-base text-grey-dark/60">
            <?php $location_icon = hengegroup_theme_render_icon([
                'name' => 'map-pin',
                'set' => 'lucide',
                'class' => 'size-4 shrink-0',
            ]); ?>
            <?php printf(
                '%s',
                $location_icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ); ?>
            <?php echo esc_html($location_label); ?>
          </p>
        <?php endif; ?>
      </div>
    </header>

    <div class="wrapper-small gap-y-10 pb-20">
      <?php if ($facts !== []): ?>
        <dl class="col-span-12 grid gap-4 rounded-2xl bg-white p-6 shadow-[0_1px_3px_rgba(0,0,0,0.06)] sm:grid-cols-2 lg:grid-cols-3">
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
      <?php endif; ?>

      <div class="col-span-12 text-base leading-relaxed text-grey-dark [&_a]:text-henge-green [&_a]:underline [&_h2]:mt-8 [&_h2]:mb-3 [&_h2]:text-2xl [&_h2]:font-semibold [&_h3]:mt-6 [&_h3]:mb-3 [&_h3]:text-xl [&_h3]:font-bold [&_li]:leading-[1.7] [&_ol]:mb-3.5 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mb-3.5 [&_ul]:mb-3.5 [&_ul]:list-disc [&_ul]:pl-5">
        <?php the_content(); ?>

        <?php foreach (hengegroup_theme_get_job_list_sections($job) as $section): ?>
          <section class="my-6">
            <h2 class="mb-3 text-xl font-bold"><?php echo esc_html($section['heading']); ?></h2>
            <ul>
              <?php foreach ($section['items'] as $item): ?>
                <li><?php echo esc_html($item); ?></li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endforeach; ?>
      </div>
    </div>
  </article>

  <?php if ($application_email !== ''): ?>
    <section id="bewerbung" class="bg-grey-dark py-16 md:py-24" aria-labelledby="bewerbung-titel">
      <div class="wrapper-small">
        <div class="col-span-12">
          <?php get_template_part('template-parts/base/typography', null, [
              'config' => [
                  'variant' => 'headline-sm',
                  'tag' => 'h2',
                  'text' => __('Jetzt bewerben', 'hengegroup-theme'),
                  'color' => 'light',
                  'class' => 'mb-3',
                  'attributes' => ['id' => 'bewerbung-titel'],
              ],
          ]); ?>
          <?php get_template_part('template-parts/base/typography', null, [
              'config' => [
                  'variant' => 'body-sm',
                  'text' => sprintf(
                      /* translators: %s: job title. */
                      __('Bewerbung für: %s', 'hengegroup-theme'),
                      get_the_title(),
                  ),
                  'color' => 'light',
                  'class' => 'mb-6 opacity-70',
              ],
          ]); ?>
          <?php get_template_part('template-parts/base/typography', null, [
              'config' => [
                  'variant' => 'body-base',
                  'text' => sprintf(
                      /* translators: %s: application e-mail address. */
                      __(
                          'Schick uns deinen Lebenslauf und deine Zeugnisse (PDF) per E-Mail an %s – gern mit einem kurzen Satz, ab wann du starten kannst.',
                          'hengegroup-theme',
                      ),
                      antispambot($application_email),
                  ),
                  'color' => 'light',
                  'class' => 'mb-8 max-w-2xl',
              ],
          ]); ?>
          <?php get_template_part('template-parts/base/button', null, [
              'config' => [
                  'text' => __('Bewerbung per E-Mail senden', 'hengegroup-theme'),
                  'href' =>
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
                  'variant' => 'henge-green',
                  'size' => 'lg',
                  'icon' => ['name' => 'mail', 'set' => 'lucide'],
              ],
          ]); ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php $contact_card = hengegroup_theme_render_job_contact_card($job['contact']); ?>
  <?php if ($contact_card !== ''): ?>
    <section class="py-16 md:py-20" aria-labelledby="ansprechpartner-titel">
      <div class="wrapper-small">
        <div class="col-span-12 sm:col-span-8 lg:col-span-6">
          <?php get_template_part('template-parts/base/typography', null, [
              'config' => [
                  'variant' => 'headline-xs',
                  'tag' => 'h2',
                  'text' => __('Ansprechpartner', 'hengegroup-theme'),
                  'class' => 'mb-5',
                  'attributes' => ['id' => 'ansprechpartner-titel'],
              ],
          ]); ?>
          <?php printf(
              '%s',
              $contact_card, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          ); ?>
        </div>
      </div>
    </section>
  <?php endif; ?>
<?php
endwhile;
get_footer();
