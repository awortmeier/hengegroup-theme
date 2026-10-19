# Umsetzungsplan: Komponenten vereinheitlichen

Plan, um alle Komponenten nach `docs/komponenten-vereinheitlichen.md` zu ueberarbeiten. Grundlage
ist eine Vorab-Inventur (Stand 2026-10-18). Die volle Inventur je
Komponente (Phase 1) folgt beim Start der Komponente. Datei loeschen, wenn alles umgesetzt ist
(Ergebnisse gehoeren dann nach `entscheidungen.md`/`how-to.md`).

Jede Komponente durchlaeuft die Phasen 1-6 der Anleitung mit den dortigen Stopps. Hier steht pro
Komponente nur, was vorab auffaellt und was voraussichtlich zu tun ist.

## Reihenfolge

| #   | Komponente                       | Aufrufer | Befund in einem Satz                                                                                    | Nutzen      |
| --- | -------------------------------- | -------- | ------------------------------------------------------------------------------------------------------- | ----------- |
| 1   | ~~Typografie + Sektionskopf~~    | 49       | Jeder Block baut seinen Kopf selbst, mit Margins und Groessen-Ueberschreibungen; Editor kopiert Klassen | hoch        |
| 2   | Button                           | 17       | Editor-Vorschau spiegelt Klassen von Hand; zwei Inline-Nachbauten; Margins/`!font-semibold` von aussen  | hoch        |
| 3   | Hinweis (Alert, neu)             | 4        | Vier Status-/Hinweisboxen mit eigenem Markup, keine Base                                                | hoch        |
| 4   | Formularfamilie                  | 13       | Label-/Fehlerfarben je Aufrufer, ein Inline-Nachbau, `mt-0.5` an Checkbox                               | mittel      |
| 5   | Badge                            | 6        | Kicker-Badge mit Klassen-Ueberschreibungen an drei Stellen, Chip in Historie inline                     | mittel      |
| 6   | Bild                             | 9        | Fuenf verschiedene Radien, Radius/Schatten je Aufrufer                                                  | mittel      |
| 7   | Akzentfarben                     | (alle)   | `ACCENT_CLASSES` in JSX ist Kopie von `hengegroup_theme_get_accent_classes()`                           | mittel      |
| 8   | Card (Nacharbeit)                | 1 Helfer | Stellen-Zeile baut Titel als Pseudo-Slot; Produktkarte eigener Nachbau mit eigenem Schatten             | niedrig     |
| 9   | Navigation (Header/Footer)       | 3        | Header-Pill inline, Panel-Link-Klassen in `navigation.php`                                              | niedrig     |
| 10  | Carousel/Buehne                  | 1 Block  | Reiter-Button inline in `buehne/render.php`                                                             | niedrig     |
| 11  | Interne Bausteine                | intern   | Nur von anderen Base-Teilen genutzt: Regel-Check, keine Bloecke                                         | niedrig     |
| 12  | Bibliothek ohne Projekt-Aufrufer | 0        | 25 Komponenten ohne Aufrufer: bleiben als Bibliothek                                                    | entschieden |

Begruendung der Reihenfolge: 1-3 betreffen fast jede Seite und erzeugen die meisten Margins und
Doppelungen. Typografie zuerst, weil Button, Badge, Hinweis und Formulare Text darin setzen.

---

## 1. Typografie + Sektionskopf -- erledigt

`section-header.php` + `section-header-classes.json`, `typography-classes.json`. Rest-Margins in
Produktkarte, Formularen, Kennzahl, Stellen-Liste und Footer folgen in 4, 8 und 9.

## 2. Button

**Befund**

- `base/button.php`: Varianten nach Markenfarben + `outline`/`ghost`/`link`, Groessen
  `sm`/`base`/`lg`/`icon-*`. Klassen inline im PHP.
- Editor spiegelt einen Teil von Hand (`BUTTON_PREVIEW_VARIANTS`/`_SIZES` in `shared.jsx`).
- Aufrufer-Ueberschreibungen: `ml-auto` (firma, auszeichnung), `mt-2 !font-semibold`
  (product-card), `w-full sm:w-auto` (buehne, 2x), `bg-grey-light/10 text-grey-light`
  (footer, Ghost-Icon auf dunkel).
- Nachbauten: Reiter-Button in `buehne/render.php:211`, Pill in `header.php:73`.
- Genutzt: `grey-dark`, `grey-light`, `ghost`, Akzentfarben, `lg`, `icon-base`, `icon-lg`.
  Vermutlich ungenutzt: `henge-grey`, `destructive`, `link`, `sm`, `icon-sm` (in Phase 1 pruefen,
  Regel 7).

**Plan**

1. Inventur inkl. Formular-Submit, Pagination, Carousel-Pfeile.
2. Zielbild: `button-classes.json`; Varianten auf genutzte reduzieren; Variante fuer
   "Ghost auf dunkel" statt Footer-Ueberschreibung; `full_width` responsive klaeren
   (`w-full sm:w-auto` als Option oder Layout-Sache des Aufrufers). Frage: Darf ein Redakteur
   die Button-Variante waehlen, oder folgt sie immer der Flaeche (wie bei der Card)?
3. Base auf JSON umstellen, Editor `buttonClassName()` aus derselben JSON statt
   `buttonPreviewClassName`.
4. `ml-auto`/`mt-2` durch Footer-Slot bzw. Gap der umgebenden Komponente ersetzen; Header-Pill
   und Buehnen-Reiter einordnen (Button-Variante, Toggle oder bewusst eigen).

## 3. Hinweis (Alert, neu)

**Befund** -- keine Base vorhanden, vier Nachbauten mit unterschiedlichem Radius/Padding:

- `request-form.php:77` Erfolgsmeldung (`rounded-2xl px-6 py-5`, Akzentfarbe, `role=status`)
- `request-form.php:156` Fehlerliste (`rounded-2xl px-6 py-4`, rot, `role=alert`, `mt-2` in Liste)
- `single-stellenangebote.php:89` Status (`rounded-xl px-5 py-4`, blau, `mb-6`)
- `single-product.php:68` Info mit Icon (`rounded-[14px] px-5 py-4`)

**Plan**

1. Inventur (auch JS-erzeugte Meldungen in `request-form.js`/`job-application.js`, `toast.php`
   abgrenzen).
2. Zielbild nach shadcn `Alert`: `base/alert.php` + `alert-classes.json`, `variant`
   (`default`/`success`/`destructive`/Akzent?), Slots `icon`, `title`, `description`; `role`
   aus Variante. Eine Stufe fuer Radius und Padding.
3. Base, Helfer `hengegroup_theme_render_alert()`.
4. Vier Stellen umstellen, JS-Meldungen per `<template>` aus derselben Base klonen.

## 4. Formularfamilie

`field/*`, `input`, `textarea`, `native-select`, `checkbox`, `label`, `input-group` sowie
`components/inquiry-form.php`, `job-application-form.php`, `request-form.php`,
Helfer `hengegroup_theme_render_form_field()`.

**Befund**

- Label-Klasse (`text-sm font-semibold text-grey-dark|light`) und Fehlerfarbe (`!text-red-300`)
  werden in jedem Formular selbst gesetzt (`label_class`/`error_class`) -- Farbe folgt der
  Flaeche, gehoert zentral (Regel "Farben auf Flaechen").
- `job-application-form.php:76` baut eine Feldgruppe (Datei-Upload) inline statt `field-set`/
  `field-legend`.
- `checkbox` bekommt `mt-0.5` von aussen; Fehlerliste mit `mt-2`.
- Zwei Aufrufwege: `hengegroup_theme_render_form_field()` und direkte `get_template_part`-Aufrufe.

**Plan**

1. Inventur aller Felder, Feldabstaende (`gap-5`), hell/dunkel.
2. Zielbild: `field-classes.json` (Label, Beschreibung, Fehler, Control je `tone` hell/dunkel);
   Achse `tone` am Formular statt Klassen je Feld. Frage: `tone` an `field` oder per
   Eltern-Selektor (`data-tone`) vererben?
3. Ein Aufrufweg (`render_form_field` oder Base direkt, nicht beides).
4. Drei Formulare umstellen, Upload-Gruppe auf `field-set`.

## 5. Badge

**Befund**

- Varianten nach Markenfarben + `outline`, `font` `primary`/`accent`.
- Kicker-Badge dreimal mit eigenen Ueberschreibungen: `section-intro` (`px-3.5 tracking-[1.5px]
uppercase`), `render_label_badge` (`uppercase tracking-wide`), Buehne (nur `font`).
- Chips (`outline`) in `products.php` und Produktkarte mit `max-w-full`.
- Historie-Punkt (`historie-eintrag/render.php:27`) ist Deko, kein Badge (Grenzfall).

**Plan**

1. Inventur, inkl. Editor (Historie, Buehne-statisch nutzen `font-accent`/`uppercase`).
2. Zielbild: `badge-classes.json`; `kicker` als eigene Variante bzw. Achse statt
   Ueberschreibung; ein Tracking-Wert.
3. `render_label_badge` und `section-intro` auf die Variante umstellen, Editor-Vorschau aus JSON.

## 6. Bild

**Befund**

- Radien je Aufrufer: `rounded-2xl` (galerie), `rounded-xl` (product-card, auszeichnung),
  `rounded-[20px]` (single-product), `rounded-card-raised` + `shadow-card-raised` (bild-text),
  `rounded-full` (auszeichnung `circle`). Editor kopiert sie (`bild-text`, `galerie`).

**Plan**

1. Inventur aller Bilder mit Radius/Schatten/Seitenverhaeltnis.
2. Zielbild: Achse `shape` (`default`/`rounded`/`circle`) und ggf. `elevated` in
   `image-classes.json`; ein Radius fuer Inhaltsbilder. Frage: Logos/Hintergrundbilder ausnehmen?
3. Aufrufer und Editor-Vorschauen umstellen.

## 7. Akzentfarben

**Befund** -- `ACCENT_CLASSES` in `shared.jsx` ist eine von Hand gespiegelte Kopie von
`hengegroup_theme_get_accent_classes()` (Zielbild 2 verletzt). Genutzt in 6 Editoren.

**Plan** -- `accent-classes.json` als einzige Quelle, PHP-Helfer und `shared.jsx` lesen sie.
Kann bei Button (2) oder Badge (5) mitgemacht werden.

## 8. Card (Nacharbeit)

**Befund**

- `hengegroup_theme_render_job_row()` (`careers.php`) uebergibt Titel und Pfeil als freies
  Markup im `content` -- Pseudo-Slot (Review-Punkt der Anleitung).
- `components/product-card.php`: eigener Aufbau (`rounded-2xl`, eigener Schatten, Margins
  `mb-1`/`mb-2`/`mt-2`). Laut Card-Runde bewusst eigene Komponente; Margins und Schatten trotzdem
  nach den Regeln.
- `careers.php` Stellen-Gruppen mit `mb-12 last:mb-0`/`mb-4.5`.

**Plan** -- nur Phase 6 + kleine Umsetzung: Titel-/Trailing-Slot fuer Stellen-Zeile pruefen,
Produktkarte auf Gap und einen Schatten-Token, Gruppen auf `gap`.

## 9. Navigation (Header/Footer)

**Befund** -- `navigation-menu` + `dropdown-menu` im Header genutzt;
`inc/template-parts/navigation.php` setzt Panel-Link-Klassen selbst; Header-Pill (`header.php:73`)
inline; Footer-Ueberschrift `mb-4`.

**Plan** -- Inventur, dann entscheiden: Panel-Link als Variante von `navigation-menu-link`, Pill
als Badge-/Button-Variante. Keine Bloecke betroffen, kein Editor-Teil. Mobile-Navigation (offen in
`to-do.md`) danach direkt auf der vereinheitlichten Base bauen.

## 10. Carousel / Buehne

**Befund** -- `carousel` nur in `buehne` genutzt; Reiter als Inline-`<button>`
(`buehne/render.php:211`); `buehne-statisch` mit `mt-3.5`; Editor-Vorschau baut Titel/Untertitel
mit Margins nach.

**Plan** -- Reiter auf `tabs`/`toggle-group` oder eigenen Carousel-Slot `indicators` abbilden
(Phase 2 klaeren); Kopf ueber Sektionskopf (1) mit Variante fuer die Buehne.

## 11. Interne Bausteine

`icon`, `spinner`, `separator`, `scroll-area`, `pagination`, `table/*`, `data-table`,
`button-group`, `toggle`, `progress`, `label` -- werden nur von anderen Base-Komponenten oder
einmal genutzt (Ausnahme `icon`: ueber Helfer ueberall, Groessen per `class`).

**Plan** -- keine Bloecke, kein Editor. Je Komponente nur Phase 6 (Review) gegen die Regeln:
Margins -> Gap, ungenutzte Optionen, doppelte Zuordnungen. Fuer `icon`: Frage, ob eine
`size`-Achse (`sm`/`base`/`lg`) die freien `size-3.5`/`size-4`/`size-5`/`size-6` ersetzt.

## 12. Bibliothek ohne Projekt-Aufrufer

Ohne Aufrufer: `accordion`, `aspect-ratio`, `attachment`, `calendar`,
`combobox`, `date-picker`, `dialog`, `dropdown-menu-*` (ausser Item), `field-*` (ausser Field,
Label, Error), `hover-card`, `input-group-text`, `kbd`, `pagination` (gross), `popover`,
`progress-circle`/`-steps`, `radio`, `select`, `separator-label`, `skeleton`, `slider`,
`switch`, `table-footer`, `tabs`, `toast`, `toggle-group`, `tooltip`.

**Entschieden:** bleiben als Bibliothek; Regel-Check (wie in 11) erst beim ersten echten Aufrufer.

---

## Querschnitt

- **Margins**: Nach 1-6 darf `mb-*`/`mt-*`/`ml-auto` an Komponenten-Aufrufen nicht mehr
  vorkommen; Pruefung per `grep -rn " m[tblr]-\| m[tblr]-auto" template-parts inc woocommerce *.php`.
- **Editor**: `shared.jsx` bekommt je Komponente `<name>ClassName()` aus der JSON; die
  Klassenliterale in den `edit.jsx` verschwinden.
- **Doku**: Neue Regeln in `komponenten-vereinheitlichen.md`, Ergebnisse in `entscheidungen.md`,
  `how-to.md`, `CHANGELOG.md`.
