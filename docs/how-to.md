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

### Einen neuen Gutenberg-Block anlegen

Konvention/Begruendung siehe `docs/entscheidungen.md` "Phase-3-Block-Architektur" (am Beispiel von
`hengegroup-theme/buehne`, `template-parts/blocks/buehne/`). Fuer einen weiteren Block:

1. **Ordner anlegen**: `template-parts/blocks/<name>/block.json` + `render.php` (gleiche
   Ordner-Konvention wie `template-parts/base/<name>/`). `block.json`s `"category"` ist `"henge"`
   (eigene Kategorie, siehe `docs/entscheidungen.md` "Eigene Block-Kategorie 'Henge' statt
   Core-Kategorie 'theme'"), nicht die Core-Kategorie `"theme"`. `"render"` zeigt auf
   `"file:./render.php"` — WordPress injiziert `$attributes`/`$content`/`$block` automatisch, kein
   manueller `render_callback` noetig. `render.php` komponiert ausschliesslich
   `template-parts/base/*`-Aufrufe (`ob_start()`/`get_template_part()`/`ob_get_clean()`, siehe
   `carousel.php`s eigener Kopfkommentar fuer das genaue Muster) — keine neue Markup-/
   Styling-Logik, Tailwind-Klassen direkt im PHP wie in jeder Base-Komponente selbst.
2. **Editor-Script**: `assets/js/blocks/<name>/edit.jsx`, importiert ausschliesslich aus
   `@wordpress/*` (kein eigenes React). Fuer eine WYSIWYG-Vorschau ohne die render.php-Markup-
   Struktur ein zweites Mal in JS nachzubauen: `ServerSideRender` mit `block={metadata.name}`
   verwenden (`metadata` = direkter JSON-Import des eigenen `block.json`).
3. **Vite-Build**: eine EIGENE Config-Datei `vite.config.editor-<name>.js` (Vite 8s `vite build`-CLI
   akzeptiert pro Lauf nur eine Config, kein Array — deshalb ein File pro Block statt eines
   gemeinsamen Multi-Entry-Builds, siehe `vite.config.editor.factory.js`s Kopfkommentar fuer die
   genaue Begruendung), die aber nur noch `createEditorBlockConfig()` aus
   `vite.config.editor.factory.js` mit `entry`/`fileName`/`name` fuer den neuen Block aufruft (siehe
   `vite.config.editor-ueberschrift-text.js` als Vorbild) — die eigentliche `build.lib`/
   `esbuild.jsx*`/`rollupOptions.external`+`output.globals`-Konfiguration lebt zentral in der
   Factory, nicht mehr dupliziert pro Block-Config. `package.json`s `build:assets`-Skript um einen
   weiteren `&& vite build --config vite.config.editor-<name>.js` ergaenzen.
4. **PHP-Registrierung**: in `inc/setup/theme-blocks.php`s `hengegroup_theme_register_blocks()`
   einen weiteren `hengegroup_theme_register_theme_block('<name>', 'hengegroup-theme-<name>-editor',
'js/blocks/<name>-edit.js')`-Aufruf ergaenzen (Vorbild: die `ueberschrift-text`-Zeile) — der
   gemeinsame Helper registriert dabei fuer jeden Block gleichermassen zuerst den
   Editor-Script-Handle per `wp_register_script()` (Dependencies: `wp-blocks`/`wp-element`/
   `wp-block-editor`/`wp-components`/`wp-i18n`, plus `wp-server-side-render` bei
   `ServerSideRender`-Nutzung) ueber `hengegroup_theme_get_vite_asset_path()`/`_uri()`, dann
   `register_block_type()` fuer den Block-Ordner.
5. **Kein `build.ps1`/`build.sh`-Aenderungsbedarf** — `template-parts/`/`inc/` werden bereits
   vollstaendig nach `dist/` kopiert, neue Dateien darin sind automatisch erfasst.

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

### SVG-Upload-Berechtigung anpassen

`inc/setup/theme-svg-support.php` erlaubt SVG-Uploads standardmaessig nur fuer Nutzer mit
`manage_options` (siehe `docs/entscheidungen.md` "SVG-Upload-Support" fuer die Begruendung, inkl.
warum das serverseitige Sanitizing ueber `enshrined/svg-sanitize` davon unabhaengig IMMER laeuft).
Fuer eine andere Capability (z. B. `edit_others_posts`, wenn auch Redakteure SVGs pflegen sollen
sollen):

```php
add_filter("hengegroup_theme_svg_upload_capability", function (string $capability): string {
    return "edit_others_posts";
});
```

- Der Filter steuert sowohl, ob `svg` im `upload_mimes`-Dialog ueberhaupt als Dateityp erscheint,
  als auch die zweite Pruefung in `wp_handle_upload_prefilter` (defense in depth) — eine Aenderung
  reicht.
- Das Sanitizing selbst (`enshrined\svgSanitize\Sanitizer`) ist NICHT Teil dieses Filters und laeuft
  fuer jede erlaubte Rolle gleich streng — hier geht es nur darum, WER ueberhaupt hochladen darf.
