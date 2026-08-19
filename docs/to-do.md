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

- **Phase 2:** ausser `--color-accent`/`--font-accent` gibt es noch kein dokumentiertes Konzept fuer
  den Umfang des Design-Token-Systems (z. B. ob/wie Dark Mode ueber `tokens.css` abgebildet wird,
  ob weitere semantische Farb-Tokens neben Tailwinds `neutral`-Skala dazukommen). Keine Aenderung
  jetzt noetig, aber eine kurze Vorab-Entscheidung wuerde verhindern, dass das erste Projekt, das
  Phase 2 tatsaechlich durchlaeuft, das Token-Modell nebenbei neu erfinden muss.
- **Phase 2:** `add_theme_support('editor-styles')` + `add_editor_style()` fehlen noch bewusst —
  `align-wide`/`responsive-embeds` sind bereits gesetzt (`inc/setup/theme-setup.php`), da sie schon
  jetzt normale Core-Bloecke in Beitraegen/Seiten betreffen; `editor-styles` bringt erst etwas,
  sobald es echtes Phase-2-CSS zum Laden gibt.
- **Phase 3:** kein dokumentiertes Konzept, wie `block.json`/Block-Registrierung strukturell
  organisiert wird (eigener `blocks/`-Ordner? Namensschema? Wie verhaelt sich das zu
  `template-parts/blocks/`, das laut `find` bereits als leerer Ordner existiert, aber in
  README/CLAUDE.md noch nicht erwaehnt wird?) — `template-parts/blocks/` als bereits angelegter,
  aber undokumentierter Ordner ist selbst ein kleiner Punkt wert: entweder fuellen/dokumentieren
  oder (falls Ueberbleibsel) entfernen, damit er nicht als stiller, nicht eingeloester Claim im
  Repo liegen bleibt.
- **Phase 3:** `inc/setup/theme-admin.php` versteckt Site-Editor-/Customizer-Menuepunkte aktiv
  (`hengegroup_theme_action_admin_menu_cleanup()`), was fuer ein reines klassisches Theme sinnvoll ist —
  sollte aber gegengeprueft werden, sobald Phase 3 eigene Bloecke registriert (der normale
  Block-Editor in Seiten/Beitraegen bleibt davon unabhaengig ohnehin erreichbar, braucht dafuer
  keinen sichtbaren Site Editor).
