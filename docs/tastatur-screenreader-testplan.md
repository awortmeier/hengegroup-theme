# Tastatur-/Screenreader-Testplan

Checkliste fuer die manuelle Pruefung der interaktiven `template-parts/base/*`-Komponenten, die
`docs/to-do.md` Abschnitt 2 als fehlende Ergaenzung zur (noch fehlenden) automatisierten
a11y-Pruefung gefordert hat — siehe `docs/entscheidungen.md` fuer die Einordnung. Ersetzt keinen
automatisierten Scan (`@axe-core/playwright`, siehe `docs/to-do.md` Abschnitt 1), sondern deckt
genau das ab, was ein DOM-/Kontrast-Scanner nicht pruefen kann: tatsaechliches Tastaturverhalten und
Screenreader-Ankuendigungen.

## Voraussetzung zum Ausfuehren

Diese Datei dokumentiert **was** zu pruefen ist, nicht das Ergebnis eines bereits durchgefuehrten
Durchlaufs — `base-theme` ist zwar ein voll aktivierbares WordPress-Theme (`page.php`, `index.php`,
... existieren bereits), es braucht dafuer aber trotzdem eine laufende WP-Instanz mit ein paar
Testseiten, die `get_template_part()` fuer die jeweilige Komponente aufrufen. Bis die zurueckgestellte
Komponenten-Showcase-Seite existiert (siehe `docs/entscheidungen.md`), reicht dafuer eine
Wegwerf-Testseite pro Durchlauf.

Empfohlene Kombination: Tastatur-only (Maus/Trackpad nicht anfassen) + ein Screenreader pro
Betriebssystem, das verfuegbar ist (Windows: NVDA, gratis; macOS: VoiceOver, eingebaut). Ergebnis
pro Durchlauf hier eintragen (Datum, SR/Browser-Kombination, Befund) statt eine separate Datei zu
fuehren.

## Status

| Komponente                         | Zuletzt geprueft | SR/Browser | Befund |
| ---------------------------------- | ---------------- | ---------- | ------ |
| _(noch keine Komponente geprueft)_ |                  |            |        |

---

## Overlays (eigenes Panel, das ueber dem Rest der Seite erscheint)

### `dialog.php`

- Oeffnen (Trigger-Button): Fokus springt in den Dialog (natives `<dialog>`-Element, Browser
  uebernimmt Focus-Trap automatisch — pruefen, dass Tab den Dialog **nicht** verlassen kann).
- `Escape`: schliesst den Dialog (natives `cancel`-Event), Fokus kehrt zum Trigger zurueck — ausser
  `dismissible: false` ist gesetzt, dann bleibt der Dialog offen (`preventDefault()` auf `cancel`).
- SR: Dialog wird beim Oeffnen als "dialog" mit seinem `aria-label`/`aria-labelledby` angekuendigt.

### `dropdown-menu.php`

- `ArrowDown`/`ArrowUp`: bewegt den Fokus (Roving Tabindex) zum naechsten/vorherigen Item, mit
  Wrap-Around an den Raendern.
- `Home`/`End`: springt zum ersten/letzten Item.
- Buchstaben-Taste(n): Type-Ahead springt zum naechsten Item, dessen sichtbarer Text damit beginnt.
- `Enter`/`Space`: aktiviert das fokussierte Item.
- `Escape`: schliesst das Menu, Fokus kehrt zum Trigger zurueck.
- `menuitemcheckbox`/`menuitemradio`-Items: SR kuendigt "checked"/"not checked" nach Umschalten
  korrekt neu an.

### `popover.php`

- `Escape`: schliesst (Fokus zurueck zum Trigger).
- Tab-Reihenfolge: alle interaktiven Elemente im Panel-Inhalt bleiben normal per Tab erreichbar
  (kein Focus-Trap wie bei `dialog.php` — bewusster Unterschied, nicht nachpruefen als Bug).

### `hover-card.php`

- Erscheint nach Fokussieren des Triggers (nicht nur bei Maus-Hover) mit kurzer Verzoegerung.
- `Escape`: schliesst die Card.
- Verschwindet bei `blur` (Fokus verlaesst den Trigger) auch ohne `Escape`.

### `tooltip.php`

- Erscheint bei Fokus auf den Trigger (nicht nur Hover).
- `Escape`: schliesst den Tooltip.
- SR: Trigger kuendigt den Tooltip-Inhalt per `aria-describedby` an (Tooltip-Element selbst bleibt
  **nicht** `hidden`, siehe `docs/neue-komponente-erstellen.md` Regel 10 — sonst wuerde die
  `aria-describedby`-Referenz brechen).

### `date-picker.php`

- Gleiches `<details>`/`<summary>`-Panel-Verhalten wie `dropdown-menu.php`: `Escape` schliesst,
  Fokus kehrt zum Trigger zurueck.
- Enthaelt eine genestete `calendar.php` — siehe deren eigenen Abschnitt unten fuer das
  Tastaturverhalten _innerhalb_ des Panels.
- Nach Datumsauswahl: Trigger-Text aktualisiert sich (SR sollte die Aenderung mitbekommen, z. B.
  ueber erneuten Fokus auf den Trigger nach dem Schliessen).

---

## Navigation

### `tabs.php`

- Tab-Taste bewegt den Fokus nur auf den **aktiven** Tab (Roving Tabindex) — nicht auf jeden
  einzelnen Tab.
- `ArrowRight`/`ArrowLeft` (horizontal) bzw. `ArrowDown`/`ArrowUp` (vertikal, `orientation`-Config):
  bewegt Fokus **und** aktiviert den Tab (kein separates Enter noetig — Automatic-Activation-Pattern).
- `Home`/`End`: springt zum ersten/letzten Tab.
- SR: aktiver Tab kuendigt `aria-selected="true"` an, zugehoeriges Panel ist per
  `aria-controls`/`aria-labelledby` verknuepft.

### `navigation-menu.php`

- `Escape`: schliesst ein offenes Submenu, Fokus kehrt zum zugehoerigen Top-Level-Trigger zurueck.
- `ArrowRight`/`ArrowLeft`, `Home`/`End`: Roving-Navigation **zwischen den Top-Level-Items** (nicht
  innerhalb eines geoeffneten Submenus).
- Pruefen, ob wirklich nur ein Submenu gleichzeitig offen ist (kein gleichzeitig zweites offenes
  Submenu nach dem Wechsel).

### `breadcrumb.php`

- Reine Link-Liste, Tab-Reihenfolge = Lesereihenfolge, kein Sonderverhalten.
- Falls eine Ellipsis (`…`) fuer ausgeblendete Zwischen-Schritte gerendert wird und diese als
  `dropdown-menu.php` umgesetzt ist: siehe dessen Abschnitt oben.

### `pagination.php`

- Reine Link-Liste (`<a href>`), kein JS-Sonderverhalten — pruefen, dass die aktuelle Seite per
  `aria-current="page"` angekuendigt wird und nicht versehentlich als Link fokussierbar ist, wenn
  sie deaktiviert sein soll.

---

## Auswahl-Komponenten

### `select.php`

- Trigger per `Enter`/`Space`/`ArrowDown` oeffenbar.
- Bei geoeffneter Listbox: `ArrowDown`/`ArrowUp` bewegt `aria-activedescendant`, `Home`/`End`
  springt zum ersten/letzten Option, `Enter`/`Space` waehlt aus, `Escape` schliesst ohne Auswahl.
- Fokus bleibt waehrend der ganzen Interaktion auf dem Trigger (Select-Only-Combobox-Pattern, siehe
  `docs/neue-komponente-erstellen.md` Regel 10) — **nicht** erwarten, dass der Fokus in die Listbox
  wandert.

### `combobox.php`

- Text-Eingabe filtert die Listbox live.
- `ArrowDown`/`ArrowUp`: bewegt `aria-activedescendant` durch die gefilterten Ergebnisse.
- `Enter`: uebernimmt das aktive Ergebnis.
- `Escape`: schliesst die Listbox (Text bleibt erhalten).
- Kein `Home`/`End` implementiert (anders als `select.php`) — bei einer laengeren gefilterten Liste
  ist das erwartetes, nicht fehlendes Verhalten, nicht als Befund werten.
- Zero-JS-Fallback pruefen: mit deaktiviertem JS bleibt ein natives `<input list="...">` +
  `<datalist>` nutzbar (ungestylt, aber funktional).

### `native-select.php`

- Rein nativ (`<select>`/`<option>`/`<optgroup>`) — Tastaturverhalten kommt vollstaendig vom
  Browser, nichts Theme-Spezifisches zu pruefen ausser korrekten `<optgroup label>`-Ankuendigungen.

### `radio/radio-group.php`, `checkbox.php`, `switch.php`

- Native Inputs — Pfeiltasten-Navigation innerhalb einer `radio-group.php` (gemeinsames `name`
  auf den `<input type="radio">`s) kommt vom Browser, nicht von JS.
- `switch.php`: SR kuendigt trotz `role="switch"` weiterhin den nativen Checked-Zustand korrekt an
  ("on"/"off" statt "checked"/"not checked" bei manchen SR/Browser-Kombinationen pruefen).
- Bei `disabled`: Tab ueberspringt das Element, SR kuendigt "dimmed"/"unavailable" an.

### `slider.php`

- Natives `<input type="range">` — `ArrowLeft`/`ArrowRight`/`ArrowUp`/`ArrowDown`, `Home`/`End`,
  `PageUp`/`PageDown` kommen vom Browser. Nur pruefen, dass `aria-valuenow`/-`valuemin`/-`valuemax`
  korrekt gesetzt sind (SR sollte den aktuellen Wert vorlesen).

### `input-otp.php`

- Einzelnes natives `<input>` mit `autocomplete="one-time-code"` — kein Multi-Feld-Tabbing zu
  pruefen (bewusst kein shadcn-Box-Nachbau, siehe `docs/neue-komponente-erstellen.md` Regel 2).

### `toggle.php` / `toggle-group.php`

- `toggle.php`: fokussierbares `<label>` (nicht das versteckte Input) kuendigt sich als
  `role="button" aria-pressed`, per `Enter`/`Space` umschaltbar (native Label-Klick-Weiterleitung an
  das Checkbox-Input pruefen).
- `toggle-group.php` `multiple`-Modus: jedes Item unabhaengig umschaltbar, kein Roving Tabindex
  (jedes Item einzeln per Tab erreichbar).
- `toggle-group.php` `single`-Modus: Items sind `role="button"` unter `role="group"` (nicht mehr
  `role="radiogroup"`), aber die zugrunde liegenden `<input type="radio">`s teilen weiterhin ein
  `name` — Pfeiltasten-Exklusivitaet zwischen den Items kommt deshalb weiterhin vom Browser, nicht
  von JS. Nach einem Wechsel: SR kuendigt beim vorher aktiven Item `aria-pressed="false"` an (nicht
  nur beim neu aktiven `"true"` — das ist die Luecke, die `toggle-group.js` explizit schliesst).

---

## Daten-Komponenten

### `calendar.php`

- Tagesfelder sind einzeln per Tab erreichbar (jedes ist ein `toggle.php`-Item) — **kein**
  APG-Date-Grid-Pattern mit Pfeiltasten-Navigation zwischen Tagen implementiert. Das ist der
  aktuelle, bewusste Stand (siehe `docs/neue-komponente-erstellen.md` Regel 10), keinen Befund
  dafuer anlegen, sondern nur gegenpruefen, dass das weiterhin so dokumentiert bleibt, falls sich das
  aendert.
- Monatswechsel (Vor-/Zurueck-Buttons): per `Enter`/`Space` aktivierbar, Fokus bleibt nach dem
  Wechsel auf dem geklickten Button (nicht verloren).
- Deaktivierte Tage (`disabled`-Config): weiterhin Teil der Tab-Reihenfolge oder ausgeschlossen?
  Gegen `aria-disabled` pruefen, nicht nur visuell.

### `carousel/*`

- Scroll-Container ist per Pfeiltasten bedienbar (native CSS Scroll Snap + Tastatur-Scrolling des
  fokussierten, scrollbaren Elements) — kein JS-verwaltetes Pfeiltasten-Pattern.
- `carousel-previous.php`/`carousel-next.php`: echte Buttons, per `Enter`/`Space` aktivierbar,
  werden an den Raendern automatisch `disabled` (ausser `data-loop="true"`) — pruefen, dass
  deaktivierte Buttons wirklich aus der Tab-Reihenfolge fallen bzw. korrekt als disabled
  angekuendigt werden.

### `data-table.php`

- Sortier-/Pagination-Steuerung sind echte `<a href>`-Links, kein Sonderverhalten noetig
  (Tab/Enter reicht).
- Nach einem Sortier-/Pagination-Klick (JS fetched die Ziel-URL und tauscht das Tabellen-Fragment
  aus): pruefen, ob der Fokus sinnvoll bleibt (z. B. auf dem geklickten Link) statt auf `<body>`
  zurueckzufallen — SR-Nutzer verlieren sonst den Kontext, wo im DOM sie gerade sind.

---

## Feedback

### `toast.php`

- Erscheint ohne Fokus-Diebstahl (Toast unterbricht nicht, was der Nutzer gerade tut) — via
  `aria-live`-Region angekuendigt, pruefen, dass SR den Inhalt tatsaechlich vorliest, ohne dass der
  Fokus dorthin springt.
- Schliessen-Button ist ein normaler fokussierbarer Button (`Enter`/`Space`) — es gibt **keinen**
  dedizierten `Escape`-Shortcut zum Schliessen (anders als die Overlays oben), das ist der aktuelle
  Stand, kein Befund.
- Bei mehreren gleichzeitigen Toasts: Tab-Reihenfolge durch den Viewport sinnvoll (neueste zuerst
  oder aelteste zuerst, konsistent mit der visuellen Reihenfolge).

### `accordion.php`

- Natives `<details>`/`<summary>` — `Enter`/`Space` auf dem fokussierten Header oeffnet/schliesst
  (kein APG-Accordion-Pfeiltasten-Pattern, das waere JS-Sonderverhalten, das hier bewusst nicht
  existiert).
- `type="single"`-Verhalten (nur ein Panel offen) kommt vom gemeinsamen `name`-Attribut auf den
  `<details>`-Elementen (natives "exclusive accordion", seit neueren Browsern unterstuetzt) — pruefen,
  dass wirklich nur ein Panel gleichzeitig offen bleibt.
