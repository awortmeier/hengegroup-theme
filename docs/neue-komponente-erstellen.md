# Neue Komponente erstellen

Vorgehen fuer eine neue `template-parts/base/*`-Komponente: API-Design, Komposition, Struktur,
Barrierefreiheit, Datei-Sicherheit, gemeinsame Helper, Abgrenzung zu `components/`/`sections/`,
Doku-Pflicht und JS-Enhancement — plus die Abschluss-Checkliste. Ausgelagert aus `CLAUDE.md`, weil
dieser Ablauf nur beim tatsaechlichen Bauen einer neuen Base-Komponente gebraucht wird und aktuell
kein Arbeitsauftrag ist (siehe `docs/entscheidungen.md`). `CLAUDE.md` bleibt die Quelle
fuer alles, was unabhaengig davon gilt: Zielbild/Phasenmodell, Kernhaltung (Technologiewahl),
Regel 1 (Styling/Phase 1), Regel 11 (Tooling), Regel 12 (Entscheidungen & HowTos) sowie den
Git-Workflow.

Die Nummerierung (Regel 2-10) ist unveraendert aus `CLAUDE.md` uebernommen — Code-Kommentare im
Repo, die auf `CLAUDE.md #2`/`#7`/etc. verweisen, verweisen jetzt unter derselben Nummer auf diese
Datei.

---

## 2. API-Design: shadcn/ui als Vorbild

Die Config-API jeder Komponente orientiert sich **stark** an der jeweiligen shadcn/ui-Komponente
(https://ui.shadcn.com):

- **Vokabular uebernehmen**: `variant`, `size`, `disabled`, `loading`, `asChild`-Analogon (siehe
  `button.php`: `href` rendert `<a>` statt `<button>`) — Namen und Werte so nah wie moeglich an
  shadcn halten (z. B. `default | secondary | destructive | outline | ghost | link` fuer
  Button-Varianten), nicht frei erfinden.
- **Naming an PHP/WordPress anpassen**: shadcn nutzt camelCase (React-Props), hier wird
  konsequent snake_case verwendet (`icon_position`, `aria_label`, `data_attributes`). Die
  Bedeutung der Props bleibt 1:1, nur die Schreibweise wechselt.
- Existiert fuer den gewuenschten Baustein eine shadcn-Komponente, deren Quelltext
  (`https://ui.shadcn.com/docs/components/<name>`) vor der Implementierung kurz pruefen: welche
  Props/Varianten/States gibt es, welche States sind fuer eine PHP/Server-Render-Komponente
  ueberhaupt relevant. Anders als frueher gibt es dabei keine Bevorzugung "kein Client-JS" mehr
  (siehe Kernhaltung in `CLAUDE.md`) — auch ein State wie `open`/`onOpenChange` darf direkt als
  volle JS-Interaktion umgesetzt werden, wenn das die bessere UX ist, statt ihn kategorisch
  auszuklammern.
- **shadcn's Vokabular driftet ueber die Zeit** — ein Live-Check lohnt sich nicht nur beim
  erstmaligen Bauen einer Komponente, sondern auch, wenn eine _bestehende_ Datei inhaltlich
  angefasst wird. Den Kopfkommentar nicht blind als weiterhin aktuell annehmen, sondern bei
  Aenderungen kurz gegenchecken.
- **Neue Formular-aehnliche Komponenten zusaetzlich gegen die eigenen Geschwister-Komponenten
  abgleichen**, nicht nur gegen shadcn's Doku: gemeinsame Config-Keys wie `disabled`,
  `aria_invalid`, `required`, `class`/`attributes`/`data_attributes` sollten ueber die ganze
  **Formular-Familie** — kanonisch definiert hier, an anderer Stelle in diesem Dokument nur noch
  referenziert, nicht erneut ausgeschrieben: `input.php`, `textarea.php`, `checkbox.php`,
  `radio.php`, `native-select.php`, `select.php`, `slider.php`, `switch.php` —
  konsistent vorhanden sein. Ein Blick auf shadcn allein deckt das nicht zwingend auf, ein Blick
  auf die eigenen Nachbardateien schon.
- **Wo wirklich kein natives HTML-Verhalten existiert** (z. B. der Avatar-Fallback jenes headless
  Primitives bei fehlgeschlagenem Bild-Ladevorgang — es gibt kein CSS-`:error`-Pseudo-Element),
  lohnt sich ein serverseitiger Trick, wenn PHP die Antwort zur Renderzeit schon kennt, statt JS
  nachzubauen: `avatar.php` puffert `image.php` und zeigt automatisch den Fallback, wenn dessen
  Output leer bleibt (`image.php` liefert bei fehlendem Theme-Asset ja bereits nichts) —
  entspricht dem Verhalten jenes headless Primitives 1:1, nur zur Renderzeit statt im Browser
  entschieden. Grenze klar dokumentieren (bei `avatar.php`: funktioniert nur fuer serverseitig
  pruefbare Quellen wie `name`/`set`, nicht fuer eine extern gegebene `src`-URL, die erst im
  Browser fehlschlagen kann). Das ist ein gutes Muster, wenn es passt — keine Pflicht, wenn eine
  volle JS-Umsetzung die bessere UX liefert (siehe Kernhaltung in `CLAUDE.md`).

**Hintergrund — warum ein Teil der bestehenden Komponenten native HTML-Basis statt JS-Nachbau
nutzt:** Diese Entscheidung wurde unter der frueheren, inzwischen aufgehobenen "natives HTML hat
immer Vorrang"-Regel getroffen und bleibt fuer die betroffenen Dateien bestehen (kein Grund, sie
ohne konkreten Anlass umzubauen), ist aber **keine Vorgabe fuer neue oder ueberarbeitete
Komponenten** mehr — dort entscheidet ausschliesslich die beste UX (Kernhaltung in `CLAUDE.md`).
Zur Einordnung, falls eine dieser Dateien angefasst wird: `accordion.php` bildet shadcn's
`type="single"` (nur ein Panel offen) ueber das native `name`-Attribut auf `<details>` nach;
`checkbox.php`/`switch.php` sind native `<input type="checkbox">` (Letzteres mit
`role="switch"` — eine gueltige ARIA-Rollentransformation, anders als `role="button"` bei
`toggle.php`, siehe unten); `native-select.php` ist ein natives `<select>`/`<option>`/
`<optgroup>` (kein Custom-Styling der Options-Liste moeglich, dokumentierter Trade-off — der Name
ist bewusst nicht `select.php`, das ist die volle JS-Variante); `slider.php` ist ein natives
`<input type="range">` fuer den Einzelwert-Fall (Mehrfach-Thumb/Range hat keine native
Entsprechung und ist bewusst nicht Teil der Datei);
`toggle.php`/`tabs.php` nutzen native Checkbox/Radio-Inputs plus gestyltes `<label>` und kuendigen
dadurch `role="checkbox"`/`"radio"` statt shadcn's `role="button"`/`"tab"` an — diese Luecke wird
seit `toggle.js`/`tabs.js` per Progressive Enhancement geschlossen (siehe Regel 10), ist also kein
offener Kompromiss mehr; `scroll-area.php` nutzt das native CSS Scrollbars Module
(`scrollbar-width`/`scrollbar-color`, WebKit `::-webkit-scrollbar-*`) statt JS-verwalteter
Fake-Thumb-Elemente; `calendar.php`/`data-table.php` ersetzen ihre React-Vorbilder
(`react-day-picker` bzw. `@tanstack/react-table`) durch PHP-Kalender-Mathematik
(`DateTimeImmutable`) bzw. echte `<a href>`-Pagination/-Sortierlinks (`add_query_arg()`), beide
inzwischen mit `calendar.js` als JS-Enhancement fuer Monatswechsel ohne Reload und `data-table.js`
fuer Sortier-/Pagination-Klicks ohne Reload (fetched dieselbe `add_query_arg()`-Ziel-URL, statt
den Re-Query zu erfinden, und tauscht nur das passende `[data-slot="data-table"]`-Fragment aus dem
geparsten Response aus — kein neuer PHP-Endpunkt noetig); `carousel/*` nutzt CSS Scroll Snap
statt einer Embla-Nachbildung, mit `carousel.js` fuer die eine Luecke, die CSS (noch) nicht
abdeckt (Previous/Next-Buttons).

## 3. Verschachtelung/Komposition pruefen

Vor jeder neuen Komponente pruefen, ob sie eine bestehende Base-Komponente **nutzen** kann, statt
Markup zu duplizieren — Vorbild ist `button.php`, das `icon.php` fuer sein Icon-Slot einbindet.
Das Puffern (`ob_start()`/`get_template_part()`/`ob_get_clean()`) steckt dafuer nicht mehr lokal in
jeder Komponente, sondern zentral im Helper `hengegroup_theme_render_icon()`
(`inc/template-parts/helpers.php`, siehe Regel 7):

```php
$icon_markup = hengegroup_theme_render_icon(["name" => "arrow-right", "set" => "lucide"]);
```

Zwei wiederkehrende Muster dafuer:

- **Slot-Nesting**: eine Komponente rendert eine andere fuer einen ihrer eigenen Config-Keys —
  z. B. nestet `input.php` `label.php` fuer sein optionales `label`-Config, oder `accordion.php`
  `typography.php` fuer sein optionales Heading-Trigger-Label.
- **Content-agnostischer Wrapper**: eine Komponente hat gar kein eigenes Markup fuer ihren Inhalt,
  sondern nimmt vom Aufrufer vorgerendertes HTML per `content`-Config entgegen (siehe Beispiel im
  Kopfkommentar von `aspect-ratio.php`) — z. B. `button-group.php`, `kbd-group.php`,
  `scroll-area.php`, `dropdown-menu.php`, die `table/*`- und `field/*`-Familien.

Ein drittes, selteneres Muster: ein `data_slot`-Config-Key auf der genesteten Komponente selbst
(Default bleibt deren eigener Standard-Slot, unveraendert fuer Standalone-Nutzung) — ein composing
Parent kann so einen eigenen `data-slot`-Wert anfordern, ohne die Attribut-Aufbau-Logik der
genesteten Datei zu duplizieren. Beispiel: `input-group.php` fordert von `input.php`/`textarea.php`
`'input-group-control'` an, `field-label.php` fordert von `label.php` `'field-label'` an.

Es gibt keine separat gepflegte Uebersichts-Liste aller bestehenden Verschachtelungs-Beziehungen
mehr (README.md dokumentiert seit dem Reduzieren auf den reinen Schnellstart keine Pro-Komponente-
Inhalte mehr, siehe Regel 9) — die einzige Quelle dafuer ist der Code selbst: vor einer neuen
Komponente den Kopfkommentar thematisch verwandter Base-Komponenten pruefen (die nennen ihre
genutzten/genestenden Bausteine dort, siehe Regel 4) sowie zur Sicherheit per
`grep -rn "get_template_part" template-parts/base/` durchsuchen, ob ein passender Baustein schon
existiert. Erst wenn keiner passt, wird neues Markup geschrieben.

## 4. Struktur, die jede Komponente einhaelt

- `declare(strict_types=1);` als erste Zeile.
- Guard clause: `if (!isset($args['config']) || !is_array($args['config'])) { return; }` — bei
  fehlender Pflicht-Config still (ohne Fatal Error) nichts rendern, WordPress-Template-typisch,
  nicht shadcn-typisch (dort wuerde TypeScript/PropTypes hart failen).
- Dateikopf-Kommentar, der alle unterstuetzten `config`-Keys mit Typ, erlaubten Werten und Default
  dokumentiert (siehe `button.php`/`typography.php`/`image.php` als Vorbild fuers Format) — gilt
  ausnahmslos fuer jede Base-Komponente, bei jeder Config-Aenderung mitpflegen. **Keine
  Datei-Enumeration in dieser Regel fuehren**: eine vollstaendige Namensliste aller Komponenten mit
  Kopfkommentar-Doku driftet garantiert auseinander, sobald eine neue Datei dazukommt oder eine
  bestehende beim Pflegen vergessen wird (genau das Muster, vor dem Regel 2 fuer shadcn's Vokabular
  warnt) — zuletzt bei `tabs.php`/`spinner.php` verifiziert, die trotz korrektem Kopfkommentar in
  einer frueheren Fassung dieser Liste fehlten. Ob eine Datei die Doku-Pflicht erfuellt, entscheidet
  ein Blick in die Datei selbst, nicht ein Abgleich gegen eine Liste hier.
- **Besteht eine Komponente aus mehr als einer Datei, wandert sie in einen eigenen, nach der
  Komponente benannten Unterordner** unter `template-parts/base/` (aktuell: `attachment/`,
  `button-group/`, `carousel/`, `dropdown-menu/`, `field/`, `input-group/`, `kbd/`, `radio/`,
  `table/`, `toggle/`) — Dateinamen bleiben dabei unveraendert (kein Umbenennen/Kuerzen), nur der
  Ordner kommt dazu; der `get_template_part()`-Pfad enthaelt den Ordnernamen entsprechend doppelt,
  z. B. `template-parts/base/radio/radio-group`. Eine JS-Enhancement-Datei (Regel 10) bleibt davon
  unberuehrt und **flach** direkt unter `assets/js/template-parts/base/`, unabhaengig vom
  PHP-Unterordner — pro Komponente gibt es ohnehin nur eine JS-Datei, ein eigener JS-Unterordner
  waere unnoetig (z. B. `assets/js/template-parts/base/dropdown-menu.js`, obwohl die zugehoerige
  `dropdown-menu.php` unter `template-parts/base/dropdown-menu/` liegt). Komponenten mit nur einer
  Datei (z. B. `button.php`, `select.php`, `checkbox.php`) bleiben direkt in
  `template-parts/base/` — kein Unterordner fuer ein einzelnes File.
- Enums (`variant`, `size`, `tag`, ...) ueber ein Allow-List-Array validieren und bei ungueltigem
  Wert still auf den Default zurueckfallen (`in_array($x, $allowed, true)` bzw.
  `array_key_exists($x, $map)`) — nie werfen, nie fataler Fehler.
- `class` / `attributes` / `data_attributes` als Passthrough-Config-Keys anbieten (Konsistenz
  ueber alle Komponenten hinweg). Praezedenz bei Konflikten mit generierten Attributen: `attributes`
  gewinnt nur bei rein optischen/beliebigen Attributen (siehe `image.php`:
  `array_merge($image_attributes, $attributes)`); strukturell bedeutungstragende generierte
  Attribute (`data-slot`, `role`, `type`, ...) bleiben geschuetzt und werden NACH `attributes`
  gesetzt, ueberschreiben es also — der ueberwiegende Rest der Komponenten setzt deshalb erst
  `$element_attributes = $attributes;` und danach die generierten Structural-Keys, damit ein
  Aufrufer nicht versehentlich `data-slot`/`role`/`type` kaputt macht. `image.php`s
  `attributes`-zuletzt-Reihenfolge ist bewusst der Ausreisser, nicht die Norm — dort hat der
  Aufrufer keinen strukturellen Key zu schuetzen, den er ueberschreiben koennte.
- Ausgabe ausschliesslich ueber `esc_html()`/`esc_attr()`; vorab escapte/zusammengesetzte Strings
  mit `// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped` kennzeichnen.

## 5. Barrierefreiheit ist Teil der API, nicht optional

Jede neue interaktive/visuelle Komponente braucht von Anfang an a11y-Config-Keys, keine
Nachruestung: z. B. `aria_label` fuer icon-only Elemente (Pflicht, wenn kein sichtbarer Text),
`decorative`-Flag fuer Icons/Bilder ohne semantischen Wert (`aria-hidden` vs. `role="img"` +
`aria-label`/`<title>`), `aria-busy`/`aria-disabled` fuer Lade-/Deaktiviert-Zustaende (siehe
`button.php`).

**Bei Formular-Komponenten: `disabled`/`aria_invalid` zusaetzlich als `data-disabled="true"`/
`data-invalid="true"` spiegeln**, nicht nur das native `disabled`-Attribut bzw. `aria-invalid`
setzen — shadcn macht das inzwischen bei allen eigenen Formular-Komponenten genauso (reiner
CSS-Hook neben der nativen/ARIA-Semantik, fuer Projekt-CSS, das nicht ausschliesslich auf
`:disabled`/`[aria-invalid]`-Selektoren setzen will). Bei Wrapper-Elementen ohne natives
`disabled` (z. B. `radio-group.php`s `<div role="radiogroup">`) reicht `data-disabled` allein.
Siehe die Formular-Familie (Regel 2) als Vorbild, plus `radio-group.php` als Beispiel fuer den
Wrapper-ohne-natives-`disabled`-Fall oben.

## 6. Sicherheit bei Datei-Zugriffen

Jede Komponente, die Config-Werte zu einem Dateipfad zusammenbaut (`icon.php`, `image.php`),
muss `name`/`set` gegen ein striktes Whitelist-Regex pruefen (`/^[a-zA-Z0-9\/_-]+$/` bzw.
`/^[a-zA-Z0-9._-]+$/` auf `basename()`) und bei fehlender Datei still `return`, bevor
`file_get_contents()`/Pfad-Aufbau passiert — kein direktes Einsetzen von Nutzer-/Config-Input in
Pfade ohne diese Pruefung.

## 7. Gemeinsame Helper: zentral in `inc/template-parts/helpers.php`, nie kopieren

Cross-cutting Logik, die mehrere Base-Komponenten brauchen (Attribute-Array → HTML-String,
Text-Highlighting, u. ae.), lebt **ausschliesslich** in `inc/template-parts/helpers.php`
(eingebunden ueber `functions.php`, global verfuegbar, Praefix `hengegroup_theme_`). Aktuell vorhanden:

- `hengegroup_theme_render_attributes(array $attributes): string` — Attribute-Array in einen
  HTML-Attribut-String rendern (inkl. Bool-Attribute, `esc_attr()`). Genutzt von praktisch allen
  Base-Komponenten, inkl. `icon.php` (dort in den bestehenden `<svg>`-Tag injiziert statt einen
  neuen Tag zu erzeugen).
- `hengegroup_theme_render_accent_text(string $content, array $highlighted_words): string` —
  `accent_words`-Highlighting (`<span class="font-accent">`). Genutzt von `typography.php`.
- `hengegroup_theme_render_icon(array $icon_config): string` — puffert einen `template-parts/base/icon`-
  Aufruf und gibt das SVG-Markup als String zurueck. Genutzt von `button.php` (Icon-Slot +
  Loading-Spinner), `accordion.php` (Chevron), `badge.php` (Icon-Slot), `avatar.php`
  (Icon-Fallback), `breadcrumb.php` (Separator + Ellipsis), `select.php` (Chevron +
  Selected-Indikator-Template), `toast.php` (Close-Icon + Close-Icon-Template), `toggle.php`
  (Icon-Slot, gleiches `data-icon`-Prinzip wie `button.php`), `combobox.php`
  (Selected-Indikator-Template, gleiches Prinzip wie `select.php`), `dropdown-menu-item.php`
  (optionaler Icon-Slot), `dropdown-menu-checkbox-item.php`/`dropdown-menu-radio-item.php`
  (Check-/Dot-Indikator), `calendar.php` (Vor-/Zurueck-Chevrons in der Monatsnavigation),
  `attachment.php` (optionaler Icon-Media-Slot), `date-picker.php` (Trigger-Icon, Default
  `calendar`), `data-table.php` (Sortier-Chevrons in sortierbaren Spaltenkoepfen) — ersetzt die
  vorher in mehreren Dateien lokal duplizierte `ob_start()`/`get_template_part()`/
  `ob_get_clean()`-Closure.
- `hengegroup_theme_render_image(array $image_config): string` — puffert einen
  `template-parts/base/image`-Aufruf und gibt das Markup als String zurueck (leerer String, wenn
  `image.php` selbst nichts rendert, z. B. fehlende Datei — siehe dessen `is_file()`-Check). Genutzt
  von `avatar.php` (Bild-Slot, dessen Leer-Pruefung den Fallback triggert, siehe Regel 2),
  `card.php` (optionaler Cover-Media-Slot) und `attachment.php` (optionaler Image-Media-Slot,
  gleiches `variant`-Switch-Prinzip wie dessen Icon-Media-Slot oben) — ersetzt die vorher in
  mehreren Dateien separat duplizierte `ob_start()`/`get_template_part('.../image')`/
  `ob_get_clean()`-Sequenz, gleiches
  Prinzip wie `hengegroup_theme_render_icon()` oben, nur fuer `image.php`.
- `hengegroup_theme_warn_missing_aria_label(string $component, bool $is_icon_only, string $aria_label): void`
  — Dev-Time-Hinweis (`_doing_it_wrong()`, no-op ausser bei `WP_DEBUG`) fuer icon-only interaktive
  Elemente ohne das dokumentiert-Pflicht-`aria_label`; rendert in Produktion identisch, macht den
  Fehler beim Entwickeln sichtbar statt ihn erst im a11y-Audit zu finden (verhindert kein Rendering,
  siehe Regel 4s "nie hart failen"). Genutzt von `button.php` und `toggle.php` (beide dokumentieren
  `aria_label` als Pflicht fuer den Icon-only-Fall, siehe deren Kopfkommentare).
- `hengegroup_theme_field_description_id(string $control_id): string` /
  `hengegroup_theme_field_error_id(string $control_id): string` — leiten die `id` von
  `field-description.php`/`field-error.php` deterministisch aus der `id` der zugehoerigen
  Formular-Kontrolle ab (reine Funktionen, kein `wp_unique_id()`), statt dass der Aufrufer eine
  eigene `id` erfindet und an zwei Stellen (Description/Error-Komponente + Kontrolle) identisch
  abtippen muss. Genutzt von `field-description.php`/`field-error.php` selbst (ueber deren `for`-
  Config) sowie von `hengegroup_theme_field_describedby()` direkt darunter.
- `hengegroup_theme_field_describedby(string $control_id, bool $has_description = true, bool $has_error = true): string`
  — baut den `aria-describedby`-Wert fuer eine Formular-Kontrolle aus denselben zwei Helpern oben
  (space-separated, wie `aria-describedby` es fuer mehrere IDs verlangt); `$has_description`/
  `$has_error` auf `false`, wenn nur eine der beiden Komponenten tatsaechlich gerendert wird (eine
  ID auf ein nicht existierendes Element ist schlimmer als keine ID). Genutzt in der
  `field.php`-Komposition (siehe deren Kopfkommentar) — reduziert den laut Audit
  vom 2026-08-07 haeufigsten Fehlerquell-Punkt der Formular-Familie (von Hand erfundene/kopierte
  `aria-describedby`-IDs) auf einen einzigen gemeinsamen `$control_id`, ohne `field.php`s bewusst
  content-agnostisches Wrapper-Design (siehe dessen Kopfkommentar) anzutasten.

**Verbindlich bei jeder neuen Komponente:**

1. **Erst pruefen, ob es die benoetigte Funktion schon gibt** — `inc/template-parts/helpers.php`
   durchsuchen, bevor eine neue Closure/Funktion fuer Attribut-Rendering, Text-Verarbeitung o. ae.
   geschrieben wird. Nicht blind eine lokale Kopie anlegen.
2. **Nichts mehr lokal (per `static function`-Closure) im Template-Part nachbauen**, was schon in
   `helpers.php` existiert — einfach aufrufen (`hengegroup_theme_render_attributes($element_attributes)`
   statt einer neuen `$render_attributes`-Closure).
3. **Wird eine neue, wirklich komponentenuebergreifende Funktion gebraucht** (nicht nur fuer eine
   einzelne Komponente), gehoert sie ebenfalls nach `inc/template-parts/helpers.php` mit
   `hengegroup_theme_`-Praefix — nicht in die einzelne Template-Part-Datei.

## 8. Abgrenzung zu `components/`/`sections/`

`template-parts/base/` bleibt bewusst generisch und projektunabhaengig — Homepage-Sections,
Marken-Patterns, zusammengesetzte Layouts gehoeren nie hierher, unabhaengig vom Projekt. Alles
Projektspezifische gehoert nach demselben Atomic-Design-Muster in eigene Ordner wie
`template-parts/components/` (Molecules) oder `template-parts/sections/` (Organisms) im
jeweiligen Projekt-Theme.

## 9. Doku-Pflicht

`README.md` dokumentiert bewusst nur noch den Schnellstart ("Neues Projekt aus dieser Vorlage
starten") — keine `## Struktur`-Liste, keine Komposition-Uebersicht, keine Pro-Komponente-
Nutzungsabschnitte mehr (fruehere Fassungen dieser Datei hatten das, siehe Git-Historie, falls das
je zurueckgebaut werden soll). Die alleinige Dokumentationspflicht pro Komponente ist ihr eigener
Dateikopf-Kommentar (Regel 4: jeder unterstuetzte `config`-Key mit Typ, erlaubten Werten und
Default) — keine zweite Pflegestelle in README noetig oder gewuenscht.

Verschachtelungs-/Kompositions-Beziehungen (Regel 3) gehoeren ebenfalls in den Kopfkommentar, nicht
in eine separate Uebersicht: eine Komponente, die eine andere nestet, nennt das dort (Vorbild:
bestehende Kopfkommentare, die schon auf genutzte Bausteine verweisen, z. B. `button.php` auf
`icon.php`). Bei staerker genesteten Komponenten (z. B. `label.php`, das von vielen anderen
Formular-Komponenten eingebunden wird) reicht ein kurzer Hinweis im Kopfkommentar der genesteten
Datei selbst ("wird u. a. von input.php/textarea.php/... eingebunden"), keine vollstaendige
Rueckverfolgung noetig.

## 10. JS-Enhancement

JS ist eine vollwertige Option, kein letztes Mittel (siehe Kernhaltung in `CLAUDE.md`) — die
Entscheidung faellt danach, was fuer eine Komponente die beste UX liefert, nicht danach, ob sich JS
vermeiden laesst. Die folgenden Beispiele dokumentieren, wie die bestehenden JS-Enhancements aktuell
gebaut sind, und dienen als Vorbild fuer die etablierten Techniken (Progressive Enhancement,
Template-Cloning, `data-js`, ...) — nicht als Beleg dafuer, dass JS erst nach Ausschoepfen von
nativem HTML in Frage kommt.

`select.php`/`select.js` bauen die native `<select>`-Basis (`native-select.php`, siehe Regel 3) zu
einer voll custom-gestylten Listbox aus. `tooltip.php`/`tooltip.js`: das native `title`-Attribut
deckt shadcn's Verhalten nicht ab (unstylbar, kein Rich-Content, unzuverlaessig auf Touch), daher
volle Progressive-Enhancement-Umsetzung. `toast.php`/`toast.js` ist strukturell anders: kein
"rendere UI mit dieser Config", sondern ein einmal pro Seite gemounteter, globaler Viewport plus
eine **imperative** JS-API, die andere JS-Module aufrufen — gebaut gegen die tatsaechliche
`sonner`-npm-Bibliothek (eine Methode pro Typ: `toast.success()`/`.error()`/`.warning()`/
`.info()`/`.loading()`/`.message()`, plus `toast.promise()`/`toast.dismiss(id)`, siehe Kopfkommentar
von `toast.php`/`toast.js`). `combobox.php`/`combobox.js`: natives `<input type="text" list="...">` + `<datalist>`
liefert einen echten, wenn auch ungestylten Zero-JS-Fallback, JS baut daraus eine custom-gestylte
Filter-Listbox nach dem "Select-Only Combobox"-Prinzip (`role="combobox"` direkt auf dem
Text-Input). `dropdown-menu.php`/`dropdown-menu.js`: natives `<details>`/`<summary>` liefert Klick
zum Oeffnen/Schliessen und Tab-Erreichbarkeit aller Items bereits ohne JS; `dropdown-menu.js`
ergaenzt Outside-Click/Escape-Schliessen, das volle WAI-ARIA-Menu-Pattern (Roving Tabindex,
Pfeiltasten/Type-Ahead) und macht `menuitemcheckbox`/`menuitemradio`-Items ueberhaupt erst
umschaltbar (kein natives Pendant dafuer). `calendar.php`/`calendar.js`: ein Monatswechsel erzeugt
Daten, die PHP nie gerendert hat — `calendar.js` reimplementiert dieselbe Grid-Formel
(`DateTimeImmutable` serverseitig, natives `Date` clientseitig) selbst; alles andere (Modus,
Wochenstart, Min/Max/Disabled/Selected-Daten) liest es aus `data-*`-Attributen, die `calendar.php`
dafuer rendert — Config fliesst einmalig aus PHP, nicht zweimal gepflegt. `date-picker.php`/
`date-picker.js`: gleiches `<details>`/`<summary>`-Rezept wie `dropdown-menu.php`, nur mit
genesteter `calendar.php` statt Menu-Items als Panel-Inhalt; die JS-Schicht ist entsprechend duenn
(Outside-Click/Escape plus ein `change`-Listener, der den Trigger-Text neu formatiert).
Monatsnavigation ohne Reload bekommt es automatisch von `calendar.js` mit, das sich unabhaengig
ueber `[data-slot="calendar"]` initialisiert. `toggle.js`/`tabs.js` schliessen ausschliesslich
eine ARIA-Ankuendigungs-Luecke (`role="checkbox"`/`"radio"` statt shadcn's `role="button"`/
`"tab"`, weil `toggle.php`/`tabs.php` native Checkbox-/Radio-Inputs nutzen): das native Input wird
`aria-hidden`/`tabindex="-1"` gesetzt und das gepaarte `<label>` wird zum echten fokussierbaren
Element mit der korrekten Rolle (`role="button" aria-pressed`/`role="tab" aria-selected`, per
`change`-Listener synchron gehalten); `tabs.js` ergaenzt zusaetzlich Roving Tabindex plus
orientierungsbewusste Pfeiltasten-Navigation. `toggle.js`s Kriterium, welche Inputs es direkt
anhebt, ist bewusst **nicht** der native `type`, sondern ob ein `type: 'radio'`-Input zum
Zeitpunkt des Sweeps noch innerhalb eines `role="radiogroup"` sitzt
(`input.closest('[role="radiogroup"]')`); jedes andere Checkbox/Radio-
`[data-slot="toggle-input"]` wird direkt angehoben, inklusive `toggle-group.php`s `multiple`-Modus
und `calendar.php`s pro-Tag genestetem `toggle.php`. `calendar.js` importiert dafuer
`enhanceToggle()` aus `toggle.js` direkt (exportierte oeffentliche Funktion statt `window.*`) und
ruft sie auf jeder neu erzeugten Tageszelle selbst auf. `toggle-group.php`s `single`-Modus (die
`role="radio"`-Items unter `role="radiogroup"`) war lange eine bewusst offene, im Kopfkommentar
dokumentierte Ausnahme, weil ein Rollen-Upgrade auf `role="button"` dort ohne JS-verwaltete
Exklusivitaet ein halbfertiger Bruch gewesen waere — geschlossen durch das eigenstaendige
`toggle-group.js` (naeher an `select.php`s vollem Rebuild als an `toggle.js`s kleiner
Relabeling-Schicht, wie im Kopfkommentar als Aufwandseinschaetzung vorhergesagt): es hebt den
Wrapper zuerst von `role="radiogroup"` auf `role="group"` an, ruft danach `enhanceToggle()` aus
`toggle.js` fuer jedes Item auf (wiederverwendet statt dupliziert, Regel 7 sinngemaess auf JS
uebertragen — `toggle.js`s eigener Guard blockt zu diesem Zeitpunkt nicht mehr, da die Rolle schon
umgehaengt ist) und synchronisiert zusaetzlich bei jedem `change`-Event **aller** Geschwister-Items
deren `aria-pressed` neu, weil natives Radio-Exklusivitaets-Verhalten kein `change`-Event auf dem
still abgewaehlten Item ausloest — genau die Luecke, die `enhanceToggle()`s eigener
Pro-Item-Listener allein nicht schliessen wuerde. Die zugrunde liegenden nativen Radio-Inputs
bleiben unveraendert (gleiches gemeinsames `name`), die eigentliche Exklusivitaet kommt weiterhin
vom Browser, nicht von JS-verwaltetem Zustand — nur die angekuendigte ARIA-Rolle/-State aendert
sich. `enhanceToggle()` selbst hat dafuer einen Idempotenz-Guard (`label.dataset.js === 'toggle'`)
bekommen, damit ein doppelter Aufruf (unabhaengig davon, ob `toggle-group.js` oder `toggle.js`s
eigener Sweep zuerst laeuft) keine doppelten Event-Listener registriert. `carousel/carousel.js`:
CSS Scroll Snap (`scroll-snap-type`/`scroll-snap-align`) liefert Touch-/Trackpad-Wischen,
Mausrad-Scrollen und Tastatur-Pfeiltasten komplett ohne JS; `carousel.js` schliesst nur die eine
echte Luecke, die CSS (noch) nicht abdeckt: `carousel-previous.php`/`carousel-next.php` als reine
Buttons ohne eigenes Scroll-Verhalten (echter Klick-Handler) plus, per `IntersectionObserver`,
Enable/Disable von Previous/Next an den Raendern (uebersprungen bei `data-loop="true"`).
`data-table.js`: die Sortier-/Pagination-`<a href>`-Links aus `data-table.php`s eigenem
Kopfkommentar sind bereits echte, funktionierende `add_query_arg()`-Ziel-URLs (identisch zu
`calendar.js`s Monatsnav-Links) — dieses Modul kann den eigentlichen Re-Query serverseitiger Daten
weiterhin nicht erfinden (anders als `calendar.js`s reine Datums-Arithmetik), fetched aber genau
diese Ziel-URL per `fetch()`, parsed die Response per `DOMParser` und ersetzt nur das per Index
korrelierte `[data-slot="data-table"]`-Fragment (mehrere Tabellen auf einer Seite bleiben stabil
korrelierbar, weil `add_query_arg()` nur den Query-String aendert, nicht die Seitenstruktur) — kein
neuer PHP-Endpunkt, reine Wiederverwendung des echten Server-Renders statt eines Client-Nachbaus.
`history.pushState()` haelt die URL synchron, ein `popstate`-Listener holt bei Vor-/Zurueck-Klicks
denselben Weg nach; schlaegt der Fetch fehl, faellt das Modul auf eine echte Navigation zurueck
(`window.location.assign()`), nie ein toter Klick.

Etablierte Konventionen fuer jede neue JS-Enhancement-Datei:

- **Progressive Enhancement ist eine gute Default-Technik, keine Pflicht.** Wenn sie passt (siehe
  Beispiele oben), rendert die PHP-Komponente zuerst eine funktionsfaehige Basis, JS legt sich als
  Verbesserungsschicht darueber. Wenn eine volle Custom-JS-Umsetzung ohne native Zwischenstufe die
  bessere UX liefert, ist das genauso zulaessig — die Wahl faellt pro Komponente.
- **Eine dokumentierte, aber technisch schliessbare ARIA-Ankuendigungs-Luecke wird beim Bauen der
  Komponente sofort mitgeschlossen, nicht auf spaeter verschoben.** Wenn eine Komponente aus
  Native-HTML-Gruenden eine andere Rolle ankuendigt als ihr shadcn-Vorbild — z. B.
  `role="checkbox"`/`"radio"` statt `role="button"`/`"tab"`, weil keine gueltige
  ARIA-Rollentransformation zwischen beiden existiert (`toggle.php`/`tabs.php`) —, wird das nicht
  als dauerhafter Kompromiss stehen gelassen: natives Element `aria-hidden`/`tabindex="-1"`, das
  gepaarte sichtbare Element (typischerweise ein `<label>`) uebernimmt die korrekte Rolle, per
  `change`-Listener synchron gehalten. Diese JS-Datei wird **direkt beim Bauen der Komponente mit
  angelegt**, nicht als offener Punkt im Kopfkommentar zurueckgelassen — Vorbild: `toggle.js`/
  `tabs.js`. Zustands-/Sichtbarkeitslogik bleibt dabei unangetastet, JS ruehrt nur ARIA/Fokus an.
  **Ausnahme, wenn der Trick selbst mit der Komposition kollidiert:** steckt das native Element in
  einer Gruppen-Komposition, deren Wrapper-Rolle bei einem Rollen-Upgrade der einzelnen Items
  brechen wuerde, ist das ein eigenstaendiges, groesseres JS-Vorhaben statt des kleinen Tricks —
  bleibt so lange explizit im Kopfkommentar als bewusst offene, groessere Baustelle dokumentiert,
  bis sie separat priorisiert und umgesetzt wird. `toggle-group.php`s `single`-Modus war genau
  dieser Fall (`role="radio"`-Items unter `role="radiogroup"`) und ist inzwischen durch
  `toggle-group.js` geschlossen (siehe oben) — als Vorbild dafuer, wie so ein Vorhaben aussieht,
  wenn es priorisiert wird: Wrapper-Rolle zuerst umhaengen (`role="radiogroup"` -> `role="group"`),
  danach erst die kleine Item-Relabeling-Funktion (hier: `toggle.js`s `enhanceToggle()`)
  wiederverwenden statt duplizieren (Regel 7), plus die durch den Rollen-Wechsel neu entstehende
  JS-Verantwortung (hier: Exklusivitaets-Announcement der Geschwister-Items resynchronisieren, weil
  natives Radio-Verhalten dafuer kein `change`-Event liefert) explizit mit abdecken, nicht nur die
  Rolle selbst umhaengen.
  **Erzeugt eine andere Komponente `toggle.php`-foermiges Markup client-seitig nach dem initialen
  JS-Sweep** (z. B. `calendar.js` bei der Monatsnavigation), ist diese Komponente selbst dafuer
  verantwortlich, die schliessende Funktion (z. B. `enhanceToggle()`) per echtem Modul-Import auf
  ihr neu erzeugtes Markup anzuwenden — nicht durch Warten auf einen erneuten globalen Sweep oder
  Duplizieren der Logik.
- **Wiederkehrende Sub-Strukturen ueber ein `<template>`-Element klonen statt in JS neu zu
  erfinden.** Wenn JS wiederholt Markup erzeugt, das serverseitig bereits einmal gerendert wird
  (z. B. ein Icon aus `icon.php`), dieses Markup einmal in ein verstecktes
  `<template data-slot="...">` rendern und per `template.content.cloneNode(true)` im JS
  wiederverwenden, statt eine zweite, abweichende Darstellung nachzubauen — sonst sehen
  serverseitig und per JS erzeugte Instanzen unterschiedlich aus. Vorbild: `select.php`s
  Selected-Indikator-Template, `toast.php`s Close-Icon-Template und je ein Icon-Template pro
  Toast-Typ, `combobox.php`s eigenes Selected-Indikator- und Empty-State-Template.
- **Eine echte oeffentliche JS-API exportieren, wenn das der Zweck der Komponente ist.**
  `toast.js` exportiert neben `initToast()` auch `toast()` als API, die andere JS-Module
  importieren und aufrufen (`import { toast } from ".../toast.js"`) — nicht jede JS-Enhancement-
  Komponente ist rein deklarativ/init-only; wenn andere Module die Komponente imperativ ansteuern
  koennen sollen, ist ein benannter Export dafuer der richtige Ort, nicht ein globales `window.*`.
  `toast` traegt dabei zusaetzliche Methoden direkt auf der Funktion (`toast.success`/`.error`/
  `.warning`/`.info`/`.loading`/`.message`/`.custom`/`.dismiss`/`.promise`) statt fuer jede einzeln
  einen eigenen benannten Export zu erzeugen — dasselbe Objekt-mit-Methoden-Muster wie die echte
  `sonner`-Bibliothek selbst.
- **`data-js`-Attribut als CSS-Schalter.** Das JS setzt nach der Initialisierung ein `data-js`-
  Attribut auf den Wrapper; Projekt-CSS entscheidet anhand dessen, was sichtbar ist (natives
  Element vor JS, Custom-UI danach) — dokumentiert im Kopfkommentar der jeweiligen Komponente.
  `tooltip.js` entfernt zusaetzlich das `title`-Attribut nach der Initialisierung, um einen
  doppelten Tooltip (nativ + Custom-Panel) zu vermeiden.
- **Niemals `hidden`/`display:none` fuer Inhalte, die trotzdem per ARIA referenziert werden.**
  `tooltip.php`s Content-Element bekommt bewusst kein `hidden`, weil das es aus dem
  Accessibility-Tree entfernen und die `aria-describedby`-Referenz brechen wuerde — Sichtbarkeit
  laeuft stattdessen ueber ein `data-state`-Attribut, das Projekt-CSS interpretiert.
- **Ein Modul pro Template-Part, Pfad spiegelt die PHP-Datei.** Enhancement-JS fuer eine Datei
  unter `template-parts/<...>.php` liegt unter dem identischen relativen Pfad in
  `assets/js/template-parts/<...>.js` — z. B. `template-parts/base/select.php` →
  `assets/js/template-parts/base/select.js`. So findet man das JS zu einer Komponente ohne
  Suchen, auch wenn spaeter `template-parts/components/`/`template-parts/sections/` dazukommen.
  Allgemeines JS ohne 1:1-Bezug zu einem einzelnen Template-Part (z. B. `header.js` fuers globale
  Seiten-Header-Verhalten) bleibt in `assets/js/components/`. Jedes Modul exportiert ein
  `export function init<Name>()`, registriert in `assets/js/app.js`
  (`onDomReady(() => { ...; init<Name>(); })`) — gleiches Muster wie das bestehende `initHeader()`.
- **DOM als einzige Datenquelle, keine doppelte PHP/JS-Konfiguration.** `select.js` liest die
  Optionen direkt aus dem nativen `<select>` (`<option>`/`<optgroup>`), statt eine zweite,
  parallele Optionsliste von PHP zu erhalten — sonst koennen PHP- und JS-Zustand auseinanderlaufen.
- Etablierte WAI-ARIA-Patterns respektieren statt eigene Interaktionsmuster zu erfinden (hier:
  "Select-Only Combobox" — `role="combobox"` + `aria-activedescendant`, Fokus bleibt immer auf dem
  Trigger, nicht auf den Listbox-Items).

---

## Checkliste fuer eine neue Base-Komponente

- [ ] Phase 1 beachtet: nur funktionale Tailwind-Klassen (kein visuelles Styling), siehe
      `CLAUDE.md` Regel 1?
- [ ] Falls ueberhaupt Styling-Code entsteht: ausschliesslich Tailwind, keine andere CSS-Technik
      ohne dokumentierte Ausnahme (`CLAUDE.md` Regel 1)?
- [ ] `data-slot`/`data-variant`/`data-size`-Hooks gesetzt, auch wenn Phase 2 sie noch nicht
      konsumiert?
- [ ] Gibt es eine passende shadcn/ui-Komponente? Deren Props-/Varianten-Vokabular als Vorlage
      genutzt (snake*case, sonst 1:1) — bei Aenderung an einer \_bestehenden* Datei kurz
      gegengecheckt, ob shadcn's Vokabular seither weitergewandert ist?
- [ ] Bei Formular-aehnlichen Komponenten: Config-Keys gegen die bestehenden Geschwister der
      Formular-Familie (Regel 2) auf Konsistenz geprueft (`disabled`/`aria_invalid`/`required`/
      `data-disabled`/`data-invalid`-Spiegelung, siehe Regel 5)?
- [ ] Kann eine bestehende Base-Komponente per `get_template_part()` eingebettet werden, statt
      Markup zu duplizieren?
- [ ] `declare(strict_types=1)` + Guard clause + Dateikopf-Doku vorhanden?
- [ ] Enums per Allow-List mit stillem Default-Fallback validiert?
- [ ] `class` / `attributes` / `data_attributes` als Passthrough angeboten?
- [ ] a11y-Config (aria-label/decorative/aria-busy/...) mitgedacht, nicht nachtraeglich?
- [ ] Falls Datei-Zugriff: Name/Set-Werte per Whitelist-Regex validiert?
- [ ] `inc/template-parts/helpers.php` auf vorhandene Helper geprueft, bevor eine neue
      Closure/Funktion fuer Attribut-Rendering o. ae. geschrieben wurde?
- [ ] Neue, komponentenuebergreifende Helper nach `inc/template-parts/helpers.php` ausgelagert
      statt lokal in der Template-Part-Datei belassen?
- [ ] JS als vollwertige Option eingeplant (kein "erst natives HTML pruefen"), nicht nur als
      letztes Mittel? Falls eine JS-Schicht dazukommt: `assets/js/template-parts/<gespiegelter
Pfad>.js` + etabliertes Pattern (`data-js`, `init<Name>()`, siehe Regel 10) genutzt?
- [ ] Kuendigt eine native Loesung wegen fehlender gueltiger ARIA-Rollentransformation eine andere
      Rolle an als shadcn's Vorbild (z. B. `checkbox`/`radio` statt `button`/`tab`)? Falls ja: die
      schliessende JS-Enhancement-Datei (natives Element `aria-hidden`/`tabindex="-1"`, gepaartes
      Element uebernimmt die korrekte Rolle) direkt jetzt mitbauen, nicht nur im Kopfkommentar als
      offene Luecke dokumentieren (siehe Regel 10, Vorbild `toggle.js`/`tabs.js`)?
- [ ] Besteht die Komponente aus mehr als einer Datei (z. B. ein Wrapper + Item-Atome): eigener
      Unterordner unter `template-parts/base/<komponente>/` angelegt (eine JS-Enhancement-Datei
      bleibt dabei flach unter `assets/js/template-parts/base/`, siehe Regel 4)?
- [ ] Dateikopf-Kommentar vollstaendig (Config-Keys, Verschachtelungs-/Kompositions-Hinweise auf
      genutzte/genestete Bausteine), siehe Regel 4/9? (Kein README-Update mehr noetig — README
      dokumentiert seit Regel 9 nur noch den Schnellstart.)
- [ ] `composer lint` sauber (siehe `CLAUDE.md` Regel 11), falls `vendor/` installiert ist?
