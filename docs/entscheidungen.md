# Entscheidungen

Log fuer bewusste Entscheidungen: warum etwas so und nicht anders gebaut oder bewusst nicht gebaut
wurde. Neueste Eintraege zuerst.

Abgrenzung zu benachbarten Dokumenten:

- `docs/how-to.md` sammelt Anleitungen, wie man einen bestehenden Erweiterungspunkt nutzt (das
  "Wie"), waehrend diese Datei das "Warum" bewusster Entscheidungen festhaelt.
- `docs/to-do.md` sammelt **offene** Punkte (noch nicht entschieden/gebaut). Sobald ein dort
  gelisteter Punkt entschieden ist, bekommt die Begruendung hier einen Eintrag; der Eintrag in
  `docs/to-do.md` wird wie bisher als geloest markiert/entfernt.
- `CHANGELOG.md` haelt fest, **was** sich geaendert hat (fuer Nutzer der Vorlage). Diese Datei haelt
  fest, **warum** (fuer kuenftige Bearbeiter, inkl. Claude selbst).
- Der Dateikopf-Kommentar der betroffenen Datei bleibt die Quelle direkt am Code (siehe
  `docs/neue-komponente-erstellen.md` Regel 4/9). Ein Eintrag hier ist ein zusaetzlicher, thematisch
  gebuendelter Verweis, kein Doppel-Text mit abweichendem Inhalt — kurz halten, auf den
  Kopfkommentar verweisen statt ihn zu duplizieren.

Siehe `CLAUDE.md` Regel 12 fuer die Pflicht, wann ein Eintrag hier angelegt wird.

---

### `toast.php` gestylt, kein Datei-pro-Typ-Split, kein Ordner-Umzug (2026-09-04)

Phase-2-Styling auf Basis der Claude-Design-Referenz "Hengegroup"
(https://claude.ai/code/artifact/4955236e-3bbd-4520-913c-795cfb92c5c6). Details/Klassen-Herleitung
stehen direkt in `toast.php`s eigenem Kopfkommentar (Regel 12: kein Doppel-Text hier) -- dieser
Eintrag haelt nur die Entscheidungen fest, die nicht schon aus dem Diff folgen:

- **Kein Datei-pro-Typ-Split, kein neuer `toast/`-Ordner.** Die Referenz gliedert ihre "Varianten"
  nach `type` (success/info/error/neutral), das ist aber exakt derselbe Fall wie beim
  `tabs.php`-Eintrag oben: ein `type`-zu-Klassen-Mapping innerhalb EINER Datei
  (`$type_accent_classes`), gleiches Muster wie button.php/badge.php/separator.php/progress.php,
  keine strukturell unterschiedliche Komposition wie z. B. separator.php + separator-label.php.
  Regel 4 (Ordner nur ab mehr als einer Datei) greift entsprechend nicht -- `toast.php` bleibt
  direkt unter `template-parts/base/`.
- **`error` tintet als einziger Typ die ganze Karte** ("nur Fehler bekommt eine getönte Karte",
  wörtliches Zitat der Referenz), ueber dieses Projekts bestehendes `--color-destructive`-Token
  statt der Referenz eigenem, abweichendem Rostrot-Hex -- Konsistenz mit jeder anderen
  `aria-invalid`/Error-Faerbung im Theme (button.php/input.php/select.php/...) wog hoeher als eine
  pixelgenaue Hex-Kopie der Referenz fuer eine Farbe, die sonst nirgends im Theme vorkommt.
  `info`/`neutral` trafen dagegen exakt (Byte-fuer-Byte) auf die bestehenden
  `--color-henge-blue`/`--color-henge-grey`-Werte -- keine Neuerfindung noetig. `warning` hat kein
  Referenzbeispiel, bekam Tailwinds eigenes `amber-600` (`tokens.css`s eigene dokumentierte
  Konvention: Tailwind-Skalen referenzieren statt neu erfinden, wenn noch kein Projekt-Token
  existiert).
- **`loading`s Default-Icon nutzt jetzt spinner.php statt des alten statischen `loader-circle`
  Lucide-Icons** -- dieselbe Umstellung, die button.php's `loading`-State im `spinner.php`-Eintrag
  oben bereits vollzogen hat, hier fuer denselben Zweck wiederverwendet statt ein zweites Mal
  gelöst. Ein Caller-Override (`icons.loading`/toast-eigenes `icon`) rendert weiterhin ueber
  icon.php, siehe `toast.php`s Kopfkommentar fuer den Sentinel-Mechanismus (`'loading' =>
'spinner'`). `loader-circle.svg` wurde dadurch zum letzten verbliebenen Referenzierer ohne
  verbleibenden Aufrufer -- `pnpm build`s Icon-Sync-Schritt (`find-lucide-icons.php`) hat die Datei
  entsprechend automatisch entfernt, kein manueller Eingriff.
- **Auto-Dismiss-Laufleiste (`[data-slot="toast-life"]`)** uebernommen, mit derselben
  `style="--custom-property: ...ms"`-plus-statischer-`animate-[...]`-Technik wie
  progress-circle.php's eigenes `--pc-value` (dokumentierte Regel-1-Ausnahme fuer den einen
  wirklich pro-Toast-dynamischen Wert) -- Details/Begruendung in `toast.php`s Kopfkommentar.
  toast.js spiegelt Markup und Custom Property fuer JS-erzeugte Toasts, inkl. Pausieren via
  `animation-play-state` im bestehenden Hover-Pause-Timer.
- **Fixe Positionierung fuer alle sechs `position`-Werte** ist jetzt echt (`data-[position=...]`-
  Varianten auf `[data-slot="toaster"]`) -- Phase 1 hatte das bewusst als "project concern"
  zurueckgestellt, das ist die faellige Nachlieferung.
- **`expand`/`rich_colors`/kollabierender Stack bleiben bewusst ohne visuelle Umsetzung** -- die
  Referenz zeigt dafuer kein Beispiel (ihr eigener "Verhalten"-Abschnitt stellt jeden sichtbaren
  Toast bereits in voller Groesse dar), ein Look ohne Referenz waere erfundenes Vokabular
  (dieselbe Zurueckhaltung wie in `docs/neue-komponente-erstellen.md` #2 fuer shadcns eigenes
  Vokabular beschrieben). Bleibt reiner Config-Hook fuer einen spaeteren Pass mit eigener Referenz.
- **Das Referenz-"Auf dunklem Grund"-Beispiel wurde NICHT uebernommen**, aus demselben Grund wie
  bei jedem bisherigen Phase-2-Eintrag -- kein Alleingang ohne projektweite Dark-Strategie.
- **`page-component-showcase-toast.php` neu angelegt** (analog zu den bestehenden Showcase-Seiten).

---

### `tabs.php` gestylt, Panel-Switching-Bugfix, kein Datei-pro-Variante-Split (2026-09-04)

Phase-2-Styling auf Basis der Claude-Design-Referenz "Hengegroup"'s "Basis"/"Segmentiert"/
"Vertikal"-Abschnitte (dieselben `.dc.html`-Referenzseiten wie bei den `kbd.php`-/`table/*.php`-
Eintraegen oben). Details/Klassen-Herleitung stehen direkt in `tabs.php`s eigenem Kopfkommentar
(Regel 12: kein Doppel-Text hier) -- dieser Eintrag haelt nur die Entscheidungen fest, die nicht
schon aus dem Diff folgen:

- **Kein Datei-pro-Variante-Split, kein neuer Ordner.** Die Referenz zeigt zwei optisch sehr
  unterschiedliche Looks ("Basis" = Underline-Reiter auf einer Hairline, "Segmentiert" = Pill in
  einer Karte), die aber exakt auf `tabs.php`s bereits seit Phase 1 bestehende
  `variant: 'default' | 'line'`-Config passen -- identische Markup-/Render-Schleife, nur andere
  Tailwind-Klassen je `$variant` (plus `$orientation`, ebenfalls rein PHP-seitig verzweigt statt
  ueber `data-[orientation=...]`-Selektoren, da beide Werte zur Renderzeit feststehen). Gleiches
  Ein-Datei-`$variant_classes`-Muster wie button.php/badge.php/kbd.php, gleiche Begruendung wie
  beim `kbd.php`-Eintrag oben (Nutzer-Entscheidung, siehe AskUserQuestion-Antwort dieser Session) --
  ein Ordner-Split lohnt sich in diesem Theme bislang nur fuer echte Sub-Komponenten mit eigenem
  Markup, nicht fuer Styling-Varianten eines einzelnen Elements. `tabs.php` bleibt entsprechend
  direkt unter `template-parts/base/` statt in einen eigenen Unterordner zu ziehen.
- **Bugfix: Panel-Switching war nie verdrahtet.** `tabs.php`s eigener Kopfkommentar spezifizierte
  das `:has()` + positionelle `:nth-child()`-CSS-Kontrakt fuer die Panel-Sichtbarkeit bereits seit
  Phase 1, es existierte aber nirgends in `assets/css/app.css` -- alle Panels waren gleichzeitig
  sichtbar. Jetzt als dokumentierte Regel-1-Rohcss-Ausnahme in `app.css` ergaenzt (generiert bis 16
  Tabs, siehe dortiger Kommentar fuer die Begruendung/wie man die Grenze anhebt).
- **Das Referenz-"Auf dunklem Grund"-Beispiel wurde NICHT uebernommen**, aus demselben Grund wie bei
  jedem bisherigen Phase-2-Eintrag -- kein Alleingang ohne projektweite Dark-Strategie.
- **`page-component-showcase-tabs.php` neu angelegt** (analog zu den bestehenden
  Showcase-Seiten), inkl. drei bislang fehlender Lucide-Icons nachsynchronisiert (`truck`,
  `flask-conical`, `archive`, als String-Literale direkt vom statischen `find-lucide-icons.php`-
  Scanner gefunden, kein `scripts/lucide-icons.json`-Eintrag noetig -- anders als beim
  `table/*.php`-Eintrag oben, wo die Icon-Namen ueber eine PHP-Variable liefen) fuers "Icon, Badge &
  deaktiviert"-Beispiel, per `sync-lucide-icons.sh` aus `node_modules/lucide-static` kopiert.

---

### `spinner.php` gestylt: Umstieg von `icon.php`-Delegation auf eigenes Zwei-Kreis-SVG, `size`-/

`color`-Vokabular, `button.php`s `loading`-Spinner darauf umgestellt (2026-09-04)

Phase-2-Styling auf Basis der Claude-Design-Referenz "Hengegroup"
(https://claude.ai/code/artifact/795f39d7-99e9-4211-9b9a-c15dabacc6ab). Details/Klassen-Herleitung
stehen direkt in `spinner.php`s eigenem Kopfkommentar (Regel 12: kein Doppel-Text hier) -- dieser
Eintrag haelt nur die Entscheidungen fest, die nicht schon aus dem Diff folgen:

- **Kein einzelnes Lucide-Icon bildet die Referenz-Form ab** (Ring-Track + kurzer rotierender
  Viertelkreis-Akzent) -- `loader-circle` ist ein einzelner ~270-Grad-Pfad ohne separaten Track.
  `spinner.php` rendert deshalb ab jetzt sein eigenes inline `<svg>` (zwei konzentrische `<circle>`s
  in einer gemeinsamen 24er-`viewBox`) statt ueber `hengegroup_theme_render_icon()` zu delegieren --
  gleiche "die Referenz braucht eine Form, die shadcn/lucide nicht mitbringt"-Situation wie bei
  `progress-circle.php`. `icon`/`set`-Config ersatzlos entfernt (kein Aufrufer nutzte etwas
  ausserhalb des Defaults, siehe `grep -rn "base/spinner"` vor dieser Aenderung).
- **Die Strichstaerke bekommt KEINEN eigenen Wert pro `size`** -- Track/Arc teilen sich fuer jede
  Groesse dieselbe `viewBox="0 0 24 24"` und dasselbe `stroke-width="2"`; die tatsaechliche
  Pixel-Staerke ergibt sich automatisch aus der gerenderten Box-Groesse (Tailwinds `size-*`-Klasse).
  Deckt die Referenz-Vorgabe ("Die Strichstärke wächst mit dem Durchmesser") ohne zusaetzliche
  Fallunterscheidung ab.
- **`color`-Vokabular `default | muted | inherit` statt des sonst ueblichen `default | light`
  ("welche Oberflaeche") von accordion.php/typography.php/progress-steps.php.** Die Referenz zeigt
  drei tatsaechlich unterschiedliche Betonungsstufen fuer den Akzent-Arc (Marken-Gruen standalone/
  in Liste/Karte, gedaempftes Grau im sekundaeren Button-Beispiel, und -- im `button.php`-Abschnitt
  unten -- eine dritte, die von der jeweiligen Button-Textfarbe abhaengt), keinen Hell/Dunkel-
  Oberflaechen-Schalter. `inherit` setzt bewusst KEINE eigene Farbklasse (statt `currentColor` per
  `class`-Override zu erzwingen), weil ein `class`-Override gegen eine bereits gesetzte
  `text-*`-Utility bei gleicher CSS-Spezifitaet nicht zuverlaessig gewinnt (button.php's eigener
  Kopfkommentar dokumentiert genau diese Einschraenkung bereits allgemein).
- **Referenz-Abschnitt "Auf dunklem Grund" NICHT uebernommen**, aus demselben Grund wie beim
  `kbd.php`-/`pagination.php`-/`table/*.php`-/`separator.php`-Eintrag -- kein Alleingang ohne
  projektweite Dark-Strategie.
- **`page-component-showcase-attachment.php`s bestehendes "Verarbeitung laeuft"-Beispiel** (bislang
  `class => 'size-4 text-henge-grey'`) auf `size => 'base', color => 'muted'` migriert -- der
  einzige real existierende Aufrufer von `spinner.php` vor dieser Aenderung.
- **`button.php`s `loading`-Zustand rendert jetzt `spinner.php` statt eines eigenen
  `spinner_icon`-Configs ueber `hengegroup_theme_render_icon()`.** Die Referenz zeigt den neuen
  Ring-Spinner explizit als Teil ihres eigenen "In Buttons"-Abschnitts -- ohne diese Umstellung
  wuerde jeder ladende Button weiterhin die alte Lucide-Form zeigen, sichtbar inkonsistent zum
  Rest der Komponente. `spinner_icon` (Config-Key) umbenannt zu `spinner` (kein Icon mehr, das
  Config sind jetzt spinner.php-Overrides), Groesse aus der Button-`size` abgeleitet
  (`sm/base/lg` 1:1, `icon-*` auf ihr Text-Pendant), `color: 'inherit'` (siehe oben) statt eines
  festen Werts, weil kein einzelner Farbwert fuer JEDE Button-`variant`
  (henge-green/henge-blue/.../outline/ghost) passt. Kein weiterer Aufrufer betroffen (`grep -rn
"spinner_icon"` zeigte nur `button.php` selbst).
- **Kein Datei-pro-Variante-Split, kein Umzug in einen eigenen Ordner** -- `size`/`color` sind reine
  Klassen-Varianten innerhalb EINER Datei (gleiches Muster wie button.php's/kbd.php's eigenes
  `variant`/`size`, siehe `kbd.php`-Eintrag oben, "keine Datei-pro-Variante"); nichts in der
  Referenz verlangt eine strukturell andere Spinner-Komposition. `template-parts/base/spinner.php`
  bleibt eine einzelne flache Datei (Regel 4 greift erst ab mehr als einer Datei).

---

### `separator.php`: Bugfix fuer unsichtbaren vertikalen Separator (`h-full` -> `self-stretch`/`h-auto`), neuer `style: 'gradient'` (henge-blue – henge-green – henge-grey) (2026-09-04)

Follow-up zum `separator.php`/`separator-label.php`-Eintrag direkt unterhalb, auf Nutzer-Meldung
("der vertikale separator funktioniert nicht") sowie expliziten Wunsch nach einem dritten,
dreifarbigen Verlauf. Details stehen direkt in `separator.php`s eigenem Kopfkommentar (Regel 12:
kein Doppel-Text hier) -- dieser Eintrag haelt nur fest, was nicht schon aus dem Diff folgt:

- **Root Cause verifiziert, nicht nur vermutet**: ein Chrome-Headless-Screenshot (`--headless=new
--screenshot=...`) einer statischen Test-Seite mit den tatsaechlich kompilierten Klassen zeigte,
  dass `data-[orientation=vertical]:h-full` (shadcns eigene, unveraendert uebernommene Technik) in
  DREI Kontexten unsichtbar blieb: `items-center` UND `items-stretch`-Flex-Reihen sowie standalone
  -- nicht nur der erwartete `items-center`-Fall. Prozentuale Hoehen loesen sich gegen eine
  unbestimmte Containerhoehe nicht wie erwartet ueber `align-items: stretch` auf (anders als eine
  reine "auto"-Kreuzachsengroesse). Derselbe Screenshot-Beweis wurde nach dem Fix erneut gefahren,
  um `self-stretch`+`h-auto` zu verifizieren, statt sich auf Spezifikations-Lektuere allein zu
  verlassen.
- **`self-stretch`/`h-auto` ist jetzt der DEFAULT statt eines Call-Site-Overrides.**
  `button-group.php`s vertikaler Trenner hatte genau dieses Paar bereits von Hand als `class`-Config
  gesetzt (einziger bislang bekannter funktionierender Workaround) -- jetzt redundant und entfernt,
  `button-group.php`/`page-component-showcase-button-group.php` behalten nur noch die tatsaechlich
  weiterhin noetigen Overrides (`bg-input m-0!`).
- **`page-component-showcase-separator.php`s eigene `class: 'h-4.5'`/`'h-4'`/`'h-3.5'`-Overrides an
  allen vier vertikalen Beispielen entfernt** -- waren der Autorin bereits beim ersten Bauen als
  Workaround fuer genau dieses Problem aufgefallen (nicht dokumentiert, da zu dem Zeitpunkt als
  Nebenaspekt behandelt), jetzt ueberfluessig, seit der Default selbst greift.
- **`style: 'gradient'` (fix: henge-blue -> henge-green -> henge-grey, Richtung folgt
  `orientation`) ist ein eigener, benannter `style`-Wert statt (wie die einfarbigen "Verlauf"-
  Beispiele der Referenz) nur ueber `class` erreichbar** -- explizit als wiederverwendbarer,
  benennbarer Look angefragt, nicht als Einzelfall-Farbarbeit; siehe `separator.php`s
  Kopfkommentar fuer die volle Abgrenzung zu den weiterhin nur per `class` erreichbaren
  Referenz-Gradienten. Per Headless-Screenshot verifiziert (blau -> gruen -> grau, korrekte
  Reihenfolge/Farben).
- **Kein `weight`-Ausschluss fuer `gradient`** (anders als `dashed`): `gradient` respektiert
  `weight`s Dicke/Rundung wie `accent` bereits, kein Grund fuer eine Sonderregel.

---

### `separator.php` gestylt, `separator-label.php` neu, Umzug in `separator/`-Ordner (2026-09-04)

Phase-2-Styling auf Basis der Claude-Design-Referenz "Hengegroup" (dieselben `.dc.html`-
Referenzseiten wie beim `table/*.php`-Eintrag unten). Details/Klassen-Herleitung stehen direkt in
`separator.php`s/`separator-label.php`s eigenen Kopfkommentaren (Regel 12: kein Doppel-Text hier)
-- dieser Eintrag haelt nur die Entscheidungen fest, die nicht schon aus dem Diff folgen:

- **`bg-border` (shadcns eigene, immer 100 % deckende `--color-border`-Rolle) durch
  `bg-foreground` in abgestufter Opazitaet ersetzt**, gesteuert ueber eine neue `weight`-Achse
  (thin/default/thick/section = 1px@8%/1px@12%/1px@24%/3px@16%+`rounded-full`) -- die Referenz'
  eigene "Stärken"-Sektion zeigt genau diese 4 benannten Stufen als halbtransparente dunkle Linien,
  nicht einen flachen Token. Gleiches "Tailwind-Opacity-Modifier auf bestehender Rolle"-Prinzip wie
  `kbd.php`s `border-foreground/15`.
- **Neue `style`-Achse (solid/dashed/accent)** deckt nur die zwei der Referenz-"Stile", die eine
  andere CSS-Technik bzw. eine feste Farbe brauchen: `dashed` (echter `border-*-dashed` statt
  `background-color`-Fuellung, ignoriert `weight`, immer Hairline) und `accent` (feste
  `bg-henge-green`-Fuellung -- **keine** konfigurierbare Farbe, gleiche feste-Marken-Farbe-
  Entscheidung wie `pagination.php`s aktive-Seite-Fuellung). `accent` als eigener `style`-Wert statt
  ueber `class` erreichbar, weil ein per `class` angehaengtes `bg-henge-green` gegen die `weight`-
  eigene `bg-foreground/*`-Klasse um dieselbe CSS-Spezifitaet konkurrieren wuerde (exakt die
  Ueberschreib-Unzuverlaessigkeit, die `kbd-group.php`s eigener Kopfkommentar fuer `class` bereits
  allgemein dokumentiert) -- ein dedizierter `style`-Wert umgeht dieses Risiko fuer den haeufigen
  Fall komplett.
- **Die drei restlichen Referenz-"Stile" (ein Verlauf, zwei mehrfarbige Farbverlaeufe) haben
  bewusst KEINE eigene Config bekommen.** `background-image`-Gradients (`bg-gradient-to-r`/
  `from-*`/`to-*`) malen ueber `background-color`, kollidieren also nicht mit der `weight`-eigenen
  Fuell-Klasse und sind bereits ueber das bestehende `class`-Passthrough erreichbar (gleiches
  "fuer Einzelfall-Farbarbeit auf `class` setzen"-Prinzip wie `button-group.php`s eigener
  Kopfkommentar fuer dessen vertikalen Trenner) -- kein mehrstufiges Gradient-Vokabular existiert
  sonst irgendwo im Theme, aus dem sich eine dedizierte Config ableiten liesse (gleiche
  "keine spekulative Erweiterung" Begruendung wie `data-table.php`s einzelnes `filter_column`).
- **Bugfix waehrend der Umsetzung: `bg-foreground/<opacity>` darf NICHT per String-Konkatenation
  (`'bg-foreground/' . $opacity`) zusammengesetzt werden** -- Tailwinds Build-Scanner findet nur
  Klassennamen, die irgendwo als vollstaendiger Literal-String im Quelltext stehen, exakt dieselbe
  Luecke, die `find-lucide-icons.php`s eigener Kopfkommentar fuer dynamisch zusammengesetzte
  Icon-Namen dokumentiert (siehe `table/*.php`-Eintrag unten). Ein erster Entwurf dieser Datei ging
  faelschlich davon aus, das waere unproblematisch; ein `pnpm run build:theme` +
  Kompilat-Grep verifizierte, dass `bg-foreground/8|12|24|16` ohne die Literal-String-Korrektur
  komplett im finalen CSS fehlten. `$weight_map` haelt die vier vollstaendigen Klassennamen deshalb
  jetzt als Literal-Strings.
- **`separator-label.php` ist eine neue, zweite Datei statt eines neuen `separator.php`-Config-
  Werts**, weil die Referenz-Sektion "Mit Beschriftung" strukturell etwas anderes ist (zwei
  `flex-1`-Linien plus ein Label-/Punkt-Element dazwischen) als das bestehende Ein-Element-Markup --
  gleiche Kein-Config-Wert-reicht-nicht-Begruendung wie beim `pagination-compact.php`-Eintrag unten.
  Kein shadcn-Vorbild dafuer (shadcns eigenes Separator kennt kein Label), ausdruecklich als
  Implementierungs-Erweiterung gekennzeichnet -- **nicht** dieselbe Technik wie
  `field-separator.php`s bereits bestehendem gelabelten Trenner (shadcns FieldSeparator:
  eine absolut positionierte Linie hinter einem hintergrundfarbenen "Erase"-Label), die an
  `field-group.php`s eigenen Layout-Kontext gebunden bleibt -- `separator-label.php` funktioniert
  eigenstaendig vor jedem Hintergrund, siehe dessen Kopfkommentar fuer die volle Abgrenzung.
- **Der Label-Text der `start`-Position nutzt exakt `table-head.php`s eigene Eyebrow-Klassen**
  (`text-xs font-semibold tracking-widest text-muted-foreground uppercase`) -- beide leiten sich
  vom selben Referenzwert (`letter-spacing:0.1em`) ab, ein gemeinsamer Look statt zwei unabhaengig
  hergeleiteter.
- **Kein Dark-Abschnitt** (Referenz: "Auf dunklem Grund"), aus demselben Grund wie beim
  `kbd.php`-/`pagination.php`-/`table/*.php`-Eintrag -- kein Alleingang ohne projektweite
  Dark-Strategie.
- **Umzug nach `template-parts/base/separator/`** (Regel 4: sobald eine Komponente aus mehr als
  einer Datei besteht, bekommt sie einen eigenen Ordner) -- alle bestehenden Aufrufer
  (`field-separator.php`, `dropdown-menu.php`, `button-group.php`,
  `page-component-showcase-button-group.php`) per `grep -rn "base/separator"` gefunden und auf den
  neuen Pfad `template-parts/base/separator/separator` umgestellt.

---

### `data-table.php`: Architektur-Wechsel auf vollstaendig client-seitiges Sortieren/Suchen/Filtern/Blaettern per JS, Toolbar (Suche/Kategorie-Filter/Spalten-Toggles) nachgezogen, Pagination via `pagination-compact.php` (2026-09-04)

Auf expliziten Wunsch (Nutzer-Prompt: "Die Data Table soll mit JS funktionieren, also alle
Eintraege laden und nur via js weiterblaettern... koennen wir nicht die pagination dafuer
verwenden? ... oben fehlen das Suchfeld usw. das soll auch via JS funktionieren"): kehrt v1's
eigene, einen Tag zuvor bewusst getroffene Entscheidung um ("sorting and pagination become real
`<a href>` navigation links... genuinely functional with zero JS, not a fake/inert control"). Das
ist kein Widerspruch zu CLAUDE.md, sondern genau deren Kernhaltung angewendet: es gibt keine
kategorische Zero-JS-Praeferenz, UX/DX entscheiden pro Fall (siehe CLAUDE.md "Kernhaltung") -- hier
eben neu, weil der Nutzer es explizit so will, nicht weil zero-JS grundsaetzlich falsch gewesen
waere. Details/Klassen-/API-Herleitung stehen direkt in `data-table.php`s eigenem Kopfkommentar
(Regel 12: kein Doppel-Text hier) -- dieser Eintrag haelt nur die Entscheidungen fest, die nicht
schon aus dem Diff folgen:

- **`rows` ist jetzt IMMER der komplette Datensatz**, nicht mehr nur eine Seite -- PHP rendert
  alles auf einmal, `assets/js/template-parts/base/data-table.js` blendet Zeilen rein ueber
  `hidden` ein/aus (Suche/Filter/Pagination) und ordnet sie fuers Sortieren im DOM um. Kein Fetch,
  kein neuer PHP-Endpunkt, keine `add_query_arg()`-Navigation mehr fuer Sortierung/Pagination.
- **Ohne JS: alle Zeilen sichtbar, unsortiert/ungefiltert, Toolbar-Controls inert** -- eine bewusst
  akzeptierte Regression gegenueber v1's eigener "genuinely functional without JS"-Haltung, nicht
  uebersehen. Es gibt dafuer auch keine sinnvolle Zero-JS-Alternative mehr: sobald ALLE Zeilen
  serverseitig im DOM stehen, waere ein "Seite 2"-Link ohne JS nur ein No-Op-Reload derselben
  Seite -- anders als bei `pagination.php`/`pagination-compact.php` (echte Server-Paginierung mit
  jeweils nur einer Teilmenge der Daten pro Request), wo ein echter Reload weiterhin einen echten
  Sinn hat und deshalb dort NICHT angetastet wurde.
- **`pagination-compact.php` wird 1:1 unveraendert genested statt eines dritten
  Hand-gebauten Prev/Next-Streifens** (Nutzer-Wunsch: "koennen wir nicht die pagination dafuer
  verwenden?"). Einzige Aenderung an `pagination-compact.php` selbst: ein neues
  `data-action="previous"|"next"`-Hook auf dessen Vor/Zurueck-Buttons (via deren bereits
  bestehenden `data_attributes`-Passthrough) -- rein additiv, keine visuelle Aenderung, keine
  Aenderung an dessen eigener (weiterhin echt server-seitiger) Nutzung anderswo. `pagination.php`
  (nummerierte Seiten mit Ellipsis) wurde bewusst NICHT gewaehlt: nach jeder Filteraenderung
  aendert sich die Gesamtseitenzahl, `pagination-compact.php`s "Seite X von Y"-Label braucht dafuer
  nur einen Text-Update, waehrend `pagination.php`s Ellipsis-Fenster-Logik (aktuell reines PHP)
  komplett im Browser nachgebaut werden muesste.
- **Sortierung/Filterung/Suche brauchen echte (nicht aus dem gerenderten HTML ableitbare)
  Rohwerte** -- geloest ueber serverseitig berechnete `data-search`/`data-filter`/
  `data-sort-<key>`-Attribute pro Zeile (`wp_strip_all_tags()` auf die Zell-HTML als Default,
  `sort_values`/`search` als neue optionale Rich-Row-Overrides fuer Faelle, wo der sichtbare Text
  falsch sortiert/durchsucht wird -- z. B. eine "42 t"-Zelle mit Status-Badge braucht
  `sort_values: ['bestand' => 42]` fuer numerische statt lexikografische Sortierung). Kein
  serverseitiges Escaping-Risiko: die Rohwerte sind bereits vom Aufrufer gelieferter Text, nicht
  neu von aussen eingespeist.
- **Alle State-Wechsel im JS toggeln ausschliesslich `data-state`/`aria-*`/`hidden`-Attribute,
  nie Klassenlisten** -- die eigentliche Optik steckt als statische `data-[state=active]:...`-
  Tailwind-Variante bereits im PHP-gerenderten Klassenstring (gleiches Muster wie
  `table-row.php`s eigenes `data-[state=selected]:bg-henge-green/5`). Vermeidet das
  "Tailwind-Klassen-Strings von Hand in JS duplizieren"-Problem, das z. B. `calendar.js` bewusst
  eingeht (dort mangels Alternative, hier vermeidbar, weil dieses File die Klassen ohnehin selbst
  first-party rendert) -- einzige Ausnahme: der Sortier-Pfeil selbst (drei vorgerenderte SVGs,
  `hidden` togglet zwischen ihnen, weil CSS ein SVG-Icon nicht in ein anderes morphen kann) und das
  Vor/Zurueck-`href`/`aria-disabled`-Paar (strukturelle, keine Optik-Attribute).
- **`filter_column` ist bewusst eine einzelne Spalte, keine Mehrfachfilter-API** -- deckt exakt die
  Referenz ab ("Kategorie"), keine spekulative Erweiterung ohne konkreten Anwendungsfall (gleiche
  Kategorie Entscheidung wie native-select.php's `multiple`).
- **Spalten-Toggle/Checkbox-Mehrfachauswahl bleiben bewusst getrennte Themen**: Spalten-Sichtbarkeit
  wurde jetzt gebaut (Nutzer-Wunsch), Zeilen-Auswahl (Checkboxen + Bulk-Aktionen) weiterhin nicht --
  nicht angefragt, weiterhin ohne konkreten Konsumenten (siehe `data-table.php`s Kopfkommentar).

---

### `table/*.php` gestylt, `data-table.php` nach `table/` verschoben, kein Datei-pro-Variante-Split (2026-09-03)

Phase-2-Styling auf Basis der Claude-Design-Referenz "Hengegroup" (dieselben `.dc.html`-
Referenzseiten wie beim `pagination.php`-Eintrag oben). Details/Klassen-Herleitung stehen direkt in
den jeweiligen Kopfkommentaren (Regel 12: kein Doppel-Text hier) -- dieser Eintrag haelt nur die
Entscheidungen fest, die nicht schon aus dem Diff folgen:

- **`data-table.php` zieht nach `template-parts/base/table/`**, statt einen eigenen Ordner zu
  bekommen (Nutzer-Entscheidung, siehe AskUserQuestion-Antwort dieser Session) -- shadcn's eigene
  Data-Table-Doku eroeffnet selbst mit "This is not a data-table component", sondern einem Muster,
  das komplett auf `Table` aufbaut (siehe `data-table.php`s eigener Kopfkommentar); fachlich also
  dieselbe Komponenten-Familie, nicht zwei getrennte. Kein Umbenennen von `table.php`/`table-*.php`
  noetig, `data-table.php` bekommt schlicht erstmals einen Ordner, als Geschwisterdatei ohne
  `table-`-Praefix (Regel 4 verlangt nur den gemeinsamen Ordner, kein gemeinsames Datei-Praefix).
  Kein anderer Aufrufer referenzierte den alten `template-parts/base/data-table`-Pfad (per
  `grep` verifiziert), daher kein weiterer Migrationsaufwand.
- **Kein Datei-pro-Variante-Split fuer die Referenz-Abschnitte "Gestreift"/"Kompakt, ohne
  Rahmen"** (anders als `pagination.php`/`pagination-compact.php`, wo die Kompakt-Variante eine
  strukturell andere Config-API brauchte). Hier reichen zwei einfache Bool-Konfigs auf `table.php`
  selbst: `striped` (ein `[&_tbody>tr:nth-child(even)]:...`-Hook, kein neues Markup) und `card`
  (default `true`, schaltet den Card-Look des AEUSSEREN scroll-area.php-Containers komplett ab/an).
  `card` musste ein dedizierter Konfig werden statt ueber `table.php`s bestehendes
  `class`-Passthrough zu laufen: dieses erreicht nachweislich nur das INNERE `<table>`-Element
  (shadcns eigene Aufteilung, siehe Kopfkommentar), der Card-Look sitzt aber eine Ebene hoeher auf
  dem scroll-area.php-Container -- keine Kombination aus `class`/`attributes` haette ihn je erreicht;
  ein frueherer Entwurf dieses Eintrags/der Datei ging faelschlich davon aus, "Kompakt, ohne Rahmen"
  liesse sich per `class: 'border-0 bg-transparent shadow-none'` erreichen, was aber nur auf dem
  falschen Element gelandet waere. "Kompakt, ohne Rahmen" kombiniert `card: false` mit
  `table-head.php`s bereits seit Phase 1 vorhandenem `scope: 'row'` fuer die linke Label-Spalte --
  siehe `table.php`s Kopfkommentar sowie `page-component-showcase-table.php`s "Kompakt"-Beispiel.
  Kein neuer shadcn-fremder Zustand, keine neue Datei noetig, gleiche Kein-Split-Begruendung wie beim
  `kbd.php`-Eintrag oben, nur diesmal weil "nur zwei Konfig-Varianten" statt "nur eine
  Config-Werte-Variante" zutrifft.
- **`data-[state=selected]` auf `table-row.php`: `henge-green/5` statt shadcns eigenem `bg-muted`**
  (Design-Referenz zeigt einen brand-farbenen statt neutralen Tint fuer ausgewaehlte Zeilen) --
  gleiches Henge-Green-fuer-"aktiv/ausgewaehlt"-Prinzip wie `pagination.php`s eigene aktive-Seite-
  Variante.
- **`table-head.php`s Header-Zellen-Optik (uppercase/tracking-widest/muted-foreground) ersetzt
  shadcns eigenes `font-medium text-foreground`** komplett, nicht nur ergaenzt -- die Referenz zeigt
  diesen Look in JEDEM Abschnitt konsistent (Basis/Varianten/Data Table), keine Mischung aus beidem.
- **`align` (start/center/end) wird auf `table-head.php`/`table-cell.php` first-class Config statt
  weiterhin nur `data-align`-Attribut** -- `data-table.php`s eigener Kopfkommentar dokumentierte das
  bislang explizit als "for project CSS"-Luecke, die nie geschlossen wurde (kein
  `[data-align="end"]`-Regelwerk existierte irgendwo im Theme). Jetzt echte Tailwind-Klassen
  (`text-left`/`text-center`/`text-right`), `data-align` bleibt zusaetzlich als Hook erhalten.
- **Das Referenz-"Auf dunklem Grund"-Beispiel wurde NICHT uebernommen**, aus demselben Grund wie
  beim `kbd.php`-/`pagination.php`-Eintrag oben -- kein Alleingang ohne projektweite Dark-Strategie.
- **Vier bislang fehlende Lucide-Icons nachsynchronisiert** (`chevron-up`, `chevrons-up-down`,
  `chevrons-left`, `chevrons-right`, ueber `scripts/lucide-icons.json` + `sync-lucide-icons.sh`) --
  `data-table.php`s Sortier-/Erste-Letzte-Seite-Icons wurden nur ueber eine PHP-Variable an
  `icon.php` durchgereicht (`['name' => $icon_name, ...]`), nicht als String-Literal, daher vom
  statischen Scanner (`find-lucide-icons.php`) nie gefunden -- ein bereits vor diesem Auftrag
  bestehender, stiller Luecken-Fall (die Icons fehlten schlicht als Datei, `icon.php` gibt dann
  bewusst nichts aus statt fataler zu werden), der beim Styling dieser Komponente aufgefallen ist;
  `scripts/lucide-icons.json` ist genau fuer diesen "dynamisch zusammengesetzter Name"-Fall gedacht
  (siehe `find-lucide-icons.php`s eigener Kopfkommentar).

---

### `kbd.php`/`kbd-group.php`: Keycap-Styling, `size`-Skala + `pressed`-State, kein Dark-Abschnitt, keine Datei-pro-Variante (2026-09-03)

Phase-2-Styling auf Basis der Claude-Design-Referenz "Hengegroup" (dieselben `.dc.html`-
Referenzseiten wie beim `button.php`-Padding/Shape-Eintrag oben). Details/Klassen-Herleitung
stehen direkt in `kbd.php`s/`kbd-group.php`s eigenen Kopfkommentaren (Regel 12: kein Doppel-Text
hier) -- dieser Eintrag haelt nur die Entscheidungen fest, die nicht schon aus dem Diff folgen:

- **`size` (sm/default/lg) und `pressed` sind eine bewusste Erweiterung ueber shadcns eigenes Kbd
  hinaus** (das kennt weder das eine noch das andere, siehe `kbd.php`-Kopfkommentar). Gerechtfertigt
  durch die Referenz selbst, dieselbe Kategorie Abweichung wie button.php's/badge.php's
  Marken-Vokabular -- keine Erfindung ohne Anlass.
- **Der Referenz-Abschnitt "Auf dunklem Grund" wurde NICHT uebernommen.** Dieses Theme hat noch
  keine Dark-Mode-/Dark-Surface-Strategie (siehe `docs/to-do.md`); button.php/badge.php droppen aus
  demselben Grund bereits shadcns eigene `dark:`-Klassen. Ein Kbd-spezifischer "auf dunklem
  Hintergrund"-Modus waere ein Alleingang ohne den Rest der Komponenten-Familie -- wird nachgezogen,
  sobald es eine projektweite Dark-Strategie gibt, nicht isoliert vorgezogen.
- **Kein Datei-pro-Variante-Split.** `kbd/` ist bereits ein eigener Ordner (kbd.php + kbd-group.php,
  seit Phase 1); die `size`-Werte sind reine Klassen-Varianten innerhalb EINER Datei, ueber ein
  `$size_classes`-Array -- exakt dasselbe Muster wie button.php's `variant`/`size` oder badge.php's
  `variant` (siehe deren Dateien). Diese Komponenten haben trotz mehrerer Werte nie eine Datei pro
  Wert bekommen; ein Ordner-Split lohnt sich in diesem Theme bislang nur fuer echte Sub-Komponenten
  mit eigenem Markup (button-group.php + button-group-text.php, toggle.php + toggle-group.php, ...),
  nicht fuer Styling-Varianten eines einzelnen Elements.

---

### `pagination.php` gestylt, `pagination-compact.php` neu, Umzug in `pagination/`-Ordner (2026-09-03)

Phase-2-Styling auf Basis der Claude-Design-Referenz "Hengegroup" (dieselben `.dc.html`-
Referenzseiten wie beim `kbd.php`-Eintrag oben). Details/Klassen-Herleitung stehen direkt in
`pagination.php`s/`pagination-compact.php`s eigenen Kopfkommentaren (Regel 12: kein Doppel-Text
hier) -- dieser Eintrag haelt nur die Entscheidungen fest, die nicht schon aus dem Diff folgen:

- **Die Referenz-Formen (eckige ~9px-Radius-Buttons, 30/38/46px Hoehen) wurden NICHT 1:1
  uebernommen.** `pagination.php` nested `button.php` fuer jedes Item (bestehende
  Architektur-Entscheidung, siehe Kopfkommentar); `button.php` ist bereits Phase-2-gestylt (Pill-
  Form, henge-green, eigene `sm`/`base`/`lg`-Skala) und bleibt laut eigenem Kopfkommentar die
  einzige Quelle dafuer, wie "ein Button" in diesem Theme aussieht. Die Referenz-Formen 1:1
  nachzubauen haette Pagination optisch aus dem Rest des Themes herausfallen lassen (oder verlangt,
  button.php selbst mitzuaendern -- ausserhalb dieses Auftrags). Aus der Referenz uebernommen wurde
  stattdessen nur, was button.php nicht schon mitbringt: der 6px-Item-Abstand (`gap-1.5` statt
  shadcns eigenem `gap-1`) und die Ellipsis-Optik (`size-8`, `text-muted-foreground`).
- **Aktive Seite: `henge-green`-Variante statt shadcns eigenem `outline`** (Design-Wunsch,
  2026-09-03) -- ein gefuellter Marken-Farb-Pill statt eines nur umrandeten, matcht die Referenz'
  eigenen "Gefüllt"-Look fuer die aktive Seite. Einzige Variant-Abweichung von shadcns
  PaginationLink; `ghost` fuer inaktive Seiten sowie Vor/Zurueck bleibt unveraendert.
- **Kein Dark-Abschnitt** (Referenz: "Auf dunklem Grund"), aus demselben Grund wie beim
  `kbd.php`-Eintrag oben -- kein Alleingang ohne projektweite Dark-Strategie.
- **`pagination-compact.php` ist eine neue, zweite Datei statt eines neuen `pagination.php`-Config-
  Werts**, weil die Referenz-Sektion "Kompakt" strukturell etwas anderes ist (Karten-Leiste mit
  Status-Label + optionaler Eintraege-pro-Seite-Auswahl) als die bestehende items-array-API, naeher
  an `data-table.php`s eigenem, page-count-getriebenen Pagination-Footer als an `pagination.php`
  selbst -- siehe `pagination-compact.php`-Kopfkommentar fuer die volle Herleitung/API. Kein
  shadcn-Vorbild dafuer (`pagination.php`s Kopfkommentar deckt shadcns eigentliche Pagination
  bereits vollstaendig ab); ausdruecklich als Implementierungs-Erweiterung gekennzeichnet, gleiche
  Kategorie wie `tabs.php`s Badge-Slot.
- **Umzug nach `template-parts/base/pagination/`** (Regel 4: sobald eine Komponente aus mehr als
  einer Datei besteht, bekommt sie einen eigenen Ordner) -- `pagination.php` hatte noch keine
  Aufrufer ausserhalb der neuen Showcase-Seite, daher kein weiterer Migrationsaufwand.

---

### `button.php`: Font-Size je `size`, Size-Vokabular auf `sm`/`base`/`lg` reduziert (2026-08-30)

Bislang teilten sich alle `size`-Werte dieselbe `text-sm` (14px) aus `$base_classes`, nur `xs`
wich mit `text-xs` (12px) ab. Auf Basis der Buttons im Claude-Design-Referenzprojekt
"Hengegroup" (dieselben `.dc.html`-Referenzseiten wie beim Padding/Shape-Eintrag oben) zeigte
sich, dass die echten Hengegroup-Pill-Buttons unterschiedliche Schriftgroessen je Groesse nutzen
(Nav-Pill "Kontakt" 16px, Hero-/Section-CTA-Pills 18px). Auf expliziten Wunsch daraus zunaechst 3
Font-Size-Stufen ueber die bestehenden 4 `size`-Werte (`default`/`xs`/`sm`/`lg`) verteilt, dann in
einem zweiten Schritt das Size-Vokabular selbst auf 3 Werte reduziert/umbenannt, weil `default`
und `sm` ohnehin dieselbe Font-Size teilten: `sm` (bislang `xs`), `base` (bislang `sm`), `lg`
(unveraendert) -- `default`/`icon` (h-9/size-9) entfallen ersatzlos, `base` uebernimmt ihre Rolle
als Fallback-Wert. Nur echte Tailwind-Scale-Klassen, keine Arbitrary Values (gleiche Konvention
wie beim `typography.php`-Eintrag oben):

- `sm`/`icon-sm`: `text-sm` (14px)
- `base`/`icon-base`: `text-base` (16px)
- `lg`/`icon-lg`: `text-lg` (18px)

`icon-sm`/`icon-base`/`icon-lg` haben keinen sichtbaren Text, spiegeln die Font-Size ihres
Text-Pendants aber trotzdem (rein kosmetisch/zukunftssicher) -- siehe `button.php`-Kopfkommentar.
Alle Aufrufer, die bislang `size => 'default'`/`'icon'` hart codiert hatten (`pagination.php`,
`data-table.php`, `carousel-previous.php`/`carousel-next.php`), sowie `icon-xs`-Aufrufer
(`page-component-showcase-form-elements.php`) sind auf die neuen Namen (`base`/`icon-base` bzw.
`icon-sm`) migriert.

---

### `typography.php`: Variant-Vokabular von shadcns `h1-h4/p/lead/large/small/muted` auf eigenes `headline-lg/base/sm/xs`/`body-lg/base/sm/xs` umgestellt (2026-08-30)

Auf expliziten Wunsch komplett eigenes, groessenbasiertes Vokabular statt shadcns Namen zu
uebernehmen (bewusste Abweichung von `docs/neue-komponente-erstellen.md` Regel 2, gleiche
Kategorie Entscheidung wie button.php's/badge.php's Marken-Farbnamen, siehe deren Eintraege oben).
Ausloeser: eine konkrete Ziel-Groessen-Tabelle (`headline-lg: 64px` ... `text-tiny: 14px`), gemappt
auf die naechstliegende(n) echte(n) Tailwind-`text-*`-Klasse(n) -- **keine** eigenen
Pixel-Arbitrary-Values, nur die eingebaute Skala (`text-6xl`/`text-5xl`/`text-4xl`/`text-3xl`/
`text-2xl`/`text-lg`/`text-base`/`text-sm`).

- **`headline-sm` (42px) faellt komplett weg statt zu kollidieren.** Tailwinds Skala springt fix von
  `text-4xl` (36px) auf `text-5xl` (48px) -- 42px liegt exakt in der Mitte, jede Zuordnung waere
  optisch identisch mit einer Nachbarstufe (`headline-base`/48px oder dem, was `headline-xs` werden
  sollte/36px) gewesen. Auf expliziten Wunsch deshalb nur 4 Ueberschriften-Stufen statt der
  urspruenglich geplanten 5 (`headline-lg/base/sm/xs` = `text-6xl/5xl/4xl/3xl` = 60/48/36/30px), keine
  kuenstlich zusammengelegte Stufe.
- **`text-large` (22px) -> `text-2xl` (24px) statt `text-xl` (20px).** Ebenfalls eine Luecke in
  Tailwinds Skala (kein Stop bei 22px, `text-xl`/20px und `text-2xl`/24px gleich weit entfernt),
  hier aber unkritisch (keine Kollision mit einer Nachbarstufe) -- `text-2xl` gewaehlt fuer eine
  gleichmaessigere Stufung zur naechstgroesseren Stufe (`headline-xs`/30px) hin.
- **`text-*`-Namenspraefix zu `body-*` umbenannt**, auf meinen Vorschlag: `text-*` kollidiert
  begrifflich mit Tailwinds eigenem generischem `text-`-Praefix (Groesse UND Farbe, z. B.
  `text-red-500`) -- `headline-*`/`body-*` ist zudem ein gaengiges Namenspaar fuer Ueberschrift vs.
  Fliesstext.

Kein eigener `muted`-Groessen-Wert mehr -- die vorherige `muted`-Variante bekam ihre gedaempfte
Farbe fest eingebacken; jetzt macht das ausschliesslich die bereits bestehende `color`-Achse
(`color: 'neutral'` -> `text-muted-foreground`) auf einer beliebigen Groessen-Variante, siehe
`typography.php`-Kopfkommentar. Vereinfacht die Komponente: keine Sonderfall-Farblogik pro Variante
mehr, `color` ist jetzt die alleinige Farbachse fuer alle acht Varianten.

**Migration der vier Composing-Komponenten**, die die alten Variant-Namen direkt referenzierten
(sonst waeren sie durch den Fallback auf `body-base` degradiert, ohne Fehler, aber falsch
gestylt):

- `card.php` (Titel: `h3` -> `body-lg` + `class: 'font-semibold'`, Beschreibung: `p` ->
  `body-sm`), `dialog.php` (Titel: `h2` -> `body-lg` + `class: 'font-semibold'`,
  Beschreibung: `p` -> `body-sm`), `accordion.php` (Trigger-Heading: `h4` -> `body-base` +
  `class: 'font-semibold'`) -- `body-lg`/`body-base` sind per Default `font-normal` (siehe
  oben, keine eigene Gewichts-Skala pro Groessen-Stufe angefragt), Titel/Trigger brauchen aber
  sichtbare Betonung gegenueber ihrem Beschreibungstext -- deshalb per additivem `class`-Passthrough
  ergaenzt statt eine eigene "titel"-Variante zu erfinden.
- `data-table.php` (Pagination-Label: `muted` -> `body-xs` + `color: 'neutral'`) -- 1:1-Ersatz,
  keine Groessenaenderung (beide 14px/`text-sm`).

`page-component-showcase-typography.php` auf das neue Vokabular nachgezogen (Abschnitt "muted"
entfernt, Farben-Abschnitt erklaert stattdessen die `color: 'neutral'`-Ablaesung).

---

### PHPUnit `^13.3` -> `^11.5`: CI-Runner nutzt PHP 8.2, `composer.json` verspricht `>=8.2` (2026-08-30)

CI (`.github/workflows/ci.yml`) scheiterte im `php`-Job bei `composer install` mit lauter
"requires php >=8.4"-Fehlern (PHPUnit 13.3.1 + dessen `sebastian/*`/`phpunit/php-*`-Unterpakete).
Ursache: `composer.json`s eigene `require.php` sagt `>=8.2`, aber `phpunit/phpunit: ^13.3` zieht
PHPUnit 13 nach, das selbst PHP `>=8.4.1` braucht -- ein in sich widersprüchliches
`composer.json`, das lokal nur deshalb nicht auffiel, weil die Entwicklungsmaschine PHP 8.5 hat
und `composer update` dort klaglos die neueste (PHP-8.4-only) PHPUnit-Version aufloeste.

Fix: `phpunit/phpunit` auf `^11.5` (letzte Major-Linie, die noch `php: >=8.2` voraussetzt,
`composer update phpunit/phpunit --with-all-dependencies`) statt CI/`composer.json`s
PHP-Untergrenze auf 8.4 anzuheben -- `>=8.2` bleibt die bewusste Kompatibilitätszusage dieses
Themes (siehe CI-Konfiguration), nicht die zufällige lokale PHP-Version. `composer test`/
`composer lint` liefen nach dem Downgrade unveraendert gruen (18/18 Tests), kein API-Bruch fuer
die hier genutzte PHPUnit-Oberflaeche (`TestCase`, Brain-Monkey-Setup).

### `typography.php` gestylt: Groessen-Skala aus Referenzdesign statt shadcn-Stock-Werten, `variant`/`tag` bewusst entkoppelt (2026-08-30)

Dritte tatsaechlich gestylte Base-Komponente (siehe `button.php`/`badge.php`-Eintraege unten fuer
den generellen Ablauf: Phase 2 startet inhaltlich bereits, obwohl `CLAUDE.md`s Kopfabschnitt
weiterhin "aktuell laeuft Phase 1" sagt). Anders als `button.php`/`badge.php` NICHT 1:1 aus shadcns
eigener `buttonVariants()`/`badgeVariants()`-cva()-Definition uebernommen -- shadcns Typography-
Groessen tauchen im Referenzdesign nirgends auf. Stattdessen alle sechs `.dc.html`-Seiten des
Referenzdesigns (Startseite, Karriere, Karrieredetail, Produkte, Produktdetail, Anwendungen,
Downloads, `claude.ai/design`-Projekt, live analysiert 2026-08-30) nach Schriftgroesse/-gewicht/
-line-height geclustert und auf die neun bestehenden Varianten (`h1-h4/p/lead/large/small/muted`)
gemappt, siehe `typography.php`-Kopfkommentar fuer die konkreten Klassen. Zwei bewusste
Entscheidungen, beide auf expliziten Wunsch:

- **Kein `clamp()`.** Das Referenzdesign nutzt fuer seine Hero-Ueberschriften (H1/H2) echtes
  CSS `clamp()` (viewport-fluid). Hier stattdessen einfache Tailwind-Breakpoints (`sm:`/`md:`/
  `lg:`) -- weniger exakte 1:1-Übernahme, aber idiomatischeres Tailwind ohne Extra-Sonderfall pro
  Komponente. `clamp()` bleibt ein moeglicher spaeterer Ausbauschritt, kein aktueller Bedarf.
- **`variant` (Optik) und `tag` (Semantik) sind bewusst unabhaengige Achsen**, nicht wie bei einem
  klassischen `h1`-`h6`-Set 1:1 gekoppelt. Grund: das Referenzdesign nutzt fuer denselben visuellen
  "H2"-Look sowohl grosse Hauptsektionstitel (34-42px, z. B. "Produkte"/"Kontakt") als auch
  kleinere Box-Titel (26-30px, z. B. "Ansprechpartner") -- beide liessen sich durch `h2`s eigene
  Breakpoint-Spanne (`text-3xl md:text-4xl`, 30px Basis -> 36px ab `md:`) abdecken, ohne dafuer
  eine zehnte Variante zu erfinden oder eine feste 1:1-Kopplung an ein bestimmtes `<h*>`-Tag zu
  brauchen. Praktische Konsequenz: eine visuell "h2"-grosse Ueberschrift kann als `<h4>` gerendert
  werden (oder umgekehrt), je nachdem was die tatsaechliche Dokumentgliederung an der Stelle
  braucht -- `tag` immer explizit setzen, sobald visuelle Groesse und semantische Ebene
  auseinanderfallen. `typography.php` unterstuetzte dieses Auseinanderfallen von `variant`/`tag`
  strukturell schon vorher (Phase-1-API), diese Entscheidung nutzt es jetzt aktiv statt es nur
  bereitzuhalten.

Der bestehende `color`-Config-Key (`default | light | neutral`) bleibt die einzige Farbachse --
`muted` ist die eine Ausnahme, die (wie in shadcns eigenem Stock-Schema) ihre eigene gedaempfte
Farbe fest in der Variante traegt statt sie ueber `color` zu beziehen, siehe Kopfkommentar. Ersetzt
damit auch das Referenzdesign-Muster, gedaempften Text ueber `opacity:0.6/0.7` auf der normalen
Textfarbe zu simulieren (z. B. Job-Standort-Zeile), durch das echte `--color-muted-foreground`-
Token.

### `button.php`: `full_width`-Config-Key (2026-08-30)

Auf expliziten Wunsch bekommt `button.php` einen neuen `full_width`-Bool-Config-Key statt eines
neuen `size`-Werts -- anders als `size` ist "volle Breite des Elternelements" orthogonal zu
Variante/Groesse (mit jeder Kombination kombinierbar) statt eine eigene Groessenstufe. Haengt bei
`true` schlicht `w-full` an die berechnete Klasse an; kein neues `data-*`-Attribut dafuer, gleiche
Begruendung wie bei `disabled`/`loading` (native/aria Semantik reicht dort, hier ist es ein reiner
Layout-Utility ohne eigenen State zum Selektieren).

### `badge.php`: Padding/Schriftgroesse an Produkte-/Karriere-Referenz angepasst (2026-08-30)

Auf expliziten Wunsch, orientiert an den Pill-Labels der Produkte-/Karriere-Sektion im
Startseite-Referenzdesign (`Startseite.dc.html`: Produkt-Kategorie-Badge, Anwendungs-Tags,
Karriere-Job-Tag) -- alle drei nutzen dort spuerbar grosszuegigeres Padding (~6px/12px) und
12-14px Text statt shadcns knappem `px-2 py-0.5 text-xs`. Geaendert: `px-2 py-0.5 text-xs` ->
`px-3 py-1.5 text-sm`. Bewusst NICHT angefasst (auf expliziten Wunsch): Variant-Vokabular, Farben,
`rounded-full` (Form/Rundung passte bereits zur Referenz, siehe Eintrag "badge.php gestylt"
unten).

### `badge.php`: `font`-Config-Key fuer Outfit/Crillee (2026-08-30)

Auf expliziten Wunsch bekommt `badge.php` einen neuen `font`-Config-Key (`primary | accent`,
Default `primary`) statt eines neuen `variant`-Werts oder eines pauschalen CSS-Overrides -- die
Startseiten-Referenz (`Startseite.dc.html`, Karriere-Job-Badges wie "IMEXCO"/"HENGE") nutzt fuer
einzelne Badges bewusst die Marken-Akzentschrift Crillee statt der site-weiten Outfit-Fliesstext-
schrift. Kein neuer eigener CSS-Klassenname: `accent` haengt dieselbe `.font-accent`-Utility an, die
`hengegroup_theme_render_accent_text()` (siehe `inc/template-parts/helpers.php`) bereits fuer
einzelne hervorgehobene Woerter in `typography.php` nutzt -- hier auf das gesamte Label angewendet
statt auf einzelne Woerter, aber dieselbe Font-Rolle/dasselbe Token (`--font-accent`,
`assets/css/tokens.css`). `primary` fuegt bewusst keine Klasse hinzu (bereits von der site-weiten
`body`-Regel geerbt) -- gleiches "nur Abweichungen vom globalen Default deklarieren"-Muster wie
ueberall sonst in diesem Theme. Zusaetzliches `data-font`-Attribut als Hook, gleiche Konvention wie
`data-variant`.

### `badge.php` gestylt: engeres Variant-Vokabular als `button.php` (2026-08-30)

Zweite tatsaechlich gestylte Base-Komponente nach `button.php` (siehe dessen Eintrag oben fuer den
generellen Ablauf/die generelle Begruendung: Phase 2 startet inhaltlich bereits, obwohl `CLAUDE.md`s
Kopfabschnitt weiterhin "aktuell laeuft Phase 1" sagt). Klassen 1:1 aus shadcns echter
`badgeVariants()`-cva()-Definition uebernommen (`registry/new-york-v4/ui/badge.tsx` auf GitHub, live
abgerufen 2026-08-30 -- siehe `badge.php`-Kopfkommentar), mit denselben zwei button.php-Abweichungen
(`dark:`-Klassen weggelassen, keine eigene Dark-Mode-Strategie) plus zwei badge-spezifischen:

- **Variant-Vokabular auf Marken-Farbnamen umbenannt UND bewusst enger als `button.php`**, auf
  expliziten Wunsch: `grey-dark | grey-light | henge-blue | henge-green | henge-grey | outline` --
  dieselben fuenf Voll-Farb-Namen wie bei `button.php`, aber ohne `destructive`/`ghost`/`link`
  (anders als `button.php`, das diese drei shadcn-Varianten unveraendert behaelt). Begruendung: ein
  statisches Label hat kein Destruktiv-/Call-to-Action-/Inline-Text-Link-Anwendungsfall -- die drei
  Varianten waeren totes API-Vokabular gewesen (Regel: shadcns Vokabular uebernehmen, nicht frei
  erfinden, aber auch nicht ungenutztes Vokabular mitschleppen). `default`/`secondary` faellt damit
  ebenfalls weg (ersetzt durch `henge-green`/`grey-light`, gleiches Mapping wie bei `button.php`).
- **`outline`s Border nutzt `--color-grey-light` statt shadcns `--color-border`-Rolle**, ebenfalls
  auf expliziten Wunsch -- analog zu `button.php`s `outline`/`ghost`-Hover-Farben, die ebenfalls das
  Marken-Grau statt der generischen shadcn-Rolle nutzen (siehe `button.php`-Kopfkommentar).

`class`-Config-Key ist jetzt wie bei `button.php` kein reines Passthrough mehr, sondern wird HINTER
die berechneten Base-/Variant-Klassen angehaengt (String-Konkatenation, kein `tailwind-merge`/`cn()`
in PHP verfuegbar) -- gleiche Einschraenkung, gleiche Doku-Stelle (Kopfkommentar).

### `build.ps1`/`build.sh`: Top-Level-PHP-Templates per `*.php`-Wildcard statt fester Liste (2026-08-29)

Beide Fassungen kopierten Top-Level-Theme-Dateien bislang ueber eine fest enumerierte Liste
(`style.css`, `functions.php`, `header.php`, ... `theme.json`, `screenshot.png/jpg`). Ein neues
Custom-Page-Template nach WordPress-Template-Hierarchie-Konvention (z. B.
`page-component-showcase-button.php`) landete dadurch beim Build **nicht** in `dist/` — kein
Deploy-Bug (der Upload selbst laeuft korrekt, `dist/` enthielt die Datei schlicht nie), sondern
eine Luecke, die in `build.ps1` und `build.sh` gleichermassen bestand.

Fix: `style.css`/`theme.json`/`screenshot.png`/`screenshot.jpg` bleiben eine feste Liste
(`$themeStaticFiles`/`theme_static_files`, WP-Konvention mit garantiert fixem Namen), aber jede
Top-Level-`*.php`-Datei im Repo-Root wird jetzt automatisch mitkopiert (`Get-ChildItem -Filter
"*.php"` bzw. eine `for ... in "$repo_root"/*.php`-Schleife) — deckt damit automatisch die volle
WordPress-Template-Hierarchie ab (`page-{slug}.php`, `single-{post-type}.php`,
`category-{slug}.php`, `taxonomy-{slug}.php`, `front-page.php`, ...), ohne dass die Liste bei
jedem neuen Custom-Template von Hand nachgezogen werden muss. Sicher, weil im Repo-Root
grundsaetzlich nur echte Theme-Templates als `*.php` liegen (Tooling-Config wie `composer.json`
ist kein `.php`).

### Bash-Pendants zu allen `scripts/*.ps1` fuer macOS/Linux, ueber `run.mjs` dispatcht (2026-08-28)

Alle zehn `scripts/*.ps1`-Skripte (`build`, `clean`, `deploy`, `deploy-changed`, `i18n-make-pot`,
`pull-base-updates`, `rename-theme`, `sync-lucide-icons`, `sync-tabler-icons`,
`sync-theme-tokens`, `sync-theme-version`) waren bislang nur fuer Windows PowerShell geschrieben —
auf macOS/Linux ohne separat installierte PowerShell Core (`pwsh`) gar nicht lauffaehig. Jedes
bekommt jetzt ein `scripts/<name>.sh`-Pendant (Bash, kompatibel zu macOS' Standard-`/bin/bash`
3.2 — keine Bash-4-Features wie `wait -n`/assoziative Arrays), verhaltensgleich zum `.ps1`-Original
uebersetzt und einzeln end-to-end gegen Kopien/isolierte Test-Repos verifiziert. Beide Fassungen
bleiben dauerhaft parallel gepflegt (keine Migration auf PowerShell Core als einzige Variante), weil
das Windows-`.ps1`-Original ohne zusaetzliche Installation lauffaehig bleiben soll und ein
Nebeneinander zweier Skript-Sprachen im selben Ordner ohnehin schon Konvention dieses Repos ist
(`.php`-Icon-Scanner neben `.ps1`).

Technische Eckpunkte der Uebersetzung, damit kuenftige Aenderungen an einer Fassung nicht die
andere unbemerkt auseinanderlaufen lassen:

- **Text-/JSON-lastige Logik (Versions-Sync, Token-Sync, Rename, Manifest-Checks, Deploy-State)
  delegiert an `node -e`** statt an `sed`/sh-Bordmittel — Node ist ueber `pnpm`/Vite ohnehin
  bereits harte Voraussetzung, und die Ersetzungs-/Zaehl-Logik laesst sich so nahezu 1:1 aus dem
  PowerShell-Original uebertragen (String-basiert, kein Regex-Escaping-Aerger). `jq` wurde bewusst
  nicht als neue Abhaengigkeit eingefuehrt.
- **`deploy.sh`/`deploy-changed.sh` parallelisieren FTP-Uploads ueber `xargs -P`** statt
  PowerShells `Start-Job`/`Start-ThreadJob`-Jobsteuerung (kein Bash-4-Aequivalent auf macOS'
  Standard-Bash verfuegbar). Eine Abweichung bleibt bewusst bestehen: das PowerShell-Original
  bricht das Nachlegen neuer Uploads beim ersten Fehlschlag ab (laufende Jobs werden noch fertig
  abgewartet), waehrend `xargs -P` bereits aufgereihte Uploads durchlaufen laesst, bevor mit
  Exit-Code 1 abgebrochen wird — Ergebnis (Fehlschlag = kein erfolgreiches Deploy) ist gleich, nur
  der Abbruchzeitpunkt etwas spaeter. Ausfuehrlich im Kopfkommentar von `deploy.sh` begruendet.
  Beide zeigen ausserdem eine `Write-Progress`-Entsprechung: in einem echten Terminal (TTY) eine
  sich per `\r`/ANSI-Clear ueberschreibende Fortschrittszeile, ohne TTY (Log-Datei, CI) stattdessen
  eine Zeile pro Upload, damit das Log kein Steuerzeichen-Wirrwarr wird (2026-08-29, Ergaenzung).
- **`package.json` "scripts" rufen ab jetzt `node scripts/run.mjs <name> [--flag ...]`** statt
  direkt `powershell -File scripts/<name>.ps1` — der Dispatcher waehlt anhand von
  `process.platform` die `.ps1`- oder `.sh`-Fassung und uebersetzt dabei `--kebab-case`-Flags 1:1
  in PowerShells `-PascalCase`-Parameternamen (die `.sh`-Skripte verwenden konsequent die
  kebab-case-Form des jeweiligen `.ps1`-Parameternamens, z. B. `-NoGitAdd` <-> `--no-git-add`,
  `-NewSlug` <-> `--new-slug`). So bleibt jeder `package.json`-Scripts-Eintrag auf beiden
  Plattformen identisch, statt zwei parallele Skript-Namen pro Aufgabe pflegen zu muessen. Neue
  `-Parameter`s in einem `.ps1` muessen dieselbe Namenskonvention einhalten, damit der Dispatcher
  sie ohne Sonderfall uebersetzen kann.
- **`rename-theme.sh`/`pull-base-updates.sh` bleiben wie ihre `.ps1`-Vorbilder ausserhalb von
  `package.json`** — beide sind seltene, manuelle Bootstrap-/Update-Schritte mit vielen optionalen
  Parametern, direkt aufgerufen (`bash scripts/rename-theme.sh --new-slug ...`), kein
  `pnpm run ...`-Eintrag noetig.
- `rename-theme.sh`s Ausschlussliste/`--included-extensions` schliesst jetzt auch `.sh` mit ein
  (analog zu `.ps1`) und schuetzt zusaetzlich zu `scripts/rename-theme.ps1`/
  `scripts/pull-base-updates.ps1` auch deren `.sh`-Pendants vor versehentlicher Selbst-Umbenennung
  (gleicher Grund wie im `.ps1`-Kopfkommentar: die `-OldSlug`/`-OldPrefix`-Defaults muessen auf das
  literale `base-theme`/`base_theme_` zeigen bleiben, sonst laeuft ein spaeterer Aufruf ohne
  explizite `--old-slug`/`--old-prefix` ins Leere).

### `button.php` gestylt: Variant-Vokabular auf Marken-Farbnamen umbenannt (2026-08-28)

Erste tatsaechlich gestylte Base-Komponente (Phase 2 startet damit inhaltlich, auch wenn
`CLAUDE.md`s Kopfabschnitt weiterhin "aktuell laeuft Phase 1" sagt -- die Formulierung dort noch
nicht nachgezogen, da das eine eigene, groessere Aenderung waere). Klassen 1:1 aus shadcns echter
`buttonVariants()`-cva()-Definition uebernommen (`registry/new-york-v4/ui/button.tsx` auf GitHub,
live abgerufen 2026-08-28 -- siehe `button.php`-Kopfkommentar), zwei bewusste Abweichungen:

- **Variant-Vokabular umbenannt auf Marken-Farbnamen**, auf expliziten Wunsch: `henge-green` (ersetzt
  `default`), `henge-blue`, `henge-grey`, `grey-dark` (drei neue Voll-Varianten, vorher nicht
  vorhanden), `grey-light` (ersetzt `secondary`), `destructive`/`outline`/`ghost`/`link` unveraendert.
  Das ist eine bewusste Abweichung von `docs/neue-komponente-erstellen.md` Regel 2 ("Vokabular
  uebernehmen, nicht frei erfinden") -- hier ausdruecklich gewuenscht, weil das Projekt lieber direkt
  am Markennamen statt an shadcns abstrakter default/secondary-Nomenklatur entlang designen will.
  `badge.php` hat noch das alte shadcn-Vokabular (`default | secondary | ...`) -- beide
  Komponenten sind dadurch aktuell inkonsistent zueinander, bis `badge.php` (oder andere
  Komponenten mit dem gleichen Variant-Enum) denselben Umbau bekommen.
- **`dark:`-Klassen komplett weggelassen** (shadcns Original hat u. a. `dark:bg-destructive/60`,
  `dark:border-input`, `dark:hover:bg-accent/50`) -- Dark Mode ist laut `docs/to-do.md` weiterhin
  offen, ein halb umgesetzter Dark-Mode-Pfad (shadcns literale dark:-Utilities ohne eigene
  Dark-Tokens dahinter) waere schlechter als gar keiner.
- **`destructive` nutzt `text-destructive-foreground` statt shadcns hartcodiertem `text-white`** --
  konsequent aus der grey-light-statt-Weiss-Entscheidung (siehe Eintrag unten) abgeleitet.

Neue Tokens in `assets/css/tokens.css` fuer die drei neuen Voll-Varianten (`--color-henge-blue-
foreground`, `--color-henge-grey-foreground`, `--color-grey-dark`/`-foreground`,
`--color-grey-light`/`-foreground`) nach demselben `<name>`/`<name>-foreground`-Schema wie
`--color-henge-green`/`-foreground`. `--color-grey-dark`/`--color-grey-light` sind bewusst
zusaetzliche Tokens (sonst gilt "keine eigenen Tokens fuer die Marken-Grautoene", siehe Eintrag
"Marken-Tokens" unten) -- Ausnahme, weil die Komponenten-API jetzt selbst diese Namen als
`variant`-Werte erwartet und dafuer eine `bg-grey-dark`/`bg-grey-light`-Tailwind-Klasse braucht.

`assets/css/app.css`s `body`-Regel (vorher leer) bekommt `@apply bg-background text-foreground
font-primary` -- erste echte Nutzung von `--font-primary`/`--color-background`/`--color-foreground`
(vorher dokumentiert-aber-ungenutzte Tokens, siehe "Marken-Tokens"-Eintrag unten). `button.php`
selbst wiederholt Text-/Hintergrundfarbe nur dort, wo eine Variante vom globalen Default abweicht
(shadcns eigenes Muster) -- kein `font-primary` auf dem Button selbst, das wird vom `body` geerbt.

`class`-Config-Key ist jetzt nicht mehr reines Passthrough, sondern wird HINTER die berechneten
Base-/Variant-/Size-Klassen angehaengt (String-Konkatenation) -- PHP hat kein `tailwind-merge`/`cn()`-
Aequivalent, ein per `class` uebergebenes konfligierendes Utility "gewinnt" also nicht garantiert
gegen die berechnete Variante (anders als bei shadcns `className`-Prop). Dokumentiert im
`button.php`-Kopfkommentar; fuer rein additive Klassen (Margins, Layout) unproblematisch, fuer
`bg-*`/`text-*`-Overrides nicht verlaesslich.

**Nicht lokal verifiziert:** `composer lint`/`composer test` liefen in dieser Session nicht (kein
PHP/Composer in der Umgebung verfuegbar) -- `pnpm exec vite build`, `pnpm exec prettier --check` und
`pnpm test` (Vitest) liefen und sind gruen; die generierten Klassen (`bg-henge-green`,
`hover:bg-henge-green/90`, `ring-ring/50`, etc.) wurden im kompilierten CSS stichprobenartig
verifiziert.

### Semantische Rollen-Farb-Tokens (shadcn-Vokabular) + `--color-accent` -> `--color-henge-green` (2026-08-28)

Phase-2-Vorarbeit fuer das Variant-Vokabular der Base-Komponenten (`button.php`/`badge.php`:
`default | secondary | destructive | outline | ghost | link`): `assets/css/tokens.css` bekommt das
volle Set an shadcn-typischen semantischen Rollen-Tokens (`--color-background`/`-foreground`,
`-card*`, `-popover*`, `-secondary*`, `-muted*`, `-accent*`, `-destructive*`, `-border`, `-input`,
`-ring`), referenziert wo moeglich Tailwinds eigene Skalen (`var(--color-neutral-*)`,
`var(--color-red-*)`, `var(--color-white)`) statt Werte zu duplizieren — gleiche Konvention wie das
bestehende Marken-Grau-Mapping (siehe Eintrag unten).

- **`--color-accent` (bisher henge-green) heisst jetzt `--color-henge-green`.** Grund: der Name
  "accent" ist in shadcn selbst bereits vergeben — eine eigene, neutrale Hover-/Subtle-Rolle (z. B.
  `skeleton.php`s eigener Kopfkommentar zitiert shadcns Original-Markup mit `bg-accent` fuer den
  Placeholder-Hintergrund), keine Marken-Akzentfarbe. Mit henge-green als `--color-accent` waeren
  spaetere 1:1-uebernommene shadcn-Klassen wie `bg-accent`/`text-accent-foreground` (Regel 2:
  "Vokabular uebernehmen, nicht frei erfinden") grundfalsch gruen statt dezent grau geworden.
  `--color-accent`/`--color-accent-foreground` decken jetzt korrekt shadcns eigentliche Rolle ab
  (gleicher Hintergrund wie `--color-muted`, aber volltonige `-foreground` statt gedaempft, wie im
  shadcn-Original). Der WP-Editor-Palette-Slug in `theme.json` bleibt bewusst weiterhin `"accent"`
  (Label bereits `"Henge Green"`) — den nachtraeglich umzubenennen wuerde bereits gespeicherte
  `has-accent-color`-Blockklassen in Inhalten brechen, waehrend das interne CSS-Token frei umbenennbar
  war, da es noch nirgends im Markup referenziert wird (Phase 1 hat kein Styling). Kein neues
  `--color-primary`-Alias fuer henge-green ergaenzt — Variant-Class-Maps referenzieren spaeter direkt
  `var(--color-henge-green)`, gleiche Namenskonvention wie die bestehenden
  `--color-henge-blue`/`--color-henge-grey`-Tokens. `scripts/sync-theme-tokens.ps1`s Regex auf die
  neue Token-Bezeichnung angepasst.
- **`secondary` bleibt neutral (grey-light/grey-dark), nicht henge-blue** — die Variante ist
  komponentenuebergreifend (Button, Badge, ...) dieselbe generische Rolle; mit henge-blue haetten
  `secondary` und `default` gleich prominent/bunt gewirkt, was shadcns eigener Intention (secondary
  = dezente Alternative) widerspricht. henge-blue/henge-grey bleiben eigene, frei nutzbare
  Marken-Tokens ausserhalb der generischen Variant-Rollen.
- **`--color-destructive` ist vorlaeufig Tailwinds Standard-Rot (`red-600`)**, kein Marken-Rotton —
  im Brand-Guide bislang keiner hinterlegt. Bei Bedarf gezielt austauschen, sobald einer feststeht.
- **`--color-ring` = `blue-500`** (Tailwinds eigener Standard-Blauton), **weder henge-green noch
  shadcns eigener neutraler Default** (`neutral-400`) (2026-08-30, zweite Korrektur dieses Eintrags
  -- erst bewusst markenkonsistentes Gruen, dann kurzzeitig shadcns neutraler Default, siehe
  Git-Historie dieser Datei fuer beide vorherigen Fassungen) — auf expliziten Wunsch Tailwinds
  eigener, unkonfigurierter Standard-Fokusring-Ton, projektweit fuer alle Komponenten, die
  `ring-ring`/`border-ring` nutzen (button.php, badge.php, alle Phase-2-gestylten
  Form-Base-Komponenten unter `template-parts/base/`).
- **"Weisser" Text ist bewusst grey-light (`--color-neutral-100`), nicht reines Weiss (2026-08-28,
  Ergaenzung):** `--color-henge-green-foreground`/`--color-destructive-foreground` (Text auf
  henge-green- bzw. destructive-Hintergrund) nutzen `var(--color-neutral-100)` statt
  `var(--color-white)` — Design-Vorgabe, jede Stelle mit "weissem" Text soll grey-light statt
  reinem Weiss verwenden. `--color-background`/`-card`/`-popover` (Flaechenfarben, kein Text)
  bleiben unveraendert reines Weiss.
- **Kontrast-Hinweis:** grey-light auf henge-green liegt rechnerisch bei ca. 4.0:1, grey-light auf
  destructive bei ca. 4.4:1 — beide ueber WCAG AA fuer grossen/fetten UI-Text (>= 3:1), unter der
  4.5:1-Schwelle fuer normalen Fliesstext (mit reinem Weiss waeren es ca. 4.3:1 bzw. 4.8:1 gewesen,
  siehe vorherige Fassung dieses Eintrags in der Git-Historie). Fuer Button-/Badge-Text (i. d. R.
  fett, kurze Labels) unkritisch, aber kein Freibrief fuer laengeren Fliesstext in
  henge-green-/destructive-Vordergrundfarbe anderswo.

### Marken-Tokens: drei Akzentfarben, Grau-Mapping, zwei Font-Rollen (2026-08-19)

Projekt-Setup (README "Neues Projekt aus dieser Vorlage starten", Schritt 3) fuer die echte
Henge-Group-Marke: drei Akzentfarben (henge-green, henge-blue, henge-grey), drei Marken-Grautoene
und zwei self-gehostete Fonts (Outfit, Crillee) — mehr als das bisherige "ein `--color-accent`, ein
`--font-accent`"-Modell aus `assets/css/tokens.css` vorsah. `docs/to-do.md` Abschnitt 3 listete den
Umfang des Design-Token-Systems als offene Grundsatzfrage; folgende Entscheidungen loesen sie:

- **Farben:** `--color-accent` (henge-green, `#3b875e`) bleibt die _eine_ automatisiert per
  `pnpm run sync-theme-tokens` nach `theme.json` gesynct'e Farbe (Link-Farbe,
  `settings.color.palette`-Slug `accent`) — das Sync-Skript kann nur einen Wert abbilden.
  `--color-henge-blue`/`--color-henge-grey` sind zusaetzliche, eigene Tokens im
  `--color-*`-Namespace (erzeugen automatisch `.bg-henge-blue`/`.text-henge-blue`-Utilities etc.)
  und manuell zusaetzlich in `theme.json`s `settings.color.palette` gepflegt, da sie ausserhalb der
  Sync-Skript-Automatik liegen.
- **Marken-Grautoene:** grey-light `#EFEFEF`, grey-medium `#E5E3DF`, grey-dark `#222222` bekommen
  bewusst **keine** eigenen Tokens, sondern werden per Kommentar in `tokens.css` auf die
  naechstliegenden Tailwind-`neutral-*`-Stufen gemappt (neutral-100/neutral-200/neutral-800) — haelt
  die bestehende Konvention ("Tailwinds `neutral`-Skala statt eigener Grau-Aliase") statt sie fuer
  drei Werte aufzuweichen. Einzige Naeherung: grey-medium hat einen warmen/beigen Unterton, den
  neutral-200 nicht abbildet; falls ein Anwendungsfall den exakten Wert braucht, dafuer gezielt ein
  eigenes Token ergaenzen statt neutral-200 zu erzwingen.
- **Zwei Font-Rollen statt einer:** `--font-primary` (Outfit) fuer Fliesstext/UI, `--font-accent`
  (Crillee, vorher nur System-Font-Platzhalter) fuer Akzent-/Display-Text. Bewusst **nicht** als
  Tailwinds `--font-sans` registriert — das wuerde ueber Tailwinds Preflight-Basisstil sofort
  site-weit die Body-Schrift aendern, ein pauschales visuelles Styling, das laut `CLAUDE.md` Regel 1
  erst Phase 2 gehoert. `--font-primary` erzeugt zwar schon jetzt die Utility-Klasse `.font-primary`,
  bleibt aber ungenutzt bis Phase 2. `--font-accent` ist dagegen schon jetzt aktiv (Akzent-Woerter
  in `typography.php` ueber `hengegroup_theme_render_accent_text()`), weil das eine dokumentierte
  funktionale API ist, keine pauschale Optik-Entscheidung.
- **Font-Loading vorgezogen:** die in "Komponenten-Showcase-Seite und Performance-Tooling" (unten)
  fuer Phase 2 skizzierte Font-Loading-Strategie (Self-Hosting, `font-display: swap`) wird jetzt
  schon fuer Outfit/Crillee angewendet (`assets/css/fonts.css`, `assets/fonts/README.md`), obwohl
  Phase 1 noch laeuft — Ausnahme, weil es sich um das Bereitstellen von Marken-Assets handelt
  (Projekt-Setup), nicht um deren visuelle Anwendung in einer Komponente. Bewusst noch offen:
  Preload/Subsetting — weiterhin fuer den tatsaechlichen Phase-2-Start vorgemerkt, sobald eine
  konkrete above-the-fold-Nutzung feststeht.
- **WOFF2-Konvertierung nachgezogen (2026-08-19, Ergaenzung):** `npx ttf2woff2 < input > output`
  funktioniert zuverlaessig (der fruehere Haenger beim ersten Test lag an leerem Stdin-Input als
  Testfall, nicht am Tool selbst) — `outfit.ttf` (110.572 -> 45.704 Bytes, -59 %) und `crillee.otf`
  (28.136 -> 16.616 Bytes, -41 %) liegen jetzt zusaetzlich als WOFF2 vor, `fonts.css` listet WOFF2
  vor der jeweiligen TTF/OTF-Quelle (Browser waehlt das erste unterstuetzte Format). Dabei
  festgestellt: `outfit.ttf` ist ein **Variable Font** (Achse `wght`, 100–900) — `font-weight` in
  `fonts.css` deshalb auf die Bereichs-Syntax `100 900` korrigiert (vorher faelschlich `400`, hat die
  Variable-Font-Faehigkeit nicht genutzt). `crillee.otf` ist statisch, keine `fvar`-Tabelle.
- **`setup.md` PII-Frage mit "Ja" beantwortet:** `inc/setup/theme-admin.php`s
  `hengegroup_theme_action_admin_menu_cleanup()` entfernt `export-personal-data.php`/
  `erase-personal-data.php` nicht mehr aus dem Tools-Menue. `wp_add_privacy_policy_content()` sowie
  `wp_privacy_personal_data_exporters`-/`-erasers`-Filter bleiben offen, bis das konkrete
  PII-sammelnde Feature (z. B. Kontaktformular) technisch existiert (siehe `setup.md`).

### Manueller Tastatur-/Screenreader-Testplan angelegt (2026-08-18)

`docs/to-do.md` Abschnitt 2 forderte einen dokumentierten manuellen Testplan als guenstige Ergaenzung
zur weiterhin fehlenden automatisierten a11y-Pruefung. Angelegt: `docs/tastatur-screenreader-
testplan.md`, eine Checkliste pro interaktiver Komponente (Overlays, Navigation,
Auswahl-Komponenten, Daten-Komponenten, Feedback), abgeleitet aus dem tatsaechlich implementierten
Tastaturverhalten der jeweiligen `assets/js/template-parts/base/*.js`-Datei — nicht aus einer
generischen WAI-ARIA-Checkliste, die von der echten Implementierung abweichen koennte.

- **Nur die Checkliste selbst ist fertig, nicht ihre Ausfuehrung.** Ein tatsaechlicher Durchlauf
  (Tastatur-only + NVDA/VoiceOver) braucht eine laufende WP-Instanz mit Testseiten pro Komponente —
  dafuer fehlt aktuell die zurueckgestellte Komponenten-Showcase-Seite (siehe Eintrag oben).
  `hengegroup-theme` ist zwar ein voll aktivierbares Theme, eine Wegwerf-Testseite pro Durchlauf waere bis
  dahin die pragmatische Zwischenloesung. Die Status-Tabelle in der neuen Datei bleibt bis zum ersten
  echten Durchlauf leer.
- Dokumentiert bewusst auch bekannte, aktuell fehlende Patterns als "kein Befund" statt als offene
  Luecke im Testplan selbst — z. B. `calendar.php`s fehlendes APG-Date-Grid-Pfeiltasten-Pattern (nur
  Tab pro Tag) oder `combobox.php`s fehlendes Home/End (anders als `select.php`) — damit ein
  Durchlauf diese nicht faelschlich als neuen Befund meldet.

### i18n-Konsistenz-Check: keine Abweichungen gefunden (2026-08-18)

Grep ueber alle `__()`/`_e()`/`esc_html__()`/`esc_attr__()`/... -Aufrufe im gesamten Theme
(`template-parts/`, `inc/`, Root-Templates) ergab durchgehend die Text-Domain `hengegroup-theme` (identisch
zu `style.css`s `Text Domain`-Header) — keine Abweichungen gefunden, kein Fix noetig. JS-seitig
(`assets/js/`) gibt es bewusst keine `wp.i18n`-Nutzung (siehe Kopfkommentar von `toast.js`: JS hat
keinen Zugriff auf WordPress' PHP-Uebersetzungen, Strings werden dort manuell dupliziert) — kein
Punkt, den dieser Check serverseitig abdecken kann.

### WP-/PHP-Versionsmatrix dokumentiert, Dependencies aktualisiert (2026-08-18)

`docs/to-do.md` Abschnitt 4 (Kompatibilitaet) forderte eine dokumentierte WP-/PHP-Versionsmatrix,
vorher alle Dependencies aktualisiert. Umgesetzt:

- **Dependencies aktualisiert**: `composer update` (u. a. `phpunit/phpunit` 13.3.0 -> 13.3.1,
  `mockery` 1.6.12 -> 1.6.13) sowie `pnpm update` (u. a. `prettier` 3.8.1 -> 3.9.6, `lucide-static`
  1.28.0 -> 1.31.0) — beides innerhalb der bestehenden Versions-Ranges aus `composer.json`/
  `package.json`, keine Breaking Changes. `composer lint`/`composer test`/`pnpm format:check`/
  `pnpm test` danach gruen, `composer audit`/`pnpm audit` ohne neue Funde.
- **`Requires PHP` in `style.css` 8.1 -> 8.2 angehoben**, zusaetzlich als explizites
  `require.php` in `composer.json` ergaenzt (fehlte vorher komplett) — PHP 8.1 ist zum
  Entscheidungszeitpunkt bereits vollstaendig End-of-Life (keine Security-Fixes mehr), 8.2 ist die
  aelteste noch (Security-only) unterstuetzte Version. `.github/workflows/ci.yml`s PHP-Job testet
  jetzt ebenfalls 8.2 (vorher 8.3) — CI soll die tatsaechlich deklarierte Mindestversion pruefen,
  nicht eine hoehere, ungetestete Annahme.
- **`Tested up to` in `style.css` 6.8 -> 7.0 angehoben** (aktuelle WordPress-Stable-Version zum
  Entscheidungszeitpunkt). Wichtige Einschraenkung: das ist eine deklarierte
  Kompatibilitaets-Zielmarke, keine durch einen echten WP-Integrationstest verifizierte Aussage —
  dafuer fehlt weiterhin die `wp-env`-Infrastruktur (siehe "Test-/CI-Tooling"-Eintrag unten sowie
  `docs/to-do.md` Abschnitt 1). Sollte bei jeder groesseren WordPress-Version erneut angehoben
  werden, nicht als einmalig erledigt betrachtet werden.
- **Matrix dokumentiert in README.md** ("Anforderungen"), inkl. Node/pnpm-Versionen aus
  `.github/workflows/ci.yml`/`package.json`s `packageManager`-Feld als Kontext, auch wenn dafuer
  kein `Requires`-Feld existiert. `docs/to-do.md` Abschnitt 4 dadurch geloest und entfernt.

### Kein `CONTRIBUTING.md`: Alleinentwickler (2026-08-18)

`docs/to-do.md` Abschnitt 4 (Governance) fuehrte das Fehlen eines `CONTRIBUTING.md` bisher als
offenen, niedrig priorisierten Punkt, der relevant wuerde, sobald ein zweites Teammitglied
mitarbeitet. Endgueltig geklaert: es wird **kein** `CONTRIBUTING.md` geben — an der Basis arbeitet
dauerhaft nur eine Person (ggf. + Agent), kein Szenario mit zweitem Teammitglied geplant. Der
Abschnitt ist daher aus `to-do.md` entfernt statt weiter als "spaeter relevant" vorgemerkt.

### Komponenten-Showcase-Seite und Performance-Tooling: bewusst zurueckgestellt (2026-08-16)

`docs/to-do.md` Abschnitt 1 (Komponenten-Showcase-Seite) und Abschnitt 3
(Performance) sind auf spaeter zurueckgestellt, aktuell kein Arbeitsauftrag — beide Abschnitte
sind deshalb aus `to-do.md` entfernt, die verbleibenden Abschnitte
durchnummeriert. Performance haengt inhaltlich an der Showcase-Seite als Mess-/Scan-Ziel, daher
gemeinsam zurueckgestellt statt einzeln.

- **Komponenten-Showcase-Seite**: ein dev-only Template (z. B. `page-component-showcase.php`) mit
  einem Aufruf pro Base-Komponente inkl. wichtigster Varianten bleibt sinnvoll (manuelle visuelle/
  funktionale Kontrolle schon in Phase 1, spaeter Scan-Ziel fuer a11y/visuelle Regression/
  Performance), wird aber nicht vor dem naechsten groesseren Anlass gebaut. Weiterhin Voraussetzung
  fuer den a11y-Scan-Punkt in `docs/to-do.md` Abschnitt 1 (Testing &
  Qualitaetssicherung).
- **Font-Loading-Strategie**: aktuell irrelevant — `--font-accent` in `tokens.css` ist nur ein
  System-Font-Stack, keine echten Web-Fonts geladen (Phase 1 macht bewusst kein visuelles Styling,
  siehe `CLAUDE.md` Regel 1). Geplant, sobald Phase 2 echte Web-Fonts einfuehrt: Self-Hosting statt
  Google-Fonts-CDN, `font-display: swap` als Default in jedem `@font-face`, `<link rel="preload"
as="font" type="font/woff2" crossorigin>` nur fuer die tatsaechlich above-the-fold genutzte
  Schriftschnitt-Datei (per `wp_head`-Filter, nicht hart eincodiert), Variable Fonts bevorzugt vor
  mehreren Static-Weight-Files, Subsetting auf tatsaechlich genutzte Unicode-Ranges.
- **Core-Web-Vitals-Budget/Lighthouse-CI**: wird nicht isoliert aufgesetzt, sondern an dieselbe
  `wp-env`-Infrastruktur angedockt, die fuer den a11y-Scan sowieso vorgemerkt ist (siehe
  "Test-/CI-Tooling"-Eintrag unten) — beide brauchen eine echte gerenderte Seite als Ziel, die es
  erst mit der Showcase-Seite gibt. Geplante Reihenfolge, sobald relevant: Showcase-Seite bauen ->
  einmaliges `wp-env`-Setup in CI (a11y und Lighthouse gemeinsam nutzbar) -> `@lhci/cli` mit
  `lighthouserc.json` gegen die Showcase-Seite, zunaechst als nicht-blockierender Report-Schritt
  (`continue-on-error: true`, gleiches Muster wie `composer audit`/`pnpm audit` in `ci.yml`), bis
  in Phase 2 eine sinnvolle Baseline feststeht.

### Vitest-Tests fuer select.js/combobox.js/data-table.js: zwei jsdom-Grenzen (2026-08-14)

`docs/to-do.md` Abschnitt 1 listete `select.js`/`combobox.js`/`data-table.js`
als offene Luecke im Vitest-Aufbau aus dem 2026-08-13-Eintrag unten. Ergaenzt: `select.test.js`,
`combobox.test.js`, `data-table.test.js`, nach demselben Muster wie `toggle.test.js` (Markup direkt
in jsdom nachgebaut statt PHP zu rendern, siehe Kopfkommentare der Testdateien). Dabei zwei
wiederkehrende jsdom-Grenzen aufgetreten, die bei kuenftigen Enhancement-Modul-Tests direkt wieder
auftreten werden:

- **`Element.prototype.scrollIntoView` existiert in jsdom nicht** (jsdom implementiert kein Layout).
  `select.js`/`combobox.js` rufen es beim Aktivieren eines Listbox-Items auf. Gemeinsamer No-op-Stub
  in `assets/js/test-setup.js`, eingehaengt ueber `vitest.config.js`s `setupFiles` — global fuer alle
  Suiten, nicht pro Testdatei dupliziert.
- **`window.location.assign()`/`.reload()` lassen sich in jsdom nicht per `vi.spyOn()` abfangen** —
  beide sind nicht-konfigurierbare, nicht schreibbare Own-Properties auf dem `Location`-Objekt
  (`Object.getOwnPropertyDescriptor` zeigt `configurable: false, writable: false`), nicht normale
  Prototype-Methoden. `data-table.test.js`s Fallback-Tests (Fetch schlaegt fehl / Response hat zu
  wenige Tabellen / `popstate`-Re-Fetch schlaegt fehl) weisen den jeweiligen Catch-Zweig deshalb
  indirekt nach: `fetch` wurde mit der erwarteten URL aufgerufen, aber `history.pushState()` blieb
  aus und der Tabelleninhalt aendert sich nicht — das schliesst den Erfolgspfad aus, ohne den
  Navigations-Aufruf selbst zu spyen. Kommentar dazu direkt in `data-table.test.js`.

### Test-/CI-Tooling: Brain Monkey statt wp-env, Vitest statt Jest (2026-08-13)

`docs/to-do.md` Abschnitt 1 forderte CI, einen Pre-Commit-Hook und
automatisierte Tests. Umgesetzt: `.github/workflows/ci.yml` (zwei Jobs, PHP/JS getrennt), `husky` +
`lint-staged` (`.husky/pre-commit`), PHPUnit + `brain/monkey` (`tests/Unit/HelpersTest.php`) und
Vitest (`vitest.config.js`, `assets/js/template-parts/base/toggle.test.js`). Siehe `CLAUDE.md`
Regel 11 fuer die Befehle.

- **Brain Monkey statt eines vollen `wp-env`/WP-PHPUnit-Testcontainers** fuer die PHP-Unit-Suite:
  `inc/template-parts/helpers.php` ruft nur eine Handvoll WP-Funktionen auf (`esc_attr()`,
  `esc_html()`, `_doing_it_wrong()`, `get_template_part()`), braucht dafuer aber keine echte
  WordPress-Installation samt Datenbank in CI — Brain Monkey stubt genau diese Funktionen pro Test.
  Ein echter `wp-env`-Container waere fuer den aktuellen Umfang (reine Logik-Helper) unverhaeltnismaessig
  schwer (Docker, MySQL, WP-Core-Checkout) und bleibt Vormerkung fuer den Tag, an dem die (aktuell
  zurueckgestellte) Komponenten-Showcase-Seite (siehe Eintrag oben) sowie die a11y-/
  Visual-Regression-Punkte aus `docs/to-do.md` Abschnitt 1 tatsaechlich
  eine echte laufende WP-Instanz brauchen (axe-core/Playwright gegen eine gerenderte Seite, nicht
  gegen isolierte PHP-Funktionen) — dann lohnt sich `wp-env` fuer diesen Zweck, nicht als Ersatz
  fuer die schnelle Brain-Monkey-Suite.
- **`hengegroup_theme_render_icon()`/`hengegroup_theme_render_image()` bewusst nicht in dieser Suite getestet**:
  beide puffern `get_template_part()`-Aufrufe gegen echte Template-Dateien (`template-parts/base/
icon.php`/`image.php`) — Brain Monkey kann `get_template_part()` stubben, aber dann wird nicht
  mehr das echte Rendering getestet, nur noch, dass der Helper die Funktion aufruft. Ein
  aussagekraeftiger Test dafuer ist ein WP-gestuetzter Integrationstest, kein Unit-Test-Fall.
- **Vitest statt Jest** fuer die JS-Unit-Suite: teilt sich Vite/`vite.config.js`s Toolchain, die das
  Projekt fuer den Asset-Build ohnehin schon hat (kein zweites Bundler-/Transform-Setup noetig),
  eigene `vitest.config.js` mit `environment: "jsdom"` fuer DOM-Zugriff in den Tests.
- **`husky` + `lint-staged` statt `simple-git-hooks`**: etabliertere, staerker dokumentierte
  Kombination fuer ein Vorlagen-Repo, das andere Personen uebernehmen — `simple-git-hooks` waere
  minimal leichter, aber weniger vertraut. `.husky/pre-commit` ruft `lint-staged` auf, das
  `prettier --write` (alle staged `*.{css,js,json,md,php}`) und `phpcs` (staged `*.php`, echtes
  Gate statt reinem Autofix, da die aktiven WPCS-Sniffs aus Regel 11 -- `strict_types`, Escaping,
  sanitisierte Datei-Zugriffe -- nicht automatisch fixbar sind) ausfuehrt.
- **CI in zwei unabhaengigen Jobs (PHP/JS)** statt einem gemeinsamen: ein kaputter `pnpm audit`
  o. ae. im JS-Toolchain soll den PHP-Lint-/Test-Status nicht verdecken und umgekehrt.
- **a11y-Scan (axe-core/Playwright) und Visual-Regression bewusst nicht Teil dieser Runde** — beide
  brauchen eine echte gerenderte Seite als Ziel, die es erst mit der (aktuell zurueckgestellten)
  Komponenten-Showcase-Seite gibt (siehe "Komponenten-Showcase-Seite und Performance-Tooling"-Eintrag
  oben). Vormerkung, sobald diese existiert.

### Mehrsprachigkeit ueber Multisite statt Hreflang-Plugin (2026-08-13)

Mehrsprachigkeit ist ueber WordPress Multisite geplant (ein Netzwerk-Standort pro Sprache), nicht
ueber ein Single-Site-Plugin wie WPML/Polylang. `inc/setup/theme-seo-output.php` liefert deshalb
noch kein `hreflang`-Markup — das braucht eine eigene, gegen das tatsaechliche Netzwerk-Setup
gebaute URL-Zuordnung (welcher Standort entspricht welcher Sprache, welche Seite dort ist das
Pendant zur aktuellen), die sich sinnvoll erst bauen laesst, sobald das Netzwerk existiert.
Vormerkung fuer dann. Siehe Kopfkommentar von `theme-seo-output.php`.

### Kein RTL-Support (2026-08-13)

`dir="rtl"` wird bewusst nicht unterstuetzt — dieses Projekt braucht nie RTL-Sprachen. Kein offener
Punkt, keine Vormerkung noetig.

### Start-CSP bewusst locker, keine harte Absicherung out of the box (2026-08-13)

`inc/setup/theme-hardening.php` liefert auf dem Front-End eine Start-Content-Security-Policy, die
effektiv alles ueber `https:` sowie Inline-Scripts/Styles/`eval` erlaubt (ausser
`frame-ancestors 'self'`, das schon von Anfang an sinnvoll restriktiv ist). Ziel ist nicht Schutz
ab Werk, sondern eine fertige Direktiven-Struktur, die ein Projekt-Theme gegen seine tatsaechlich
genutzten Hosts (Fonts, WooCommerce/Stripe/PayPal, Embeds, ...) verschaerft, ohne bei
Theme-Uebernahme sofort etwas zu brechen. Die Baseline-Security-Header (`X-Content-Type-Options`,
`Referrer-Policy`, `X-Frame-Options`, `Permissions-Policy`) daneben sind dagegen ohne Trade-off
scharf, weil sie nichts erlauben/verbieten, was ein Plugin/Core-Screen brechen koennte — laufen
deshalb zusaetzlich auf `wp-admin`/Login, die CSP nur auf dem Front-End. Siehe Kopfkommentar von
`theme-hardening.php`.

### Dependency-Audits: automatisiert bei jedem CI-Lauf, aber weiterhin kein blockierendes Gate (2026-08-13, aktualisiert)

`composer audit`/`pnpm audit` laufen seit `.github/workflows/ci.yml` (siehe
"Test-/CI-Tooling"-Eintrag oben) automatisch bei jedem Push/PR mit, zusaetzlich weiterhin auf
Zuruf lokal (siehe `CLAUDE.md` Regel 11) — beides bewusst als `continue-on-error`/reiner Report,
nicht als Schritt, der einen Build/PR blockiert. Urspruenglich (siehe erste Fassung dieses
Eintrags) war das Zurueckstellen eines Audit-Gates noch mit "es gibt noch keine CI-Pipeline"
begruendet; die Pipeline existiert jetzt, das Gate bleibt trotzdem bewusst aus — ein Advisory in
einer transitiven Dev-Dependency erzwingt nicht automatisch ein sofortiges Update (Breaking
Changes, fehlender Patch, false positive), das rechtfertigt noch keinen roten Build. Funde werden
weiterhin manuell per Versions-Update behoben, wie zuletzt bei `vite`/`@tailwindcss/vite`/
`immutable` (siehe CHANGELOG "Fixed"-Eintrag).

### Dokumentations-Struktur: `entscheidungen.md`/`how-to.md`/`to-do.md` statt zwei kombinierter Dateien (2026-08-16)

`docs/entscheidungen-und-howtos.md` (Entscheidungen + HowTos in einer Datei) und
`docs/luecken-und-empfehlungen.md` (offene Punkte) wurden aufgeteilt in drei fokussierte Dateien:
diese Datei (`docs/entscheidungen.md`, nur Entscheidungen), `docs/how-to.md` (nur Anleitungen) und
`docs/to-do.md` (nur offene/noch nicht entschiedene Punkte, gleicher Inhalt/gleiche
Abschnittsnummerierung wie zuvor `luecken-und-empfehlungen.md`). Grund: die beiden vorherigen
Dateien mischten pro Datei zwei unterschiedliche Lesarten (bereits Entschiedenes vs. noch offene
Fragen bzw. Entscheidung vs. Anleitung), was das Auffinden erschwerte, je feingranularer das Log
wurde. `CLAUDE.md` selbst bleibt unveraendert die einzige automatisch geladene, normative
Anweisungsdatei — nur die Verweise auf die alten Dateinamen in Regel 11/12 wurden auf die drei neuen
Dateien umgestellt, der bindende Regel-Inhalt selbst ist nicht verschoben worden.
