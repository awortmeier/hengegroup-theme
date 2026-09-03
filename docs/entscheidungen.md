# Entscheidungen

Log fuer bewusste Entscheidungen: warum etwas so und nicht anders gebaut oder bewusst nicht gebaut
wurde. Neueste Eintraege zuerst.

Abgrenzung zu benachbarten Dokumenten:

- `docs/how-to.md` sammelt Anleitungen, wie man einen bestehenden Erweiterungspunkt nutzt (das
  "Wie"), waehrend diese Datei das "Warum" bewusster Entscheidungen festhaelt.
- `docs/to-do.md` sammelt **offene** Punkte (noch nicht entschieden/gebaut). Sobald ein dort
  gelisteter Punkt entschieden ist, bekommt die Begruendung hier einen Eintrag; der Eintrag in
  `docs/to-do.md` wird wie bisher als geloest markiert/entfernt.
- `CHANGELOG.md` haelt fest, **was** sich geaendert hat (fuer Nutzer der Vorlage). Diese Datei haelt
  fest, **warum** (fuer kuenftige Bearbeiter, inkl. Claude selbst).
- Der Dateikopf-Kommentar der betroffenen Datei bleibt die Quelle direkt am Code (siehe
  `docs/neue-komponente-erstellen.md` Regel 4/9). Ein Eintrag hier ist ein zusaetzlicher, thematisch
  gebuendelter Verweis, kein Doppel-Text mit abweichendem Inhalt — kurz halten, auf den
  Kopfkommentar verweisen statt ihn zu duplizieren.

Siehe `CLAUDE.md` Regel 12 fuer die Pflicht, wann ein Eintrag hier angelegt wird.

---

### `kbd.php`/`kbd-group.php`: Keycap-Styling, `size`-Skala + `pressed`-State, kein Dark-Abschnitt, keine Datei-pro-Variante (2026-09-03)

Phase-2-Styling auf Basis der Claude-Design-Referenz "Hengegroup" (dieselben `.dc.html`-
Referenzseiten wie beim `button.php`-Padding/Shape-Eintrag oben). Details/Klassen-Herleitung
stehen direkt in `kbd.php`s/`kbd-group.php`s eigenen Kopfkommentaren (Regel 12: kein Doppel-Text
hier) -- dieser Eintrag haelt nur die Entscheidungen fest, die nicht schon aus dem Diff folgen:

- **`size` (sm/default/lg) und `pressed` sind eine bewusste Erweiterung ueber shadcns eigenes Kbd
  hinaus** (das kennt weder das eine noch das andere, siehe `kbd.php`-Kopfkommentar). Gerechtfertigt
  durch die Referenz selbst, dieselbe Kategorie Abweichung wie button.php's/badge.php's
  Marken-Vokabular -- keine Erfindung ohne Anlass.
- **Der Referenz-Abschnitt "Auf dunklem Grund" wurde NICHT uebernommen.** Dieses Theme hat noch
  keine Dark-Mode-/Dark-Surface-Strategie (siehe `docs/to-do.md`); button.php/badge.php droppen aus
  demselben Grund bereits shadcns eigene `dark:`-Klassen. Ein Kbd-spezifischer "auf dunklem
  Hintergrund"-Modus waere ein Alleingang ohne den Rest der Komponenten-Familie -- wird nachgezogen,
  sobald es eine projektweite Dark-Strategie gibt, nicht isoliert vorgezogen.
- **Kein Datei-pro-Variante-Split.** `kbd/` ist bereits ein eigener Ordner (kbd.php + kbd-group.php,
  seit Phase 1); die `size`-Werte sind reine Klassen-Varianten innerhalb EINER Datei, ueber ein
  `$size_classes`-Array -- exakt dasselbe Muster wie button.php's `variant`/`size` oder badge.php's
  `variant` (siehe deren Dateien). Diese Komponenten haben trotz mehrerer Werte nie eine Datei pro
  Wert bekommen; ein Ordner-Split lohnt sich in diesem Theme bislang nur fuer echte Sub-Komponenten
  mit eigenem Markup (button-group.php + button-group-text.php, toggle.php + toggle-group.php, ...),
  nicht fuer Styling-Varianten eines einzelnen Elements.

### `button.php`: Font-Size je `size`, Size-Vokabular auf `sm`/`base`/`lg` reduziert (2026-08-30)

Bislang teilten sich alle `size`-Werte dieselbe `text-sm` (14px) aus `$base_classes`, nur `xs`
wich mit `text-xs` (12px) ab. Auf Basis der Buttons im Claude-Design-Referenzprojekt
"Hengegroup" (dieselben `.dc.html`-Referenzseiten wie beim Padding/Shape-Eintrag oben) zeigte
sich, dass die echten Hengegroup-Pill-Buttons unterschiedliche Schriftgroessen je Groesse nutzen
(Nav-Pill "Kontakt" 16px, Hero-/Section-CTA-Pills 18px). Auf expliziten Wunsch daraus zunaechst 3
Font-Size-Stufen ueber die bestehenden 4 `size`-Werte (`default`/`xs`/`sm`/`lg`) verteilt, dann in
einem zweiten Schritt das Size-Vokabular selbst auf 3 Werte reduziert/umbenannt, weil `default`
und `sm` ohnehin dieselbe Font-Size teilten: `sm` (bislang `xs`), `base` (bislang `sm`), `lg`
(unveraendert) -- `default`/`icon` (h-9/size-9) entfallen ersatzlos, `base` uebernimmt ihre Rolle
als Fallback-Wert. Nur echte Tailwind-Scale-Klassen, keine Arbitrary Values (gleiche Konvention
wie beim `typography.php`-Eintrag oben):

- `sm`/`icon-sm`: `text-sm` (14px)
- `base`/`icon-base`: `text-base` (16px)
- `lg`/`icon-lg`: `text-lg` (18px)

`icon-sm`/`icon-base`/`icon-lg` haben keinen sichtbaren Text, spiegeln die Font-Size ihres
Text-Pendants aber trotzdem (rein kosmetisch/zukunftssicher) -- siehe `button.php`-Kopfkommentar.
Alle Aufrufer, die bislang `size => 'default'`/`'icon'` hart codiert hatten (`pagination.php`,
`data-table.php`, `carousel-previous.php`/`carousel-next.php`), sowie `icon-xs`-Aufrufer
(`page-component-showcase-form-elements.php`) sind auf die neuen Namen (`base`/`icon-base` bzw.
`icon-sm`) migriert.

---

### `typography.php`: Variant-Vokabular von shadcns `h1-h4/p/lead/large/small/muted` auf eigenes `headline-lg/base/sm/xs`/`body-lg/base/sm/xs` umgestellt (2026-08-30)

Auf expliziten Wunsch komplett eigenes, groessenbasiertes Vokabular statt shadcns Namen zu
uebernehmen (bewusste Abweichung von `docs/neue-komponente-erstellen.md` Regel 2, gleiche
Kategorie Entscheidung wie button.php's/badge.php's Marken-Farbnamen, siehe deren Eintraege oben).
Ausloeser: eine konkrete Ziel-Groessen-Tabelle (`headline-lg: 64px` ... `text-tiny: 14px`), gemappt
auf die naechstliegende(n) echte(n) Tailwind-`text-*`-Klasse(n) -- **keine** eigenen
Pixel-Arbitrary-Values, nur die eingebaute Skala (`text-6xl`/`text-5xl`/`text-4xl`/`text-3xl`/
`text-2xl`/`text-lg`/`text-base`/`text-sm`).

- **`headline-sm` (42px) faellt komplett weg statt zu kollidieren.** Tailwinds Skala springt fix von
  `text-4xl` (36px) auf `text-5xl` (48px) -- 42px liegt exakt in der Mitte, jede Zuordnung waere
  optisch identisch mit einer Nachbarstufe (`headline-base`/48px oder dem, was `headline-xs` werden
  sollte/36px) gewesen. Auf expliziten Wunsch deshalb nur 4 Ueberschriften-Stufen statt der
  urspruenglich geplanten 5 (`headline-lg/base/sm/xs` = `text-6xl/5xl/4xl/3xl` = 60/48/36/30px), keine
  kuenstlich zusammengelegte Stufe.
- **`text-large` (22px) -> `text-2xl` (24px) statt `text-xl` (20px).** Ebenfalls eine Luecke in
  Tailwinds Skala (kein Stop bei 22px, `text-xl`/20px und `text-2xl`/24px gleich weit entfernt),
  hier aber unkritisch (keine Kollision mit einer Nachbarstufe) -- `text-2xl` gewaehlt fuer eine
  gleichmaessigere Stufung zur naechstgroesseren Stufe (`headline-xs`/30px) hin.
- **`text-*`-Namenspraefix zu `body-*` umbenannt**, auf meinen Vorschlag: `text-*` kollidiert
  begrifflich mit Tailwinds eigenem generischem `text-`-Praefix (Groesse UND Farbe, z. B.
  `text-red-500`) -- `headline-*`/`body-*` ist zudem ein gaengiges Namenspaar fuer Ueberschrift vs.
  Fliesstext.

Kein eigener `muted`-Groessen-Wert mehr -- die vorherige `muted`-Variante bekam ihre gedaempfte
Farbe fest eingebacken; jetzt macht das ausschliesslich die bereits bestehende `color`-Achse
(`color: 'neutral'` -> `text-muted-foreground`) auf einer beliebigen Groessen-Variante, siehe
`typography.php`-Kopfkommentar. Vereinfacht die Komponente: keine Sonderfall-Farblogik pro Variante
mehr, `color` ist jetzt die alleinige Farbachse fuer alle acht Varianten.

**Migration der vier Composing-Komponenten**, die die alten Variant-Namen direkt referenzierten
(sonst waeren sie durch den Fallback auf `body-base` degradiert, ohne Fehler, aber falsch
gestylt):

- `card.php` (Titel: `h3` -> `body-lg` + `class: 'font-semibold'`, Beschreibung: `p` ->
  `body-sm`), `dialog.php` (Titel: `h2` -> `body-lg` + `class: 'font-semibold'`,
  Beschreibung: `p` -> `body-sm`), `accordion.php` (Trigger-Heading: `h4` -> `body-base` +
  `class: 'font-semibold'`) -- `body-lg`/`body-base` sind per Default `font-normal` (siehe
  oben, keine eigene Gewichts-Skala pro Groessen-Stufe angefragt), Titel/Trigger brauchen aber
  sichtbare Betonung gegenueber ihrem Beschreibungstext -- deshalb per additivem `class`-Passthrough
  ergaenzt statt eine eigene "titel"-Variante zu erfinden.
- `data-table.php` (Pagination-Label: `muted` -> `body-xs` + `color: 'neutral'`) -- 1:1-Ersatz,
  keine Groessenaenderung (beide 14px/`text-sm`).

`page-component-showcase-typography.php` auf das neue Vokabular nachgezogen (Abschnitt "muted"
entfernt, Farben-Abschnitt erklaert stattdessen die `color: 'neutral'`-Ablaesung).

---

### PHPUnit `^13.3` -> `^11.5`: CI-Runner nutzt PHP 8.2, `composer.json` verspricht `>=8.2` (2026-08-30)

CI (`.github/workflows/ci.yml`) scheiterte im `php`-Job bei `composer install` mit lauter
"requires php >=8.4"-Fehlern (PHPUnit 13.3.1 + dessen `sebastian/*`/`phpunit/php-*`-Unterpakete).
Ursache: `composer.json`s eigene `require.php` sagt `>=8.2`, aber `phpunit/phpunit: ^13.3` zieht
PHPUnit 13 nach, das selbst PHP `>=8.4.1` braucht -- ein in sich widersprüchliches
`composer.json`, das lokal nur deshalb nicht auffiel, weil die Entwicklungsmaschine PHP 8.5 hat
und `composer update` dort klaglos die neueste (PHP-8.4-only) PHPUnit-Version aufloeste.

Fix: `phpunit/phpunit` auf `^11.5` (letzte Major-Linie, die noch `php: >=8.2` voraussetzt,
`composer update phpunit/phpunit --with-all-dependencies`) statt CI/`composer.json`s
PHP-Untergrenze auf 8.4 anzuheben -- `>=8.2` bleibt die bewusste Kompatibilitätszusage dieses
Themes (siehe CI-Konfiguration), nicht die zufällige lokale PHP-Version. `composer test`/
`composer lint` liefen nach dem Downgrade unveraendert gruen (18/18 Tests), kein API-Bruch fuer
die hier genutzte PHPUnit-Oberflaeche (`TestCase`, Brain-Monkey-Setup).

### `typography.php` gestylt: Groessen-Skala aus Referenzdesign statt shadcn-Stock-Werten, `variant`/`tag` bewusst entkoppelt (2026-08-30)

Dritte tatsaechlich gestylte Base-Komponente (siehe `button.php`/`badge.php`-Eintraege unten fuer
den generellen Ablauf: Phase 2 startet inhaltlich bereits, obwohl `CLAUDE.md`s Kopfabschnitt
weiterhin "aktuell laeuft Phase 1" sagt). Anders als `button.php`/`badge.php` NICHT 1:1 aus shadcns
eigener `buttonVariants()`/`badgeVariants()`-cva()-Definition uebernommen -- shadcns Typography-
Groessen tauchen im Referenzdesign nirgends auf. Stattdessen alle sechs `.dc.html`-Seiten des
Referenzdesigns (Startseite, Karriere, Karrieredetail, Produkte, Produktdetail, Anwendungen,
Downloads, `claude.ai/design`-Projekt, live analysiert 2026-08-30) nach Schriftgroesse/-gewicht/
-line-height geclustert und auf die neun bestehenden Varianten (`h1-h4/p/lead/large/small/muted`)
gemappt, siehe `typography.php`-Kopfkommentar fuer die konkreten Klassen. Zwei bewusste
Entscheidungen, beide auf expliziten Wunsch:

- **Kein `clamp()`.** Das Referenzdesign nutzt fuer seine Hero-Ueberschriften (H1/H2) echtes
  CSS `clamp()` (viewport-fluid). Hier stattdessen einfache Tailwind-Breakpoints (`sm:`/`md:`/
  `lg:`) -- weniger exakte 1:1-Übernahme, aber idiomatischeres Tailwind ohne Extra-Sonderfall pro
  Komponente. `clamp()` bleibt ein moeglicher spaeterer Ausbauschritt, kein aktueller Bedarf.
- **`variant` (Optik) und `tag` (Semantik) sind bewusst unabhaengige Achsen**, nicht wie bei einem
  klassischen `h1`-`h6`-Set 1:1 gekoppelt. Grund: das Referenzdesign nutzt fuer denselben visuellen
  "H2"-Look sowohl grosse Hauptsektionstitel (34-42px, z. B. "Produkte"/"Kontakt") als auch
  kleinere Box-Titel (26-30px, z. B. "Ansprechpartner") -- beide liessen sich durch `h2`s eigene
  Breakpoint-Spanne (`text-3xl md:text-4xl`, 30px Basis -> 36px ab `md:`) abdecken, ohne dafuer
  eine zehnte Variante zu erfinden oder eine feste 1:1-Kopplung an ein bestimmtes `<h*>`-Tag zu
  brauchen. Praktische Konsequenz: eine visuell "h2"-grosse Ueberschrift kann als `<h4>` gerendert
  werden (oder umgekehrt), je nachdem was die tatsaechliche Dokumentgliederung an der Stelle
  braucht -- `tag` immer explizit setzen, sobald visuelle Groesse und semantische Ebene
  auseinanderfallen. `typography.php` unterstuetzte dieses Auseinanderfallen von `variant`/`tag`
  strukturell schon vorher (Phase-1-API), diese Entscheidung nutzt es jetzt aktiv statt es nur
  bereitzuhalten.

Der bestehende `color`-Config-Key (`default | light | neutral`) bleibt die einzige Farbachse --
`muted` ist die eine Ausnahme, die (wie in shadcns eigenem Stock-Schema) ihre eigene gedaempfte
Farbe fest in der Variante traegt statt sie ueber `color` zu beziehen, siehe Kopfkommentar. Ersetzt
damit auch das Referenzdesign-Muster, gedaempften Text ueber `opacity:0.6/0.7` auf der normalen
Textfarbe zu simulieren (z. B. Job-Standort-Zeile), durch das echte `--color-muted-foreground`-
Token.

### `button.php`: `full_width`-Config-Key (2026-08-30)

Auf expliziten Wunsch bekommt `button.php` einen neuen `full_width`-Bool-Config-Key statt eines
neuen `size`-Werts -- anders als `size` ist "volle Breite des Elternelements" orthogonal zu
Variante/Groesse (mit jeder Kombination kombinierbar) statt eine eigene Groessenstufe. Haengt bei
`true` schlicht `w-full` an die berechnete Klasse an; kein neues `data-*`-Attribut dafuer, gleiche
Begruendung wie bei `disabled`/`loading` (native/aria Semantik reicht dort, hier ist es ein reiner
Layout-Utility ohne eigenen State zum Selektieren).

### `badge.php`: Padding/Schriftgroesse an Produkte-/Karriere-Referenz angepasst (2026-08-30)

Auf expliziten Wunsch, orientiert an den Pill-Labels der Produkte-/Karriere-Sektion im
Startseite-Referenzdesign (`Startseite.dc.html`: Produkt-Kategorie-Badge, Anwendungs-Tags,
Karriere-Job-Tag) -- alle drei nutzen dort spuerbar grosszuegigeres Padding (~6px/12px) und
12-14px Text statt shadcns knappem `px-2 py-0.5 text-xs`. Geaendert: `px-2 py-0.5 text-xs` ->
`px-3 py-1.5 text-sm`. Bewusst NICHT angefasst (auf expliziten Wunsch): Variant-Vokabular, Farben,
`rounded-full` (Form/Rundung passte bereits zur Referenz, siehe Eintrag "badge.php gestylt"
unten).

### `badge.php`: `font`-Config-Key fuer Outfit/Crillee (2026-08-30)

Auf expliziten Wunsch bekommt `badge.php` einen neuen `font`-Config-Key (`primary | accent`,
Default `primary`) statt eines neuen `variant`-Werts oder eines pauschalen CSS-Overrides -- die
Startseiten-Referenz (`Startseite.dc.html`, Karriere-Job-Badges wie "IMEXCO"/"HENGE") nutzt fuer
einzelne Badges bewusst die Marken-Akzentschrift Crillee statt der site-weiten Outfit-Fliesstext-
schrift. Kein neuer eigener CSS-Klassenname: `accent` haengt dieselbe `.font-accent`-Utility an, die
`hengegroup_theme_render_accent_text()` (siehe `inc/template-parts/helpers.php`) bereits fuer
einzelne hervorgehobene Woerter in `typography.php` nutzt -- hier auf das gesamte Label angewendet
statt auf einzelne Woerter, aber dieselbe Font-Rolle/dasselbe Token (`--font-accent`,
`assets/css/tokens.css`). `primary` fuegt bewusst keine Klasse hinzu (bereits von der site-weiten
`body`-Regel geerbt) -- gleiches "nur Abweichungen vom globalen Default deklarieren"-Muster wie
ueberall sonst in diesem Theme. Zusaetzliches `data-font`-Attribut als Hook, gleiche Konvention wie
`data-variant`.

### `badge.php` gestylt: engeres Variant-Vokabular als `button.php` (2026-08-30)

Zweite tatsaechlich gestylte Base-Komponente nach `button.php` (siehe dessen Eintrag oben fuer den
generellen Ablauf/die generelle Begruendung: Phase 2 startet inhaltlich bereits, obwohl `CLAUDE.md`s
Kopfabschnitt weiterhin "aktuell laeuft Phase 1" sagt). Klassen 1:1 aus shadcns echter
`badgeVariants()`-cva()-Definition uebernommen (`registry/new-york-v4/ui/badge.tsx` auf GitHub, live
abgerufen 2026-08-30 -- siehe `badge.php`-Kopfkommentar), mit denselben zwei button.php-Abweichungen
(`dark:`-Klassen weggelassen, keine eigene Dark-Mode-Strategie) plus zwei badge-spezifischen:

- **Variant-Vokabular auf Marken-Farbnamen umbenannt UND bewusst enger als `button.php`**, auf
  expliziten Wunsch: `grey-dark | grey-light | henge-blue | henge-green | henge-grey | outline` --
  dieselben fuenf Voll-Farb-Namen wie bei `button.php`, aber ohne `destructive`/`ghost`/`link`
  (anders als `button.php`, das diese drei shadcn-Varianten unveraendert behaelt). Begruendung: ein
  statisches Label hat kein Destruktiv-/Call-to-Action-/Inline-Text-Link-Anwendungsfall -- die drei
  Varianten waeren totes API-Vokabular gewesen (Regel: shadcns Vokabular uebernehmen, nicht frei
  erfinden, aber auch nicht ungenutztes Vokabular mitschleppen). `default`/`secondary` faellt damit
  ebenfalls weg (ersetzt durch `henge-green`/`grey-light`, gleiches Mapping wie bei `button.php`).
- **`outline`s Border nutzt `--color-grey-light` statt shadcns `--color-border`-Rolle**, ebenfalls
  auf expliziten Wunsch -- analog zu `button.php`s `outline`/`ghost`-Hover-Farben, die ebenfalls das
  Marken-Grau statt der generischen shadcn-Rolle nutzen (siehe `button.php`-Kopfkommentar).

`class`-Config-Key ist jetzt wie bei `button.php` kein reines Passthrough mehr, sondern wird HINTER
die berechneten Base-/Variant-Klassen angehaengt (String-Konkatenation, kein `tailwind-merge`/`cn()`
in PHP verfuegbar) -- gleiche Einschraenkung, gleiche Doku-Stelle (Kopfkommentar).

### `build.ps1`/`build.sh`: Top-Level-PHP-Templates per `*.php`-Wildcard statt fester Liste (2026-08-29)

Beide Fassungen kopierten Top-Level-Theme-Dateien bislang ueber eine fest enumerierte Liste
(`style.css`, `functions.php`, `header.php`, ... `theme.json`, `screenshot.png/jpg`). Ein neues
Custom-Page-Template nach WordPress-Template-Hierarchie-Konvention (z. B.
`page-component-showcase-button.php`) landete dadurch beim Build **nicht** in `dist/` — kein
Deploy-Bug (der Upload selbst laeuft korrekt, `dist/` enthielt die Datei schlicht nie), sondern
eine Luecke, die in `build.ps1` und `build.sh` gleichermassen bestand.

Fix: `style.css`/`theme.json`/`screenshot.png`/`screenshot.jpg` bleiben eine feste Liste
(`$themeStaticFiles`/`theme_static_files`, WP-Konvention mit garantiert fixem Namen), aber jede
Top-Level-`*.php`-Datei im Repo-Root wird jetzt automatisch mitkopiert (`Get-ChildItem -Filter
"*.php"` bzw. eine `for ... in "$repo_root"/*.php`-Schleife) — deckt damit automatisch die volle
WordPress-Template-Hierarchie ab (`page-{slug}.php`, `single-{post-type}.php`,
`category-{slug}.php`, `taxonomy-{slug}.php`, `front-page.php`, ...), ohne dass die Liste bei
jedem neuen Custom-Template von Hand nachgezogen werden muss. Sicher, weil im Repo-Root
grundsaetzlich nur echte Theme-Templates als `*.php` liegen (Tooling-Config wie `composer.json`
ist kein `.php`).

### Bash-Pendants zu allen `scripts/*.ps1` fuer macOS/Linux, ueber `run.mjs` dispatcht (2026-08-28)

Alle zehn `scripts/*.ps1`-Skripte (`build`, `clean`, `deploy`, `deploy-changed`, `i18n-make-pot`,
`pull-base-updates`, `rename-theme`, `sync-lucide-icons`, `sync-tabler-icons`,
`sync-theme-tokens`, `sync-theme-version`) waren bislang nur fuer Windows PowerShell geschrieben —
auf macOS/Linux ohne separat installierte PowerShell Core (`pwsh`) gar nicht lauffaehig. Jedes
bekommt jetzt ein `scripts/<name>.sh`-Pendant (Bash, kompatibel zu macOS' Standard-`/bin/bash`
3.2 — keine Bash-4-Features wie `wait -n`/assoziative Arrays), verhaltensgleich zum `.ps1`-Original
uebersetzt und einzeln end-to-end gegen Kopien/isolierte Test-Repos verifiziert. Beide Fassungen
bleiben dauerhaft parallel gepflegt (keine Migration auf PowerShell Core als einzige Variante), weil
das Windows-`.ps1`-Original ohne zusaetzliche Installation lauffaehig bleiben soll und ein
Nebeneinander zweier Skript-Sprachen im selben Ordner ohnehin schon Konvention dieses Repos ist
(`.php`-Icon-Scanner neben `.ps1`).

Technische Eckpunkte der Uebersetzung, damit kuenftige Aenderungen an einer Fassung nicht die
andere unbemerkt auseinanderlaufen lassen:

- **Text-/JSON-lastige Logik (Versions-Sync, Token-Sync, Rename, Manifest-Checks, Deploy-State)
  delegiert an `node -e`** statt an `sed`/sh-Bordmittel — Node ist ueber `pnpm`/Vite ohnehin
  bereits harte Voraussetzung, und die Ersetzungs-/Zaehl-Logik laesst sich so nahezu 1:1 aus dem
  PowerShell-Original uebertragen (String-basiert, kein Regex-Escaping-Aerger). `jq` wurde bewusst
  nicht als neue Abhaengigkeit eingefuehrt.
- **`deploy.sh`/`deploy-changed.sh` parallelisieren FTP-Uploads ueber `xargs -P`** statt
  PowerShells `Start-Job`/`Start-ThreadJob`-Jobsteuerung (kein Bash-4-Aequivalent auf macOS'
  Standard-Bash verfuegbar). Eine Abweichung bleibt bewusst bestehen: das PowerShell-Original
  bricht das Nachlegen neuer Uploads beim ersten Fehlschlag ab (laufende Jobs werden noch fertig
  abgewartet), waehrend `xargs -P` bereits aufgereihte Uploads durchlaufen laesst, bevor mit
  Exit-Code 1 abgebrochen wird — Ergebnis (Fehlschlag = kein erfolgreiches Deploy) ist gleich, nur
  der Abbruchzeitpunkt etwas spaeter. Ausfuehrlich im Kopfkommentar von `deploy.sh` begruendet.
  Beide zeigen ausserdem eine `Write-Progress`-Entsprechung: in einem echten Terminal (TTY) eine
  sich per `\r`/ANSI-Clear ueberschreibende Fortschrittszeile, ohne TTY (Log-Datei, CI) stattdessen
  eine Zeile pro Upload, damit das Log kein Steuerzeichen-Wirrwarr wird (2026-08-29, Ergaenzung).
- **`package.json` "scripts" rufen ab jetzt `node scripts/run.mjs <name> [--flag ...]`** statt
  direkt `powershell -File scripts/<name>.ps1` — der Dispatcher waehlt anhand von
  `process.platform` die `.ps1`- oder `.sh`-Fassung und uebersetzt dabei `--kebab-case`-Flags 1:1
  in PowerShells `-PascalCase`-Parameternamen (die `.sh`-Skripte verwenden konsequent die
  kebab-case-Form des jeweiligen `.ps1`-Parameternamens, z. B. `-NoGitAdd` <-> `--no-git-add`,
  `-NewSlug` <-> `--new-slug`). So bleibt jeder `package.json`-Scripts-Eintrag auf beiden
  Plattformen identisch, statt zwei parallele Skript-Namen pro Aufgabe pflegen zu muessen. Neue
  `-Parameter`s in einem `.ps1` muessen dieselbe Namenskonvention einhalten, damit der Dispatcher
  sie ohne Sonderfall uebersetzen kann.
- **`rename-theme.sh`/`pull-base-updates.sh` bleiben wie ihre `.ps1`-Vorbilder ausserhalb von
  `package.json`** — beide sind seltene, manuelle Bootstrap-/Update-Schritte mit vielen optionalen
  Parametern, direkt aufgerufen (`bash scripts/rename-theme.sh --new-slug ...`), kein
  `pnpm run ...`-Eintrag noetig.
- `rename-theme.sh`s Ausschlussliste/`--included-extensions` schliesst jetzt auch `.sh` mit ein
  (analog zu `.ps1`) und schuetzt zusaetzlich zu `scripts/rename-theme.ps1`/
  `scripts/pull-base-updates.ps1` auch deren `.sh`-Pendants vor versehentlicher Selbst-Umbenennung
  (gleicher Grund wie im `.ps1`-Kopfkommentar: die `-OldSlug`/`-OldPrefix`-Defaults muessen auf das
  literale `base-theme`/`base_theme_` zeigen bleiben, sonst laeuft ein spaeterer Aufruf ohne
  explizite `--old-slug`/`--old-prefix` ins Leere).

### `button.php` gestylt: Variant-Vokabular auf Marken-Farbnamen umbenannt (2026-08-28)

Erste tatsaechlich gestylte Base-Komponente (Phase 2 startet damit inhaltlich, auch wenn
`CLAUDE.md`s Kopfabschnitt weiterhin "aktuell laeuft Phase 1" sagt -- die Formulierung dort noch
nicht nachgezogen, da das eine eigene, groessere Aenderung waere). Klassen 1:1 aus shadcns echter
`buttonVariants()`-cva()-Definition uebernommen (`registry/new-york-v4/ui/button.tsx` auf GitHub,
live abgerufen 2026-08-28 -- siehe `button.php`-Kopfkommentar), zwei bewusste Abweichungen:

- **Variant-Vokabular umbenannt auf Marken-Farbnamen**, auf expliziten Wunsch: `henge-green` (ersetzt
  `default`), `henge-blue`, `henge-grey`, `grey-dark` (drei neue Voll-Varianten, vorher nicht
  vorhanden), `grey-light` (ersetzt `secondary`), `destructive`/`outline`/`ghost`/`link` unveraendert.
  Das ist eine bewusste Abweichung von `docs/neue-komponente-erstellen.md` Regel 2 ("Vokabular
  uebernehmen, nicht frei erfinden") -- hier ausdruecklich gewuenscht, weil das Projekt lieber direkt
  am Markennamen statt an shadcns abstrakter default/secondary-Nomenklatur entlang designen will.
  `badge.php` hat noch das alte shadcn-Vokabular (`default | secondary | ...`) -- beide
  Komponenten sind dadurch aktuell inkonsistent zueinander, bis `badge.php` (oder andere
  Komponenten mit dem gleichen Variant-Enum) denselben Umbau bekommen.
- **`dark:`-Klassen komplett weggelassen** (shadcns Original hat u. a. `dark:bg-destructive/60`,
  `dark:border-input`, `dark:hover:bg-accent/50`) -- Dark Mode ist laut `docs/to-do.md` weiterhin
  offen, ein halb umgesetzter Dark-Mode-Pfad (shadcns literale dark:-Utilities ohne eigene
  Dark-Tokens dahinter) waere schlechter als gar keiner.
- **`destructive` nutzt `text-destructive-foreground` statt shadcns hartcodiertem `text-white`** --
  konsequent aus der grey-light-statt-Weiss-Entscheidung (siehe Eintrag unten) abgeleitet.

Neue Tokens in `assets/css/tokens.css` fuer die drei neuen Voll-Varianten (`--color-henge-blue-
foreground`, `--color-henge-grey-foreground`, `--color-grey-dark`/`-foreground`,
`--color-grey-light`/`-foreground`) nach demselben `<name>`/`<name>-foreground`-Schema wie
`--color-henge-green`/`-foreground`. `--color-grey-dark`/`--color-grey-light` sind bewusst
zusaetzliche Tokens (sonst gilt "keine eigenen Tokens fuer die Marken-Grautoene", siehe Eintrag
"Marken-Tokens" unten) -- Ausnahme, weil die Komponenten-API jetzt selbst diese Namen als
`variant`-Werte erwartet und dafuer eine `bg-grey-dark`/`bg-grey-light`-Tailwind-Klasse braucht.

`assets/css/app.css`s `body`-Regel (vorher leer) bekommt `@apply bg-background text-foreground
font-primary` -- erste echte Nutzung von `--font-primary`/`--color-background`/`--color-foreground`
(vorher dokumentiert-aber-ungenutzte Tokens, siehe "Marken-Tokens"-Eintrag unten). `button.php`
selbst wiederholt Text-/Hintergrundfarbe nur dort, wo eine Variante vom globalen Default abweicht
(shadcns eigenes Muster) -- kein `font-primary` auf dem Button selbst, das wird vom `body` geerbt.

`class`-Config-Key ist jetzt nicht mehr reines Passthrough, sondern wird HINTER die berechneten
Base-/Variant-/Size-Klassen angehaengt (String-Konkatenation) -- PHP hat kein `tailwind-merge`/`cn()`-
Aequivalent, ein per `class` uebergebenes konfligierendes Utility "gewinnt" also nicht garantiert
gegen die berechnete Variante (anders als bei shadcns `className`-Prop). Dokumentiert im
`button.php`-Kopfkommentar; fuer rein additive Klassen (Margins, Layout) unproblematisch, fuer
`bg-*`/`text-*`-Overrides nicht verlaesslich.

**Nicht lokal verifiziert:** `composer lint`/`composer test` liefen in dieser Session nicht (kein
PHP/Composer in der Umgebung verfuegbar) -- `pnpm exec vite build`, `pnpm exec prettier --check` und
`pnpm test` (Vitest) liefen und sind gruen; die generierten Klassen (`bg-henge-green`,
`hover:bg-henge-green/90`, `ring-ring/50`, etc.) wurden im kompilierten CSS stichprobenartig
verifiziert.

### Semantische Rollen-Farb-Tokens (shadcn-Vokabular) + `--color-accent` -> `--color-henge-green` (2026-08-28)

Phase-2-Vorarbeit fuer das Variant-Vokabular der Base-Komponenten (`button.php`/`badge.php`:
`default | secondary | destructive | outline | ghost | link`): `assets/css/tokens.css` bekommt das
volle Set an shadcn-typischen semantischen Rollen-Tokens (`--color-background`/`-foreground`,
`-card*`, `-popover*`, `-secondary*`, `-muted*`, `-accent*`, `-destructive*`, `-border`, `-input`,
`-ring`), referenziert wo moeglich Tailwinds eigene Skalen (`var(--color-neutral-*)`,
`var(--color-red-*)`, `var(--color-white)`) statt Werte zu duplizieren — gleiche Konvention wie das
bestehende Marken-Grau-Mapping (siehe Eintrag unten).

- **`--color-accent` (bisher henge-green) heisst jetzt `--color-henge-green`.** Grund: der Name
  "accent" ist in shadcn selbst bereits vergeben — eine eigene, neutrale Hover-/Subtle-Rolle (z. B.
  `skeleton.php`s eigener Kopfkommentar zitiert shadcns Original-Markup mit `bg-accent` fuer den
  Placeholder-Hintergrund), keine Marken-Akzentfarbe. Mit henge-green als `--color-accent` waeren
  spaetere 1:1-uebernommene shadcn-Klassen wie `bg-accent`/`text-accent-foreground` (Regel 2:
  "Vokabular uebernehmen, nicht frei erfinden") grundfalsch gruen statt dezent grau geworden.
  `--color-accent`/`--color-accent-foreground` decken jetzt korrekt shadcns eigentliche Rolle ab
  (gleicher Hintergrund wie `--color-muted`, aber volltonige `-foreground` statt gedaempft, wie im
  shadcn-Original). Der WP-Editor-Palette-Slug in `theme.json` bleibt bewusst weiterhin `"accent"`
  (Label bereits `"Henge Green"`) — den nachtraeglich umzubenennen wuerde bereits gespeicherte
  `has-accent-color`-Blockklassen in Inhalten brechen, waehrend das interne CSS-Token frei umbenennbar
  war, da es noch nirgends im Markup referenziert wird (Phase 1 hat kein Styling). Kein neues
  `--color-primary`-Alias fuer henge-green ergaenzt — Variant-Class-Maps referenzieren spaeter direkt
  `var(--color-henge-green)`, gleiche Namenskonvention wie die bestehenden
  `--color-henge-blue`/`--color-henge-grey`-Tokens. `scripts/sync-theme-tokens.ps1`s Regex auf die
  neue Token-Bezeichnung angepasst.
- **`secondary` bleibt neutral (grey-light/grey-dark), nicht henge-blue** — die Variante ist
  komponentenuebergreifend (Button, Badge, ...) dieselbe generische Rolle; mit henge-blue haetten
  `secondary` und `default` gleich prominent/bunt gewirkt, was shadcns eigener Intention (secondary
  = dezente Alternative) widerspricht. henge-blue/henge-grey bleiben eigene, frei nutzbare
  Marken-Tokens ausserhalb der generischen Variant-Rollen.
- **`--color-destructive` ist vorlaeufig Tailwinds Standard-Rot (`red-600`)**, kein Marken-Rotton —
  im Brand-Guide bislang keiner hinterlegt. Bei Bedarf gezielt austauschen, sobald einer feststeht.
- **`--color-ring` = `blue-500`** (Tailwinds eigener Standard-Blauton), **weder henge-green noch
  shadcns eigener neutraler Default** (`neutral-400`) (2026-08-30, zweite Korrektur dieses Eintrags
  -- erst bewusst markenkonsistentes Gruen, dann kurzzeitig shadcns neutraler Default, siehe
  Git-Historie dieser Datei fuer beide vorherigen Fassungen) — auf expliziten Wunsch Tailwinds
  eigener, unkonfigurierter Standard-Fokusring-Ton, projektweit fuer alle Komponenten, die
  `ring-ring`/`border-ring` nutzen (button.php, badge.php, alle Phase-2-gestylten
  Form-Base-Komponenten unter `template-parts/base/`).
- **"Weisser" Text ist bewusst grey-light (`--color-neutral-100`), nicht reines Weiss (2026-08-28,
  Ergaenzung):** `--color-henge-green-foreground`/`--color-destructive-foreground` (Text auf
  henge-green- bzw. destructive-Hintergrund) nutzen `var(--color-neutral-100)` statt
  `var(--color-white)` — Design-Vorgabe, jede Stelle mit "weissem" Text soll grey-light statt
  reinem Weiss verwenden. `--color-background`/`-card`/`-popover` (Flaechenfarben, kein Text)
  bleiben unveraendert reines Weiss.
- **Kontrast-Hinweis:** grey-light auf henge-green liegt rechnerisch bei ca. 4.0:1, grey-light auf
  destructive bei ca. 4.4:1 — beide ueber WCAG AA fuer grossen/fetten UI-Text (>= 3:1), unter der
  4.5:1-Schwelle fuer normalen Fliesstext (mit reinem Weiss waeren es ca. 4.3:1 bzw. 4.8:1 gewesen,
  siehe vorherige Fassung dieses Eintrags in der Git-Historie). Fuer Button-/Badge-Text (i. d. R.
  fett, kurze Labels) unkritisch, aber kein Freibrief fuer laengeren Fliesstext in
  henge-green-/destructive-Vordergrundfarbe anderswo.

### Marken-Tokens: drei Akzentfarben, Grau-Mapping, zwei Font-Rollen (2026-08-19)

Projekt-Setup (README "Neues Projekt aus dieser Vorlage starten", Schritt 3) fuer die echte
Henge-Group-Marke: drei Akzentfarben (henge-green, henge-blue, henge-grey), drei Marken-Grautoene
und zwei self-gehostete Fonts (Outfit, Crillee) — mehr als das bisherige "ein `--color-accent`, ein
`--font-accent`"-Modell aus `assets/css/tokens.css` vorsah. `docs/to-do.md` Abschnitt 3 listete den
Umfang des Design-Token-Systems als offene Grundsatzfrage; folgende Entscheidungen loesen sie:

- **Farben:** `--color-accent` (henge-green, `#3b875e`) bleibt die _eine_ automatisiert per
  `pnpm run sync-theme-tokens` nach `theme.json` gesynct'e Farbe (Link-Farbe,
  `settings.color.palette`-Slug `accent`) — das Sync-Skript kann nur einen Wert abbilden.
  `--color-henge-blue`/`--color-henge-grey` sind zusaetzliche, eigene Tokens im
  `--color-*`-Namespace (erzeugen automatisch `.bg-henge-blue`/`.text-henge-blue`-Utilities etc.)
  und manuell zusaetzlich in `theme.json`s `settings.color.palette` gepflegt, da sie ausserhalb der
  Sync-Skript-Automatik liegen.
- **Marken-Grautoene:** grey-light `#EFEFEF`, grey-medium `#E5E3DF`, grey-dark `#222222` bekommen
  bewusst **keine** eigenen Tokens, sondern werden per Kommentar in `tokens.css` auf die
  naechstliegenden Tailwind-`neutral-*`-Stufen gemappt (neutral-100/neutral-200/neutral-800) — haelt
  die bestehende Konvention ("Tailwinds `neutral`-Skala statt eigener Grau-Aliase") statt sie fuer
  drei Werte aufzuweichen. Einzige Naeherung: grey-medium hat einen warmen/beigen Unterton, den
  neutral-200 nicht abbildet; falls ein Anwendungsfall den exakten Wert braucht, dafuer gezielt ein
  eigenes Token ergaenzen statt neutral-200 zu erzwingen.
- **Zwei Font-Rollen statt einer:** `--font-primary` (Outfit) fuer Fliesstext/UI, `--font-accent`
  (Crillee, vorher nur System-Font-Platzhalter) fuer Akzent-/Display-Text. Bewusst **nicht** als
  Tailwinds `--font-sans` registriert — das wuerde ueber Tailwinds Preflight-Basisstil sofort
  site-weit die Body-Schrift aendern, ein pauschales visuelles Styling, das laut `CLAUDE.md` Regel 1
  erst Phase 2 gehoert. `--font-primary` erzeugt zwar schon jetzt die Utility-Klasse `.font-primary`,
  bleibt aber ungenutzt bis Phase 2. `--font-accent` ist dagegen schon jetzt aktiv (Akzent-Woerter
  in `typography.php` ueber `hengegroup_theme_render_accent_text()`), weil das eine dokumentierte
  funktionale API ist, keine pauschale Optik-Entscheidung.
- **Font-Loading vorgezogen:** die in "Komponenten-Showcase-Seite und Performance-Tooling" (unten)
  fuer Phase 2 skizzierte Font-Loading-Strategie (Self-Hosting, `font-display: swap`) wird jetzt
  schon fuer Outfit/Crillee angewendet (`assets/css/fonts.css`, `assets/fonts/README.md`), obwohl
  Phase 1 noch laeuft — Ausnahme, weil es sich um das Bereitstellen von Marken-Assets handelt
  (Projekt-Setup), nicht um deren visuelle Anwendung in einer Komponente. Bewusst noch offen:
  Preload/Subsetting — weiterhin fuer den tatsaechlichen Phase-2-Start vorgemerkt, sobald eine
  konkrete above-the-fold-Nutzung feststeht.
- **WOFF2-Konvertierung nachgezogen (2026-08-19, Ergaenzung):** `npx ttf2woff2 < input > output`
  funktioniert zuverlaessig (der fruehere Haenger beim ersten Test lag an leerem Stdin-Input als
  Testfall, nicht am Tool selbst) — `outfit.ttf` (110.572 -> 45.704 Bytes, -59 %) und `crillee.otf`
  (28.136 -> 16.616 Bytes, -41 %) liegen jetzt zusaetzlich als WOFF2 vor, `fonts.css` listet WOFF2
  vor der jeweiligen TTF/OTF-Quelle (Browser waehlt das erste unterstuetzte Format). Dabei
  festgestellt: `outfit.ttf` ist ein **Variable Font** (Achse `wght`, 100–900) — `font-weight` in
  `fonts.css` deshalb auf die Bereichs-Syntax `100 900` korrigiert (vorher faelschlich `400`, hat die
  Variable-Font-Faehigkeit nicht genutzt). `crillee.otf` ist statisch, keine `fvar`-Tabelle.
- **`setup.md` PII-Frage mit "Ja" beantwortet:** `inc/setup/theme-admin.php`s
  `hengegroup_theme_action_admin_menu_cleanup()` entfernt `export-personal-data.php`/
  `erase-personal-data.php` nicht mehr aus dem Tools-Menue. `wp_add_privacy_policy_content()` sowie
  `wp_privacy_personal_data_exporters`-/`-erasers`-Filter bleiben offen, bis das konkrete
  PII-sammelnde Feature (z. B. Kontaktformular) technisch existiert (siehe `setup.md`).

### Manueller Tastatur-/Screenreader-Testplan angelegt (2026-08-18)

`docs/to-do.md` Abschnitt 2 forderte einen dokumentierten manuellen Testplan als guenstige Ergaenzung
zur weiterhin fehlenden automatisierten a11y-Pruefung. Angelegt: `docs/tastatur-screenreader-
testplan.md`, eine Checkliste pro interaktiver Komponente (Overlays, Navigation,
Auswahl-Komponenten, Daten-Komponenten, Feedback), abgeleitet aus dem tatsaechlich implementierten
Tastaturverhalten der jeweiligen `assets/js/template-parts/base/*.js`-Datei — nicht aus einer
generischen WAI-ARIA-Checkliste, die von der echten Implementierung abweichen koennte.

- **Nur die Checkliste selbst ist fertig, nicht ihre Ausfuehrung.** Ein tatsaechlicher Durchlauf
  (Tastatur-only + NVDA/VoiceOver) braucht eine laufende WP-Instanz mit Testseiten pro Komponente —
  dafuer fehlt aktuell die zurueckgestellte Komponenten-Showcase-Seite (siehe Eintrag oben).
  `hengegroup-theme` ist zwar ein voll aktivierbares Theme, eine Wegwerf-Testseite pro Durchlauf waere bis
  dahin die pragmatische Zwischenloesung. Die Status-Tabelle in der neuen Datei bleibt bis zum ersten
  echten Durchlauf leer.
- Dokumentiert bewusst auch bekannte, aktuell fehlende Patterns als "kein Befund" statt als offene
  Luecke im Testplan selbst — z. B. `calendar.php`s fehlendes APG-Date-Grid-Pfeiltasten-Pattern (nur
  Tab pro Tag) oder `combobox.php`s fehlendes Home/End (anders als `select.php`) — damit ein
  Durchlauf diese nicht faelschlich als neuen Befund meldet.

### i18n-Konsistenz-Check: keine Abweichungen gefunden (2026-08-18)

Grep ueber alle `__()`/`_e()`/`esc_html__()`/`esc_attr__()`/... -Aufrufe im gesamten Theme
(`template-parts/`, `inc/`, Root-Templates) ergab durchgehend die Text-Domain `hengegroup-theme` (identisch
zu `style.css`s `Text Domain`-Header) — keine Abweichungen gefunden, kein Fix noetig. JS-seitig
(`assets/js/`) gibt es bewusst keine `wp.i18n`-Nutzung (siehe Kopfkommentar von `toast.js`: JS hat
keinen Zugriff auf WordPress' PHP-Uebersetzungen, Strings werden dort manuell dupliziert) — kein
Punkt, den dieser Check serverseitig abdecken kann.

### WP-/PHP-Versionsmatrix dokumentiert, Dependencies aktualisiert (2026-08-18)

`docs/to-do.md` Abschnitt 4 (Kompatibilitaet) forderte eine dokumentierte WP-/PHP-Versionsmatrix,
vorher alle Dependencies aktualisiert. Umgesetzt:

- **Dependencies aktualisiert**: `composer update` (u. a. `phpunit/phpunit` 13.3.0 -> 13.3.1,
  `mockery` 1.6.12 -> 1.6.13) sowie `pnpm update` (u. a. `prettier` 3.8.1 -> 3.9.6, `lucide-static`
  1.28.0 -> 1.31.0) — beides innerhalb der bestehenden Versions-Ranges aus `composer.json`/
  `package.json`, keine Breaking Changes. `composer lint`/`composer test`/`pnpm format:check`/
  `pnpm test` danach gruen, `composer audit`/`pnpm audit` ohne neue Funde.
- **`Requires PHP` in `style.css` 8.1 -> 8.2 angehoben**, zusaetzlich als explizites
  `require.php` in `composer.json` ergaenzt (fehlte vorher komplett) — PHP 8.1 ist zum
  Entscheidungszeitpunkt bereits vollstaendig End-of-Life (keine Security-Fixes mehr), 8.2 ist die
  aelteste noch (Security-only) unterstuetzte Version. `.github/workflows/ci.yml`s PHP-Job testet
  jetzt ebenfalls 8.2 (vorher 8.3) — CI soll die tatsaechlich deklarierte Mindestversion pruefen,
  nicht eine hoehere, ungetestete Annahme.
- **`Tested up to` in `style.css` 6.8 -> 7.0 angehoben** (aktuelle WordPress-Stable-Version zum
  Entscheidungszeitpunkt). Wichtige Einschraenkung: das ist eine deklarierte
  Kompatibilitaets-Zielmarke, keine durch einen echten WP-Integrationstest verifizierte Aussage —
  dafuer fehlt weiterhin die `wp-env`-Infrastruktur (siehe "Test-/CI-Tooling"-Eintrag unten sowie
  `docs/to-do.md` Abschnitt 1). Sollte bei jeder groesseren WordPress-Version erneut angehoben
  werden, nicht als einmalig erledigt betrachtet werden.
- **Matrix dokumentiert in README.md** ("Anforderungen"), inkl. Node/pnpm-Versionen aus
  `.github/workflows/ci.yml`/`package.json`s `packageManager`-Feld als Kontext, auch wenn dafuer
  kein `Requires`-Feld existiert. `docs/to-do.md` Abschnitt 4 dadurch geloest und entfernt.

### Kein `CONTRIBUTING.md`: Alleinentwickler (2026-08-18)

`docs/to-do.md` Abschnitt 4 (Governance) fuehrte das Fehlen eines `CONTRIBUTING.md` bisher als
offenen, niedrig priorisierten Punkt, der relevant wuerde, sobald ein zweites Teammitglied
mitarbeitet. Endgueltig geklaert: es wird **kein** `CONTRIBUTING.md` geben — an der Basis arbeitet
dauerhaft nur eine Person (ggf. + Agent), kein Szenario mit zweitem Teammitglied geplant. Der
Abschnitt ist daher aus `to-do.md` entfernt statt weiter als "spaeter relevant" vorgemerkt.

### Komponenten-Showcase-Seite und Performance-Tooling: bewusst zurueckgestellt (2026-08-16)

`docs/to-do.md` Abschnitt 1 (Komponenten-Showcase-Seite) und Abschnitt 3
(Performance) sind auf spaeter zurueckgestellt, aktuell kein Arbeitsauftrag — beide Abschnitte
sind deshalb aus `to-do.md` entfernt, die verbleibenden Abschnitte
durchnummeriert. Performance haengt inhaltlich an der Showcase-Seite als Mess-/Scan-Ziel, daher
gemeinsam zurueckgestellt statt einzeln.

- **Komponenten-Showcase-Seite**: ein dev-only Template (z. B. `page-component-showcase.php`) mit
  einem Aufruf pro Base-Komponente inkl. wichtigster Varianten bleibt sinnvoll (manuelle visuelle/
  funktionale Kontrolle schon in Phase 1, spaeter Scan-Ziel fuer a11y/visuelle Regression/
  Performance), wird aber nicht vor dem naechsten groesseren Anlass gebaut. Weiterhin Voraussetzung
  fuer den a11y-Scan-Punkt in `docs/to-do.md` Abschnitt 1 (Testing &
  Qualitaetssicherung).
- **Font-Loading-Strategie**: aktuell irrelevant — `--font-accent` in `tokens.css` ist nur ein
  System-Font-Stack, keine echten Web-Fonts geladen (Phase 1 macht bewusst kein visuelles Styling,
  siehe `CLAUDE.md` Regel 1). Geplant, sobald Phase 2 echte Web-Fonts einfuehrt: Self-Hosting statt
  Google-Fonts-CDN, `font-display: swap` als Default in jedem `@font-face`, `<link rel="preload"
as="font" type="font/woff2" crossorigin>` nur fuer die tatsaechlich above-the-fold genutzte
  Schriftschnitt-Datei (per `wp_head`-Filter, nicht hart eincodiert), Variable Fonts bevorzugt vor
  mehreren Static-Weight-Files, Subsetting auf tatsaechlich genutzte Unicode-Ranges.
- **Core-Web-Vitals-Budget/Lighthouse-CI**: wird nicht isoliert aufgesetzt, sondern an dieselbe
  `wp-env`-Infrastruktur angedockt, die fuer den a11y-Scan sowieso vorgemerkt ist (siehe
  "Test-/CI-Tooling"-Eintrag unten) — beide brauchen eine echte gerenderte Seite als Ziel, die es
  erst mit der Showcase-Seite gibt. Geplante Reihenfolge, sobald relevant: Showcase-Seite bauen ->
  einmaliges `wp-env`-Setup in CI (a11y und Lighthouse gemeinsam nutzbar) -> `@lhci/cli` mit
  `lighthouserc.json` gegen die Showcase-Seite, zunaechst als nicht-blockierender Report-Schritt
  (`continue-on-error: true`, gleiches Muster wie `composer audit`/`pnpm audit` in `ci.yml`), bis
  in Phase 2 eine sinnvolle Baseline feststeht.

### Vitest-Tests fuer select.js/combobox.js/data-table.js: zwei jsdom-Grenzen (2026-08-14)

`docs/to-do.md` Abschnitt 1 listete `select.js`/`combobox.js`/`data-table.js`
als offene Luecke im Vitest-Aufbau aus dem 2026-08-13-Eintrag unten. Ergaenzt: `select.test.js`,
`combobox.test.js`, `data-table.test.js`, nach demselben Muster wie `toggle.test.js` (Markup direkt
in jsdom nachgebaut statt PHP zu rendern, siehe Kopfkommentare der Testdateien). Dabei zwei
wiederkehrende jsdom-Grenzen aufgetreten, die bei kuenftigen Enhancement-Modul-Tests direkt wieder
auftreten werden:

- **`Element.prototype.scrollIntoView` existiert in jsdom nicht** (jsdom implementiert kein Layout).
  `select.js`/`combobox.js` rufen es beim Aktivieren eines Listbox-Items auf. Gemeinsamer No-op-Stub
  in `assets/js/test-setup.js`, eingehaengt ueber `vitest.config.js`s `setupFiles` — global fuer alle
  Suiten, nicht pro Testdatei dupliziert.
- **`window.location.assign()`/`.reload()` lassen sich in jsdom nicht per `vi.spyOn()` abfangen** —
  beide sind nicht-konfigurierbare, nicht schreibbare Own-Properties auf dem `Location`-Objekt
  (`Object.getOwnPropertyDescriptor` zeigt `configurable: false, writable: false`), nicht normale
  Prototype-Methoden. `data-table.test.js`s Fallback-Tests (Fetch schlaegt fehl / Response hat zu
  wenige Tabellen / `popstate`-Re-Fetch schlaegt fehl) weisen den jeweiligen Catch-Zweig deshalb
  indirekt nach: `fetch` wurde mit der erwarteten URL aufgerufen, aber `history.pushState()` blieb
  aus und der Tabelleninhalt aendert sich nicht — das schliesst den Erfolgspfad aus, ohne den
  Navigations-Aufruf selbst zu spyen. Kommentar dazu direkt in `data-table.test.js`.

### Test-/CI-Tooling: Brain Monkey statt wp-env, Vitest statt Jest (2026-08-13)

`docs/to-do.md` Abschnitt 1 forderte CI, einen Pre-Commit-Hook und
automatisierte Tests. Umgesetzt: `.github/workflows/ci.yml` (zwei Jobs, PHP/JS getrennt), `husky` +
`lint-staged` (`.husky/pre-commit`), PHPUnit + `brain/monkey` (`tests/Unit/HelpersTest.php`) und
Vitest (`vitest.config.js`, `assets/js/template-parts/base/toggle.test.js`). Siehe `CLAUDE.md`
Regel 11 fuer die Befehle.

- **Brain Monkey statt eines vollen `wp-env`/WP-PHPUnit-Testcontainers** fuer die PHP-Unit-Suite:
  `inc/template-parts/helpers.php` ruft nur eine Handvoll WP-Funktionen auf (`esc_attr()`,
  `esc_html()`, `_doing_it_wrong()`, `get_template_part()`), braucht dafuer aber keine echte
  WordPress-Installation samt Datenbank in CI — Brain Monkey stubt genau diese Funktionen pro Test.
  Ein echter `wp-env`-Container waere fuer den aktuellen Umfang (reine Logik-Helper) unverhaeltnismaessig
  schwer (Docker, MySQL, WP-Core-Checkout) und bleibt Vormerkung fuer den Tag, an dem die (aktuell
  zurueckgestellte) Komponenten-Showcase-Seite (siehe Eintrag oben) sowie die a11y-/
  Visual-Regression-Punkte aus `docs/to-do.md` Abschnitt 1 tatsaechlich
  eine echte laufende WP-Instanz brauchen (axe-core/Playwright gegen eine gerenderte Seite, nicht
  gegen isolierte PHP-Funktionen) — dann lohnt sich `wp-env` fuer diesen Zweck, nicht als Ersatz
  fuer die schnelle Brain-Monkey-Suite.
- **`hengegroup_theme_render_icon()`/`hengegroup_theme_render_image()` bewusst nicht in dieser Suite getestet**:
  beide puffern `get_template_part()`-Aufrufe gegen echte Template-Dateien (`template-parts/base/
icon.php`/`image.php`) — Brain Monkey kann `get_template_part()` stubben, aber dann wird nicht
  mehr das echte Rendering getestet, nur noch, dass der Helper die Funktion aufruft. Ein
  aussagekraeftiger Test dafuer ist ein WP-gestuetzter Integrationstest, kein Unit-Test-Fall.
- **Vitest statt Jest** fuer die JS-Unit-Suite: teilt sich Vite/`vite.config.js`s Toolchain, die das
  Projekt fuer den Asset-Build ohnehin schon hat (kein zweites Bundler-/Transform-Setup noetig),
  eigene `vitest.config.js` mit `environment: "jsdom"` fuer DOM-Zugriff in den Tests.
- **`husky` + `lint-staged` statt `simple-git-hooks`**: etabliertere, staerker dokumentierte
  Kombination fuer ein Vorlagen-Repo, das andere Personen uebernehmen — `simple-git-hooks` waere
  minimal leichter, aber weniger vertraut. `.husky/pre-commit` ruft `lint-staged` auf, das
  `prettier --write` (alle staged `*.{css,js,json,md,php}`) und `phpcs` (staged `*.php`, echtes
  Gate statt reinem Autofix, da die aktiven WPCS-Sniffs aus Regel 11 -- `strict_types`, Escaping,
  sanitisierte Datei-Zugriffe -- nicht automatisch fixbar sind) ausfuehrt.
- **CI in zwei unabhaengigen Jobs (PHP/JS)** statt einem gemeinsamen: ein kaputter `pnpm audit`
  o. ae. im JS-Toolchain soll den PHP-Lint-/Test-Status nicht verdecken und umgekehrt.
- **a11y-Scan (axe-core/Playwright) und Visual-Regression bewusst nicht Teil dieser Runde** — beide
  brauchen eine echte gerenderte Seite als Ziel, die es erst mit der (aktuell zurueckgestellten)
  Komponenten-Showcase-Seite gibt (siehe "Komponenten-Showcase-Seite und Performance-Tooling"-Eintrag
  oben). Vormerkung, sobald diese existiert.

### Mehrsprachigkeit ueber Multisite statt Hreflang-Plugin (2026-08-13)

Mehrsprachigkeit ist ueber WordPress Multisite geplant (ein Netzwerk-Standort pro Sprache), nicht
ueber ein Single-Site-Plugin wie WPML/Polylang. `inc/setup/theme-seo-output.php` liefert deshalb
noch kein `hreflang`-Markup — das braucht eine eigene, gegen das tatsaechliche Netzwerk-Setup
gebaute URL-Zuordnung (welcher Standort entspricht welcher Sprache, welche Seite dort ist das
Pendant zur aktuellen), die sich sinnvoll erst bauen laesst, sobald das Netzwerk existiert.
Vormerkung fuer dann. Siehe Kopfkommentar von `theme-seo-output.php`.

### Kein RTL-Support (2026-08-13)

`dir="rtl"` wird bewusst nicht unterstuetzt — dieses Projekt braucht nie RTL-Sprachen. Kein offener
Punkt, keine Vormerkung noetig.

### Start-CSP bewusst locker, keine harte Absicherung out of the box (2026-08-13)

`inc/setup/theme-hardening.php` liefert auf dem Front-End eine Start-Content-Security-Policy, die
effektiv alles ueber `https:` sowie Inline-Scripts/Styles/`eval` erlaubt (ausser
`frame-ancestors 'self'`, das schon von Anfang an sinnvoll restriktiv ist). Ziel ist nicht Schutz
ab Werk, sondern eine fertige Direktiven-Struktur, die ein Projekt-Theme gegen seine tatsaechlich
genutzten Hosts (Fonts, WooCommerce/Stripe/PayPal, Embeds, ...) verschaerft, ohne bei
Theme-Uebernahme sofort etwas zu brechen. Die Baseline-Security-Header (`X-Content-Type-Options`,
`Referrer-Policy`, `X-Frame-Options`, `Permissions-Policy`) daneben sind dagegen ohne Trade-off
scharf, weil sie nichts erlauben/verbieten, was ein Plugin/Core-Screen brechen koennte — laufen
deshalb zusaetzlich auf `wp-admin`/Login, die CSP nur auf dem Front-End. Siehe Kopfkommentar von
`theme-hardening.php`.

### Dependency-Audits: automatisiert bei jedem CI-Lauf, aber weiterhin kein blockierendes Gate (2026-08-13, aktualisiert)

`composer audit`/`pnpm audit` laufen seit `.github/workflows/ci.yml` (siehe
"Test-/CI-Tooling"-Eintrag oben) automatisch bei jedem Push/PR mit, zusaetzlich weiterhin auf
Zuruf lokal (siehe `CLAUDE.md` Regel 11) — beides bewusst als `continue-on-error`/reiner Report,
nicht als Schritt, der einen Build/PR blockiert. Urspruenglich (siehe erste Fassung dieses
Eintrags) war das Zurueckstellen eines Audit-Gates noch mit "es gibt noch keine CI-Pipeline"
begruendet; die Pipeline existiert jetzt, das Gate bleibt trotzdem bewusst aus — ein Advisory in
einer transitiven Dev-Dependency erzwingt nicht automatisch ein sofortiges Update (Breaking
Changes, fehlender Patch, false positive), das rechtfertigt noch keinen roten Build. Funde werden
weiterhin manuell per Versions-Update behoben, wie zuletzt bei `vite`/`@tailwindcss/vite`/
`immutable` (siehe CHANGELOG "Fixed"-Eintrag).

### Dokumentations-Struktur: `entscheidungen.md`/`how-to.md`/`to-do.md` statt zwei kombinierter Dateien (2026-08-16)

`docs/entscheidungen-und-howtos.md` (Entscheidungen + HowTos in einer Datei) und
`docs/luecken-und-empfehlungen.md` (offene Punkte) wurden aufgeteilt in drei fokussierte Dateien:
diese Datei (`docs/entscheidungen.md`, nur Entscheidungen), `docs/how-to.md` (nur Anleitungen) und
`docs/to-do.md` (nur offene/noch nicht entschiedene Punkte, gleicher Inhalt/gleiche
Abschnittsnummerierung wie zuvor `luecken-und-empfehlungen.md`). Grund: die beiden vorherigen
Dateien mischten pro Datei zwei unterschiedliche Lesarten (bereits Entschiedenes vs. noch offene
Fragen bzw. Entscheidung vs. Anleitung), was das Auffinden erschwerte, je feingranularer das Log
wurde. `CLAUDE.md` selbst bleibt unveraendert die einzige automatisch geladene, normative
Anweisungsdatei — nur die Verweise auf die alten Dateinamen in Regel 11/12 wurden auf die drei neuen
Dateien umgestellt, der bindende Regel-Inhalt selbst ist nicht verschoben worden.
