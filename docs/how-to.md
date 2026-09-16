# HowTos

Anleitungen, wie man einen bestehenden Erweiterungspunkt (Filter/Hook/Config-Key) nutzt.

Abgrenzung zu benachbarten Dokumenten:

- `docs/entscheidungen.md` sammelt bewusste Entscheidungen (das "Warum"), waehrend diese Datei das
  "Wie" fuer bestehende Erweiterungspunkte festhaelt.
- `docs/to-do.md` sammelt **offene** Punkte (noch nicht entschieden/gebaut).
- Der Dateikopf-Kommentar der betroffenen Datei bleibt die Quelle direkt am Code (siehe
  `docs/neue-komponente-erstellen.md` Regel 4/9). Ein Eintrag hier ist ein zusaetzlicher, thematisch
  gebuendelter Verweis, kein Doppel-Text mit abweichendem Inhalt — kurz halten, auf den
  Kopfkommentar verweisen statt ihn zu duplizieren.

Siehe `CLAUDE.md` Regel 12 fuer die Pflicht, wann ein Eintrag hier angelegt wird.

---

### Ein weiteres JSON-LD-Schema ergaenzen (z. B. Product/JobPosting)

`inc/setup/theme-seo-output.php` rendert immer das site-weite Organization-Schema und bietet dafuer
den Filter `hengegroup_theme_seo_structured_data(array $schemas, int $post_id)` als Erweiterungspunkt:

```php
add_filter(
    "hengegroup_theme_seo_structured_data",
    function (array $schemas, int $post_id): array {
        if (get_post_type($post_id) === "product") {
            $schemas[] = [
                "@context" => "https://schema.org",
                "@type" => "Product",
                "name" => get_the_title($post_id),
                // ...
            ];
        }
        return $schemas;
    },
    10,
    2,
);
```

- Jeder Eintrag im zurueckgegebenen Array wird als eigenes `<script type="application/ld+json">`
  gerendert (einfacher fuer additive Callbacks als ein gemeinsames `@graph`-Merge).
- `$post_id` ist `0` ausserhalb eines Singular-/"Page for posts"-Kontexts (Archive, Suche, 404) —
  siehe `hengegroup_theme_seo_current_post_id()` im selben File.
- Ein bestehender Eintrag laesst sich per `@type`-Abgleich im uebergebenen `$schemas`-Array auch
  ersetzen/entfernen, nicht nur ergaenzen.
- Details/Contract siehe Docblock von `hengegroup_theme_get_seo_structured_data()` im selben File.

### Ein weiteres Icon ergaenzen

`template-parts/base/icon.php` (bzw. der Helper `hengegroup_theme_render_icon()`) rendert nur
Icons, die als statische SVG-Datei unter `assets/images/icons/<set>/` vorliegen — welche Dateien
dort liegen, haengt vom `set`-Wert ab:

- **Lucide** (`set => 'lucide'`, z. B. `['name' => 'arrow-right', 'set' => 'lucide']`): Datei kommt
  aus `node_modules/lucide-static`. Icon-Name im Code referenzieren (als String-Literal in der
  `icon.php`-Config), dann `pnpm icons:lucide` ausfuehren — synct automatisch nur die tatsaechlich
  referenzierten Icons (`scripts/find-lucide-icons.php` scannt dafuer den gesamten Theme-PHP-Code)
  und raeumt nicht mehr benoetigte Dateien mit auf. Laeuft ausserdem automatisch bei jedem
  `pnpm build`.
- **Tabler** (`set => 'tabler/outline'` bzw. `'tabler/filled'`): analog ueber `pnpm icons:tabler`,
  Quelle `node_modules/@tabler/icons`, Scanner `scripts/find-tabler-icons.php`.
- **Name wird erst zur Laufzeit zusammengesetzt** (z. B. `['name' => $icon_name, ...]` mit
  `$icon_name` aus einer PHP-Variable/Expression) — der statische Scanner sieht dann kein
  String-Literal und findet das Icon nicht. In diesem Fall den Icon-Namen zusaetzlich in
  `scripts/lucide-icons.json` bzw. `scripts/tabler-icons.json` eintragen (einfaches JSON-Array,
  bei Tabler `{"name": "...", "variant": "outline"|"filled"}`-Objekte) — beide Sync-Skripte lesen
  diese Datei zusaetzlich zum Scan-Ergebnis aus.
- **Icon jenseits Lucide/Tabler** (Marken-/Custom-SVG): kein Sync-Skript noetig, einfach manuell
  unter `assets/images/icons/<eigener-set-name>/` ablegen und mit `set => '<eigener-set-name>'`
  referenzieren — `icon.php` liest jede vorhandene Datei unter `assets/images/icons/`, unabhaengig
  davon, ob ein Sync-Skript sie dorthin kopiert hat.

Details zur Config (`name`/`set`/`class`/`decorative`/`title`/`attributes`/`data_attributes`)
stehen im Kopfkommentar von `template-parts/base/icon.php`.

### Ein weiteres, Seiten-spezifisches SEO-Feld nutzen

Die SEO-Ausgabe (`inc/setup/theme-seo-output.php`) folgt fuer Titel/Beschreibung/Social-Bild/
Robots ueberall derselben Fallback-Kette: per-Seite "SEO"-Metabox-Wert (`theme-seo-admin.php`) ->
site-weiter Settings > SEO Standard (`hengegroup_theme_get_seo_options()`) -> ein sinnvoller berechneter
Default (Post-Excerpt, Featured Image, Permalink, ...) -> ganz weggelassen. Ein neues Feld reiht
sich in dieselbe Kette ein, statt eine eigene Logik zu erfinden — siehe bestehende Resolver-
Funktionen in `theme-seo-output.php` als Vorlage. Weitere Post-Types fuer die Metabox kommen ueber
den `hengegroup_theme_seo_post_types`-Filter dazu, nicht durch Code-Aenderung an `theme-seo-admin.php`.
