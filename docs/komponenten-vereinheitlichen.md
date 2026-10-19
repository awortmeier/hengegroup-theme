# Komponenten vereinheitlichen

Arbeitsanleitung, um eine Komponentenfamilie so zu ueberarbeiten wie die Card. Aufruf:
"Vereinheitliche <Komponente> nach `docs/komponenten-vereinheitlichen.md`." Mehr Erklaerung ist
nicht noetig; alles Weitere steht hier.

Die Card ist die Referenzimplementierung (Dateien am Ende). Wo diese Anleitung "Card" nennt, ist
das ein Beispiel, kein Wert zum Kopieren: Werte werden je Komponente neu bestimmt, das Vorgehen
bleibt gleich.

## Zielbild

Am Ende gilt fuer die Komponente:

1. **Eine Base-Komponente, Unterschiede als Varianten.** Keine nachgebauten Kopien. Projektteile
   (`components/`, Bloecke, Templates) liefern nur Inhalte und rufen die Base auf.
2. **Eine Quelle fuer Klassen.** Klassenlisten stehen in einer JSON neben der Base-Datei; PHP und
   Block-Editor lesen sie. Keine von Hand gespiegelten Listen.
3. **Eine Abstandslogik.** Der Container traegt das Padding, Abstaende innen kommen nur aus `gap`.
   Keine Margins zwischen Elementen.
4. **Eine Stufe je Eigenschaft.** Ein Abstandswert, ein Radius, ein Schatten (je Komponente
   festgelegt). Abweichungen nur mit benanntem Grund.
5. **Eine Klicklogik.** Entweder ist das ganze Element ein Link (mit Hover) oder nur die Buttons
   darin (ohne Hover). Nie beides, nie verschachtelte Links.
6. **Redakteure waehlen die Variante im Editor**, pro Element: beim Hinzufuegen und spaeter in der
   Werkzeugleiste. Jeder Block bietet nur die zu ihm passenden Varianten an.
7. **Keine toten Optionen, keine Reste.** Was kein Aufrufer nutzt, ist nicht in der API.
8. **Ein Aufrufweg.** Genau ein Render-Helfer, kein zweiter Weg.

## Arbeitsweise

- Phasen der Reihe nach. Jede Phase endet mit Deploy auf Dev (`pnpm deploy:changed`), eigener
  Pruefung (Abschnitt "Pruefen") und einer kurzen Meldung an den Nutzer. An den mit **Stopp**
  markierten Stellen auf Antwort warten.
- Jede Rueckmeldung des Nutzers ist eine Regel, kein Einzelfall: fragen "gilt das fuer alle
  Varianten und alle Aufrufer?", zentral umsetzen (JSON, Base, Editor-Gegenstueck) und an allen
  Stellen pruefen. Neue allgemeine Regeln unten in "Regeln" ergaenzen.
- Bestehende Seiten duerfen nach jeder Phase nicht kaputt sein: abwaertskompatibel bauen, Aufrufer
  im selben Schritt anpassen.
- Nicht committen (siehe `CLAUDE.md`).

## Phase 1: Inventur

Ziel: jede Stelle kennen, die die Komponente rendert oder nachbaut.

1. Suchen (Repo-Wurzel; untracked Dateien beachten, also `grep -r` statt `git grep`):
    - Aufrufe der Base: `grep -rn "base/<name>'" template-parts inc woocommerce *.php`
    - Render-Helfer und Projektkomponenten: `grep -rn "<name>" inc/template-parts template-parts/components`
    - Bloecke: `grep -rln "<name>" template-parts/blocks assets/js/blocks`
    - Nachbauten ohne die Base: typische Klassen der Komponente suchen (Card: `rounded-*`,
      `bg-card`, `shadow-*`, `border-l-4`) in `template-parts`, `inc`, `woocommerce`, Templates.
    - Editor-Vorschauen, die das Markup in JSX nachbauen: `assets/js/blocks/*/edit.jsx`.
2. Jede Fundstelle einordnen: nutzt die Base · eigenes Markup · nur aehnlich (Grenzfall).
3. Ergebnis an den Nutzer: Tabelle _Komponente · Auspraegung · wo (Datei:Zeile) · Merkmale_ plus
   Grenzfaelle mit Begruendung, warum sie (nicht) dazugehoeren.

**Stopp:** Der Nutzer gibt seine Liste der Auspraegungen aus dem Design zurueck und sagt, was
ausgenommen bleibt.

## Phase 2: Zielbild abstimmen

Ziel: die Auspraegungen auf wenige Achsen und Slots verdichten.

1. Unterschiede jeder Auspraegung notieren und sortieren:
    - **Achse (Variante)**: Unterschied in Flaeche, Farbe, Anordnung, Zustand. Wird ein
      Enum-Key (`variant`, `accent`, `orientation` ...).
    - **Slot**: Unterschied nur im Inhalt (Icon, Logo, Badge, Bild, Buttons). Wird ein Config-Key
      mit vorgerendertem Markup.
    - **Eigene Komponente** nur, wenn Aufbau _und_ Daten grundverschieden sind und kein Slot passt
      (Card: Produktbox, an WooCommerce-Daten gebunden, eigener Aufbau).
2. Vokabular: shadcn/ui-Namen, wo sie passen (`variant`, `size`, `orientation`); sonst die
   bestehenden Theme-Namen (`accent` fuer Markenfarben, Flaechen `default`, `filled`, `split`,
   `ghost`, `glass`). Gleiche Bedeutung = gleicher Name ueber alle Komponenten.
3. Zuordnung jeder Auspraegung zu Achsen + Slots als Tabelle zeigen; unsichere Zuordnungen
   markieren.
4. Drei Fragen stellen (mit Empfehlung):
    - Variante pro Element oder pro Liste? (Card: pro Element, neue Elemente uebernehmen die
      vorige.)
    - Alle Varianten in jedem Block oder eine Auswahl je Block? (Card: Auswahl je Block.)
    - Erst Base-Komponente pruefen lassen, dann Bloecke? (Card: ja.)

**Stopp:** Antworten abwarten.

## Phase 3: Base-Komponente bauen

1. `template-parts/base/<name>-classes.json` anlegen: alle Klassenlisten als vollstaendige
   Literale, gegliedert nach Achsen und Slots (Vorlage: `card-classes.json`).
2. Lader `hengegroup_theme_get_<name>_classes()` in `inc/template-parts/helpers.php` (statischer
   Cache), Render-Helfer `hengegroup_theme_render_<name>(array $config): string`.
3. Base-Datei umbauen nach `CLAUDE.md` (Guard, Allow-Lists mit stillem Fallback, Passthrough
   `class`/`attributes`/`data_attributes`, `data-slot`/`data-variant`), Klassen nur aus der JSON.
   Aufbau nach den Regeln unten (Container mit Padding, Slots ohne Padding, Inhalt als
   Gap-Spalte, Footer unten).
4. Bestehende Aufrufer muessen weiter funktionieren; wo die DOM-Struktur sich aendert, Aufrufer im
   selben Schritt anpassen.
5. Pruefen, deployen. Es gibt keine Showcase-Seiten: geprueft wird auf den echten Seiten auf Dev.
   Aendert sich die Optik bestehender Aufrufer, Phase 4 direkt anschliessen.

**Stopp:** Nutzer prueft die betroffenen Seiten (Liste mit URL je Auspraegung) und die Zuordnung.

## Phase 4: Alles umstellen

1. **Projektkomponenten** (`components/`, Helfer in `inc/`) rendern ueber den Render-Helfer und
   liefern nur Inhalte. Alte Varianten-Namen auf die neuen abbilden.
2. **Inline-Nachbauten** in Bloecken und Templates durch die Komponente ersetzen.
3. **Bloecke**, deren Kinder die Komponente sind:
    - `block.json`: Attribut `variant` mit `enum` der passenden Varianten, erste = Standard; bei
      Bedarf `accent`. Gibt es Bestandsinhalte, deren Optik von einer alten Einstellung abhing:
      kein `default` setzen, Fallback im Code.
    - `render.php`: Grundwerte ueber einen Block-Config-Helfer (Vorlage:
      `hengegroup_theme_get_block_card_config()`), dann Render-Helfer.
    - Editor: Block-Variationen aus dem `enum`, Toolbar-Komponente (Variante + ggf. Farbe),
      Hinzufuegen-Kachel mit Varianten-Auswahl, Vorschau ueber eine gemeinsame `<Name>Inner`-
      Komponente und `<name>ClassName()` aus derselben JSON.
4. Editor-Bausteine in `assets/js/blocks/shared.jsx`: vorhandene allgemeine Teile wiederverwenden
   (`AddItemTile`, `buttonPreviewClassName`, `ACCENT_CLASSES`, `AccentDropdown`); Card-spezifische
   Teile (`cardVariations`, `CardToolbar`, `CardInner`) als Vorlage fuer die neue Komponente
   nehmen. Wird eine Funktion von zwei Komponenten gebraucht, verallgemeinern statt kopieren.
5. CHANGELOG, `entscheidungen.md`, `how-to.md` nachziehen.

## Phase 5: Feinschliff

Der Nutzer meldet Abweichungen (Abstaende, Hover, Klickbarkeit, Optik). Pro Meldung:

1. Ursache im Code finden, nicht nur das Symptom (Card: zusaetzlicher Abstand kam von einer leeren
   Grid-Zeile, nicht von zu grossem Gap).
2. Als Regel formulieren und zentral umsetzen: JSON, Base, Editor-Gegenstueck, Aufrufer.
3. Messen statt schaetzen (Abschnitt "Pruefen"), Screenshots der betroffenen Varianten ansehen.
4. Ist die Regel neu und allgemein: unten unter "Regeln" ergaenzen.

## Phase 6: Review

Die fertige Komponente nur lesen, nichts aendern. Pruefen:

- Stehen Klassen oder Logik doppelt (PHP und JSX, mehrere Bloecke, mehrere Helfer)?
- Gibt es Optionen, die kein Aufrufer nutzt? (Je Option alle Aufrufer pruefen.)
- Gibt es Reste frueherer Runden (Klassen fuer entfernte Mechanismen, Ueberschreibungen, die nicht
  mehr greifen)?
- Gibt es mehr als einen Aufrufweg?
- Bauen Aufrufer Pseudo-Slots im Inhalt nach (eigene Titel, Logos, Zwischenueberschriften mit
  freien Klassen)? Dann fehlt ein Slot.
- Gibt es zwei Stufen, wo eine reicht (Schatten, Groessen, Abstaende)?
- Gleiche Zuordnungen in mehreren Funktionen (Farben, Varianten)?

Ergebnis: Liste nach Nutzen sortiert ("lohnt sich" · "aufraeumen" · "zu entscheiden").

**Stopp:** Nutzer waehlt aus und entscheidet die offenen Punkte. Danach umsetzen, pruefen,
deployen.

## Regeln

Gelten fuer jede Komponente mit Flaeche und Inhalt. Spalte "Card" zeigt die dort gewaehlten Werte.

| Thema               | Regel                                                                                              | Card                                                        |
| ------------------- | -------------------------------------------------------------------------------------------------- | ----------------------------------------------------------- |
| Padding             | Nur der Container traegt Padding, rundum. Slots darin haben keins.                                 | `card-body` `p-7`                                           |
| Abstaende           | Nur `gap`, nie Margins. Der Inhalts-Slot ist selbst eine Gap-Spalte; Aufrufer liefern Geschwister. | `card-content` `flex flex-col gap-3`                        |
| Zusammengehoeriges  | Titel und Subline ohne Abstand. Kleinere Gruppen mit kleinerem Gap, nie mit Margin.                | Kopf ohne gap, Label + Chips `gap-2`, Kontaktzeilen `gap-1` |
| Eine Stufe          | Ein Abstand, ein Radius, ein Schatten je Komponente.                                               | `gap-3`, `rounded-card-raised`, `shadow-card-raised`        |
| Geteilte Flaechen   | Teile gleich bauen (Padding rundum je Teil), an der Trennkante weniger.                            | Split: Trennkante 20 statt 28px                             |
| Footer/Aktionen     | Sitzen unten (`mt-auto`), damit sie in Reihen auf einer Linie stehen.                              | `card-footer`                                               |
| Leere Slots         | Erzeugen kein Markup und keinen Abstand (auch keine leeren Grid-Zeilen).                           | Header nur mit Inhalt                                       |
| Klickbarkeit        | Ganzes Element als `<a>` nur ohne Links/Buttons darin; sonst ist nur der Button klickbar.          | `href` wird bei Buttons ignoriert                           |
| Hover/Fokus         | Nur wenn das ganze Element klickbar ist.                                                           | Download-Karte ohne Hover                                   |
| Listen-Markup       | Ein verlinktes Listenelement bleibt `<li>`, der Link kommt hinein.                                 | `<li>` um das `<a>`                                         |
| Farben auf Flaechen | Text-, Icon- und Button-Farbe folgen der Flaeche (hell/dunkel), zentral, nicht je Aufrufer.        | `darkVariants`, `button` in der JSON                        |
| Breite/Raster       | Die Komponente setzt keine Breite und keinen Rasterplatz; das gibt der Aufrufer per `class`.       | Sektionskopf: `col-span-12 max-w-3xl`                       |
| Barrierefreiheit    | Dekorative Bilder `decorative`, sichtbarer Text statt `aria-label`, wo moeglich.                   | Logo-Alt aus der Mediathek                                  |

Komponenten ohne Flaeche (Formularfelder, Tabs, Accordion): Padding-, Gap-, Stufen-, Slot- und
Aufrufweg-Regeln gelten; Klick- und Hover-Regeln nur, wenn das Element als Ganzes klickbar sein
kann; Schatten nur, wenn die Komponente einen hat.

## Pruefen

Nach jeder Phase und jeder Feinschliff-Runde:

1. `composer lint`, `composer test`, `pnpm format:check`, `pnpm test`.
2. `pnpm deploy:changed` (baut mit).
3. **Smoke-Test** jeder Seite mit der Komponente: HTTP-Status, `fatal error`/`warning:` im HTML,
   Anzahl je `data-variant` vorher/nachher gleich. Seiten finden:
   `php /tmp/wp-cli.phar post list --post_type=page --s="wp:hengegroup-theme/<block>" --field=ID`.
4. **Klassen im CSS**: neue Klassen aus der JSON im gebauten `dist/assets/css/app-*.css` suchen.
5. **Messen und ansehen** mit dem Skript unten (berechnete Paddings/Margins je Slot,
   DOM statt rohem HTML, Screenshot je Variante). Bilder selbst ansehen.
6. **Editor**: Hinzufuegen, Variante wechseln, Vorschau gegen Frontend. Kann ich das nicht selbst
   pruefen, dem Nutzer ausdruecklich als offenen Punkt nennen.

Skript (im Scratchpad ablegen, nicht im Repo):

```js
// node inspect.mjs <url> <js-expression> [screenshot.png] [selector]
import { spawn } from "node:child_process";
import { writeFileSync } from "node:fs";

const [url, expression, png, selector] = process.argv.slice(2);
const chrome = spawn(
    "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
    [
        "--headless=new",
        "--remote-debugging-port=9340",
        "--window-size=1440,1000",
        "--hide-scrollbars",
        `--user-data-dir=${process.env.TMPDIR}/inspect-profile`,
        "about:blank",
    ],
    { stdio: "ignore" }
);
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
let targets = [];
for (let i = 0; i < 50 && !targets.some((t) => t.type === "page"); i++) {
    await sleep(200);
    targets = await fetch("http://127.0.0.1:9340/json").then(
        (r) => r.json(),
        () => []
    );
}
const ws = new WebSocket(targets.find((t) => t.type === "page").webSocketDebuggerUrl);
await new Promise((r) => ws.addEventListener("open", r));
let id = 0;
const pending = new Map();
ws.addEventListener("message", (e) => {
    const m = JSON.parse(e.data);
    pending.get(m.id)?.(m.result);
});
const send = (method, params = {}) =>
    new Promise((r) => {
        pending.set(++id, r);
        ws.send(JSON.stringify({ id, method, params }));
    });
await send("Page.navigate", { url });
await sleep(4000);
const { result } = await send("Runtime.evaluate", { expression, returnByValue: true });
console.log(
    typeof result.value === "string"
        ? result.value
        : JSON.stringify(result.value ?? result, null, 1)
);
if (png && selector) {
    const box = await send("Runtime.evaluate", {
        returnByValue: true,
        expression: `(() => { const el = document.querySelector(${JSON.stringify(selector)}); if (!el) return null; el.scrollIntoView({ block: "center" }); const r = el.getBoundingClientRect(); return { x: r.x + scrollX - 24, y: r.y + scrollY - 24, width: r.width + 48, height: r.height + 48, scale: 1 }; })()`,
    });
    await sleep(800);
    if (box.result.value) {
        const shot = await send("Page.captureScreenshot", {
            clip: box.result.value,
            captureBeyondViewport: true,
        });
        writeFileSync(png, Buffer.from(shot.data, "base64"));
    } else {
        console.log("selector not found");
    }
}
chrome.kill();
process.exit(0);
```

Beispiele:

```sh
# Padding je Slot
node inspect.mjs https://dev.hengegroup.com/kominex/ '[...document.querySelectorAll("[data-slot=card]")].map(c => c.dataset.variant + ":" + getComputedStyle(c.querySelector("[data-slot=card-body]")).padding).join(" | ")'

# Margins innerhalb der Komponente finden (Ergebnis soll leer sein)
node inspect.mjs <url> '[...document.querySelectorAll("[data-slot=card] *")].filter(e => !e.closest("svg") && (parseFloat(getComputedStyle(e).marginTop) || parseFloat(getComputedStyle(e).marginBottom))).map(e => e.className).join(" | ")'

# DOM-Markup statt HTML (zeigt, was WordPress umgebaut hat)
node inspect.mjs <url> 'document.querySelector("[data-slot=card]").outerHTML.slice(0, 1500)'

# Screenshot eines Elements
node inspect.mjs <url> '1' card.png '[data-slot=card][data-variant=split]'
```

## Stolperfallen

| Falle                                                               | Folge                                          | Abhilfe                                                                                      |
| ------------------------------------------------------------------- | ---------------------------------------------- | -------------------------------------------------------------------------------------------- |
| `>` in einer nicht escapten Klasse (`[&>a]:flex-1`) im Block-Output | `wptexturize` zerlegt das Tag, Optik weg       | Klassen ueber `esc_attr`/`hengegroup_theme_render_attributes()`, sonst ohne `>` (`*:flex-1`) |
| Grid mit festen Zeilen und Gap, obwohl eine Zeile leer ist          | unsichtbarer Zusatzabstand                     | Zeilen nur bei Inhalt                                                                        |
| Stretched Link ueber Elementen mit Buttons                          | zwei Klickziele, Hover auf nicht Klickbarem    | Regel "ganz oder nur Buttons"                                                                |
| `tag: li` plus `href`                                               | `<a>` direkt im `<ul>`                         | `<li>` um den Link                                                                           |
| Margins im Inhalt plus Gap der Komponente                           | doppelte, ungleiche Abstaende                  | Margins entfernen                                                                            |
| `phpcs:ignore` hinter mehrzeiligem `echo`                           | Lint-Fehler                                    | Kommentar in die Zeile vor dem `echo`                                                        |
| Prettier verschiebt `phpcs:ignore` zwischen `?>` und `<?php`        | Lint-Fehler                                    | Aufruf in den vorherigen PHP-Block ziehen                                                    |
| `prettier --write .`                                                | fremde Dateien umformatiert                    | nur eigene Pfade formatieren                                                                 |
| Klassen zur Laufzeit zusammengesetzt                                | Tailwind erzeugt sie nicht                     | nur vollstaendige Literale (JSON oder Code)                                                  |
| Bestandsinhalte ohne neues Attribut                                 | Optik aendert sich ungewollt                   | im `enum` kein `default`, Fallback im Code                                                   |
| `<?php // phpcs:ignore` vor `echo` in HTML-Templates                | Prettier verschiebt den Kommentar, Lint-Fehler | Markup in eine Variable rendern, dann `printf('%s', $var, // phpcs:ignore ...)`              |
| Kinder per `contents` direkt im `.wrapper`-Grid, Kopf darueber      | Kopf-Abstand = Zeilenabstand der Karten        | Kinder in `col-span-12 grid grid-cols-subgrid` mit eigenem `gap-y`                           |
| Nur HTTP 200 geprueft                                               | kaputte Optik bleibt unbemerkt                 | messen und Screenshots ansehen                                                               |
| `curl`-HTML statt DOM gelesen                                       | Umbauten durch WordPress/Parser uebersehen     | DOM im Browser auslesen                                                                      |

## Referenz: Card

| Was                         | Datei                                                                                                                                            |
| --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| Klassen                     | `template-parts/base/card-classes.json`                                                                                                          |
| Base-Komponente             | `template-parts/base/card.php`                                                                                                                   |
| Helfer                      | `inc/template-parts/helpers.php` (`hengegroup_theme_*card*`, `get_accent_classes`, `get_link_attributes`)                                        |
| Projektkomponente auf Basis | `template-parts/components/contact-card.php`                                                                                                     |
| Block mit Varianten         | `template-parts/blocks/firma/` (`block.json`, `render.php`)                                                                                      |
| Editor                      | `assets/js/blocks/shared.jsx` (`CardInner`, `cardClassName`, `CardToolbar`, `cardVariations`, `AddItemTile`), `assets/js/blocks/firmen/edit.jsx` |

## Checkliste

**Inventur und Zielbild**

- [ ] Alle Aufrufer und Nachbauten gefunden, Grenzfaelle begruendet
- [ ] Auspraegungen vom Nutzer, auf Achsen und Slots verdichtet, Ausnahmen festgelegt
- [ ] Drei Fragen beantwortet (pro Element/Liste, Auswahl je Block, Reihenfolge)

**Komponente**

- [ ] Klassen in `<name>-classes.json`, PHP und Editor lesen sie
- [ ] Ein Render-Helfer, kein anderer Aufrufweg
- [ ] Padding nur am Container, Abstaende nur per `gap`, keine Margins
- [ ] Eine Stufe je Eigenschaft
- [ ] Klickbarkeit ganz oder nur Buttons; Hover nur bei ganz klickbar
- [ ] Slots statt Pseudo-Slots; leere Slots ohne Markup
- [ ] Keine toten Optionen, keine Reste
- [ ] Projektkomponenten komponieren die Base

**Gutenberg**

- [ ] `variant` mit `enum` je `block.json`, Fallback fuer Bestandsinhalte
- [ ] Block-Config-Helfer in `render.php`
- [ ] Variationen, Toolbar, Hinzufuegen-Kachel mit Auswahl
- [ ] Editor-Vorschau ueber `<Name>Inner` und dieselbe JSON

**Pruefen und Abschluss**

- [ ] Lint, Tests, Formatierung gruen
- [ ] Dev: Status, keine PHP-Fehler, Anzahl je Variante unveraendert
- [ ] Gemessen (Padding, Margins), Screenshots jeder Variante angesehen
- [ ] Editor geprueft oder als offen gemeldet
- [ ] CHANGELOG, `entscheidungen.md`, `how-to.md`; neue allgemeine Regeln hier ergaenzt
