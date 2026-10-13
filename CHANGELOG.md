# Changelog

Format nach [Keep a Changelog](https://keepachangelog.com/de/1.1.0/). Version in `package.json`,
`pnpm version` spiegelt sie nach `style.css`.

## [Unreleased]

### Added

- Header, Footer (Einstellungen > Footer), 404 nach Design.
- Bloecke: Buehne, Buehne (statisch), Textabschnitt, Produkte, Produktkategorie, Kontakt, Anwendungsgruppe,
  Offene Stellen, Karriere-Teaser, Stellen-Liste, Benefits, Auszeichnungen, Galerie, Historie, Personen,
  Kennzahlen, Unternehmen der Gruppe.
- Benefits wahlweise als Karten (Unternehmensseite), neue Icons Entwicklung und Nachhaltigkeit.
- Hauptmenue: Punkte mit Unterpunkten und echter URL bleiben Links zur Seite, das Dropdown oeffnet per
  Hover oder ueber einen eigenen Chevron-Button; Punkte mit "#" bleiben reine Dropdowns.
- Benefits mit HTML-Anker (Erweitert > HTML-Anker); Sprungziele halten Abstand zum Sticky-Header.
- Produktbereich: Uebersicht /produkte/, Detailseite (Technische Daten, Koernungen, Anwendungen,
  Downloads, Anfrage, verwandte Produkte), Anwendungen-Uebersicht /anwendungen/.
- Stellenangebote mit Google-Jobs-JSON-LD, Karriereseite, Bewerbungsformular.
- Produktanfragen und Bewerbungen als Backend-Eintraege.
- Komponenten `product-card`, `contact-card`, Formularfelder ueber die Field-Komponenten.
- Styling aller Base-Komponenten, Marken-Tokens, Karten-Tokens.
- SEO-Metabox, Sitemap-Anpassungen, SVG-Upload mit Sanitizing.
- Inline-Format "Akzent-Schrift" als eigener Toolbar-Button in "Textabschnitt", den Bereichsueberschriften
  der Bloecke sowie Core-Ueberschrift und -Absatz.

### Changed

- Backend aufgeraeumt (ungenutzte WooCommerce-Menues, Datei-Editor, Versand aus).
- Produktraster der Bloecke und verwandte Produkte laufen ueber eine Funktion
  (`hengegroup_theme_render_product_cards()`).
- Buehne: "Folie" heisst in Editor, Frontend-Labels und Code durchgehend "Slide".
- Sektionsabstaende einheitlich ueber `section-spacing`/`section-spacing-lg`, Footer im `.wrapper`-Raster,
  Footer-Links ohne Hover-Farbe.
- Anfrage- und Bewerbungsformular teilen sich die Huelle `components/request-form` (Banner,
  Spam-Schutz, Datenschutz, Absenden); die Anfrage fokussiert jetzt auch ihre Statusmeldung.
- Wiederkehrende Klassenketten als Utilities `section-dark`, `brand-gradient`, `rich-text`
  (`app.css`); Kopf von Produktkategorie und Anwendungsgruppe als Komponente `section-intro`.
- Bei `prefers-reduced-motion` laufen Transitionen und Animationen sofort durch, Carousel-Navigation
  scrollt ohne Smooth-Scrolling.
- Seiten, die mit einer Buehne beginnen, zeigen den Seitentitel nur noch fuer Screenreader bzw. gar
  nicht, wenn die statische Buehne die h1 liefert.
- Buehne: Pause-Button fuer Autoplay, aktiver Dot mit `aria-current`, Hover/Tastaturfokus auf den
  Dots haelt den Wechsel an, Dot-Klick startet das Intervall neu.

### Removed

- Windows-/PowerShell-Skripte, Vorlagen-Skripte (`rename-theme.sh`, `pull-base-updates.sh`), `archive-product.php`, `archive-stellenangebote.php`, `woocommerce.php`.

## [1.0.0] - 2026-08-12

Erste Version: Base-Komponenten, WordPress-/WooCommerce-Grundgeruest, Vite-Build, Deploy-, i18n- und
Icon-Skripte, Lint/Format-Tooling.

[Unreleased]: https://github.com/awortmeier/wordpress-hengegroup-theme/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/awortmeier/wordpress-hengegroup-theme/releases/tag/v1.0.0
