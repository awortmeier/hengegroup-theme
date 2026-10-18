# CLAUDE.md

## Projekt

PHP-WordPress-Theme fuer die fertige Hengegroup-Website: generische Bausteine in
`template-parts/base/` (nach shadcn/ui), projektspezifische Teile in `template-parts/components/`,
Seiteninhalte als Gutenberg-Bloecke in `template-parts/blocks/`.

Technik nach bester UX/DX waehlen (JS, Bibliotheken, Build-Tools sind erlaubt). Native
HTML-Semantik ist ein guter Start, aber keine Pflicht.

## Styling

- **Ausschliesslich Tailwind** (inkl. Arbitrary Values und Tokens aus `assets/css/tokens.css`). Kein
  eigenes CSS/SCSS, keine `style`-Attribute fuer Optik. Ausnahme nur, wenn nachweislich nicht mit
  Tailwind machbar -- dann im Code kurz begruenden.
- `data-slot`/`data-variant`/`data-size`/`data-state` als stabile Hooks fuer JS und Tests setzen.

## Base-Komponenten

- Config-API nach shadcn/ui (Vokabular uebernehmen, snake_case). Gemeinsame Keys der
  Formular-Familie konsistent halten (`disabled`, `aria_invalid`, `required`, gespiegelt als
  `data-disabled`/`data-invalid`).
- Bestehende Komponenten komponieren statt Markup duplizieren; gemeinsame Logik nur in
  `inc/template-parts/helpers.php` (Praefix `hengegroup_theme_`), vorher dort nachsehen.
- Jede Datei: `declare(strict_types=1);`, Guard `if (!isset($args['config']) ...) return;`, Enums per
  Allow-List mit stillem Default-Fallback, Passthrough `class`/`attributes`/`data_attributes`
  (strukturelle Attribute wie `data-slot`/`role` werden danach gesetzt und gewinnen).
- Ausgabe nur ueber `esc_html()`/`esc_attr()`; vorab escapte Strings mit `phpcs:ignore` markieren.
- Pfade aus Config (`icon.php`, `image.php`) per Whitelist-Regex pruefen.
- a11y gehoert zur API (`aria_label` bei icon-only, `decorative`, `aria-busy`, ...). Weicht die
  ARIA-Rolle wegen nativer Elemente von shadcn ab, sofort per JS schliessen (Vorbild `toggle.js`).
- Mehrere Dateien -> Unterordner `template-parts/base/<name>/`. JS liegt unter
  `assets/js/template-parts/<gleicher Pfad>.js` (Base-JS flach), exportiert `init<Name>()`,
  registriert in `assets/js/app.js`. DOM ist die einzige Datenquelle; wiederholtes Markup per
  `<template>` klonen.
- Projektspezifisches gehoert nach `template-parts/components/` bzw. `template-parts/blocks/`, nicht
  nach `base/`.

## Gutenberg-Bloecke

- Alles, was Redakteure pflegen, ist direkt im Block-Editor einstellbar (Canvas oder Inspector),
  nicht in Theme-Code, Customizer oder Options-Seiten.
- Ausnahme: Wird der Inspector dadurch unuebersichtlich, Optionen gruppieren (`PanelBody`,
  standardmaessig zugeklappt) oder selten genutzte auf sinnvolle Defaults festlegen.

## Kommentare und Doku

So knapp wie moeglich. Code, der sich selbst erklaert, bekommt keinen Kommentar.

- **Kommentieren nur das Warum**, das nicht im Code steht: Workarounds, Browser-/WP-/WC-Eigenheiten,
  Sicherheitsgruende, bewusst ungewoehnliche Loesungen. Ein bis zwei Zeilen.
- **Nicht kommentieren**: was der Code tut, Historie ("vorher", "Bugfix vom ...", "auf Wunsch"),
  Daten, Herkunft aus shadcn/Design, Verweise auf Doku/Phasen/Regeln, Aufzaehlungen von Aufrufern,
  verworfene Alternativen.
- **Dateikopf** einer Komponente: ein Satz Zweck plus die `config`-Keys, je eine Zeile
  (`key  typ  werte (default)`), Erklaerung nur wenn nicht offensichtlich. Andere Dateien: Kopf nur,
  wenn der Zweck nicht aus Name und Code folgt.
- **Docblocks** nur, wenn Name und Typen nicht reichen. Keine `@param`/`@return`, die nur die
  Signatur wiederholen.
- **Doku-Dateien**: `docs/entscheidungen.md` (Entscheidungen, die nicht aus dem Code folgen, je
  ein bis drei Zeilen, nach Thema; veraltete ersetzen statt "Nachtrag" anhaengen), `docs/how-to.md`
  (Pflege/Erweiterungspunkte), `docs/to-do.md` (nur Offenes, Erledigtes loeschen), `CHANGELOG.md`
  (was sich geaendert hat, ein Satz pro Punkt). Keine neuen Doku-Dateien, keine Audit-/Berichtsdateien
  im Repo. Nichts doppelt: Infos stehen an genau einer Stelle.

## Tooling

- Einmalig `composer install` und `pnpm install` (richtet den Pre-Commit-Hook ein: Prettier +
  phpcs auf staged Dateien).
- Vor Commit/PR: `composer lint`, `composer test`, `pnpm format:check`, `pnpm test`. Dieselben Checks
  laufen in CI. Fehler beheben, nicht ignorieren.
- Formatierung macht Prettier (`@prettier/plugin-php`), phpcs prueft nur strict_types, Escaping und
  Input-Sanitizing.

## Dev-Server (dev.hengegroup.com)

Direkt nutzen statt nach Screenshots zu fragen.

- `ssh hengegroup`, WordPress-Root `/dev.hengegroup.com/httpdocs`, Theme
  `wp-content/themes/hengegroup`.
- wp-cli: `php /tmp/wp-cli.phar ...` im WP-Root; fehlt die Datei, neu laden von
  `https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar`.
- Lesen jederzeit. Schreiben nur im Rahmen des Auftrags, nichts ungefragt loeschen.
- Theme-Code immer erst im Repo, Deploy per `pnpm deploy:changed`. Ein `scp` einzelner Dateien zum
  Testen ist ok (vorher Server-Datei gegen lokal diffen).
- Funktionalitaet gehoert in den Theme-Code, nicht als einmalige DB-Aenderung.
- Nur Dev, nie Live.

## Git

Nie ungefragt committen, pushen oder taggen -- nur wenn im aktuellen Schritt explizit verlangt.
Eine Freigabe gilt nur fuer diesen Anlass.
