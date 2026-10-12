# Entscheidungen

Nur aktuell gueltige Entscheidungen, die nicht aus dem Code ersichtlich sind. Historie: Git.

## Architektur & Tooling

- **Nur macOS** als Entwicklungs-/Deploy-Plattform; `scripts/*.sh` bleiben Bash-3.2-kompatibel
  (laeuft damit auch in CI unter Linux).
- **Brain Monkey statt wp-env** fuer PHPUnit: die getesteten Helper brauchen nur wenige
  WP-Funktionen. Alles mit echtem Template-Rendering bleibt ausserhalb der Unit-Suite.
- **Vitest + jsdom** fuer JS-Module. jsdom kennt kein Layout (`scrollIntoView` u. ae. in Tests
  stubben).
- **PHPUnit `^11.5`**: letzte Linie mit PHP 8.2-Support (`composer.json` verspricht `>=8.2`).
- **Dependency-Audits** laufen in CI nur als Report, kein Gate: ein Advisory in einer transitiven
  Dev-Dependency erzwingt kein sofortiges Update.
- **Kein ACF**: eigene Metaboxen/Term-Meta statt Plugin-Abhaengigkeit.
- **Gutenberg-Bloecke nativ** (`block.json` + `render.php`), Editor-Script per Vite gegen die
  `wp.*`-Globals statt `@wordpress/scripts`. Eine Vite-Config pro Block, weil `vite build` nur eine
  Config je Lauf nimmt (`vite.config.editor.factory.js`).
- **`.wrapper`** (12-Spalten-Grid, `app.css`) ist eine CSS-Klasse statt Template-Part: ein
  Grid-Container hat keine Config-API ueber `col-span-*` hinaus.
- **Komponenten-Showcase und Performance-Tooling** zurueckgestellt (siehe `to-do.md`).
- **Kein `CONTRIBUTING.md`** (Alleinentwickler), **kein RTL-Support**.

## Styling

- **Nur Tailwind** (`CLAUDE.md`). Rohes CSS nur im Backend (wp-admin hat keinen Tailwind-Build),
  z. B. zum Ausblenden von Kernfeldern ohne Filter.
- **Kein `tailwind-merge` in PHP**: `class`-Passthrough ist additiv. Konkurrieren zwei Utilities um
  dieselbe CSS-Property, entscheidet Tailwinds Ausgabereihenfolge, nicht die Reihenfolge im
  String -- dann `!`-Modifier verwenden.
- **Marken-Tokens** in `assets/css/tokens.css`: drei Akzentfarben (henge-green/-blue/-grey),
  Grautoene, zwei Schriften (Outfit, Crillee). Neutraltoene ohne Komponenten-API-Bedarf nutzen
  Tailwinds Skala direkt (z. B. `neutral-500`) statt eigener Tokens.
- **Shadcn-Rollen-Tokens** (`--color-background`, `-muted`, ...) existieren fuer die
  Base-Komponenten; `--color-background` bleibt weiss, der Seitenhintergrund (`grey-light`) kommt vom
  `body`.
- **Eigenes Vokabular statt shadcn**, wo das Design es verlangt: Button-/Badge-Varianten heissen
  nach Markenfarben, `typography.php` nutzt `headline-*`/`body-*` mit fester Skala (keine
  Pixel-Einzelwerte aus dem Design).
- **Karten**: zwei Stufen als Token (`card-flat`/`card-raised`, nicht `card`, weil `shadow-card` mit
  der Farbe `--color-card` kollidieren wuerde). `card.php` ist die Standard-Karte; Kontaktkarte,
  Download-Karte und Produktbox sind bewusst eigene Komponenten.
- **Kein Dark Mode** (`dark:`-Klassen aus shadcn entfernt).
- **`button.php`** setzt `wp-element-button` und `!`-Farben, weil WordPress' globale Link-Farbe sonst
  im Editor gewinnt.

## WordPress & Backend

- **Header-/Footer-Navigation** aus `wp_nav_menu`, Kontaktdaten unter Einstellungen > Footer (nicht
  Customizer, der ist ausgeblendet). Leere Felder blenden Zeilen aus.
- **Sprachumschalter** ist nur Optik: Mehrsprachigkeit kommt per Multisite (kein Hreflang-Plugin),
  erst dann echte Links und `hreflang`.
- **Datei-Editor** (Theme/Plugin) im Backend entfernt: Code nur ueber Repo + Deploy. Kein
  `DISALLOW_FILE_EDIT`, weil das pro Instanz gepflegt werden muesste.
- **Start-CSP bewusst locker** (`theme-hardening.php`): fertige Struktur, die ein Projekt
  verschaerft, ohne bei Uebernahme etwas zu brechen.
- **SVG-Upload** nur mit `manage_options` und immer serverseitig sanitisiert
  (`enshrined/svg-sanitize`).
- **Core-Bloecke ausgeblendet**, die das Theme nicht stylt; `customClassName` global aus
  (`editor-customizations.js`).
- **Eigene Block-Kategorie `henge`**, an erster Stelle im Inserter.
- **Keine `if/else`-Alternativsyntax mit Kommentaren um grosse HTML-Bereiche**:
  `@prettier/plugin-php` verschiebt/vervielfacht Kommentare dort.

## Formulare

- **Eintraege im Backend statt E-Mail** (Post-Types `produktanfrage`, `bewerbung`), Status
  neu/in Bearbeitung/erledigt, Zaehler am Menuepunkt. Sichtbar fuer Redakteure, nie automatisch
  geloescht; beim Loeschen einer Bewerbung gehen ihre Dateien mit.
- **POST + Redirect statt AJAX**: funktioniert ohne JS, kein Doppel-Absenden. Fehler/Eingaben 15 min
  per Transient.
- **Spam-Schutz ohne Captcha**: Nonce, Honeypot, Mindest-Ausfuellzeit. Bots bekommen die
  Erfolgsmeldung.
- **Bewerbungsunterlagen ausserhalb des Web-Roots** (neben dem WP-Stammverzeichnis), weil
  `.htaccess` unter nginx nicht greift. Rueckfall auf uploads/ mit `.htaccess`, wenn
  `open_basedir` das verhindert (Dev-Server). Gespeichert wird nur der Dateiname. Download nur ueber
  admin-post.php mit Rechte-/Nonce-Pruefung.
- **Upload-Limit** folgt `wp_max_upload_size()`, auch wenn das Design 5 MB nennt.

## Produkte (WooCommerce)

- **/produkte/ ist eine Seite aus Bloecken**, keine Shop-Seite (`woocommerce_get_shop_page_id` = -1).
  Produkte unter /produkte/<slug>/ per Filter statt Permalink-Option.
- **Keine Taxonomie-Archive**: jede Taxonomie an `product` leitet per 301 auf die Uebersicht
  (Kategorie auf ihren Anker) und fehlt in der Sitemap. Deshalb kein `archive-product.php` und kein
  `woocommerce.php` im Root (das wuerde jedes WC-Override ueberschreiben).
- **`product_cat` = Sektionen** der Uebersicht, mit Kicker/Farbe/Ueberschrift/Ansprechpartner als
  Term-Meta.
- **Anwendungen = Taxonomie `produkt_anwendung`** (zwei Ebenen: Gruppe > Anwendung), nicht
  oeffentlich, nur Uebersicht /anwendungen/. Zuordnung nur am Produkt; /anwendungen/ verlinkt
  Produkte, nicht umgekehrt.
- **Koernungen als globales Attribut `pa_koernung`**, damit sie spaeter ohne Umbau zu Varianten
  werden. Chemische Analyse als freie Zeilen (reine Info).
- **Badge** = Postmeta-Paar Text + Farbe, keine Taxonomie.
- **Verwandte Produkte** = Up-Sells (im Editor umbenannt), aufgefuellt auf 4. Cross-Sells bleiben fuer
  den Warenkorb frei.
- **Produktbeschreibungen nur im Visual-Editor** mit reduzierter Toolbar. Zusaetzlich
  `tiny_mce_before_init`, weil WooCommerce die `teeny_mce_buttons`-Toolbar ueberschreibt.
- **Ungenutzte WC-Teile aus**: Produkttypen gruppiert/extern, Virtuell/Herunterladbar, Rezensionen-
  und Marken-Verwaltung, Marketing, Menuepunkte WooCommerce/Zahlungen, Versand. Fuer einen spaeteren
  Shop wieder zuschalten. Taxonomie-Untermenues brauchen `&amp;` im Slug fuer
  `remove_submenu_page()`.
- **`content-product.php` feuert `woocommerce_before/after_shop_loop_item` nicht**: WC haengt dort
  eigenes Markup (Link-Wrapper, Warenkorb-Button) an.

## Stellenangebote

- **Stammdaten als Taxonomien** (`stellen_unternehmen`, `stellen_standort`, `stellen_bereich`) mit
  Term-Meta, nicht oeffentlich. Stellenfelder in einer klassischen Metabox.
- **/karriere/ ist eine Seite aus Bloecken**, Stellen unter /karriere/<slug>/; ein `request`-Filter
  biegt Unterseiten der Karriereseite um.
- **Listen (Aufgaben/Profil/Benefits) als Block `stellen-liste`**, damit JSON-LD sie zuordnen kann.
  Leere Benefits-Liste = Standard-Benefits des Unternehmens.
- **Abgelaufene/besetzte Stellen**: 301 auf /karriere/, raus aus Listen, Sitemap und JSON-LD.
  Pruefung zur Abfragezeit, kein Cron. Alte Live-URLs (`?job=`) per 301 ueber "Alte Stellen-ID".
- **JobPosting** wird aus denselben Daten wie die Seite gebaut (`hengegroup_theme_get_job_data()`)
  und ist unit-getestet. Arbeitgeber-`url` = Unternehmens-Unterseite (nicht `sameAs`), damit Google
  die Firmen nicht zusammenfasst.
- **Benefit-Icons als feste Lucide-Auswahl** statt SVG-Upload.
- **Galerie als Bento-Raster**, das bei jeder Bildanzahl volle Reihen ergibt (PHP und Editor-JS
  rechnen identisch, beide getestet).
