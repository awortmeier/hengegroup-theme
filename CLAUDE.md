# CLAUDE.md

Projektspezifische Anweisungen fuer Claude Code. Ergaenzt `README.md` (Schnellstart: neues Projekt
aus dieser Vorlage starten) um verbindliche Konventionen fuer die Arbeit an diesem Theme.

## Zielbild und Phasen

Endergebnis ist ein **PHP-basiertes WordPress-Theme**. Der Weg dahin laeuft in drei Phasen —
**aktuell laeuft Phase 1**, Phase 2 und 3 sind geplant, aber noch nicht begonnen:

1. **Phase 1 — Base-Komponenten (aktuell).** `template-parts/base/` bekommt die shadcn/ui-
   Komponenten als generische, projektunabhaengige PHP-Bausteine: Markup, Config-API, Verhalten
   (nativ und/oder JS) und Barrierefreiheit. **Kein visuelles Styling in dieser Phase** — Tailwind-
   Klassen sind nur erlaubt, wenn sie funktional notwendig sind, nicht fuer Optik (siehe Regel 1).
2. **Phase 2 — Styling-Layer (spaeter).** Visuelles Design (Farben, Abstaende, Typografie,
   Radien, Schatten, Animationen, ...) kommt als eigener Schritt obendrauf, **ausschliesslich
   ueber Tailwind** (siehe Regel 1).
3. **Phase 3 — Gutenberg-Block-Wrapper (spaeter).** Die fertig gestylten Base-Komponenten werden
   in einer weiteren Schicht als Gutenberg-Bloecke verfuegbar gemacht (Block-Registrierung,
   `block.json`, Editor-/Save-Rendering o. Ae.).

Diese Reihenfolge ist bewusst: Base-Komponenten sollen sich in Phase 2 stylen lassen, ohne dass
ihre Struktur/API sich nochmal aendern muss, und sich in Phase 3 in Gutenberg-Bloecke wrappen
lassen, ohne dass Phase 1/2 nochmal angefasst werden muss. Beim Bauen einer Phase-1-Komponente
also immer mitdenken, dass sie in Phase 2 gestylt und in Phase 3 gewrappt werden muss — z. B.
stabile `data-slot`/`data-variant`/`data-size`-Hooks setzen (siehe Regel 1), auch wenn sie aktuell
noch kein Projekt-CSS ansprechen.

## Kernhaltung: moderner Tech-Stack, keine unnoetigen Einschraenkungen

Technologieentscheidungen fallen ausschliesslich danach, was fuer **UX** (und fuer DX/
Wartbarkeit) am besten ist — nicht danach, welche Technologie am "nativsten" wirkt oder am
wenigsten "kostet". Es gibt keinen Grund, JavaScript, ein JS-Framework/eine Bibliothek
(Alpine.js, htmx, Web Components, Lit, ...), ein Build-Tool oder jede andere Technologie
grundsaetzlich zu vermeiden oder erst als letztes Mittel in Betracht zu ziehen, wenn sie im
Einzelfall die bessere Loesung ist. Die einzige bewusste Einschraenkung im gesamten Stack ist die
Styling-Technologie (Regel 1: ausschliesslich Tailwind) — alles andere ist offen und wird pro
Komponente neu entschieden.

Native HTML-Semantik (Formularelemente, `<details>`, `<dialog>`, CSS Scroll Snap, ...) bleibt
trotzdem oft die pragmatischste Wahl und ein guter Ausgangspunkt, ist aber keine Pflichtvorgabe,
die eine bessere UX verhindern darf. Wo natives Verhalten eine schwaechere ARIA-Rolle als shadcn's
Vorbild ankuendigen wuerde (z. B. `role="checkbox"` statt `role="button"`), bleibt das kein
dauerhaft akzeptierter Kompromiss, sondern wird entweder durch eine vollstaendige JS-Umsetzung
oder eine schliessende JS-Enhancement-Schicht behoben.

### 1. Styling: was jetzt erlaubt ist, was Phase 2 gehoert

Diese Regel geht allen anderen vor, solange Phase 1 laeuft:

- **Ausschliesslich Tailwind**, wenn ueberhaupt Styling-Code entsteht — kein eigenes Utility-CSS,
  kein SCSS/LESS, kein CSS-in-JS, keine Inline-`style`-Attribute fuer Optik. Nur wenn ein
  konkreter Fall mit Tailwind (inkl. Arbitrary Values und den `@theme`-Tokens aus
  `assets/css/tokens.css`) nachweislich nicht abbildbar ist, darf rohes CSS/eine
  CSS-Custom-Property als Ausnahme genutzt werden — die Ausnahme kurz im Dateikopf-Kommentar
  begruenden.
- **Phase 1 (jetzt): nur funktionale Klassen.** Erlaubt sind Tailwind-Klassen, die ein Verhalten
  bzw. einen Zustand abbilden, ohne den die Komponente nicht korrekt funktionieren wuerde —
  typischerweise Sichtbarkeits-/Zustandsklassen, die an `data-state`/`aria-*`/JS gekoppelt sind
  (z. B. ein Dropdown-Panel, das erst nach dem Oeffnen sichtbar sein darf: `hidden` als Default,
  Umschaltung ueber JS/`data-state`). **Nicht erlaubt** in Phase 1: alles, was eine gestalterische
  Entscheidung ist — Farben, Abstaende/Spacing ueber das fuer die Funktion noetige Minimum hinaus,
  Typografie, Radien, Schatten, rein optische Transitions/Animationen. Testfrage im Zweifel:
  "Wuerde die Komponente ohne diese Klasse kaputt/falsch funktionieren (nicht nur schlechter
  aussehen)?" — nur dann gehoert sie jetzt schon ins PHP.
- `data-slot`/`data-variant`/`data-size`/weitere `data-*`-Attribute werden trotzdem weiterhin
  gesetzt — nicht als Styling-Pflichtmechanik, sondern als stabile Hooks fuer JS-Zustands-
  Selektoren, Tests und die kommende Phase-2-Styling-Arbeit, egal ob diese am Ende
  `[data-variant="..."]`-Selektoren oder direkt im PHP gesetzte Tailwind-Klassen pro Variante
  nutzt.
- Dass eine Komponente in Phase 1 optisch "unfertig" aussieht, weil kein visuelles Styling
  existiert, ist erwartet und kein Bug — Phase 2 holt das gesammelt nach, ohne die Struktur/API
  der Komponente nochmal anfassen zu muessen.

### Neue Komponente erstellen

Siehe `docs/neue-komponente-erstellen.md` — API-Design, Komposition, Datei-Struktur, a11y,
Datei-Sicherheit, Helper, Abgrenzung, Doku-Pflicht, JS-Enhancement, Checkliste. Ausgelagert, weil
aktuell kein Arbeitsauftrag (Phase 1 ist fast abgeschlossen); dort nachschlagen, sobald wieder eine
neue Base-Komponente entsteht.

### 11. Tooling

Regeln, die reine Prosa bleiben, verlassen sich vollstaendig auf manuelle/Agent-gestuetzte Pruefung
statt auf einen Lint-Lauf. `phpcs.xml` (WPCS via `squizlabs/php_codesniffer` +
`wp-coding-standards/wpcs`, siehe `composer.json`) deckt automatisiert einen Teilausschnitt bereits
schriftlich fixierter Regeln ab: `declare(strict_types=1)`, `esc_html()`/`esc_attr()`-Escaping
inkl. dokumentierter `phpcs:ignore`-Ausnahmen, sanitisierte Datei-Zugriffe. Bewusst **nicht** Teil
des Rulesets: WordPress-Extra's volle Formatierungssniffs (Tabs,
Whitespace, Array-Einrueckung, ...) — die uebernimmt bereits `@prettier/plugin-php`
(`.prettierrc.json`); zwei konkurrierende Format-Tools waeren Drift-Risiko statt Mehrwert.

Ergaenzend dazu deckt PHPUnit (`phpunit/phpunit` + `brain/monkey`, `tests/Unit/`) die reinen
Logik-Helper (`hengegroup_theme_render_attributes()`, `hengegroup_theme_render_accent_text()`, die
`field_*`-ID-Helper, `hengegroup_theme_warn_missing_aria_label()`) mit echten Verhaltens-Tests ab, statt
nur Syntax-/Escaping-Muster zu pruefen — Brain Monkey stubt die paar WP-Funktionen, die diese
Helper aufrufen (`esc_attr()`/`esc_html()`/`_doing_it_wrong()`), ohne dass dafuer eine echte
WordPress-Installation noetig ist (siehe `docs/entscheidungen.md` fuer die Begruendung
dieser Wahl statt eines vollen `wp-env`-Testcontainers). Vitest (`vitest.config.js`) deckt analog
dazu die komplexeren JS-Enhancement-Module unter `assets/js/template-parts/` ab (aktuell
`toggle.js` als erstes Beispiel) via `jsdom`. `hengegroup_theme_render_icon()`/`hengegroup_theme_render_image()`
sowie alles, was tatsaechliches PHP-Template-Rendering braucht, bleibt bewusst ausserhalb dieser
Unit-Suite — das ist ein spaeterer WP-gestuetzter Integrationstest, kein Job fuer Brain Monkey (siehe
`docs/to-do.md` Abschnitt 1).

- Einmalig: `composer install` (installiert `vendor/`, nicht committed, siehe `.gitignore`) sowie
  `pnpm install` (installiert `node_modules/`; das `prepare`-Skript richtet dabei automatisch den
  Git-Pre-Commit-Hook unten ein).
- Vor dem Commit/PR: `composer lint` (bzw. `composer lint:fix` fuer automatisch behebbare Befunde),
  `composer test` (PHPUnit), `pnpm format:check` (bzw. `pnpm format` zum Schreiben) und `pnpm test`
  (Vitest).
- **Pre-Commit-Hook** (`husky` + `lint-staged`, `.husky/pre-commit`): laeuft automatisch bei jedem
  `git commit` — `prettier --write` auf allen staged `*.{css,js,json,md,php}`-Dateien, zusaetzlich
  `phpcs` auf staged `*.php`-Dateien; ein verbleibender WPCS-Verstoss blockt den Commit, bevor er
  entsteht, statt erst in CI oder Review aufzufallen.
- **CI** (`.github/workflows/ci.yml`, GitHub Actions): erzwingt bei jedem Push/PR dieselben Checks
  automatisiert (`composer lint`, `composer test`, `pnpm format:check`, `pnpm test`) in zwei
  unabhaengigen Jobs (PHP/JS), plus `composer audit`/`pnpm audit` als nicht blockierenden
  Report-Schritt (siehe naechster Punkt).
- Ein Lint-/Test-Fehler ist ein echter Regelverstoss gegen die oben dokumentierten Konventionen,
  kein Stil-Vorschlag — beheben statt zu ignorieren, ausser eine dokumentierte Ausnahme (z. B.
  Regel 1's begruendete rohes-CSS-Ausnahme) greift bereits.
- `composer audit`/`pnpm audit` pruefen die jeweiligen Dependencies gegen bekannte
  Sicherheits-Advisories. Laufen sowohl automatisiert bei jedem CI-Lauf (als reiner,
  nicht-blockierender Report-Schritt, siehe oben) als auch weiterhin auf Zuruf lokal; Funde werden
  in beiden Faellen manuell per Versions-Update behoben, kein automatischer Fix, kein Commit-/PR-Gate
  (siehe `docs/entscheidungen.md` fuer die Begruendung, warum bewusst kein Gate).
- Deckt nur ab, was Lint-/Test-Tooling ueberhaupt automatisiert pruefen kann (Syntax-/Escaping-/
  Security-Muster sowie das schmale Set an Unit-getesteter Helper-/JS-Logik oben). Semantische
  Vorgaben (API-Vokabular gegen shadcn, Komposition statt Duplikation, a11y-Config, Doku-Pflicht)
  sowie alles, was PHP-Template-Rendering oder eine echte WP-Instanz braucht (a11y-Scan,
  Visual-Regression, siehe `docs/to-do.md` Abschnitt 1), bleiben weiterhin
  Aufgabe manueller/Agent-gestuetzter Review — kein Ersatz dafuer, nur eine zusaetzliche,
  guenstige Absicherung fuer den automatisierbaren Teil.

### 12. Entscheidungen & HowTos: zentral in `docs/entscheidungen.md`/`docs/how-to.md`

Bewusste Architektur-/Prozessentscheidungen (z. B. "Hreflang wird nicht implementiert, weil...")
werden **zusaetzlich** zum jeweiligen Dateikopf-/Code-Kommentar in `docs/entscheidungen.md`
festgehalten, praktische Anleitungen fuer wiederkehrende Erweiterungen (z. B. "wie ergaenze ich ein
weiteres JSON-LD-Schema") in `docs/how-to.md` — beides nicht nur im Kopfkommentar der betroffenen
Datei oder im CHANGELOG-Eintrag, wo die Begruendung bei der naechsten Aufraeumaktion/dem naechsten
Release untergehen wuerde.

Abgrenzung zu benachbarten Dokumenten (steht auch im Kopf von `docs/entscheidungen.md`/
`docs/how-to.md` selbst, hier nur zur Einordnung):

- `docs/to-do.md` sammelt **offene** Punkte (noch nicht entschieden/gebaut). Sobald ein dort
  gelisteter Punkt entschieden ist, wandert die Begruendung als neuer Eintrag nach
  `docs/entscheidungen.md`; der to-do.md-Eintrag wird wie bisher als geloest markiert/entfernt.
- `CHANGELOG.md` haelt fest, **was** sich geaendert hat (fuer Nutzer der Vorlage) —
  `docs/entscheidungen.md`/`docs/how-to.md` halten fest, **warum/wie** (fuer kuenftige Bearbeiter,
  inkl. Claude selbst).
- Der Dateikopf-Kommentar der betroffenen Datei bleibt die Quelle direkt am Code; ein
  Eintrag in `docs/entscheidungen.md`/`docs/how-to.md` ist ein zusaetzlicher, thematisch
  gebuendelter Verweis, kein Doppel-Text mit abweichendem Inhalt — kurz halten, auf den
  Kopfkommentar verweisen statt ihn zu duplizieren.

Ein Eintrag in `docs/entscheidungen.md` ist noetig bei jeder bewussten Entscheidung, die nicht
offensichtlich aus dem Code folgt (typische Form: "warum kein X" / "warum Y statt Z"); ein Eintrag
in `docs/how-to.md` bei jeder wiederkehrenden Erweiterungs-Anleitung fuer einen Extension-Point
(Filter/Hook/Config-Key), den ein spaeteres Projekt-Theme voraussichtlich nutzen wird — nicht bei
jeder Implementierungsdetail-Entscheidung, die schon aus dem Diff/Kopfkommentar ersichtlich ist.

## Git-Workflow

Claude Code committet und pusht **niemals ungefragt**, unabhaengig davon, wie klar/abgeschlossen
eine Aenderung wirkt oder wie explizit eine vorherige Anweisung im selben Gespraech war (eine
Freigabe gilt fuer den einen Anlass, nicht stillschweigend fuer alle folgenden Aenderungen):

- `git commit` und `git push` (inkl. Tags: `git tag`/`git push origin <tag>`) nur, wenn der Prompt
  im aktuellen Gespraechsschritt explizit danach fragt ("committe das", "push das", "erstelle einen
  PR", o. ae.) — nicht automatisch am Ende einer Aufgabe, auch wenn Tests/Lint erfolgreich waren.
- Lokale, nicht-oeffentliche Arbeitsschritte (`git init`, `git add`, `git status`, `git diff`,
  Branches/Commits in einem Wegwerf-Testklon wie bei der Verifikation von
  `scripts/pull-base-updates.ps1`) sind davon unberuehrt — die Grenze ist nicht "irgendein
  Git-Befehl", sondern alles, was den Verlauf des tatsaechlichen Projekt-Repos veraendert
  (Commit) oder etwas an ein Remote sendet (Push).
- Im Zweifel nachfragen oder den fertigen Stand beschreiben und explizit auf den ausstehenden
  Commit/Push hinweisen, statt ihn selbststaendig auszufuehren.
