# Changelog

Format nach [Keep a Changelog](https://keepachangelog.com/de/1.1.0/). Version in `package.json`,
`pnpm version` spiegelt sie nach `style.css`.

## [Unreleased]

### Added

- Header, Footer (Einstellungen > Footer), 404 nach Design.
- Bloecke: Buehne, Ueberschrift & Text, Produkte, Produktkategorie, Kontakt, Anwendungsgruppe,
  Offene Stellen, Karriere-Teaser, Stellen-Liste, Benefits, Auszeichnungen, Galerie.
- Produktbereich: Uebersicht /produkte/, Detailseite (Technische Daten, Koernungen, Anwendungen,
  Downloads, Anfrage, verwandte Produkte), Anwendungen-Uebersicht /anwendungen/.
- Stellenangebote mit Google-Jobs-JSON-LD, Karriereseite, Bewerbungsformular.
- Produktanfragen und Bewerbungen als Backend-Eintraege.
- Komponenten `product-card`, `contact-card`, Formularfelder ueber die Field-Komponenten.
- Styling aller Base-Komponenten, Marken-Tokens, Karten-Tokens.
- SEO-Metabox, Sitemap-Anpassungen, SVG-Upload mit Sanitizing.

### Changed

- Backend aufgeraeumt (ungenutzte WooCommerce-Menues, Datei-Editor, Versand aus).

### Removed

- Windows-/PowerShell-Skripte, Vorlagen-Skripte (`rename-theme.sh`, `pull-base-updates.sh`), `archive-product.php`, `archive-stellenangebote.php`, `woocommerce.php`.

## [1.0.0] - 2026-08-12

Erste Version: Base-Komponenten, WordPress-/WooCommerce-Grundgeruest, Vite-Build, Deploy-, i18n- und
Icon-Skripte, Lint/Format-Tooling.

[Unreleased]: https://github.com/awortmeier/wordpress-hengegroup-theme/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/awortmeier/wordpress-hengegroup-theme/releases/tag/v1.0.0
