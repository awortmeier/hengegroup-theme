# Changelog

Format nach [Keep a Changelog](https://keepachangelog.com/de/1.1.0/). Version in `package.json`,
`pnpm version` spiegelt sie nach `style.css`.

## [Unreleased]

### Added

- Header, Footer (Einstellungen > Footer), 404 nach Design.
- Bloecke: Buehne, Buehne (statisch), Textabschnitt, Produkte, Produktkategorie, Kontakt, Anwendungsgruppe,
  Offene Stellen, Karriere-Teaser, Stellen-Liste, Benefits, Auszeichnungen, Galerie, Historie, Personen,
  Kennzahlen, Unternehmen der Gruppe, Bild & Text, Ansprechpartner, Standorte.
- Bild & Text: Bild links/rechts, hell/grau/dunkel, Text als Absaetze, Zwischenueberschriften und
  nummerierte Listen (01, 02, ...).
- Kontakt: Akzentfarbe der Karte und eigene Kontaktdaten statt Einstellungen > Footer.
- Offene Stellen: eigener Ansprechpartner statt Karriere > Einstellungen und Hinweistext ohne Stellen.
- Karriere-Teaser: Hinweistext ohne Stellen im Block pflegbar.
- Stellen-Liste: Ueberschrift im Block aenderbar.
- Produktkategorie und Anwendungsgruppe: Kicker, Farbe, Ueberschrift(-Element) und Text im Block
  ueberschreibbar.
- Kontaktkarte und Ansprechpartner wahlweise mit Porträt links neben der Karte.
- Kachel-Bloecke (Ansprechpartner, Standorte, Auszeichnungen, Unternehmen, Personen, Benefits-Karten)
  waehlbar in voller Breite oder im 4er-Raster; Akzentfarben heissen Blau, Gruen, Grau, Dunkelgrau.
- Offene Stellen ohne Abstand nach oben.
- Sektionsabstaende nur noch ueber section-spacing, -top und -bottom; header-offset fuer Seiten
  ohne Buehne (404, Produkt, Stellenanzeige).
- Karriere-Teaser: Filter auf ein Unternehmen (dann ohne Unternehmens-Badge) und dunkle Variante.
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

- Alle Karten (ausser der Produktbox, inkl. Download-Karte) laufen ueber `card.php` mit den Varianten Weiss,
  Einfarbig, Zweigeteilt, Transparent und Glas, Akzentfarbe, Farbrand links und einheitlichem Padding.
- Kontaktkarte, Standort, Ansprechpartner und die Inhaltsbox der Buehne nutzen `card.php`.
- Karten-Bloecke (Benefit, Person, Unternehmen, Historie-Eintrag, Auszeichnung, Standort,
  Ansprechpartner) waehlen ihre Variante je Karte beim Hinzufuegen und in der Werkzeugleiste.
- Karten sind entweder ganz klickbar (dann mit einheitlichem Hover und Fokusring) oder nur ihre
  Buttons; Unternehmen und Auszeichnungen ohne Button-Text verlinken die ganze Karte. Die
  Download-Karte hat keinen Hover mehr.
- Kein Abstand mehr zwischen Karten-Titel und Subline, keine leere Kopfzeile ohne Subline; bei
  Karten mit Icon steht die Subline neben dem Icon. Abstaende in Karten kommen nur noch aus `gap`.
- Karten: Klassen in `card-classes.json` fuer PHP und Editor; Logo/Siegel als Leading-Visual
  (Unternehmen, Auszeichnung), Badge als Kicker (Buehne); ungenutzte Optionen (`size`, `action`,
  `media_badge`, `footer_divider`) entfernt.
- Karten haben nur noch eine Schattenstufe (`card-raised`); `elevation` und die Tokens `card-flat`
  und `card-hover` entfallen.
- Kind-Bloecke holen Grundwerte ihrer Karte aus `hengegroup_theme_get_block_card_config()`;
  `hengegroup_theme_get_accent_classes()` liefert zusaetzlich die Textfarbe (`fg`) und ersetzt
  `hengegroup_theme_get_variant_background_class()`.
- Benefits: die Darstellung Liste/Karten des Blocks entfaellt, bestehende Benefits behalten ihr
  Aussehen.

- Buehne: Play/Pause-Button sitzt rechts unten statt in der Dot-Leiste.
- Produkte bekommen ein Unternehmen statt Badge-Text und -Farbe; Badge, Anwendungsbereiche und
  Senden-Button der Produktseite tragen dessen Farbe, ebenso der Bewerben-Button einer Stelle. Bestehende Badges werden einmalig uebernommen.
- Unternehmen ist ein eigener Menuepunkt unter Stellenangebote statt Untermenue von Stellen und Produkten.
- Kontaktkarte der Produktseite als Split-Karte: oben Firmenfarbe, unten Adresse des Unternehmens
  (neues Feld, sonst Footer-Adresse).
- Textabschnitt: Abstand unten abschaltbar (Seitenleiste > Abstaende).
- Block "Ansprechpartner": graue Sektion mit Split-Kontaktkarten (oben Name/Funktion in der
  Akzentfarbe, unten E-Mail/Telefon/Fax).
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
