# HowTos

## Neue WordPress-Instanz

In `wp-config.php` setzen, sonst meldet der Website-Zustand ein fehlendes Standard-Theme:

```php
define("WP_DEFAULT_THEME", "hengegroup");
```

## Produktbereich pflegen

1. Produkte > Kategorien: je Sektion der Uebersicht eine Kategorie (Kicker, Farbe, Ueberschrift,
   optional Ansprechpartner).
2. Produkte > Einstellungen: Uebersichtsseite und Standard-Ansprechpartner.
3. Seite /produkte/: Buehne, je Kategorie ein Block "Produktkategorie", am Ende "Kontakt".
4. Produkt: Beschreibung, Kurzbeschreibung, Beitragsbild, Badge (Text + Farbe), Kategorie,
   Anwendungen; Tab "Technische Daten" (Analyse, Downloads, Recycling); Attribut "Koernung";
   "Verwandte Produkte" fuer bis zu 4 feste Empfehlungen.
5. Anwendungen: erst Gruppe (ohne Eltern), dann Anwendung mit Gruppe als Eltern. Zuordnung am Produkt
   oder per Quick Edit. Neues Icon: `hengegroup_theme_get_anwendung_icons()` ergaenzen, dann
   `pnpm icons:lucide`.
6. Eingaenge (Produktanfragen/Bewerbungen): Status in der Seitenleiste setzen, damit der Zaehler nur
   Neues zeigt.

Weitere Badge-Farbe: `hengegroup_theme_get_badge_variants()` erweitern.

## Stellenangebote pflegen

1. Stellenangebote > Unternehmen/Standorte anlegen, unter Einstellungen Karriereseite und
   Standard-Ansprechpartner waehlen.
2. Neue Stelle: Einleitung, Listen-Bloecke ("Wir bieten dir" leer = Standard-Benefits),
   "Stellendetails"-Box. Der gelbe Hinweis nennt fehlende Google-Jobs-Angaben.
3. Stelle beenden: "Gueltig bis" oder "besetzt" setzen, nicht loeschen (sonst keine 301).
4. Neues JobPosting-Feld: in `hengegroup_theme_get_job_data()` lesen, in
   `hengegroup_theme_build_job_posting_schema()` ausgeben, in `single-stellenangebote.php` anzeigen,
   Test in `tests/Unit/CareersTest.php`.

## Footer

Kontaktdaten und Social-URLs unter Einstellungen > Footer, Links ueber die Menue-Position
"Footermenü". Weiteres Netzwerk: Eintrag in `hengegroup_theme_get_footer_social_networks()` und Key
in `hengegroup_theme_get_footer_options()`, dann `pnpm icons:tabler`.

## Neues Formular

1. Komponente unter `template-parts/components/`: Felder per `hengegroup_theme_render_form_field()`
   in einen Puffer rendern und als `content` an `components/request-form` geben (Banner, Honeypot,
   Datenschutz, Pflichtfeld-Hinweis, Absenden kommen von dort).
2. Handler per `admin_post(_nopriv)_<action>`: Nonce pruefen, dann `hengegroup_theme_is_form_bot()`,
   bei Fehlern `hengegroup_theme_store_form_state()` und Redirect; Zustand fuers Template per
   `hengegroup_theme_read_form_state()`.
3. Als Eingang: Post-Type und Felder in `inc/setup/theme-requests.php` ergaenzen.

## Neuer Gutenberg-Block

1. `template-parts/blocks/<name>/block.json` (`"category": "henge"`, `"render": "file:./render.php"`)
   und `render.php`, das nur Base-/Komponenten-Templates komponiert.
2. Editor: `assets/js/blocks/<name>/edit.jsx`, nur `@wordpress/*`-Imports und
   `assets/js/blocks/shared.jsx` (RichText-Werte, Ueberschrift-/Akzent-Auswahl, Bildwahl,
   Hinzufuegen-Kachel). Vorschau per `ServerSideRender`, ausser der Block braucht `RichText` im Canvas.
3. `vite.config.editor-<name>.js` per `createEditorBlockConfig()` und in `package.json`
   `build:assets` eintragen.
4. In `hengegroup_theme_register_blocks()` (`inc/setup/theme-blocks.php`) registrieren.

## Icons

Lucide-/Tabler-Namen als String-Literal im PHP verwenden, dann `pnpm icons:lucide` bzw.
`pnpm icons:tabler` (laeuft auch bei `pnpm build`). Zur Laufzeit zusammengesetzte Namen findet der
Scanner nicht: zusaetzlich in `scripts/lucide-icons.json`/`tabler-icons.json` eintragen. Eigene SVGs
direkt unter `assets/images/icons/<set>/` ablegen.

## Schriften

Pro Familie ein Ordner unter `assets/fonts/` (TTF/OTF-Quelle + WOFF2 via
`npx ttf2woff2 < in.ttf > out.woff2`), `@font-face` in `assets/css/fonts.css`.

## SEO

- Weiteres JSON-LD: Filter `hengegroup_theme_seo_structured_data(array $schemas, int $post_id)`; jeder
  Eintrag wird ein eigenes `<script type="application/ld+json">`. `$post_id` ist ausserhalb von
  Einzelseiten `0`.
- Automatische Meta-Description: Filter `hengegroup_theme_seo_description` (Beispiel:
  `theme-careers-seo.php`). Eigenen Wert der SEO-Box nicht ueberschreiben.
- SEO-Metabox fuer weitere Post-Types: Filter `hengegroup_theme_seo_post_types`.

## Filter

```php
// Ablageort der Bewerbungsunterlagen (nicht per URL erreichbar waehlen; Dateien werden nicht verschoben)
add_filter("hengegroup_theme_application_storage_dir", fn() => "/pfad/ausserhalb/webroot");

// Wer SVGs hochladen darf (Sanitizing laeuft immer)
add_filter("hengegroup_theme_svg_upload_capability", fn() => "edit_others_posts");
```
