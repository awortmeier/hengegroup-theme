# Changelog

Alle nennenswerten Aenderungen an dieser Basis-Vorlage werden hier festgehalten.

Format angelehnt an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/), Versionierung nach
[Semantic Versioning](https://semver.org/lang/de/). Die Versionsnummer ist die Single Source of
Truth in `package.json` (`version`) und wird per `pnpm version <patch|minor|major>` automatisch
nach `style.css` (`Version:`-Header) gespiegelt — siehe README "Versionierung".

## [Unreleased]

### Added

- macOS/Linux-Pendants (`scripts/*.sh`) zu allen zehn `scripts/*.ps1`-Skripten (`build`, `clean`,
  `deploy`, `deploy-changed`, `i18n-make-pot`, `pull-base-updates`, `rename-theme`,
  `sync-lucide-icons`, `sync-tabler-icons`, `sync-theme-tokens`, `sync-theme-version`) — bislang
  ohne separat installierte PowerShell Core auf macOS/Linux nicht lauffaehig. `package.json`
  "scripts" rufen jetzt `node scripts/run.mjs <name>` auf, das anhand von `process.platform`
  automatisch die passende Fassung waehlt. `deploy.sh`/`deploy-changed.sh` zeigen dabei in einem
  echten Terminal eine `Write-Progress`-Entsprechung (sich ueberschreibende Fortschrittszeile).
  Siehe `docs/entscheidungen.md` fuer Details.
- Security-Header (`inc/setup/theme-hardening.php`): `X-Content-Type-Options`, `Referrer-Policy`,
  `X-Frame-Options`, `Permissions-Policy` auf jedem Request (Front-End/wp-admin/Login) sowie eine
  bewusst lose Start-Content-Security-Policy nur auf dem Front-End (Direktiven-Geruest zum
  Verschaerfen pro Projekt, siehe Kopfkommentar der jeweiligen Funktion). `pnpm audit`-Skript
  ergaenzt (`composer audit` ist bereits ein eingebauter Composer-Befehl, kein eigenes Skript
  moeglich). Schliesst `docs/to-do.md` Punkt 4 (Security-Header/Dependency-
  Audit-Teile).
- `.github/workflows/template-init.yml`: automatisiert Schritt 2 von "Neues Projekt aus dieser
  Vorlage starten" (`scripts/rename-theme.ps1`) fuer Repos, die per GitHub "Use this template"
  erzeugt werden — leitet den Slug aus dem neuen Repo-Namen ab, committet das Ergebnis und
  entfernt sich danach selbst. Setzt voraus, dass dieses Repo unter Settings als "Template
  repository" markiert ist.
- SEO-/Meta-Grundgerüst ohne SEO-Plugin (`inc/setup/theme-seo-admin.php`,
  `inc/setup/theme-seo-output.php`, `assets/js/admin/theme-seo.js`): eigenes "Settings > SEO"
  fuer site-weite Standardwerte (Titel-Vorlage, Meta-Beschreibung, Social-Bild, Twitter-Handle,
  Indexierung) plus eine "SEO"-Metabox auf Beitraegen/Seiten (`hengegroup_theme_seo_post_types`-Filter
  fuer weitere Post-Types) fuer Seiten-spezifische Ueberschreibungen — leer gelassen greift immer
  der site-weite Standard. Rendert `<title>`, Meta-Description, Canonical, Robots-Meta,
  Open-Graph-/Twitter-Card-Tags sowie ein JSON-LD-Organization-Schema; Letzteres ueber den
  `hengegroup_theme_seo_structured_data`-Filter erweiterbar (Vormerkung fuer spaeter: Product-/
  JobPosting-Schema auf entsprechenden CPT-Templates). `robots.txt`/XML-Sitemap bleiben bewusst
  WordPress-Core ueberlassen, `hreflang` bewusst ausgeklammert bis zur geplanten Multisite-basierten
  Mehrsprachigkeit (siehe Kopfkommentar von `theme-seo-admin.php`/`theme-seo-output.php` sowie
  `docs/entscheidungen.md`). Schliesst `docs/to-do.md` Punkt 3.
- `docs/entscheidungen.md`/`docs/how-to.md`: zentraler Log fuer bewusste Architektur-/Prozess-
  entscheidungen (Warum) und praktische Erweiterungs-Anleitungen (Wie, z. B. weiteres JSON-LD-
  Schema per `hengegroup_theme_seo_structured_data`-Filter ergaenzen) — bisher nur verstreut in
  Kopfkommentaren/CHANGELOG dokumentiert. `CLAUDE.md` Regel 12 macht das Pflegen dieser Dateien
  verbindlich fuer kuenftige Entscheidungen/HowTos. Spaeter (siehe letzter Eintrag unten) aus einer
  gemeinsamen `docs/entscheidungen-und-howtos.md` in zwei fokussierte Dateien aufgeteilt.
- CI + Test-Tooling (`.github/workflows/ci.yml`, `composer test`, `pnpm test`,
  `.husky/pre-commit`): `composer lint`/`composer test` (PHPUnit + `brain/monkey`, neue
  `tests/Unit/HelpersTest.php` gegen die reinen Logik-Helper aus `inc/template-parts/helpers.php`)
  sowie `pnpm format:check`/`pnpm test` (Vitest, neue
  `assets/js/template-parts/base/toggle.test.js`) laufen jetzt automatisiert bei jedem Push/PR
  (zwei Jobs, PHP/JS getrennt), plus `composer audit`/`pnpm audit` als nicht-blockierender
  Report-Schritt. `husky` + `lint-staged` fuehren `prettier --write`/`phpcs` zusaetzlich lokal bei
  jedem `git commit` aus (via `pnpm install`s `prepare`-Skript automatisch eingerichtet). Schliesst
  `docs/to-do.md` Abschnitt 1 groesstenteils (CI, Pre-Commit-Hook,
  Unit-Test-Grundgerüst) — a11y-Scan und Visual-Regression bleiben offen, siehe
  `docs/entscheidungen.md`.
- `docs/entscheidungen.md`, `docs/how-to.md`, `docs/to-do.md`: `docs/entscheidungen-und-howtos.md`
  (Entscheidungen + HowTos gemischt) und `docs/luecken-und-empfehlungen.md` (offene Punkte) durch
  drei fokussierte Dateien ersetzt — je eine pro Themenbereich, damit "bereits entschieden" und
  "noch offen" bzw. "Warum" und "Wie" nicht mehr in derselben Datei vermischt sind. `CLAUDE.md`
  selbst bleibt unveraendert die einzige automatisch geladene Anweisungsdatei, nur die Verweise in
  Regel 11/12 wurden auf die drei neuen Dateien umgestellt. Siehe `docs/entscheidungen.md` fuer die
  Begruendung.

- `docs/tastatur-screenreader-testplan.md`: manuelle Checkliste (Tab-Reihenfolge, Escape/
  Pfeiltasten/Type-Ahead) pro interaktiver Base-Komponente, abgeleitet aus dem tatsaechlich
  implementierten Tastaturverhalten der jeweiligen `assets/js/template-parts/base/*.js`-Datei.
  Schliesst den Testplan-Teil von `docs/to-do.md` Abschnitt 2 — die tatsaechliche Ausfuehrung
  bleibt offen, siehe `docs/entscheidungen.md`.
- WP-/PHP-Versionsmatrix dokumentiert (README "Anforderungen"): `style.css`s `Requires PHP` 8.1 ->
  8.2 (aelteste noch mit Security-Fixes unterstuetzte PHP-Version), `Tested up to` 6.8 -> 7.0
  (aktuelle WordPress-Version), `composer.json` bekommt erstmals ein explizites `require.php`.
  `.github/workflows/ci.yml`s PHP-Job testet entsprechend jetzt 8.2 statt 8.3, damit CI die
  tatsaechlich deklarierte Mindestversion prueft. Schliesst `docs/to-do.md` Abschnitt 4. Siehe
  `docs/entscheidungen.md` fuer die Einordnung von "Tested up to" als deklarierte Zielmarke, nicht
  als verifizierte Aussage.

### Fixed

- `build.ps1`/`build.sh` kopierten Top-Level-Theme-Dateien bislang ueber eine fest enumerierte
  Liste (`style.css`, `functions.php`, ... `theme.json`) — ein neues Custom-Page-Template nach
  WordPress-Template-Hierarchie (z. B. `page-{slug}.php`) landete dadurch nie in `dist/`. Beide
  Fassungen kopieren jetzt jede Top-Level-`*.php`-Datei automatisch per Wildcard mit, statt die
  Liste bei jedem neuen Template von Hand nachzuziehen. Siehe `docs/entscheidungen.md`.
- Composer-/pnpm-Dependencies aktualisiert (`composer update`/`pnpm update`, u. a. `phpunit` 13.3.0
  -> 13.3.1, `prettier` 3.8.1 -> 3.9.6, `lucide-static` 1.28.0 -> 1.31.0), alles innerhalb der
  bestehenden Versions-Ranges. Lint/Test/Audit danach erneut gruen.
- Von `pnpm audit` gefundene Sicherheitsluecken in Dev-Dependencies behoben: `vite` 5.4 -> 8.2,
  `@tailwindcss/vite`/`tailwindcss` 4.1 -> 4.3 (gleicher Peer-Dependency-Bereich), dadurch
  automatisch gepatchte transitive `esbuild`/`postcss`/`nanoid`. `immutable` (transitiv ueber
  `vite`s ungenutzte optionale `sass`/`sass-embedded`-Peer-Deps) per neuem
  `pnpm-workspace.yaml`-`overrides`-Eintrag auf die gepatchte Version erzwungen.
  `vite.config.js`s `__dirname` auf `import.meta.dirname` umgestellt (Vite-8-Deprecation-Warning).
  Build und `pnpm audit` danach verifiziert.

## [1.0.0] - 2026-08-12

Erste versionierte Basis, mit Git-Historie ab diesem Stand (siehe README "Prozess & Versionierung").

### Added

- Erstes Git-Repository fuer die Basis-Vorlage (vorher unversioniert), inkl. Remote auf
  `github.com/awortmeier/wordpress-hengegroup-theme`.
- ~90 generische, projektunabhaengige Base-Komponenten unter `template-parts/base/` (Phase 1 laut
  `CLAUDE.md`: Markup, Config-API, Verhalten, Barrierefreiheit — noch ohne visuelles Styling).
- Progressive-Enhancement-JS fuer die interaktiveren Komponenten unter
  `assets/js/template-parts/base/` (u. a. `select`, `combobox`, `dropdown-menu`, `calendar`,
  `data-table`, `carousel`, `dialog`, `tabs`/`toggle`-ARIA-Schliessung).
- WordPress-/WooCommerce-Grundgerüst (`inc/setup/`), Vite-Build (`vite.config.js`), FTP-Deploy-
  Skripte (`scripts/deploy*.ps1`), i18n-Tooling, Icon-Sync (Lucide/Tabler).
- `scripts/rename-theme.ps1` zum Bootstrappen neuer Projekt-Themes aus dieser Vorlage.
- WPCS-Lint (`composer lint`) + Prettier-Formatierung als Tooling-Grundlage (siehe `CLAUDE.md`
  Regel 11).
- `LICENSE` (proprietaer/All Rights Reserved).

[Unreleased]: https://github.com/awortmeier/wordpress-hengegroup-theme/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/awortmeier/wordpress-hengegroup-theme/releases/tag/v1.0.0
