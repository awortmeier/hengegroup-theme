<?php declare(strict_types=1);

/**
 * Template Name: Component Showcase - Toast
 *
 * Dev-only page template: renders template-parts/base/toast.php (the toaster viewport, once, as
 * documented in that file's own header) plus a set of trigger buttons that call the imperative
 * `toast()` API from assets/js/template-parts/base/toast.js, for manual visual/functional review
 * during Phase 2 styling work -- not meant for production content or navigation. Analog zu
 * page-component-showcase-tabs.php/-separator.php.
 *
 * Usage: create a WP Page, assign this template via the block editor's Page Attributes panel
 * ("Component Showcase - Toast"), and tick "noindex" in that page's own SEO metabox
 * (inc/setup/theme-seo-admin.php) so it never gets indexed -- no noindex hardcoded here, reuses the
 * existing per-page mechanism instead of a second one.
 *
 * Unlike every other showcase page so far, this one can't lay its examples out inline in the page
 * flow: a toast IS a fixed-position overlay by design (that's the whole point of a toaster, see
 * toast.php's own header), so there is no non-fixed "preview" rendering to fall back to without
 * duplicating toast.php's own markup/classes outside their source of truth. Every example here is
 * therefore triggered on demand via a button, exactly how sonner's own live docs demo their toasts
 * too -- click a button, watch the real, fixed-position toaster in the corner, not a static
 * side-by-side grid of every variant at once. `duration` is left at the toaster's own real default
 * (4000ms) on purpose so the auto-dismiss life bar is actually visible in this demo, unlike a
 * `duration: 0` "hold it open for review" example would show.
 *
 * Design reference: https://claude.ai/code/artifact/4955236e-3bbd-4520-913c-795cfb92c5c6
 */

get_header();
?>

<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="mb-2 text-3xl font-semibold">Toast — Component Showcase</h1>
    <p class="mb-12 text-neutral-500">
        <code>template-parts/base/toast.php</code> + <code>toast.js</code>. Dev-only, nicht für
        Produktivinhalte verlinken.
    </p>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Varianten (<code>type</code>)</h2>
        <p class="mb-4 text-sm text-neutral-500">
            Nur <code>error</code> tintet die ganze Karte -- jeder andere Typ faerbt nur sein Icon
            (und die Laufleiste unten am Kartenrand, die den <code>duration</code>-Countdown zeigt).
        </p>
        <div class="flex flex-wrap gap-3">
            <?php
            $variant_triggers = [
                'default' => [
                    'henge-grey',
                    'Entwurf gespeichert',
                    'Zuletzt geändert vor wenigen Sekunden.',
                ],
                'success' => [
                    'henge-green',
                    'Anfrage gesendet',
                    'Wir melden uns innerhalb von zwei Werktagen mit einem Angebot.',
                ],
                'info' => [
                    'henge-blue',
                    'Datenblatt aktualisiert',
                    'Version 2026-08 ersetzt die vorherige Ausgabe.',
                ],
                'warning' => [
                    'outline',
                    'Lagerbestand niedrig',
                    'Nur noch 3 Paletten Quarzsand H-40 verfügbar.',
                ],
                'error' => [
                    'destructive',
                    'Upload fehlgeschlagen',
                    'Die Datei überschreitet 25 MB. Bitte erneut versuchen.',
                ],
                'loading' => ['grey-light', 'Wird hochgeladen …', 'Datenblatt.pdf, 4,1 MB.'],
            ];

            foreach ($variant_triggers as $type => [$button_variant, $message, $description]):
                $payload = wp_json_encode(['type' => $type, 'description' => $description]); ?>
                <?php get_template_part('template-parts/base/button', null, [
                    'config' => [
                        'variant' => $button_variant,
                        'text' => ucfirst($type),
                        'attributes' => [
                            'data-toast-trigger' => $message,
                            'data-toast-payload' => (string) $payload,
                        ],
                    ],
                ]); ?>
            <?php
            endforeach;
            ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Aktion &amp; Abbrechen</h2>
        <p class="mb-4 text-sm text-neutral-500">
            <code>action</code>/<code>cancel</code> -- beide dismissen den Toast, <code>action</code>
            zusätzlich mit einer eigenen (hier nur geloggten) Callback-Aktion.
        </p>
        <div class="flex flex-wrap gap-3">
            <?php get_template_part('template-parts/base/button', null, [
                'config' => [
                    'variant' => 'outline',
                    'text' => 'Mit Aktion',
                    'attributes' => [
                        'data-toast-trigger' => 'Datenblatt gespeichert',
                        'data-toast-payload' => (string) wp_json_encode([
                            'type' => 'default',
                            'action' => ['label' => 'Rückgängig'],
                        ]),
                    ],
                ],
            ]); ?>
            <?php get_template_part('template-parts/base/button', null, [
                'config' => [
                    'variant' => 'outline',
                    'text' => 'Mit Abbrechen',
                    'attributes' => [
                        'data-toast-trigger' => 'Termin vorgemerkt',
                        'data-toast-payload' => (string) wp_json_encode([
                            'type' => 'success',
                            'description' => 'Werksbesichtigung Nord, 14. Oktober, 10:00 Uhr.',
                            'cancel' => ['label' => 'Abbrechen'],
                        ]),
                    ],
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold"><code>toast.promise()</code></h2>
        <p class="mb-4 text-sm text-neutral-500">
            Zeigt sofort einen "loading"-Toast (keine Laufleiste, kein Auto-Dismiss), baut denselben
            Toast nach ~1,5s zu "success" oder "error" um -- keine zweite Karte, kein
            Stacking-Sprung.
        </p>
        <div class="flex flex-wrap gap-3">
            <?php get_template_part('template-parts/base/button', null, [
                'config' => [
                    'variant' => 'henge-green',
                    'text' => 'Erfolgreiches Promise',
                    'attributes' => ['data-toast-promise' => 'success'],
                ],
            ]); ?>
            <?php get_template_part('template-parts/base/button', null, [
                'config' => [
                    'variant' => 'destructive',
                    'text' => 'Fehlgeschlagenes Promise',
                    'attributes' => ['data-toast-promise' => 'error'],
                ],
            ]); ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="mb-6 text-xl font-semibold">Position &amp; Alle schließen</h2>
        <div class="flex flex-wrap items-center gap-3">
            <label class="flex items-center gap-2 text-sm text-neutral-600">
                Position
                <select
                    id="toast-showcase-position"
                    class="rounded-md border border-neutral-200 px-2 py-1 text-sm"
                >
                    <option value="bottom-right" selected>bottom-right</option>
                    <option value="bottom-center">bottom-center</option>
                    <option value="bottom-left">bottom-left</option>
                    <option value="top-right">top-right</option>
                    <option value="top-center">top-center</option>
                    <option value="top-left">top-left</option>
                </select>
            </label>
            <?php get_template_part('template-parts/base/button', null, [
                'config' => [
                    'variant' => 'ghost',
                    'text' => 'Alle schließen',
                    'attributes' => ['data-toast-clear' => 'true'],
                ],
            ]); ?>
        </div>
    </section>
</div>

<?php // The toaster viewport itself -- rendered ONCE per page, see toast.php's own header.

// `close_button: true` so every trigger above shows the close button styling too.
get_template_part('template-parts/base/toast', null, [
    'config' => [
        'close_button' => true,
    ],
]); ?>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        var api = window.hengegroupTheme && window.hengegroupTheme.toast;

        if (!api) {
            return;
        }

        document.querySelectorAll("[data-toast-trigger]").forEach(function (button) {
            button.addEventListener("click", function () {
                var message = button.getAttribute("data-toast-trigger");
                var payload = JSON.parse(button.getAttribute("data-toast-payload") || "{}");
                var type = payload.type || "default";
                delete payload.type;

                (api[type] || api)(message, payload);
            });
        });

        document.querySelectorAll("[data-toast-promise]").forEach(function (button) {
            button.addEventListener("click", function () {
                var shouldSucceed = button.getAttribute("data-toast-promise") === "success";
                var promise = new Promise(function (resolve, reject) {
                    window.setTimeout(function () {
                        shouldSucceed ? resolve() : reject();
                    }, 1500);
                });

                api.promise(promise, {
                    loading: "Wird verarbeitet …",
                    success: "Erfolgreich abgeschlossen.",
                    error: "Fehlgeschlagen.",
                });
            });
        });

        var clearButton = document.querySelector("[data-toast-clear]");

        if (clearButton) {
            clearButton.addEventListener("click", function () {
                api.dismiss();
            });
        }

        var positionSelect = document.getElementById("toast-showcase-position");
        var viewport = document.querySelector('[data-slot="toaster"]');

        if (positionSelect && viewport) {
            positionSelect.addEventListener("change", function () {
                viewport.setAttribute("data-position", positionSelect.value);
            });
        }
    });
</script>

<?php get_footer(); ?>
