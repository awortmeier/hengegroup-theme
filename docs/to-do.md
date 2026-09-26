# To-Do

Stand: 2026-08-07. Bestandsaufnahme, was in diesem Base Theme (als Vorlage fuer alle
zukuenftigen WordPress-Themes) noch fehlt, unausgereift ist oder bewusste Luecken hat, die frueher
oder spaeter geschlossen werden sollten. Kein Auftrag, alles sofort umzusetzen — eine
Priorisierungs-Grundlage. Bezieht sich durchgehend auf `CLAUDE.md`/`README.md`, nicht als Ersatz
dafuer.

Einordnung nach dem Phasenmodell aus `CLAUDE.md`: einiges hier gehoert in laufende Phase 1, einiges
ist bewusst erst Phase 2/3-Thema und nur als Vormerkung gedacht, ein Teil liegt komplett ausserhalb
des Drei-Phasen-Modells (Prozess/Tooling/WordPress-Grundgerüst).

Sobald ein hier gelisteter Punkt entschieden ist, wandert die Begruendung als neuer Eintrag nach
`docs/entscheidungen.md`; der Eintrag hier wird wie bisher als geloest markiert/entfernt (siehe
`CLAUDE.md` Regel 12).

## Prioritaeten auf einen Blick

| Prio    | Bereich                                                           |
| ------- | ----------------------------------------------------------------- |
| Mittel  | Kein automatisiertes a11y-Check trotz starker a11y-Kultur im Code |
| Niedrig | `prefers-reduced-motion`-Token                                    |

---

## 1. Testing & Qualitaetssicherung

- **Keine automatisierte a11y-Pruefung.** Weiterhin offen, haengt an der aktuell zurueckgestellten
  Komponenten-Showcase-Seite als Scan-Ziel (siehe `docs/entscheidungen.md`). `CLAUDE.md`
  und `docs/neue-komponente-erstellen.md` investieren sehr viel in a11y-Konventionen (Regel 5,
  Regel 10, `hengegroup_theme_warn_missing_aria_label()`) — das ist aktuell komplett auf manuelle/Agent-gestuetzte
  Review angewiesen. Ein automatisierter Check (`@axe-core/playwright` gegen die Showcase-Seite,
  gehostet ueber `@wordpress/env`/`wp-env` in CI) wuerde genau diese Investition absichern.
- **Keine visuelle Regressionstestung.** Aktuell irrelevant (Phase 1 hat kein Styling), wird aber
  mit Phase-2-Start relevant — sobald Tailwind-Klassen dazukommen, lohnt sich ein Snapshot-Tool
  (Playwright) gegen genau dieselbe Showcase-Seite/denselben `wp-env`-Aufbau wie der a11y-Punkt
  oben, nicht separat aufsetzen.

## 2. Barrierefreiheit (Ergaenzung zu docs/neue-komponente-erstellen.md Regel 5)

- **Kein `prefers-reduced-motion`-Token in `tokens.css`.** Aktuell irrelevant, da Phase 1 keine
  Animationen enthaelt (Regel 1) — sobald Phase 2 rein optische Transitions/Animationen einfuehrt,
  sollte von Anfang an ein Reduced-Motion-Pfad mitgedacht werden, nicht nachtraeglich.

## 3. Phase 2 / Phase 3 Vorbereitung

- **Phase 2:** Design-Token-Umfang fuer Farben/Fonts ist jetzt geklaert (siehe
  `docs/entscheidungen.md`, "Marken-Tokens: drei Akzentfarben, Grau-Mapping, zwei Font-Rollen"),
  offen bleibt nur noch, ob/wie Dark Mode ueber `tokens.css` abgebildet wird — keine Aenderung
  jetzt noetig, Vormerkung fuer den Start von Phase 2.
- **Phase 2/3:** `add_theme_support('editor-styles')` + `add_editor_style()` sowie das Konzept fuer
  `block.json`/Block-Registrierung (Ordner-Konvention `template-parts/blocks/<name>/`, natives
  Block statt ACF, Editor-Script per eigenem Vite-Build gegen WordPress' `wp.*`-Globals) sind jetzt
  entschieden und mit dem ersten Block (`hengegroup-theme/buehne`) umgesetzt — siehe
  `docs/entscheidungen.md` "Phase-3-Block-Architektur".
- **Phase 3:** `inc/setup/theme-admin.php` versteckt Site-Editor-/Customizer-Menuepunkte aktiv
  (`hengegroup_theme_action_admin_menu_cleanup()`), was fuer ein reines klassisches Theme sinnvoll ist —
  sollte aber gegengeprueft werden, sobald Phase 3 eigene Bloecke registriert (der normale
  Block-Editor in Seiten/Beitraegen bleibt davon unabhaengig ohnehin erreichbar, braucht dafuer
  keinen sichtbaren Site Editor).

## 4. Header (`header.php`)

- **Mobile-Navigation fehlt noch.** Der aktuelle Header (siehe `docs/entscheidungen.md` "Header:
  Navigationsinhalt aus wp_nav_menu statt hartkodiert"/"Header: Scroll-Verhalten aus dem
  Referenzdesign...") setzt nur die Desktop-Ansicht der Claude-Design-Referenz um — die Referenz
  selbst zeigt kein Mobile-Layout (kein Hamburger-/Off-Canvas-Menue). Sobald ein Mobile-Design
  vorliegt, muss `hengegroup_theme_primary_navigation_items()`
  (`inc/template-parts/navigation.php`) plus die Header-Composition in `header.php` entsprechend
  ergaenzt werden.
- **Sprachumschalter ist reines UI-Element ohne Funktion.** Siehe
  `docs/entscheidungen.md` "Header: Sprachumschalter als reines UI-Element" — beide Eintraege
  verlinken aktuell auf `#`. Erst mit dem geplanten Multisite-Netzwerk (siehe "Mehrsprachigkeit
  ueber Multisite statt Hreflang-Plugin") mit echten Sprach-URLs verdrahten.

## 5. Stellenangebote (`inc/setup/theme-careers.php`)

- **Strukturierte Felder fehlen noch** (Standort, Unternehmen/Marke innerhalb der HengeGroup inkl.
  Logo, ggf. Beschäftigungsart/Ansprechpartner) — aktuell bewusst nur Titel + Gutenberg-Inhalt +
  Beitragsbild, siehe `docs/entscheidungen.md` "Stellenangebote: Custom-Post-Type angelegt". Genaue
  Feldliste kommt noch vom Nutzer; "Unternehmen" ggf. als eigene Taxonomie (Vorbild `product_cat`)
  statt Freitext, falls ein Logo pro Marke wiederverwendet werden soll.
- **Kein Bewerbungsformular.** Die Live-Referenz (`hengegroup.com/karriere/?job=...`) hat ein
  Formular (Name/Alter/Berufserfahrung/Kontakt) direkt auf der Stellenanzeige — Empfang/Versand,
  Datei-Upload, DSGVO-Einwilligung sind hier komplett offen, eigener, groesserer Auftrag.
- **Kein JobPosting-JSON-LD-Schema.** `docs/how-to.md`s "Ein weiteres JSON-LD-Schema ergaenzen"
  nennt JobPosting explizit als Beispiel — sinnvoll nachzuziehen, sobald Standort/
  Beschäftigungsart/Bewerbungsschluss als echte Felder existieren (das Schema braucht die als
  Pflichtangaben, siehe schema.org/JobPosting).
- **Rewrite-Rules müssen nach dem Anlegen einmal neu geschrieben werden** (Einstellungen ->
  Permalinks -> Speichern), sonst liefert `/karriere/` 404. Ausserdem prüfen, ob bereits eine
  WordPress-Seite mit Slug "karriere" existiert (würde mit dem Archiv kollidieren, siehe
  `docs/entscheidungen.md`).
