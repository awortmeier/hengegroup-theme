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
den Filter `base_theme_seo_structured_data(array $schemas, int $post_id)` als Erweiterungspunkt:

```php
add_filter(
    "base_theme_seo_structured_data",
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
  siehe `base_theme_seo_current_post_id()` im selben File.
- Ein bestehender Eintrag laesst sich per `@type`-Abgleich im uebergebenen `$schemas`-Array auch
  ersetzen/entfernen, nicht nur ergaenzen.
- Details/Contract siehe Docblock von `base_theme_get_seo_structured_data()` im selben File.

### Ein weiteres, Seiten-spezifisches SEO-Feld nutzen

Die SEO-Ausgabe (`inc/setup/theme-seo-output.php`) folgt fuer Titel/Beschreibung/Social-Bild/
Robots ueberall derselben Fallback-Kette: per-Seite "SEO"-Metabox-Wert (`theme-seo-admin.php`) ->
site-weiter Settings > SEO Standard (`base_theme_get_seo_options()`) -> ein sinnvoller berechneter
Default (Post-Excerpt, Featured Image, Permalink, ...) -> ganz weggelassen. Ein neues Feld reiht
sich in dieselbe Kette ein, statt eine eigene Logik zu erfinden — siehe bestehende Resolver-
Funktionen in `theme-seo-output.php` als Vorlage. Weitere Post-Types fuer die Metabox kommen ueber
den `base_theme_seo_post_types`-Filter dazu, nicht durch Code-Aenderung an `theme-seo-admin.php`.
