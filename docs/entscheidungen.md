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
