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

### Stellenangebote: Custom-Post-Type angelegt (2026-09-23)

Auf expliziten Wunsch ein neuer Custom Post Type `stellenangebote` (Karriere/Jobs) --
`inc/setup/theme-careers.php`, Vorlage/Ablauf analog zum inzwischen wieder entfernten `anwendung`-
Post-Type (siehe "Anwendungen: Produktkategorie statt eigenem Post-Type" unten). Bewusst als erster,
grober Aufschlag angelegt (explizite Ansage: "leg es mal grob an, genaue Infos kommen später") --
Content-Modell aktuell nur `title`/`editor`/`thumbnail`/`excerpt`, keine strukturierten Metafelder.

- **URL-Struktur**: `has_archive` UND `rewrite.slug` beide auf `'karriere'` gesetzt --
  Übersichtsseite unter `/karriere/`, jede einzelne Stellenanzeige unter `/karriere/<slug>/`
  (explizite Vorgabe, entspricht der geplanten Live-URL-Struktur). Gleiches Muster wie WordPress
  Core selbst für `/blog/` + `/blog/post-name/` (Archiv-Slug == Rewrite-Slug einer Custom-Post-
  Type). **Achtung**: kollidiert mit einer evtl. bereits existierenden WordPress-Seite mit Slug
  "karriere" -- falls eine solche Seite existiert, muss sie vor dem Go-Live entfernt/umbenannt
  werden, sonst gewinnt die Seite gegen das CPT-Archiv. Nach dem Anlegen einmalig Permalinks
  neu speichern (Einstellungen -> Permalinks -> Speichern), sonst liefert `/karriere/` einen
  404, bis die Rewrite-Rules neu geschrieben wurden.
- **Post-Type-Key `stellenangebote`** ist bewusst unabhängig vom URL-Slug `karriere` -- beide
  Werte werden in WordPress komplett getrennt konfiguriert (`register_post_type()`s erstes
  Argument vs. `rewrite`/`has_archive`), keine Notwendigkeit, sie gleichzusetzen.
- **Inhalt ausschließlich über den nativen Gutenberg-Editor** (`the_content()`), kein eigenes
  Datenmodell für Unternehmensdarstellung/Benefits/Anforderungen/Aufgaben (explizite Ansage) --
  anders als z. B. die Produktbox gibt es hier (noch) keine strukturierten Felder, die eine eigene
  Metabox/ein eigenes Markup rechtfertigen würden.
- **`archive-stellenangebote.php`/`single-stellenangebote.php`**: Archiv nutzt dieselbe
  `.wrapper`-Grid-/`display: contents`-Technik wie `woocommerce/archive-product.php`, pro Job
  `template-parts/base/card.php` (generische Komponente, kein eigenes Markup wie bei der
  Produktbox -- noch keine projektspezifische Form zu modellieren). Die Einzelseite ist bewusst so
  schlicht wie `single.php`/`page.php` (Titel + `the_content()`), nur mit `.wrapper`-Innenabstand
  für konsistenten Randabstand unter dem `fixed`-Header.
- **SEO-Integration**: `stellenangebote` über den bestehenden `hengegroup_theme_seo_post_types`-
  Filter (siehe `docs/how-to.md`) in die SEO-Metabox aufgenommen -- kein eigenes JobPosting-JSON-LD-
  Schema (siehe `docs/how-to.md`s eigenes Beispiel dafür), da die dafür nötigen Felder
  (Standort/Beschäftigungsart/Bewerbungsschluss/...) noch nicht existieren; Vormerkung in
  `docs/to-do.md`.
- **Noch offen** (siehe `docs/to-do.md`): Standort/Unternehmen(-Taxonomie mit Logo)/sonstige
  strukturierte Felder, Bewerbungsformular (inkl. Versand/Speicherung, Datei-Upload, DSGVO) --
  bewusst nicht Teil dieses Aufschlags, folgt als eigener Auftrag, sobald das genaue Feld-/
  Formular-Konzept feststeht.

### Produkte-Block: Ueberschrift/Text als RichText, Produktraster als eigener Vorschau-Block (2026-09-23)

`hengegroup-theme/produkte` (`template-parts/blocks/produkte/`) rendert sein Produktraster ueber
`hengegroup_theme_render_produkte_grid()` (`inc/template-parts/woocommerce-product-card.php`) --
eine eigene `WP_Query` + `wc_get_template_part('content', 'product')` statt einem eigenen,
blockspezifischen Karten-Markup -- explizite Nachfrage: `woocommerce/content-product.php` (siehe
dessen eigenen Kopfkommentar) bleibt unveraendert, derselbe Baustein wie im Shop-Archiv
(`woocommerce/archive-product.php`). `wp_reset_postdata()` innerhalb des Helpers, weil dies --
anders als die Archiv-Seite -- eine Nebenquery ist, keine Hauptquery.

Kopfzeile (Ueberschrift/Text/Button) bekommt trotzdem volles `col-span-12` (nicht z. B.
`lg:col-span-7`) und wird stattdessen per `max-w-2xl` optisch schmal gehalten -- ein Teil-`col-span`
liesse die erste Produktkarte per CSS-Grid-Auto-Placement in die auf dieser Zeile noch freien
Spalten rutschen statt in eine eigene Zeile darunter (siehe render.php's Kopfkommentar).

**Nachtrag (2026-09-23): Ueberschrift/Text direkt im Editor-Content-Bereich editierbar.** Auf
expliziten Wunsch laufen Ueberschrift/Text jetzt als natives `RichText` direkt im Canvas von
`assets/js/blocks/produkte/edit.jsx` (gleiches Muster wie `ueberschrift-text/edit.jsx`) statt als
Sidebar-Felder -- der Block hatte bis dahin eine reine `ServerSideRender`-Vorschau (wie
`buehne/edit.jsx`), was mit direkt editierbarem `RichText` kollidiert: `ServerSideRender` gegen
denselben Block wuerde render.php's eigene Ueberschrift/Text ein zweites Mal statisch neben dem
editierbaren Feld rendern. Deshalb wurde die WP_Query/Loop-Logik in den oben genannten Helper
extrahiert und zusaetzlich hinter einem zweiten, im Inserter versteckten Block
`hengegroup-theme/produkte-raster` (`"supports": {"inserter": false}`, kein eigenes Editor-BUNDLE --
siehe render.php-Kopfkommentar) verfuegbar gemacht: `produkte/edit.jsx` rendert Ueberschrift/Text/Button
selbst (Klassen 1:1 aus render.php/button.php gespiegelt, gleiches Prinzip wie
`ueberschrift-text/edit.jsx`) und ruft `ServerSideRender` gezielt NUR gegen `produkte-raster` auf
(nur `productCategory`/`numberOfProducts` als Attribute), fuer eine weiterhin echte
Live-Produktraster-Vorschau ohne Ueberschrift/Text zu duplizieren.

Bekannte Einschraenkung, nur im Editor: `ServerSideRender`s eigenes Wrapper-`<div>` hat kein
`display: contents`, unterbricht also die `.wrapper`-Grid-Kette zwischen `produkte/edit.jsx`s
JSX-`.wrapper` und den Produktkarten-`<li>`s aus `produkte-raster` -- die Karten erscheinen im
Editor-Canvas gestapelt statt im 4-Spalten-Raster, mit unveraendert echten Produktdaten/-Markup. Das
Frontend (`produkte/render.php`) ist davon nicht betroffen, es nutzt `produkte-raster` gar nicht.

**Bugfix (2026-09-23): "Block type 'hengegroup-theme/produkte-raster' is not registered."** Der
Block crashte im Editor mit genau dieser React-Fehlermeldung, sobald `ServerSideRender` gegen
`produkte-raster` aufgerufen wurde. Ursache: `@wordpress/server-side-render` prueft den
uebergebenen Blocknamen zuerst gegen die CLIENTSEITIGE Block-Registry (`wp.blocks.getBlockType()`),
bevor es ueberhaupt den `/wp/v2/block-renderer/...`-REST-Endpunkt aufruft -- die rein
server-seitige `register_block_type()`-Registrierung von `produkte-raster`
(inc/setup/theme-blocks.php) reicht dafuer NICHT, obwohl sie fuer den REST-Endpunkt selbst
ausreicht. Fix: `produkte/edit.jsx` registriert `produkte-raster` zusaetzlich clientseitig via
`registerBlockType(produkteRasterMetadata, { edit: () => null, save: () => null })`, huckepack im
selben Bundle statt einer eigenen `vite.config.editor-produkte-raster.js` -- `produkte-raster`
braucht ohnehin kein eigenes `edit`-UI (`"supports": {"inserter": false}`), nur eine der
Client-Registry bekannte Definition.

**Nachtrag (2026-09-23): Ueberschrift/Text/Button ohne vordefinierte Standardwerte, Button
ebenfalls direkt im Content-Bereich editierbar.** Auf expliziten Wunsch haben `heading`/`buttonText`
jetzt `""` als `default` statt vorausgefuellter Werte ("Produkte"/"Alle Produkte") --
`template-parts/blocks/produkte/block.json`, kein Code jenseits der Defaults betroffen (render.php
ueberspringt leere Werte ohnehin, siehe dessen `if ($heading !== '')`/`if ($button_text !== '')`).

Der Button-TEXT ist jetzt ebenfalls `RichText` im Canvas (gleiches Muster wie Ueberschrift/Text),
statt eines Sidebar-`TextControl`. Der Button-LINK ist keine ComboboxControl-Seitenauswahl mehr
(das bisherige `PageLinkControl`-Muster von buehne/edit.jsx), sondern
`@wordpress/block-editor`s eigene `LinkControl`-Komponente in einem `Popover`, aufgerufen ueber ein
Link-Icon in der Block-Toolbar (`BlockControls`) -- exakt dasselbe UX-Muster wie Cores eigener
`core/button`-Block. Vorteil gegenueber dem PageLinkControl-Muster: `LinkControl` sucht selbst per
WP-REST-Suche ueber alle Inhaltstypen (nicht nur `page`) und erlaubt zusaetzlich freie/externe URLs,
kein eigener `useSelect()`-Picker mehr noetig. Die Sidebar (`InspectorControls`) enthaelt dadurch
nur noch Produktraster-KONFIGURATION (Produktkategorie/Anzahl Produkte), keine Text-/Link-Inhalte
mehr.

**Nachtrag (2026-09-23): Ueberschrift-Element (H1-H6/P) ebenfalls in der Toolbar statt Sidebar.**
Das `headingTag`-Attribut (Default `h2`, siehe block.json) war zunaechst als Sidebar-`SelectControl`
umgesetzt, auf expliziten Wunsch aber in die Block-Toolbar verschoben:
`ToolbarDropdownMenu` (`@wordpress/components`) neben dem Button-Link-Icon, Icon `heading`, Liste
H1-H6 + Absatz (P) als `controls`-Eintraege mit `isActive`/`onClick`. Bewusst NICHT an den Fokus des
Ueberschrift-`RichText` gekoppelt (kein bedingtes Ein-/Ausblenden per `onFocus`/`onBlur`, obwohl der
urspruengliche Wunsch "direkt an der Ueberschrift beim Anklicken" war) -- ein Klick auf ein
Toolbar-Control loest zuerst `onBlur` des `RichText` aus, bevor der Klick selbst verarbeitet wird;
eine fokus-gekoppelte Toolbar wuerde deshalb verschwinden, bevor man sie anklicken kann (bekannte
Gutenberg-Falle). Die Toolbar bleibt stattdessen wie ueblich an die Block-Selektion gekoppelt
(sichtbar, solange der Produkte-Block ausgewaehlt ist), nicht an die Ueberschrift-Feld-Selektion
selbst.

**Nachtrag (2026-09-23): "Ausrichten" aus der Toolbar entfernt, "Umwandeln in" bewusst NICHT.** Auf
expliziten Wunsch verschwindet die "Ausrichten"-Kontrolle aus der Block-Toolbar: `supports.align`
(vorher `["wide", "full"]`) sowie das eigene `align`-Attribut sind komplett aus block.json entfernt
(render.php hat `$attributes['align']` ohnehin nie gelesen, siehe dessen Kopfkommentar). Ohne
Ersatz wuerde die Editor-Canvas fuer diesen Block aber wieder auf theme.json's schmale
`contentSize`-Spalte (48rem) zusammenschrumpfen -- derselbe Befund, den ueberschrift-text/buehne
ueber `supports.align` loesen (siehe deren Kopfkommentare/den entsprechenden Eintrag oben). Fix:
`assets/js/blocks/produkte/edit.jsx`s `useBlockProps()` setzt `alignfull` jetzt HARDCODIERT als
Klasse -- rein die CSS-Klasse, unabhaengig davon, WIE sie zustande kommt, reicht fuer die
Iframe-Breite, keine Toolbar-UI mehr noetig dafuer.

"Umwandeln in" (Block-Switcher/Transform-Dropdown, `@wordpress/block-editor`s `BlockSwitcher`)
bleibt dagegen bestehen: recherchiert (WordPress-Block-Editor-Handbuch + Gutenberg-Quellcode,
2026-09-23) -- es gibt KEINEN dokumentierten `supports`-Schalter dafuer, das Icon wird fuer
praktisch jeden entfernbaren Block unconditional von Core gerendert (der Inhalt des aufgeklappten
Dropdowns haengt zwar von registrierten Transforms/Styles ab, der Toolbar-BUTTON selbst nicht). Die
einzigen bekannten Wege waeren entweder die Block-Locking-API (verhindert nebenbei auch
Verschieben/Entfernen des Blocks -- ungewollter Nebeneffekt fuer das eigentliche Anliegen) oder ein
`editor.BlockListBlock`-Filter + block-spezifischer CSS-Selektor (fragiler Hack gegen interne, nicht
oeffentlich stabile Gutenberg-DOM-Struktur, kein Regel-1-Tailwind-Styling-Fall). Bewusst nicht
umgesetzt, bis eine sauberere Loesung existiert oder der Nutzer den Trade-off explizit akzeptiert.

**Nachtrag (2026-09-23): Dasselbe Muster (Ueberschrift-Element + kein "Ausrichten") auch auf
ueberschrift-text uebertragen, Default fuer beide Bloecke jetzt `p` statt `h2`.**
`hengegroup-theme/ueberschrift-text` bekommt genau dieselben beiden Aenderungen wie oben fuer
`hengegroup-theme/produkte` beschrieben: `headingTag`-Attribut (H1-H6/P) ueber ein
`ToolbarDropdownMenu` in der Block-Toolbar (identischer `HEADING_TAG_OPTIONS`-Aufbau, dupliziert
statt geteilt, siehe assets/js/blocks/ueberschrift-text/edit.jsx's Kopfkommentar), und
`supports.align`/das `align`-Attribut komplett entfernt zugunsten eines hardcodierten `alignfull` in
`useBlockProps()` (hier zusaetzlich noetig, damit `containerWidth`s `.wrapper`/`.wrapper-small`
-Unterschied im Editor ueberhaupt sichtbar bleibt, siehe render.php's Kopfkommentar -- derselbe
Grund, aus dem dieser Block `supports.align` urspruenglich ueberhaupt erst bekommen hatte).

Beide Bloecke (`produkte` UND `ueberschrift-text`) haben jetzt `headingTag`-Default `p` statt `h2`
(explizite Nachfrage) -- betrifft nur NEU eingefuegte Blockinstanzen, `render.php` validiert den
Wert ohnehin gegen `h1`-`h6`/`p` mit `p` als Fallback bei ungueltigem/fehlendem Attribut.

**Nachtrag (2026-09-23): `ueberschrift-text`s Ausrichtung/Breite ebenfalls in die Toolbar
verlagert, Akzent-Woerter bleibt in der Sidebar.** Auf expliziten Wunsch verlassen **Ausrichtung**
(`textAlign`, zentriert/linksbuendig) und **Breite** (`containerWidth`, Standard/Schmal) die
Sidebar. Ausrichtung ist binaer (2 Werte) und deshalb eine `ToolbarGroup` mit zwei
`ToolbarButton`s (`isPressed` zeigt den aktiven Wert), analog zu Cores eigenen
Format-/Ausrichtungs-Toggle-Gruppen. Breite lief zunaechst genauso, ist auf erneute Nachfrage
(2026-09-23, noch am selben Tag) aber ein `ToolbarDropdownMenu` geworden -- exakt derselbe Aufbau
wie beim Ueberschrift-Element oben (`CONTAINER_WIDTH_OPTIONS` statt `HEADING_TAG_OPTIONS`,
`title`/`isActive`/`onClick`-`controls`-Eintraege), Labels "Standard" (Wert `default`, die breite
`.wrapper`-Klasse) und "Schmal" (Wert `small`, `.wrapper-small`).

**Akzent-Woerter** war kurzzeitig ebenfalls in einem Toolbar-`Popover` (`ToolbarButton` +
`accentWordsButtonRef`, exakt dasselbe Popover-Muster wie `produkte/edit.jsx`s
Button-Link-Popover), ist aber auf erneute Nachfrage (2026-09-23, noch am selben Tag) wieder
zurueck in die Sidebar (`InspectorControls`/`PanelBody`) verlagert -- als einziges verbleibendes
Freitextfeld dieses Blocks bleibt es dort besser editierbar als in einem Toolbar-Popover. Die
Sidebar enthaelt dadurch weiterhin genau eine `PanelBody` mit nur noch diesem einen Feld
(+ der `Notice` fuer nicht-treffende Akzent-Woerter).

**Nachtrag (2026-09-23): Produkte-Block bekommt eine manuelle Produktauswahl statt
Kategorie+Anzahl-Filter, plus Fix fuer die gestapelte Editor-Vorschau.** Auf expliziten Wunsch
waehlen Redakteure jetzt einzelne, bestehende Produkte in frei waehlbarer Reihenfolge/Anzahl aus,
statt eine `product_cat`-Kategorie + eine feste Produktanzahl anzugeben -- das bisherige Ergebnis
("irgendwelche N Produkte dieser Kategorie") liess sich nicht gezielt kuratieren.

- **`productCategory`/`numberOfProducts`-Attribute ersetzt durch `productIds`** (Array von
  Produkt-IDs in Anzeigereihenfolge) in `template-parts/blocks/produkte/block.json` UND dem
  Editor-Vorschau-Zwillingsblock `produkte-raster/block.json` (beide immer im Gleichschritt, siehe
  deren aeltere Eintraege oben).
- **`hengegroup_theme_render_produkte_grid()`** (`inc/template-parts/woocommerce-product-card.php`)
  nimmt jetzt `array $product_ids` statt `string $product_category, int $number_of_products` --
  `WP_Query` laeuft ueber `post__in` + `orderby: post__in` (Ausgabereihenfolge = Auswahlreihenfolge
  der Redaktion, keine implizite Kategorie-/Datumssortierung mehr), leeres Array liefert `''`
  (gleiche "buffer and check for emptiness"-Konvention wie zuvor).
- **`assets/js/blocks/produkte/edit.jsx`**: neue `ProductPicker`-Komponente ersetzt die bisherige
  `ProductCategoryControl` + `RangeControl` in der Sidebar -- laedt (wie die alte
  `ProductCategoryControl` fuer `product_cat`-Terms) ALLE veroeffentlichten Produkte einmalig ueber
  den `core`-Datenstore, eine `ComboboxControl` durchsucht die noch nicht gewaehlten Produkte zum
  Hinzufuegen, darunter eine Liste der gewaehlten Produkte mit Verschieben/Entfernen (gleiches
  `PanelRow`+Pfeil-Icons-Muster wie `buehne/edit.jsx`s Folien-Liste, siehe dessen eigenen Eintrag,
  hier ohne "Bearbeiten"-Button, weil es pro Produkt keine eigenen Felder gibt).
- **Bugfix "Karten sehen im Backend komisch aus" (Nutzer-Feedback):** die Editor-Vorschau
  (`ServerSideRender` gegen `produkte-raster`) zeigte die Produktkarten bisher gestapelt statt im
  4-Spalten-Raster -- `ServerSideRender`s eigener Wrapper-`<div>` (kein `display: contents`)
  unterbrach die `display: contents`-Kette zwischen `.wrapper`s Grid und den Produktkarten-`<li>`s
  (siehe produkte-raster/render.php's aelteren Eintrag zu dieser bis dahin bekannten
  Einschraenkung). Fix: der Tailwind-Selektor `[&>div]:contents` auf dem
  `ServerSideRender`-umschliessenden `<div>` in edit.jsx macht auch dessen direktes `<div>`-Kind (den
  SSR-Wrapper) zu `display: contents`, die Kette bleibt dadurch bis zu den `<li>`s durchgaengig --
  reine Editor-JS-Massnahme, render.php/produkte-raster/render.php selbst unveraendert.
- Ohne ausgewaehlte Produkte zeigt die Canvas jetzt einen Hinweistext statt einer leeren
  `ServerSideRender`-Anfrage (spart zugleich einen unnoetigen REST-Request).

Nachtrag zum naechsten Eintrag unten: Nach dem ersten Deploy meldete der Nutzer, dass "Vollbild" in
der Kurzbeschreibungs-Toolbar trotz
`hengegroup_theme_filter_teeny_mce_buttons_product_short_description_remove_buttons()` weiterhin
sichtbar war -- alle anderen Toolbar-Aenderungen (Hauptbeschreibung, "Medien hinzufuegen") wirkten
bereits nach diesem Deploy, was ein Deploy-/Caching-Problem ausschloss und auf einen gezielten Bug
nur bei diesem einen Filter hindeutete.

- **Vermutete Ursache**: WooCommerce uebergibt fuer den `excerpt`-Editor vermutlich einen eigenen
  `tinymce`-Settings-Teilarray (inkl. eigenem `toolbar1`-String), den `wp_editor()` per
  `array_merge()` ueber die aus `teeny_mce_buttons` gebaute WordPress-Standard-Toolbar
  draufbuegelt -- der Filter greift dadurch, greift aber ins Leere, weil das Ergebnis direkt danach
  ueberschrieben wird.
- **Fix**: zusaetzlicher `tiny_mce_before_init`-Filter
  (`hengegroup_theme_filter_tiny_mce_before_init_product_short_description_remove_buttons()`) --
  der letzte Filter vor der JSON-Kodierung des kompletten TinyMCE-Init-Arrays, entfernt
  `blockquote`/`fullscreen` direkt aus den fertig zusammengebauten `toolbar1`-`toolbar4`-Strings.
  Gewinnt garantiert gegen jeden vorherigen `array_merge()`, unabhaengig von der genauen Ursache.
  Der urspruengliche `teeny_mce_buttons`-Filter bleibt zusaetzlich bestehen (schadet nicht, greift
  ggf. auf anderen WooCommerce-Versionen ohne eigenen `tinymce`-Teilarray).

### Produktbeschreibung/-kurzbeschreibung: nur noch Visual-Editor, stark reduzierte Toolbar (2026-09-23)

Auf expliziten Wunsch bekommen die Produktbeschreibung (`content`-Editor) und die
Produktkurzbeschreibung (`excerpt`-Editor) im Produkt-Editor nur noch den Visual-Editor mit einer
reduzierten Toolbar -- Redakteure sollen kein HTML/Markup direkt bearbeiten, keine Absatz-/
Ueberschriften-Formatierung waehlen und weder "Weiterlesen"-Tag, Blockzitat, Medien-Upload noch
Vollbild/die erweiterte zweite Toolbar-Zeile nutzen koennen.

- **`inc/setup/theme-admin-woocommerce.php`**:
  `hengegroup_theme_filter_wp_editor_settings_product_description_visual_only()` (Hook:
  `wp_editor_settings`) setzt `quicktags => false` und `media_buttons => false` fuer die
  Editor-IDs `content`/`excerpt`, aber nur auf dem einzelnen Produkt-Editor
  (`hengegroup_theme_is_admin_product_edit_screen()`) -- unterdrueckt den Visual/Text-Umschalter
  sowie den "Medien hinzufuegen"-Button komplett, nicht nur versteckt sie.
  `hengegroup_theme_filter_mce_buttons_product_description_remove_buttons()` (Hook: `mce_buttons`)
  entfernt zusaetzlich `formatselect` (Absatz/Ueberschrift 1-6/...), `wp_more` ("Weiterlesen"-Tag),
  `blockquote` (Blockzitat) und `wp_adv` (Umschalter fuer die erweiterte zweite Toolbar-Zeile) aus
  der Toolbar der Hauptbeschreibung.
  `hengegroup_theme_filter_teeny_mce_buttons_product_short_description_remove_buttons()` (Hook:
  `teeny_mce_buttons`) entfernt `blockquote` und `fullscreen` (Vollbild) aus der
  Kurzbeschreibungs-Toolbar -- die Kurzbeschreibung laeuft in TinyMCEs "teeny"-Modus mit eigener,
  kleinerer Default-Toolbar (ohne `formatselect`/`wp_more`/`wp_adv`, aber inkl. `blockquote`/
  `fullscreen`), die ueber einen eigenen Core-Filter statt `mce_buttons` laeuft.
- **Bewusst die generischen WordPress-Core-Filter (`wp_editor_settings`/`mce_buttons`/
  `teeny_mce_buttons`) statt eines WooCommerce-spezifischen Hooks** (z. B.
  `woocommerce_product_short_description_editor_settings`) -- beide Editoren laufen letztlich durch
  dieselbe Core-Funktion `wp_editor()`, unabhaengig davon, ob WordPress selbst (Hauptbeschreibung)
  oder WooCommerce (Kurzbeschreibung) sie aufruft; ein einziges Filter-Set deckt so beide Editoren
  ab, statt sich auf einen WC-internen Hook-Namen zu verlassen, der zwischen WC-Versionen wandern
  koennte.
- **Auf den einzelnen Produkt-Editor beschraenkt** (gleicher Screen-Check wie
  `hengegroup_theme_action_admin_head_hide_woocommerce_virtual_downloadable()`) -- andere
  Editor-Instanzen in wp-admin (Seiten, Beitraege, andere Post-Types) bleiben unveraendert, die
  Nachfrage betraf ausschliesslich Produktbeschreibung/-kurzbeschreibung.

### Anwendungen: Produktkategorie statt eigenem Post-Type (2026-09-23)

Auf expliziten Wunsch bilden "Anwendungen" jetzt WooCommerce's eigene `product_cat`-Taxonomie ab,
statt wie zuvor ein eigener `anwendung`-Post-Type + `_anwendungen`-Produkt-Meta-Beziehung (siehe
den aelteren Eintrag "Produktbox: Badge-/Anwendungs-Datenmodell" unten, dessen
Anwendungen-spezifische Punkte damit ueberholt sind) -- weniger eigener Code/Verwaltungsaufwand,
Redakteure ordnen Produkten im Editor ohnehin schon Produktkategorien zu.

- **`inc/setup/theme-woocommerce-products.php`**: `register_post_type('anwendung', ...)`, die
  "Anwendungen"-Checkbox-Metabox im Produkt-Editor sowie deren Save-Handler komplett entfernt --
  kein eigenes Datenmodell fuer Anwendungen mehr in diesem Projekt.
- **`single-anwendung.php`** geloescht (Einzelseiten-Template des entfernten Post-Types).
- **`inc/template-parts/woocommerce-product-card.php`**:
  `hengegroup_theme_get_product_anwendungen()` entfernt (kein `_anwendungen`-Meta mehr zu lesen);
  `hengegroup_theme_render_product_anwendung_badges()` liest jetzt `get_the_terms($product_id,
'product_cat')` statt der bisherigen Post-ID-Liste, alphabetisch sortiert (`strnatcasecmp`,
  ersetzt die bisherige Reihenfolge aus der checkbox-Metabox, die durch deren eigene
  alphabetische Sortierung faktisch ebenfalls alphabetisch war). Label/Markup/`outline`-Variante/
  "kein `href`"-Vorgabe unveraendert (siehe die urspruengliche Begruendung im aelteren Eintrag) --
  nur die Datenquelle hat sich geaendert, das Anzeigekonzept "Anwendungen" bleibt bestehen.
- **WooCommerce's Default-Kategorie ("Unkategorisiert", `get_option('default_product_cat', 0)`,
  seit WC 3.3) wird explizit herausgefiltert**, bevor die Badges gerendert werden -- ohne diesen
  Filter wuerde JEDES Produkt ohne eigene Kategoriezuordnung eine "Unkategorisiert"-Anwendung
  zeigen, statt gar keine Anwendungen-Sektion (die urspruengliche `anwendung`-Beziehung hatte
  dieses Problem nicht, weil sie nie implizit befuellt wurde).
- **`docs/how-to.md`**s "Neue Anwendung anlegen und einem Produkt zuordnen" ersetzt durch
  "Anwendungen einem Produkt zuordnen" (Produktkategorien zuordnen statt eigenen CPT-Eintrag
  anlegen) -- kein eigener Erweiterungspunkt mehr, reine WooCommerce-Bordmittel.

### WooCommerce: ungenutzte Produkttypen/-felder deaktiviert, Rezensionen/Marken nur im Backend

ausgeblendet (2026-09-23)

Auf expliziten Wunsch (`inc/setup/theme-admin-woocommerce.php`):

- **"Gruppiert"/"Extern/angegliedert" raus aus dem Produkttyp-Dropdown** ueber den
  `product_type_selector`-Filter (`unset($types['grouped'], $types['external'])`) -- dieses Projekt
  nutzt nur einfache/variable Produkte, die beiden anderen Typen sollen gar nicht erst waehlbar
  sein, nicht nur "nicht empfohlen".
- **"Virtuell"/"Herunterladbar" per CSS ausgeblendet**, nicht per Filter -- WooCommerce bietet fuer
  diese beiden Checkboxen (allgemeiner Produkt-Tab) keinen Hook, sie sind in
  `html-product-data-general.php` fest verdrahtet. Rohes CSS (`display:none !important`) ist hier
  keine Regel-1-Ausnahme im eigentlichen Sinn -- reines Backend-Feld-Ausblenden ausserhalb der
  Tailwind-gestylten Theme-Oberflaeche, fuer die es in wp-admin ohnehin keinen Tailwind-Build gibt.
  **Nachtrag (2026-09-23): urspruenglicher Selektor traf nichts.** Angenommen war ein
  `_virtual_field`/`_downloadable_field`-Wrapper, wie ihn `woocommerce_wp_checkbox()` fuer andere
  Checkbox-Felder erzeugt -- die installierte WooCommerce-Version rendert "Virtuell"/
  "Herunterladbar" aber ueber eigenes, neueres Markup ohne diesen `<p class="{id}_field">`-Wrapper
  (Checkbox + Label direkt). Anhand des live gerenderten HTML (vom Nutzer per Inspect-Element
  geliefert) auf `label[for="_virtual"]`/`label[for="_downloadable"]` korrigiert -- WCs eigene,
  stabile Feld-IDs (`_virtual`/`_downloadable`, identisch mit dem Post-Meta-Key), unabhaengig vom
  Wrapper-Markup drumherum.
- **Rezensionen (`edit.php?post_type=product&page=product-reviews`) und Marken
  (`edit-tags.php?taxonomy=product_brand&post_type=product`) bleiben als Feature/Taxonomie aktiv**,
  nur die jeweilige Backend-Verwaltungsseite unter "Produkte" verschwindet ueber
  `remove_submenu_page()` (gleicher Mechanismus wie die uebrigen Eintraege in
  `hengegroup_theme_get_woocommerce_submenu_pages_to_remove()`) -- bewusst **kein**
  `update_option('wc_feature_woocommerce_brands_enabled', 'no')`, weil das die gesamte Marken-Taxonomie
  inkl. Frontend/REST abschalten wuerde; die Nachfrage war explizit auf "im Backend ausblenden"
  begrenzt.
- **Nachtrag (2026-09-23): die "Produktmarken"-Sidebar-Metabox im einzelnen Produkt-Editor blieb
  trotz entfernter Marken-Verwaltungsseite sichtbar** -- WordPress registriert diese Box
  automatisch pro an "product" gebundene Taxonomie (`register_taxonomy()`s `show_ui`), unabhaengig
  von der Menue-Sichtbarkeit; die entfernte Verwaltungsseite betraf nur den eigenen Menuepunkt, nicht
  diese Box. Behoben ueber `remove_meta_box('product_branddiv', 'product', 'side')` auf
  `add_meta_boxes_product` (`product_branddiv` ist WCs feste Box-ID fuer die
  `product_brand`-Taxonomie). Reviews brauchten keinen aequivalenten Nachtrag -- dort greift keine
  WC-Metabox-Sonderlocke, das Backend-Deaktivieren des Reviews-Features (WooCommerce-Einstellung)
  allein reicht bereits.

### `badge.php`: `outline`-Randfarbe jetzt `neutral-500` direkt, kein eigener Token (2026-09-23)

Auf expliziten Wunsch nutzt `badge.php`s `outline`-Variante jetzt `!border-neutral-500` -- Tailwinds
eigene Skala direkt referenziert, KEIN neuer `--color-grey-*`-Marken-Token dafuer (ein zwischenzeitlich
angelegter `--color-grey-medium`-Token wurde auf denselben Wunsch wieder entfernt). Ausgangspunkt war
die Suche nach einer Zwischenstufe zwischen `grey-light` (neutral-100, als Rand zu hell/kaum lesbar)
und `grey-dark` (neutral-800, button.php's eigene `outline`-Randfarbe, fuer ein statisches Label als
zu kraeftig empfunden) -- `neutral-500` liegt exakt in der Mitte dieser beiden.

- **Kein eigener Marken-Token**, weil aktuell nur diese eine Aufrufstelle den Wert braucht --
  dieselbe "Tailwinds eigene Skala referenzieren, wenn (noch) keine Komponenten-API den Markennamen
  als Wert erwartet"-Konvention wie tokens.css sie fuer die uebrigen Neutraltoene bereits dokumentiert
  (siehe deren Datei-Kopfkommentar). Direkt `border-neutral-500` statt `border-grey-medium` o. Ae.
- **`button.php`s eigene `outline`-Variante bleibt bei `grey-dark`** -- bewusst KEINE
  Vereinheitlichung: ein interaktiver Button darf einen kraeftigeren Rand haben als ein statisches
  Label, unterschiedliche Randstaerke ist hier Absicht, keine Inkonsistenz.
- Zwischenschritte auf dem Weg hierhin (`grey-light` -> `grey-dark` -> ein eigener
  `--color-grey-medium`-Token -> `neutral-500` direkt) sind nicht einzeln dokumentiert -- keiner davon
  wurde je committet, nur dieser Endstand zaehlt.

### Produktbox: eigenes Markup statt `card.php` (2026-09-22)

Auf expliziten Wunsch komponiert `woocommerce/content-product.php` sein Markup jetzt direkt
(`<article>` + eigene Tailwind-Klassen), statt wie zuvor `template-parts/base/card.php` mit dessen
`media`/`media_badge`/`content`/`footer`-Slots zu fuellen (Aenderung ggue. dem vorherigen Eintrag
"Produktbox: WooCommerce-Template statt eigenem template-part" unten, der noch card.php nutzte).
button.php/badge.php/typography.php/image.php (ueber `hengegroup_theme_render_image()`) werden
weiterhin genutzt -- nur die gemeinsame "Card"-Abstraktion selbst nicht mehr.

- **Grund**: das aktualisierte Referenz-Design (`Produktbox.dc.html`, per Markup-Export aus dem
  Design-Programm) zeigt eine Bild-Geometrie, die nicht zu card.php's `media`-Slot passt -- card.php
  laesst das Bild randlos ueber die volle Kartenbreite bluten (`-mt-6 overflow-hidden rounded-t-2xl`,
  siehe dessen Kopfkommentar), die Referenz insetted das Bild dagegen auf drei Seiten mit Padding
  (`padding:12px 12px 0`) in eine feste `height:180px`-Flaeche, mit einem Firma-Badge bei einem
  literalen Pixel-Offset (`top:22px;left:22px`) statt card.php's eigenem `top-3 left-3` (das von der
  randlosen Bild-Geometrie ausgeht). Card.php's Slot-Modell haette fuer genau diesen einen Anwendungsfall
  gebogen werden muessen, statt es einfach zu nutzen -- eigenes Markup war der direktere Weg.
- **Farben/Radius/Schatten sind literale Referenzwerte, auf Tailwind gemappt** (siehe
  `content-product.php`s eigener Kopfkommentar fuer die volle Herleitung):
  `bg-neutral-50`/`text`-Vererbung fuer `rgb(250,249,245)`/`rgb(30,29,28)` (dieselben Werte, die
  tooltip.php's eigener Phase-2-Eintrag bereits auf `neutral-50`/`neutral-900` mappt, naeher dran
  als dieses Projekts eigene `--color-background`/`-foreground`-Tokens), `rounded-2xl` statt der
  Referenz-eigenen 20px (card.php's eigene "Radius ueber Oberflaechen hinweg vereinheitlichen"-
  Konvention, siehe dessen Kopfkommentar), `shadow-[0_8px_24px_rgba(0,0,0,0.25)]` als literaler
  Arbitrary-Value (kein Standard-Tailwind-Schatten kommt an Groesse/Dunkelheit heran, gleiche
  Begruendung wie popover.php's/tooltip.php's eigene `shadow-[...]`-Eintraege).
- **Firma-Farben brauchten keine neuen Tokens**: `--color-henge-blue` (`#075f8f`) matched die
  Referenz' `rgb(7,95,143)` fast exakt; die Referenz' zweite Firma-Farbe `#1b6e46`/`rgb(27,110,70)`
  ist derselbe literale Wert, den table-row.php's eigener Phase-2-Eintrag bereits als
  `--color-henge-green` behandelt (Referenz-Exporte variieren offenbar leicht je Design-Datei,
  gemeint ist dieselbe Marken-Akzentfarbe) -- Gruppierung/Rendering in
  `inc/template-parts/woocommerce-product-card.php` blieb unveraendert richtig.
- **"Anwendungen"-Eyebrow: `color` auf `default` statt `neutral`, `font-medium`/`tracking-wider`
  statt `font-semibold`/`tracking-wide`, `mb-2.5` statt `mb-1`** (in
  `hengegroup_theme_render_product_anwendung_badges()`) -- die Referenz zeigt das Label in
  DEMSELBEN nahezu-schwarzen Ton wie Titel/Beschreibung (nicht gedaempft/grau), `font-weight:500`
  bei `letter-spacing:1px` (bei 14px naeher an Tailwinds `tracking-wider`/0.05em als an
  `tracking-widest`/0.1em), `margin-bottom:10px`.
- **Anwendungs-Badges bekamen zwischenzeitlich `!px-3 !py-1.5`** (die Referenz zeigt `padding:6px
12px` fuer diese Pillen, roomier als badge.php's eigene projektweite `px-2 py-0.75`-Basis), auf
  spaeteren expliziten Wunsch wieder entfernt (2026-09-23) -- die Anwendungs-Badges nutzen jetzt
  wieder badge.php's normales Basis-Padding, damit sie nicht sichtbar groesser wirken als die
  Firma-Badge im Bild darueber (deren Basis-Padding unangetastet blieb).
- **`badge.php`s `outline`-Randfarbe urspruenglich bewusst NICHT angefasst**, obwohl die Referenz
  einen sichtbar dunkleren Rand (`#e2e0dc`, ~ diesem Projekts `grey-medium`/neutral-200,
  = `--color-border`) zeigt -- eine Aenderung daran wuerde jede `outline`-Badge im ganzen Theme
  treffen, nicht nur diese eine Kartenvariante. Inzwischen ueberholt: siehe den neueren Eintrag
  "`badge.php`: `outline`-Randfarbe jetzt `neutral-500` direkt, kein eigener Token" oben.
- **Button behaelt seinen normalen `grey-dark`-Hover** (`hover:!bg-grey-dark/90`), obwohl die
  Referenz keinen eigenen Hover-Zustand definiert -- ein statischer Design-Export zeigt grundsaetzlich
  keine Interaktionszustaende, das ist keine Anweisung, den projektweiten Button-Hover fuer genau
  diesen einen Button zu entfernen (CLAUDE.md "Kernhaltung": Technologie-/UX-Entscheidungen nach
  bester UX, ein konsistentes Hover-Feedback ist besser als keins). `!font-semibold`
  (`font-weight:600` der Referenz, additiv auf button.php's eigenem `font-medium`, `!`-markiert aus
  demselben "gemeinsame CSS-Property"-Grund wie oben) und `size: 'lg'` (`text-lg`/18px + `px-7`/28px
  treffen die Referenz' `font-size:18px`/`padding: 14px 28px` am naechsten) wurden uebernommen; die
  feste `h-10`(40px)-Hoehe bleibt button.php's eigene, bereits etablierte Groessen-Konvention
  (2026-08-30-Design-Anfrage, siehe button.php's Kopfkommentar) und liegt unter der Referenz'
  Padding-basierten (~55px) Hoehe -- keine Sonderbehandlung fuer eine einzelne Aufrufstelle.

### `badge.php`: `outline`-Rand von `border-transparent` ueberschrieben (Bugfix, 2026-09-22)

Live-Vergleich der Produktbox gegen den Referenzentwurf (`Produktbox.dc.html`) zeigte die
"Anwendungen"-Badges (`variant: outline`) als reinen, randlosen Text -- kein Pillen-Rand, obwohl
Padding/Layout korrekt aussahen. `getComputedStyle` bestaetigte `border-color: rgba(0,0,0,0)`.

- **Ursache**: `badge.php`s `base_classes` setzt fuer JEDE Variante `border border-transparent`
  (haelt die Border-Box bei Solid-Varianten absichtlich unsichtbar, aber gleich gross).
  `outline`s eigenes `border-grey-light` sollte das fuer diese eine Variante ueberschreiben --
  konkurriert damit aber um dieselbe CSS-Property (`border-color`). Tailwind v4 (Vite-Plugin/Oxide-
  Engine) emittiert Utility-Regeln nach interner kanonischer Reihenfolge, NICHT nach der Reihenfolge
  der Klassen im `class`-String -- `.border-transparent` landete im kompilierten CSS NACH
  `.border-grey-light` und gewann dadurch bei identischer Spezifitaet.
- **Fix**: `!border-grey-light` (Tailwind-Important-Modifier) statt `border-grey-light` --
  dasselbe Werkzeug/Vorgehen wie button.php's eigene `!bg-henge-*`/`!bg-grey-*`-Varianten (siehe
  dessen Kopfkommentar zur Editor-iframe-Kollision), hier fuer eine andere Kollision (Border- statt
  Background-Farbe, gegen eine eigene Basis-Klasse statt gegen WordPress' globale Styles).
- **Lehre**: bei mehreren Utility-Klassen fuer dieselbe CSS-Property in EINEM PHP-Base-Component
  (hier: eine gemeinsame Basis-Klasse + eine variantenspezifische Klasse) reicht die Klassen-
  Reihenfolge im PHP-String nicht als Gewinn-Garantie -- ohne tailwind-merge-Aequivalent (siehe
  card.php's/badge.php's eigene Kopfkommentare zu genau dieser Einschraenkung) ist `!` der
  verlaessliche Weg, eine bestimmte Utility unabhaengig von der kompilierten Reihenfolge gewinnen
  zu lassen.

### `woocommerce.php` (Theme-Root) entfernt -- ueberschrieb jedes WC-Template-Override (Bugfix, 2026-09-22)

`woocommerce.php` im Theme-Root (vor der Produktbox-Arbeit bereits vorhanden: nur `get_header();
<section class="woocommerce-shell"><?php woocommerce_content(); ?></section> get_footer();`)
gewinnt in WooCommerces Template-Hierarchie (`WC_Template_Loader`) gegenueber jedem spezifischeren
`woocommerce/*.php`-Override wie `archive-product.php` -- `woocommerce_content()` ist eine in WC
CORE fest einprogrammierte Funktion (page-title, Ergebnis-Zaehler, Sortier-Formular, `<ul
class="products">` als rohes PHP/HTML, KEIN Template-Datei-Aufruf, also durch kein Theme-Override
erreichbar), die fuer `is_shop()`/`is_product_taxonomy()`/`is_singular('product')` komplett eigene
Markup-Bloecke rendert.

- **Symptom, das zur Diagnose fuehrte**: der live gerenderte Quelltext der Shop-Seite zeigte WCs
  eigenen `<h1 class="page-title">Shop</h1>` + `woocommerce-result-count` + `woocommerce-ordering`-
  Formular + `<ul class="products columns-4">` -- 1:1 identisch mit `woocommerce_content()`s
  eigenem Quelltext, nicht mit unserem `archive-product.php`. Die einzelne Produktbox INNERHALB
  dieses `<ul>` rendered trotzdem korrekt ueber unser `content-product.php`, weil
  `wc_get_template_part('content', 'product')` (von `woocommerce_content()` selbst aufgerufen)
  weiterhin normale Theme-Overrides respektiert -- nur der AEUSSERE Seiten-Wrapper ist bei
  `woocommerce.php` fest einprogrammiert und nicht ueberschreibbar.
- **Fix**: Datei komplett entfernt. `WC_Template_Loader` faellt fuer jede Seite, die kein
  spezifischeres `woocommerce/*.php`-Override hat (aktuell: alles ausser Shop), automatisch auf WCs
  eigene, im Plugin gebuendelte Default-Templates zurueck (ungestylt, aber funktionsfaehig) --
  Cart/Checkout/My-Account sind ohnehin normale WordPress-Seiten mit Shortcode, laufen unabhaengig
  davon immer schon ueber dieses Themes eigenes `page.php`.
- **Konsequenz fuer kuenftige WC-Template-Arbeit**: jede weitere WC-Seite (Einzelprodukt,
  Produktkategorie-Archiv, ...) braucht ihr EIGENES `woocommerce/*.php` (z. B.
  `single-product.php`, `taxonomy-product_cat.php`) -- ein genereller Root-Wrapper wie der
  entfernte ist eine Sackgasse, sobald mehr als eine WC-Seite eigenes Design bekommen soll.

### `content-product.php` feuert `woocommerce_before/after_shop_loop_item` nicht mehr (Bugfix, 2026-09-22)

Die urspruengliche Fassung von `content-product.php` feuerte `woocommerce_before_shop_loop_item`/
`woocommerce_after_shop_loop_item` mit der Begruendung "so 3rd-party plugins that target the
default loop item still fire" -- das war schlicht falsch fuer genau diese beiden Hook-Namen. WC
core selbst haengt daran seine eigenen Default-Callbacks (`class-wc-template-hooks.php`):
`woocommerce_template_loop_product_link_open()`/`_close()` (oeffnen/schliessen eine zusaetzliche
`<a class="woocommerce-LoopProduct-link">` um das GESAMTE Element) und
`woocommerce_template_loop_add_to_cart()` (rendert den Warenkorb-/"Weiterlesen"-Button + einen
Screenreader-`<span>`) -- keine optionalen, leeren Erweiterungspunkte, sondern der Kern von WCs
eigenem Default-Markup.

- **Symptom**: jede Box zeigte zusaetzlich zu unserem `card.php`-Markup eine unsichtbare (aber im
  DOM vorhandene) zweite `<a>`-Umhuellung UND einen sichtbaren "Weiterlesen"-Button unterhalb der
  Karte -- genau das WC-Default-Verhalten, das dieses Template eigentlich vollstaendig ersetzen
  soll.
- **Fix**: beide `do_action()`-Aufrufe ersatzlos entfernt. Der `<li>`-Wrapper +
  `wc_get_product_class()` bleiben (siehe Datei-Kopfkommentar) -- das ist reine CSS-Klassen-
  Kompatibilitaet ohne eigenes Markup, im Unterschied zu den beiden entfernten Hooks.
- **Lehre fuer kuenftige WC-Template-Arbeit**: vor dem Uebernehmen eines WC-Hook-Namens aus dem
  Referenz-Default-Template IMMER pruefen, ob WC selbst (nicht nur Drittanbieter-Plugins) etwas
  daran haengt (`class-wc-template-hooks.php` im Plugin) -- ein Hook-Name allein sagt nichts
  darueber aus, ob er "leer" ist.

### Build-Skripte: `woocommerce/`-Verzeichnis fehlte in der Kopier-Liste (Bugfix, 2026-09-22)

`scripts/build.ps1`/`build.sh` kopieren Theme-Verzeichnisse nach `dist/` ueber eine fest
enumerierte `$themeDirectories`/`theme_directories`-Liste (`inc`, `template-parts`, `languages`,
`assets/images`) -- als das neue `woocommerce/`-Verzeichnis (WC-Template-Overrides,
`content-product.php`/`archive-product.php`) angelegt wurde, fehlte der passende Eintrag. Anders
als Top-Level-`*.php`-Dateien (die per `Get-ChildItem -Filter "*.php" -File`/`*.php`-Glob-Wildcard
automatisch erfasst werden, siehe den Eintrag "Wildcard statt enumerierter Liste fuer Top-Level-
PHP-Dateien" weiter unten) ist das kein rekursiver Scan -- eine neue Unterordner-Ebene braucht
IMMER einen expliziten Listen-Eintrag, ein Wildcard-Scan wuerde hier auch `node_modules`/`vendor`/
`dist` selbst als "Verzeichnis im Repo-Root" mit erfassen und muesste die dann aktiv ausschliessen.

- **Symptom, das zur Diagnose fuehrte**: eine bereits per FTP deployte Umgebung zeigte auf der
  WooCommerce-Shop-Seite weiterhin den ungestylten WordPress-Kern-Fallback (ein Gutenberg-
  Query-Loop-Block mit Bild/Titel/"Weiterlesen"-Link) statt der Produktbox, OBWOHL
  `woocommerce_shop_page_id` korrekt gesetzt war und `pnpm deploy:changed` scheinbar erfolgreich
  lief -- weil `dist/` (und damit der FTP-Upload) das `woocommerce/`-Verzeichnis nie enthielt, WC
  fiel implizit auf die normale Seiten-Vorlage zurueck.
- **Fix**: `woocommerce` als weiterer Eintrag in beiden Listen (identisches Source/Destination-
  Paar wie die anderen Verzeichnisse). Kein Wildcard-Scan wie bei den Top-Level-PHP-Dateien, weil
  ein rekursiver Verzeichnis-Scan hier absichtlich vermieden wird (s. o.).
- **Wie man das kuenftig frueher merkt**: nach jedem neuen Top-Level-Ordner (nicht nur Dateien) in
  diesem Theme `dist/` nach einem lokalen Test-Build pruefen, ob der Ordner tatsaechlich mitkam --
  kein automatischer Check dafuer vorhanden (`composer lint`/`pnpm test` pruefen PHP-Code, nicht den
  Build-Output).

### Produkt-Uebersichtsseite: `.wrapper`-Grid statt WCs eigenem Float-Grid (2026-09-22)

`woocommerce/archive-product.php` (WC-Template-Override fuer die Shop-/Produkt-Archivseite) ersetzt
WCs eigenes `<ul class="products columns-N">` (float-basiert, seit dem Dequeue der
WC-Frontend-Styles ohnehin unstyled, siehe `theme-hardening-woocommerce.php`) durch ein eigenes
`grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`, vier Spalten ab `lg` (explizite Nachfrage).

- **`.wrapper` (`assets/css/app.css`) statt eines neuen Container-Divs** -- derselbe
  1600px-gedeckelte, responsiv gepolsterte 12-Spalten-Container, den bereits jeder andere
  Seitenabschnitt nutzt (`template-parts/blocks/buehne/render.php` etc.), damit die
  Uebersichtsseite auf jedem Breakpoint dieselben Raender wie der Rest der Seite hat statt eigener
  Arbitrary-Values.
- **Produktboxen sind direkte `col-span-*`-Kinder von `.wrapper`s eigenem 12-Spalten-Grid, kein
  zweites, unabhaengig skaliertes Grid mehr** (aktualisiert 2026-09-22, explizite Nachfrage --
  die urspruengliche Fassung hatte ein eigenes `grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`
  INNERHALB eines `.wrapper`-`col-span-12`-Divs verschachtelt, zwei Grids sitzen dabei eine Ebene
  auseinander). `<ul class="contents">` (`display: contents`) haelt das semantische Listenelement,
  ohne selbst einen zweiten Grid-Kontext zu eroeffnen -- seine `<li>`-Kinder (aus
  `content-product.php`) werden dadurch direkte Kinder von `.wrapper`s EIGENEM `grid-cols-12` und
  bekommen dort `col-span-12 sm:col-span-6 lg:col-span-3` (1/2/4 pro Reihe, dieselben Breakpoints
  wie zuvor, jetzt ueber dieses Projekts eigenes `col-span`-Idiom statt einer eigenen
  `grid-cols-N`-Utility). Liegt auf dem `<li>` selbst in `content-product.php`, nicht in
  `archive-product.php` -- harmlos ausserhalb eines 12-Spalten-Grid-Elternteils (`col-span-*` ohne
  Grid-Kontext ist ein No-op), bleibt also sicher fuer jeden anderen WC-Loop-Kontext (Related
  Products, `[products]`-Shortcode, ...), der `.wrapper` gar nicht nutzt.
- **Jedes ANDERE direkte Kind von `.wrapper` (Titel, WCs Before-/After-Loop-Hook-Ausgabe) bekommt
  explizit `col-span-12`** -- ein Grid-Kind ohne eigenes `col-span-*` belegt sonst nur eine der
  zwoelf Spalten (siehe `app.css`s eigener Kommentar zu `.wrapper`), wuerde also visuell auf einen
  schmalen Streifen zusammenschrumpfen statt eine volle Zeile einzunehmen.
- **`gap-y-6` zusaetzlich zu `.wrapper`** -- `.wrapper` setzt selbst nur `gap-x-*` (siehe
  `app.css`), fuer den vertikalen Abstand zwischen Titel-/Hook-Zeile und den Produkt-Zeilen war ein
  zusaetzlicher, nicht kollidierender Wert (andere CSS-Property) noetig.
- **Bewusst "einfach" gehalten** (explizite Nachfrage): kein `woocommerce_sidebar()`-Aufruf. WCs
  eigene Default-Hooks auf `woocommerce_before_shop_loop`/`woocommerce_after_shop_loop`
  (Ergebnis-Zaehler, Sortier-Dropdown, Pagination) bleiben unangetastet bestehen -- aktiv entfernt
  wurden sie nicht, u. a. damit Pagination bei mehr Produkten als `posts_per_page` funktioniert;
  Ergebnis-Zaehler/Sortierung rendern dadurch mit nativen Browser-Elementen (kein eigenes Tailwind-
  Styling dafuer bislang), nicht kaputt, nur ungestylt.
- **`content-product.php` unveraendert** -- nur das Grid drumherum wurde ersetzt, siehe
  "Produktbox: WooCommerce-Template statt eigenem template-part" unten fuer die Box selbst.

### Produktbox: WooCommerce-Template statt eigenem template-part (2026-09-22)

Auf expliziten Wunsch nutzt die neue Produktbox `woocommerce/content-product.php` -- ein echtes
WooCommerce-Template-Override (WC's eigener `WC_Template_Loader` bevorzugt eine Datei unter
`{theme}/woocommerce/...` gegenueber der im Plugin gebuendelten) statt eines eigenen, nur an einer
Stelle eingebundenen `template-parts/`-Templates. Dadurch rendert JEDER WC-Loop-Kontext (Shop-Seite,
Produktkategorie-/Tag-Archive, `[products]`-Shortcode, Related-/Upsell-/Cross-Sell-Loops) automatisch
mit der neuen Karte, ohne dass ein Aufrufer explizit dieses Template einbinden muesste.

- **`<li>`-Wrapper + `wc_get_product_class()` bleiben erhalten**: WCs eigenes
  `archive-product.php` oeffnet ein `<ul class="products">` -- ein gueltiges Kind davon MUSS ein
  `<li>` sein, sonst korrigieren Browser die ungueltige Verschachtelung und das Grid bricht. Andere
  WC-/Plugin-CSS/JS, die `.product`/`.type-product`/... ansprechen, funktionieren dadurch weiter.
- **`woocommerce_before_shop_loop_item`/`woocommerce_after_shop_loop_item` bleiben erhalten**,
  obwohl das gesamte Innenmarkup (Bild/Preis/Warenkorb-Button in WCs eigenem Default) komplett durch
  `template-parts/base/card.php` + `badge.php` ersetzt wird -- 3rd-Party-Plugins, die auf diese
  Hooks zielen (z. B. Quick-View-Erweiterungen), feuern dadurch trotzdem weiter.
- **Kein Preis, kein Warenkorb-Button** (explizite Nachfrage 2026-09-22): Kaufen/Warenkorb ist ein
  spaeterer Auftrag.
- **Verlinkung: EIN "Produkt ansehen"-Button in der Fusszeile, nicht mehr die ganze Karte**
  (aktualisiert 2026-09-22, explizite Nachfrage anhand des aktualisierten Referenz-Designs
  `Produktbox.dc.html`, das einen vollbreiten Button statt eines ganzflaechigen Karten-Links zeigt).
  Die urspruengliche Fassung setzte `card.php`s eigenes `href`-Idiom (ganze Karte als `<a>`,
  derselbe Anwendungsfall wie dessen "Mit Bild"-Referenzbeispiel) -- das kollidiert aber mit einem
  echten Footer-Button: HTML erlaubt kein verschachteltes `<a>` in `<a>`. `content-product.php`
  setzt `card.php`s `href` deshalb nicht mehr (Kartenwurzel bleibt `tag: 'article'`, kein
  Hover-Lift mehr auf der ganzen Karte -- der war ohnehin nur inert, wenn die Wurzel ein `<a>`
  ist), stattdessen wird `template-parts/base/button.php` (`variant: 'grey-dark'`,
  `full_width: true`, `href` auf die Produktseite) gebuffert in `card.php`s `footer`-Slot gereicht
  -- derselbe "vorgerendertes HTML"-Slot, den button-group.php-Callers schon nutzen, kein neuer
  Mechanismus.
- **Nur `content-product.php` wird ueberschrieben, nicht `archive-product.php`/das Grid selbst**
  -- explizit "nur die Produktbox, nicht das drumherum". Das `<ul class="products">`-Grid bleibt
  WCs eigenes (ungestyltes) Markup, bis eine spaetere Aufgabe das Archiv-Layout selbst angeht.
- **`card.php`s `media_badge`-Slot war bereits exakt fuer diesen Fall gebaut** (siehe dessen
  eigenen Kopfkommentar: "the reference's product cards overlay a colored label ... on the
  top-left corner of the cover image") -- kein neuer Slot, keine Aenderung an `card.php`/`badge.php`
  noetig, nur Komposition.
- **`badge.php`s Basis-Klassen bleiben unveraendert**, die im Referenz-Design auffaellig
  kompakte/versale Firma-Pille kommt stattdessen ueber additive, mit `badge.php`s eigenen
  Basis-Klassen NICHT kollidierende `class`-Werte (`uppercase tracking-wide`) -- dieses Projekt hat
  kein `tailwind-merge`-Aequivalent in PHP (siehe `card.php`s eigener Kopfkommentar zu genau diesem
  Problem), ein `px-2`/`text-xs` on top haette je nach Tailwinds interner CSS-Reihenfolge
  unvorhersehbar gewonnen oder verloren.

### Produktbox: Badge-/Anwendungs-Datenmodell (2026-09-22, Badge-Teil ueberarbeitet 2026-09-23)

Zwei neue Datenmodell-Bausteine fuer die Produktbox (`inc/setup/theme-woocommerce-products.php`,
Rendering in `inc/template-parts/woocommerce-product-card.php`):

**Die Anwendungen-spezifischen Punkte unten (Post-Type/Meta-Beziehung/Verlinkung) sind inzwischen
ueberholt**, siehe den neueren Eintrag "Anwendungen: Produktkategorie statt eigenem Post-Type"
oben -- der Badge-Teil bleibt unveraendert gueltig.

- **Genau EIN "Badge" pro Produkt, ein einfaches Text-/Auswahl-Feld-Paar** (`_badge_text`-Postmeta
    - `_badge_variant`-Postmeta, editierbar ueber eine "Badge"-Metabox im Produkt-Editor), KEINE
      Taxonomie mehr (explizite Ueberarbeitung 2026-09-23, ersetzt die urspruengliche `firma`-Taxonomie
    - hartkodierte Kominex/Imexco-sind-blau-Slug-Liste + Gruppierungslogik dieses Eintrags). Die
      Farbe ist jetzt eine direkte redaktionelle Wahl aus `hengegroup_theme_get_badge_variants()`
      (henge-blue | henge-green | henge-grey, dieselben drei Volltonfarben aus badge.php's Vokabular)
      statt aus einem Firmennamen abgeleitet -- Motivation: es gibt nur noch eine Badge-Anzeige pro
      Produkt, keine Mehrfachauswahl/Gruppierung mehr, eine Taxonomie mit fixer Farbregel war fuer
      diesen schmaleren Anwendungsfall Overengineering geworden. `hengegroup_theme_render_product_badge()`
      ersetzt das vorherige `hengegroup_theme_render_product_firma_badges()`/
      `hengegroup_theme_group_firma_badge_labels()`-Paar; `tests/Unit/WoocommerceProductCardTest.php`
      wurde entfernt, weil keine reine Logik mehr uebrig ist, die eine eigene PHPUnit-Suite braucht (ein
      einzelnes Text-/Variant-Postmeta-Paar hat keine Gruppierung zum Testen). `docs/how-to.md`s
      "Weitere blaue Firma ergaenzen" (der `hengegroup_theme_firma_blue_slugs`-Filter) ist damit
      ebenfalls entfallen.
- **`anwendung` ist ein eigener Post-Type, keine Taxonomie** (explizite Entscheidung zwischen beiden
  Optionen 2026-09-22): Eine Anwendung soll spaeter eine eigene, inhaltsreiche Detailseite bekommen
  (Bild, Beschreibung, eigene URL) -- eine Taxonomie-Term-Seite waere dafuer der falsche Ausgangspunkt.
  `public => true` mit eigenem Rewrite-Slug (`anwendungen`) + `single-anwendung.php` sind deshalb
  JETZT SCHON angelegt (zweite explizite Entscheidung, alternativ waere `public => false` bis ein
  Design fuer die Seite steht moeglich gewesen) -- `single-anwendung.php` ist bewusst minimal
  (wie `single.php`/`page.php`), kein eigenes Design ist Teil dieses Auftrags.
- **Produkt-zu-Anwendung ist ein `_anwendungen`-Post-Meta (Array von Post-IDs) + eigene
  Checkbox-Metabox im Produkt-Editor**, keine ACF-Relationship-Feld -- ACF ist keine
  Composer-Abhaengigkeit dieses Projekts (siehe `composer.json`), und `product` nutzt den
  klassischen Editor (kein `show_in_rest`), ein Block-Editor-Relationship-Control waere hier ohnehin
  nicht nutzbar gewesen.
- **Anwendungs-Badges verlinken nicht** (explizite Vorgabe), obwohl der Post-Type selbst oeffentlich
  ist -- `hengegroup_theme_render_product_anwendung_badges()` gibt bewusst kein `href` an `badge.php`
  weiter, rendert also ein `<span>` statt `<a>`. Das Badge im Bild verlinkt ebenfalls nicht (reines
  Text-/Farb-Label, kein Ziel dafuer vorgesehen).
- **Kein Preis in der Produktbox** (explizite Nachfrage, siehe auch den Eintrag oben) -- passend
  zum gezeigten Referenz-Design, das ebenfalls keinen Preis zeigt.
- **Kartentext ist WCs Kurzbeschreibung (`post_excerpt`), nicht die lange Produktbeschreibung**
  (explizite Nachfrage 2026-09-22, aktualisiertes Referenz-Design zeigt einen kurzen Absatz unter
  dem Titel) -- `wp_strip_all_tags()` (WCs Kurzbeschreibungs-Editor erlaubt einfaches HTML, die
  Karte zeigt reinen Text) + `wp_trim_words(..., 24)` (2-3 Zeilen wie im Referenz-Design, ohne dass
  eine laenger gepflegte Kurzbeschreibung die Kartenhoehe im Grid ungleichmaessig aufblaehen kann).
  Nutzt `card.php`s bereits vorhandenen `description`-Slot unveraendert, kein neuer Slot noetig.
- **"Anwendungen"-Eyebrow-Label vor der Badge-Reihe** (aktualisiertes Referenz-Design zeigt eine
  kleine Versal-Ueberschrift ueber den Anwendungs-Badges) -- Teil von
  `hengegroup_theme_render_product_anwendung_badges()` selbst statt separat in
  `content-product.php` komponiert, weil ein zukuenftiger single-product.php-Einsatz (siehe oben)
  voraussichtlich dieselbe Kombination aus Label + Badge-Reihe braucht.

### Buehne: Button-Links als Seiten-Auswahl statt freier URL-Eingabe (2026-09-22)

Auf expliziten Wunsch sind `primaryButtonUrl`/`secondaryButtonUrl` jetzt eine `ComboboxControl`-
Seitenauswahl (`PageLinkControl` in `edit.jsx`) statt eines freien URL-`TextControl`s -- Redakteure
sollen interne Seiten aus einer durchsuchbaren Liste waehlen statt Permalinks manuell abtippen/
kopieren zu muessen.

- **Attribute/`render.php` unveraendert**: gespeichert wird weiterhin nur die fertige Permalink-URL
  als String (kein Seiten-ID-Attribut, kein Schema-Update in `block.json`) -- `PageLinkControl`
  liefert der `ComboboxControl` lediglich `{value: page.link, label: page.title}` als Optionen,
  `onChange` schreibt exakt denselben `page.link`-String in die Folie. Bereits gespeicherte Folien
  mit einer frei getippten (externen oder inzwischen umgezogenen) URL bleiben dadurch technisch
  gueltig -- die Combobox zeigt fuer eine nicht (mehr) zu einer Seite passende URL einfach keinen
  Treffer, der gespeicherte Wert selbst wird dadurch nicht veraendert/geloescht.
- **`@wordpress/data`s `useSelect()` gegen den `core`-Datenstore** (`select('core').
getEntityRecords('postType', 'page', {status: 'publish', per_page: -1, ...})`) statt eines
  eigenen REST-Fetch -- Standard-Weg fuer Editor-Datenabfragen in Gutenberg, inkl. eingebautem
  Caching/Preloading. Nur veroeffentlichte Seiten (`status: 'publish'`), keine Entwuerfe/Papierkorb
  -- ein Button soll nur auf bereits live erreichbare Seiten zeigen koennen.
  `select('core')` per String statt eines `@wordpress/core-data`-Imports, weil kein eigener
  JS-Import aus diesem Paket noetig ist -- die PHP-Seite muss den Store trotzdem explizit per
  `wp-core-data`-Script-Dependency registrieren (`inc/setup/theme-blocks.php`, siehe dortiger
  Kommentar), sonst waere der Store beim ersten Aufruf u. U. noch nicht da.
- **`@wordpress/html-entities`s `decodeEntities()` fuer Seitentitel**: `page.title.rendered` liefert
  HTML-entity-kodierten Text (z. B. `&amp;`) direkt aus der REST-API, `decodeEntities()` macht daraus
  wieder lesbaren Klartext fuer die Options-Liste.
- **Kein eigener "Kein Link"-Zustand im WP-Kern-Sinn**: eine erste `NO_PAGE_OPTION` (Wert `""`) wird
  jeder Optionsliste vorangestellt, damit ein bereits gesetzter Link sich wieder entfernen laesst --
  `render.php` faellt bei leerem `primaryButtonUrl`/`secondaryButtonUrl` ohnehin schon auf `'#'`
  zurueck (siehe dortige `$primary_button_url !== '' ? $primary_button_url : '#'`-Stelle).
- **`@wordpress/data`/`@wordpress/html-entities` neu als Editor-Script-Dependencies** (analog zu
  `@wordpress/rich-text` beim `ueberschrift-text`-Eintrag weiter unten): `vite.config.editor.
factory.js` bekommt beide Package-zu-Global-Eintraege (`wp.data`/`wp.htmlEntities`),
  `inc/setup/theme-blocks.php`s geteilte Dependency-Liste bekommt `wp-data`/`wp-html-entities` (+
  `wp-core-data`, siehe oben) ergaenzt -- `ueberschrift-text` laedt sie dadurch ungenutzt mit, siehe
  Begruendung fuer dasselbe Muster beim `wp-rich-text`-Eintrag.

---

### Buehne: Folien-Felder im Modal statt dauerhaft offen im Sidebar-`PanelBody` (2026-09-22)

Auf expliziten Wunsch oeffnen die Folien-Felder (Bild, Kicker-Bild, Badge, Titel, Text, Akzentfarbe,
Buttons) jetzt in einem `@wordpress/components`-`Modal` pro Folie statt als aufklappbare
`PanelBody`s dauerhaft im Sidebar zu haengen -- bei mehreren Folien wurde die Sidebar sonst schnell
unuebersichtlich lang, weil alle Felder aller Folien gleichzeitig im DOM (nur eingeklappt) standen.

- **Sidebar zeigt nur noch eine kompakte Liste** (`SlideListItem` in `edit.jsx`): Folien-Label
  (Titel/Badge-Text/Fallback "Folie N"), Verschieben rauf/runter, "Bearbeiten" (oeffnet das Modal),
  Entfernen -- alles als Icon-`Button`s in einer `PanelRow`, damit eine Folie auf einen Blick
  identifizierbar/sortierbar/loeschbar ist, ohne dass dafuer ihre Felder aufgeklappt sein muessen.
- **`editingIndex`-State (`useState`) statt eines Modals pro Folie**: genau ein `Modal` wird bedingt
  gerendert, `SlideFields` bekommt nur noch `slide`/`onChange` (kein `index`/`onRemove`/`onMove`
  mehr) -- Verschieben/Entfernen bleiben ausschliesslich Aktionen der Sidebar-Liste, weil WordPress'
  `Modal` waehrend des Offenseins die Interaktion mit dem Rest der Seite (inkl. Sidebar) ohnehin
  blockiert; ein Sync von `editingIndex` bei Verschieben/Entfernen waehrend offenem Modal ist
  dadurch ein Szenario, das nicht eintreten kann, und wurde bewusst nicht gebaut.
- **`ServerSideRender`-Live-Vorschau im Canvas bleibt unveraendert** (siehe `ueberschrift-text`-
  Eintrag oben fuer die Abgrenzung, warum `buehne` dabei bleibt) -- nur die Bearbeitung der
  Folien-Felder wandert vom Sidebar ins Modal, keine strukturelle Aenderung an der Vorschau.
- **Abstand ueber `VStack`s `spacing`-Prop statt CSS/Tailwind** (Nachbesserung auf Screenshot-
  Feedback, Modal wirkte "zusammengepresst", Sidebar-Folienzeilen zu dicht): Modal-Felder
  (`SlideFields`) und Sidebar-Folienliste (`SlideListItem`s) rendern beide AUSSERHALB des
  Editor-Canvas-Iframes (siehe Kopfkommentar oben zu `add_editor_style()`) -- Tailwind-Klassen
  erreichen sie technisch gar nicht, Regel 1 der CLAUDE.md fordert Tailwind nur dort, wo ueberhaupt
  Styling-Code entsteht. Statt dessen `__experimentalVStack` (`@wordpress/components`, im Projekt
  ueblicherweise `VStack` importiert) mit expliziter `spacing`-Prop; jedes Formularfeld in
  `SlideFields` bekommt zusaetzlich `__nextHasNoMarginBottom`, damit sich dessen eigener
  Default-Bottom-Margin nicht zusaetzlich zum `VStack`-Gap aufsummiert.
- **Neues Feld `adminLabel` (erste Stelle im Modal)**: reiner Verwaltungstitel, den Redakteure zur
  Wiedererkennung der Folie in Sidebar-Liste und Modal-Titel eingeben koennen, unabhaengig vom
  tatsaechlichen (evtl. noch leeren) Inhalt -- `slideLabel()` in `edit.jsx` zieht ihn jetzt vor
  `title`/`badgeText`. Bewusst NICHT in `render.php` ausgelesen/gerendert (die Foreach-Schleife dort
  greift explizit einzelne Attribut-Keys ab, unbekannte Keys wie `adminLabel` werden schlicht
  ignoriert) -- rein internes Backend-Feld, kein Frontend-Effekt.

---

### Theme-Kategorie-Bloecke (Autor/Lesedauer) aus dem Inserter ausgeblendet (2026-09-22)

Auf expliziten Wunsch sind vier weitere Core-Bloecke jetzt in `HIDDEN_BLOCK_TYPES`
(`assets/js/editor/editor-customizations.js`) ausgeblendet: Biografie des Autors
(`core/post-author-biography`), Name des Autors (`core/post-author-name`), Avatar (`core/avatar`)
sowie Anzahl Woerter/Lesedauer zusammen (`core/post-to-read`).

- **"Anzahl Woerter" und "Lesedauer" sind ein einziger Blocktyp**: anders als bei den 25
  Embed-Anbietern (echte Block-VARIATIONEN eines gemeinsamen `core/embed`) sind das hier zwei
  Anzeige-Varianten (`word-count`/`time-to-read`) EINES eigenstaendigen Blocktyps namens
  `core/post-to-read` -- `hideBlockTypes()` mit diesem einen Slug deckt beide Anfrage-Punkte ab,
  kein `unregisterBlockVariation()` noetig. Slug gegen Gutenberg-Core verifiziert: der Ordner-/
  Titel-Name legt `core/post-time-to-read` nahe, das tatsaechliche `block.json`-`name`-Feld ist aber
  `core/post-to-read` (ohne "time").

Auf expliziten Wunsch sind 25 Embed-Anbieter jetzt per `unregisterBlockVariation('core/embed', ...)`
(`assets/js/editor/editor-customizations.js`, `HIDDEN_EMBED_VARIATIONS`) aus dem Inserter entfernt:
WordPress, SoundCloud, Flickr, Animoto, Cloudup, CrowdSignal, Dailymotion, Imgur, Issuu, Kickstarter,
Mixcloud, Pocket Casts, Reddit, ReverbNation, Scribd, SmugMug, Speaker Deck, TED, Tumblr, VideoPress,
WordPress.tv, Amazon Kindle, Pinterest, Wolfram, Bluesky. Bewusst NICHT ausgeblendet (nicht genannt):
Twitter/X, YouTube, Facebook, Instagram, Spotify, Vimeo, TikTok, CollegeHumor.

- **Block-VARIATIONEN, kein eigener Blocktyp**: anders als beim vorherigen Eintrag (Core-Bloecke wie
  Zitat/Code) sind diese 25 Anbieter technisch keine eigenen `blocks.registerBlockType()`-Eintraege,
  sondern Varianten des einen `core/embed`-Blocks (`packages/block-library/src/embed/variations.js`
  in Gutenberg-Core, `name`-Feld je Anbieter-Slug, z. B. `pocket-casts`, `speaker-deck`,
  `wolfram-cloud`, `wordpress-tv`) -- deshalb `unregisterBlockVariation()` statt eines weiteren
  Eintrags in `HIDDEN_BLOCK_TYPES`. Die exakten Slugs wurden gegen Gutenberg-Core's
  `variations.js` verifiziert statt geraten (u. a. `wolfram-cloud` nicht `wolfram`, `pocket-casts`
  mit Bindestrich).
- **`unregisterBlockVariation()` statt `unregisterBlockType('core/embed')`**: entfernt nur die
  Anbieter-spezifischen Eintraege aus dem Inserter/der Anbieter-Auswahl, der `core/embed`-Block
  selbst (inkl. der acht bewusst NICHT ausgeblendeten Anbieter) bleibt voll nutzbar -- bereits
  vorhandener Content mit einem der 25 ausgeblendeten Anbieter (`providerNameSlug`-Attribut) bleibt
  dadurch ebenfalls weiterhin normal render-/editierbar, dieselbe Nicht-destruktiv-Ueberlegung wie
  bei `hideBlockTypes()` im vorherigen Eintrag.
- **`wp-block-library` neu als Editor-Script-Dependency** (`inc/setup/theme-blocks.php`): registriert
  saemtliche Core-Bloecke inkl. der `core/embed`-Varianten -- ohne diese explizite Dependency waere
  die Ladereihenfolge nicht garantiert, `unregisterBlockVariation()` liefe dann u. U. ins Leere, weil
  die Variante zum Aufrufzeitpunkt noch gar nicht registriert ist. `wp-blocks` (fuer
  `unregisterBlockVariation()` selbst) ebenfalls ergaenzt.

---

### Standard-Gutenberg-Bloecke aus dem Inserter ausgeblendet (2026-09-22)

Auf expliziten Wunsch sind elf Core-Bloecke jetzt per `hideBlockTypes()` (`assets/js/editor/
editor-customizations.js`, `core/edit-post`-Datenstore) aus dem Inserter ausgeblendet: Zitat
(`core/quote`), Zitatkasten (`core/pullquote`), Code (`core/code`), Lyrik (`core/verse`), Klassisch
(`core/freeform`), Audio (`core/audio`), Wiedergabeliste (`core/playlist`), Individuelles HTML
(`core/html`), Neuste Kommentare (`core/latest-comments`), Seitenliste (`core/page-list`), RSS
(`core/rss`) -- keiner dieser Bloecke ist Teil der Theme-Blockpalette/hat Tailwind-Styling in
`app.css`, sollen Redakteuren deshalb nicht zur Auswahl stehen.

- **`hideBlockTypes()` statt `unregisterBlockType()`**: blendet nur aus dem Inserter aus, bereits
  vorhandener Content mit einem dieser Bloecke (z. B. aus vor der Theme-Migration importiertem
  Content) bleibt dadurch weiterhin normal render-/editierbar -- ein vollstaendiges
  `unregisterBlockType()` haette solchen Content beim naechsten Oeffnen als "ungueltiger Block"
  markiert. Alternative PHP-seitige Sperre ueber das `allowed_block_types_all`-Filter bewusst NICHT
  gewaehlt, aus demselben Grund (dieses Filter schraenkt nicht nur den Inserter ein, sondern auch,
  welche bereits im Content vorhandenen Bloecke der Editor noch als gueltig akzeptiert).
- **`core/playlist` wirkt nur mit Jetpack** (kein WordPress-Core-Block, sondern von Jetpack
  registriert, falls das Plugin aktiv ist) -- in der Liste trotzdem mit aufgefuehrt, weil vom
  Auftraggeber explizit als "Wiedergabeliste" genannt; ohne aktives Jetpack ist der Eintrag
  wirkungslos (kein Fehler, `hideBlockTypes()` prueft nicht, ob der Blocktyp tatsaechlich
  registriert ist).
- **`domReady()`**: `hideBlockTypes()` braucht den `core/edit-post`-Datenstore, der erst nach
  dessen eigenem Bootstrap sicher verfuegbar ist -- Standard-Pattern aus dem
  Block-Editor-Handbook fuer genau diesen Anwendungsfall. `wp-edit-post`/`wp-dom-ready` dafuer neu
  als Editor-Script-Dependencies (`inc/setup/theme-blocks.php`), `@wordpress/dom-ready` neu als
  External/Global in `vite.config.editor.factory.js`.
- **`assets/js/editor/hide-advanced-panel.js` umbenannt zu `editor-customizations.js`** (inkl.
  `vite.config.editor-hide-advanced-panel.js` -> `vite.config.editor-customizations.js`,
  Script-Handle `hengegroup-theme-hide-advanced-panel` -> `hengegroup-theme-editor-customizations`):
  das Script deckt jetzt zwei block-editor-weite Anpassungen ab (siehe vorheriger Eintrag zu
  `customClassName` fuer die erste) -- ein Name, der nur die erste beschreibt, waere fuer die zweite
  irrefuehrend gewesen. Ein Script statt zweier, weil beide denselben Bootstrap (Registrierung/
  Dependencies) brauchen; bei einer dritten, deutlich groesseren Anpassung lohnt sich ggf. eine
  Aufteilung.

---

### `customClassName`-Support global per Filter deaktiviert statt pro `block.json` (2026-09-22)

Auf expliziten Nachfrage-Wunsch ("generell in der Sidebar", nicht nur fuer `buehne`/
`ueberschrift-text`) ersetzt ein neues, block-editor-WEITES Script
(`assets/js/editor/editor-customizations.js`) den anfaenglichen Ansatz, `"supports":
{"customClassName": false}` einzeln in jedes eigene `block.json` einzutragen -- ohne
`customClassName`-Support haengt WordPress sonst automatisch ein "Zusaetzliche CSS-Klasse(n)"-Feld
in ein eigenes "Erweitert"-`PanelBody` am Ende jedes Block-Sidebars an. Redakteure sollen hier keine
freien CSS-Klassen vergeben koennen (widerspraeche ohnehin Regel 1s "ausschliesslich Tailwind ueber
die Config-API der Komponenten", eine frei getippte Klasse haette nie eine zugehoerige
Tailwind-Definition).

- **`blocks.registerBlockType`-Filter (`@wordpress/hooks`) statt Block-fuer-Block-`supports`**:
  wirkt automatisch auf JEDEN Block -- Core-Bloecke (Absatz, Bild, Spalten, ...) eingeschlossen,
  nicht nur die beiden eigenen -- und auf jeden kuenftigen eigenen Block, ohne dass das jedes Mal
  einzeln im `block.json` nachgezogen werden muss. Die beiden vorher gesetzten
  `"customClassName": false`-Eintraege in `buehne`/`ueberschrift-text`s `block.json` sind wieder
  entfernt (redundant, der globale Filter deckt sie mit ab).
- **`anchor`-Support bleibt bewusst unangetastet**: HTML-Anker/Sprungmarken sind ein
  eigenstaendiges, potenziell genutztes Feature (z. B. Inhaltsverzeichnis-Links) -- kein Ziel dieser
  Anfrage. Core-Bloecke mit aktiviertem `anchor`-Support (z. B. `core/heading`) zeigen "Erweitert"
  deshalb weiterhin, nur ohne das CSS-Klassen-Feld.
- **Eigenes Vite-Build-Entry statt eines block.json-`editorScript`**: `vite.config.editor-
customizations.js` nutzt dieselbe `createEditorBlockConfig()`-Factory wie jeder Block, weil
  der Build (externes IIFE gegen `wp.*`-Globals) identisch ist -- neu dabei: `@wordpress/hooks`
  (`wp.hooks`) als External/Global, bisher von keinem Block gebraucht. PHP-seitig registriert
  `hengegroup_theme_enqueue_editor_assets()` (`inc/setup/theme-blocks.php`) das Script ueber
  `enqueue_block_editor_assets` statt `register_block_type()`, weil es nicht an einen einzelnen
  Block gebunden ist.

---

### `ueberschrift-text`: Ueberschrift/Text direkt im Content-Bereich statt Sidebar-Textfeldern (2026-09-22)

Auf expliziten Wunsch bearbeiten Redakteure `heading`/`text` jetzt direkt im Editor-Canvas (per
`RichText`, wie bei `core/heading`/`core/paragraph`) statt in sidebar-`TextControl`/
`TextareaControl`-Feldern -- Eingabe passiert dort, wo der Inhalt optisch erscheint, statt blind in
einem vom Ergebnis getrennten Sidebar-Feld. `accentWords`/`textAlign`/`containerWidth` bleiben
unveraendert Sidebar-`PanelBody`-Felder (Konfiguration, kein Inhalt).

- **`ServerSideRender` entfaellt fuer diesen Block**: `edit.jsx` baut jetzt selbst dieselbe
  Section-/`.wrapper`(`-small`)/`col-span-12`/Align-Struktur wie `render.php` und stylt die beiden
  `RichText`-Felder mit denselben `typography.php`-Variant-Klassen (`headline-base`/`body-lg`) --
  der Canvas IST jetzt die Live-Vorschau, keine zweite SSR-Anfrage pro Tastenanschlag noetig. Anders
  als bei `buehne` (bleibt bei `ServerSideRender`, siehe dessen eigener Eintrag weiter unten): dort
  gibt es Carousel-/Autoplay-Verhalten, das sich nicht sinnvoll 1:1 im Editor nachbauen laesst,
  hier nur zwei reine Textfelder plus Layout-Klassen.
- **Keine Live-Akzent-Hervorhebung waehrend des Tippens** (bewusst, auf Nachfrage entschieden): die
  bestehende `Notice`-Warnung bei nicht-treffenden Akzent-Woertern bleibt die einzige Rueckmeldung
  im Editor, die eigentliche `font-accent`-Hervorhebung sieht man weiterhin erst im echten
  Frontend/in der WordPress-Vorschau. Eine live mitlaufende Hervorhebung haette eine kontrolliert
  neu formatierte `RichText`-`value` bei jedem Tastendruck gebraucht -- bekanntes Cursor-Sprung-/
  Ruckel-Risiko bei kontrollierten RichText-Werten in Gutenberg, deutlich mehr Code fuer einen rein
  kosmetischen Editor-Komfort.
- **`heading`/`text` bleiben PLAIN-STRING-Attribute** (`block.json` unveraendert) -- `RichText`
  arbeitet intern mit HTML-Strings, `toRichTextValue()`/`fromRichTextValue()` (neue Helper in
  `edit.jsx`) roundtripen ueber `@wordpress/rich-text`s `create()`/`toHTMLString()` nur fuer
  korrektes Entity-Escaping (z. B. ein literales "&"/"<" im Text); `allowedFormats={[]}` +
  `disableLineBreaks` auf beiden `RichText`-Feldern verhindert, dass echte Formatierung (fett,
  Links, `<br>`) in die gespeicherten Strings gelangt -- `render.php`/`typography.php` escapen den
  Text weiterhin selbst (`esc_html()`), eingebettetes HTML wuerde dort sonst literal (doppelt
  escaped) angezeigt statt interpretiert.
- **`@wordpress/rich-text` neu als Editor-Script-Dependency**: `vite.config.editor.factory.js`
  bekommt den Package-zu-Global-Eintrag (`wp.richText`), `inc/setup/theme-blocks.php`s geteilte
  `wp_register_script()`-Dependency-Liste bekommt `wp-rich-text` ergaenzt -- dieselbe Liste gilt
  fuer beide Bloecke (siehe `hengegroup_theme_register_theme_block()`s Kopfkommentar zur
  Konsolidierung), `buehne` laedt das Skript dadurch ungenutzt mit statt eine zweite, block-eigene
  Dependency-Liste einzufuehren.

### Buehne: Kicker-Logo-Filter fuer inaktive Dots (Bugfix) (2026-09-22)

Bug: die Kicker-Bild-Dots (Marken-Logos in der Dot-Navigation, siehe "Buehne: Dot-Navigation als
Text-Tabs statt Pillen") zeigten inaktive Logos in ihrer vollen Originalfarbe -- die Design-Referenz
zeigt sie stattdessen entsaettigt/aufgehellt (weisslich-grau), erst der aktive Dot zeigt sein Logo
in Originalfarbe. `render.php` liess das Logo-`<img>` bislang unveraendert, nur die Text-Dots
(`$accent_dot_classes`) hatten bereits einen aktiv/inaktiv-Kontrast.

- **`grayscale brightness-[1.6] opacity-80`** als Default-Filter auf dem Kicker-`<img>` (Bugfix,
  `template-parts/blocks/buehne/render.php`), aufgehoben fuer den aktiven Dot per
  `group-data-[active=true]:grayscale-0 group-data-[active=true]:brightness-100
group-data-[active=true]:opacity-100`. `brightness-[1.6]` als Arbitrary Value statt eines
  Tailwind-Stops, weil Tailwinds Brightness-Skala keinen 160%-Wert kennt (naechste Stufen sind 150%/
  200%) und die Referenz exakt 1.6 vorgibt.
- **`group`-Klasse auf dem Dot-`<button>` ergaenzt**: `group-data-[active=true]:*` auf dem
  verschachtelten `<img>` braucht diesen Marker auf dem Vorfahren, der `data-active` traegt (per
  `assets/js/template-parts/blocks/buehne.js`s `setActiveDot()` gesetzt) -- ohne `group` haette
  Tailwind den Selector nicht auf den passenden Vorfahren binden koennen.
- **Keine Aenderung an `buehne.js`**: das Script setzte `data-active` bereits korrekt, das war rein
  ein fehlender Tailwind-Klassen-Zustand auf dem Bild selbst.

### `ueberschrift-text` bekommt `"align": "full"`-Support (Bugfix) (2026-09-22)

Bug: das neue `containerWidth`-Attribut ("Schmal"/"Breit", siehe vorheriger Eintrag zum Block) hatte
im Block-Editor-Canvas keinen sichtbaren Effekt -- `.wrapper` (1600px) und `.wrapper-small` (1000px)
sahen dort optisch identisch aus, obwohl das Frontend korrekt die gewaehlte Breite zeigte. Ursache:
`theme.json`s `settings.layout.contentSize`/`wideSize` (48rem/72rem) begrenzt jeden Block OHNE
eigene `align`-Unterstuetzung im Editor-Iframe automatisch auf die schmale 48rem-Spalte -- fuer
dieses klassische (nicht Full-Site-Editing-)Theme gilt das nur im Editor, `page.php`s
`the_content()`-Ausgabe im Frontend kennt diese Breiten-Beschraenkung gar nicht. Ohne eigene
Align-Unterstuetzung quetschte der Editor also BEIDE `containerWidth`-Optionen gleichermassen auf
768px zusammen.

- **`block.json` bekommt `"align": {"type": "string", "default": "full"}` +
  `"supports": {"align": ["full"]}`**, analog zu `buehne/block.json`s bereits bestehender
  `align: full`-Vorgabe -- macht den Block im Editor-Canvas standardmaessig "full width" (bricht aus
  der `contentSize`-Spalte aus), wodurch die eigene `.wrapper`/`.wrapper-small`-Breite dort exakt so
  sichtbar wird wie im Frontend. Nur `"full"` (nicht zusaetzlich `"wide"`) im Supports-Array, weil
  `wideSize` (72rem/1152px) immer noch schmaler als `.wrapper`s 1600px waere und die "Breit"-Option
  dann weiterhin verfaelscht angezeigt haette.
- **Keine Aenderung an `render.php`**: das Frontend brauchte diesen Fix nie, `align`-Support wirkt
  nur auf den Editor-Iframe.

### `add_editor_style()` bekommt einen theme-relativen Pfad statt einer absoluten URI (Bugfix) (2026-09-22)

Bug: die Akzent-Schrift (Crillee, `@font-face src: url(../fonts/...)` in `tokens.css`) fehlte im
Block-Editor-Canvas (z. B. in `ueberschrift-text`s ServerSideRender-Vorschau), obwohl dieselbe
Schrift im Frontend korrekt lud. Ursache: `hengegroup_theme_get_vite_style_uri()` gab eine ABSOLUTE
URI (`https://.../assets/css/app-xxxx.css`) an `add_editor_style()` weiter. WordPress' eigene
`get_block_editor_theme_styles()` behandelt beide Faelle unterschiedlich -- ein theme-relativer Pfad
wird direkt von der Platte gelesen (`get_theme_file_path()`) und bekommt dabei eine korrekte
`baseURL`, gegen die der Editor-Iframe relative `url(...)`-Referenzen im Stylesheet umschreibt; eine
volle URI wird stattdessen EINMALIG per `wp_remote_get()` abgerufen und OHNE diese `baseURL` inline
eingebettet -- jedes relative `url(...)` darin (hier: die `@font-face`-Pfade) loest dann ins Leere
auf. Das Frontend war nie betroffen, weil dort dieselbe Datei ganz normal per `<link>` geladen wird,
wo relative URLs sich schon immer korrekt gegen die verlinkte Datei selbst aufloesen.

- **`hengegroup_theme_get_vite_style_uri()` zu `hengegroup_theme_get_vite_style_relative_path()`
  umbenannt und umgestellt**: gibt jetzt `"assets/" . $file` (theme-relativ) statt
  `hengegroup_theme_get_vite_asset_uri($file)` (absolute URI) zurueck -- einzige Verwendungsstelle
  bleibt `hengegroup_theme_theme_setup()`s `add_editor_style()`-Aufruf (`inc/setup/theme-setup.php`).
- **Keine Aenderung an `hengegroup_theme_get_vite_asset_uri()`/`_enqueue_vite_style_entry()`**:
  betrifft nur den `add_editor_style()`-Sonderfall, das normale `wp_enqueue_style()`
  fuer das Frontend (echtes `<link>`-Tag) braucht weiterhin die absolute URI wie bisher.

### Eigene Block-Kategorie "Henge" statt Core-Kategorie "theme" (2026-09-22)

Beide bisherigen Bloecke (`buehne`, `ueberschrift-text`) nutzten `block.json`s Core-Kategorie
`"theme"`. Auf expliziten Wunsch jetzt stattdessen eine eigene Kategorie `henge` (`inc/setup/
theme-blocks.php`, `hengegroup_theme_register_block_categories()` ueber den `block_categories_all`-
Filter) -- eigene, klar erkennbare Gruppe im Block-Inserter statt zwischen generischen Theme-
Bloecken. Per `array_unshift()` an den Anfang des Kategorien-Arrays gesetzt (WordPress rendert die
Inserter-Akkordeons in Array-Reihenfolge), damit sie als erste Gruppe erscheint statt hinten
angehaengt zu werden. Jeder neue Block bekommt `"category": "henge"` in seinem `block.json` statt
`"theme"`.

### Zweiter Gutenberg-Block `ueberschrift-text`: generisch statt "Intro"-Spezialfall, Vite-Build-Factory (2026-09-22)

Der Bereich direkt unter der Buehne im urspruenglichen Claude-Design-Mockup (zentrierte Ueberschrift

- Textabsatz, "Willkommen bei der HENGEGROUP") sollte als zweiter Block nach `buehne` umgesetzt
  werden. Entscheidung (mit dem Auftraggeber abgestimmt): kein `intro`-spezifischer Block, sondern ein
  generischer `hengegroup-theme/ueberschrift-text` (Attribute `heading`/`accentWords`/`text`/
  `textAlign`) -- wiederverwendbar fuer jede aehnliche Textsektion, nicht nur die eine Stelle unter der
  Buehne.

* **Kein Core-`Heading`+`Paragraph`(+`Group`) statt eines eigenen Blocks**: die native Loesung deckt
  die reine Struktur ab, aber weder die Akzent-Schrift auf einzelnen Woertern innerhalb der
  Ueberschrift (dafuer braeuchte es ohnehin einen eigenen RichText-Format-Type) noch die Bindung an
  das feste `headline-*`/`body-*`-Vokabular aus `typography.php` statt freier Font-Size/Farbwahl pro
  Editor-Instanz -- selbes Konsistenz-Argument wie Buehnes feste `henge-green/-blue/-grey`-Akzente
  statt eines freien Farbwaehlers.
* **`typography.php`s `accent_words`-Config wird hier zum ersten Mal tatsaechlich genutzt**
  (bislang nur in dessen eigenem Kopfkommentar/Showcase-Seite dokumentiert) -- derselbe
  `font-accent`-Span-Mechanismus, den `badge.php` per `font: 'accent'` bereits anspricht.
* **`.wrapper-small` statt eigenem Arbitrary-max-width** fuer die Textspalte (siehe
  "12-Spalten-Grid ueber .wrapper" weiter unten) -- 1000px statt der 900px aus dem Mockup, weil das
  bereits der bestehende Token fuer schmale Block-Inhalte ist und ein zweiter, nur 100px
  abweichender Wert keine eigene Deckelung rechtfertigt.
* **Vertikaler Abstand `py-16 md:py-24 lg:py-35`** (140px bei `lg:`, angelehnt an Tailwind v4s
  dynamischer Spacing-Skala -- `py-35` ist trotz fehlendem `35`-Stop in Tailwind v3 in v4 gueltig,
  da die Skala dort als `--spacing * n` berechnet statt als feste Werteliste gepflegt wird) statt
  fix 140px auf allen Breakpoints; keine bestehende Sektion im Theme hatte bislang eine eigene
  vertikale Padding-Konvention fuer einen reinen Textblock, dies ist der erste Praezedenzfall.
* **`vite.config.editor.js` zu `vite.config.editor.factory.js` (`createEditorBlockConfig()`)
  umgebaut**: mit dem zweiten Block waere die komplette `build.lib`/`esbuild.jsx*`/
  `rollupOptions.external`+`output.globals`-Konfiguration sonst wortgleich in einer zweiten
  Config-Datei dupliziert gewesen. Jede Block-Config (`vite.config.editor.js` fuer `buehne`,
  `vite.config.editor-ueberschrift-text.js` fuer den neuen Block) bleibt weiterhin eine EIGENE Datei
  mit eigenem `vite build --config ...`-Aufruf in `build:assets` -- kein gemeinsamer Multi-Entry-Build,
  weil Rollups/Vites `iife`/`umd`-Ausgabeformat in Lib-Mode keine mehreren Entry-Points in einem
  einzigen Build unterstuetzt (dieselbe Einschraenkung, die bereits gegen ein gemeinsames
  Array-Config fuer `vite.config.js` selbst sprach, siehe "Phase-3-Block-Architektur" weiter unten).
* **`hengegroup_theme_register_buehne_block()` zu `hengegroup_theme_register_theme_block(string
$block_dir, string $editor_script_handle, string $editor_script_relative_path)` verallgemeinert**
  (`inc/setup/theme-blocks.php`) -- aus demselben Grund wie der Vite-Umbau: identische Registrierungs-
  logik fuer beide Bloecke, jetzt an einer Stelle statt dupliziert.
* **UX-Falle entdeckt und mit Inline-`Notice` entschaerft (Bugfix, direkt nach der ersten
  Nutzung)**: `accent_words` hebt nur Woerter hervor, die WOERTLICH bereits im `heading`-Text
  vorkommen -- es fuegt nichts ein. Ein Redakteur, der (in Anlehnung an das urspruengliche, fest
  codierte Mockup-Markup) "HENGEGROUP" nur ins separate Akzent-Woerter-Feld eintraegt, aber nicht
  mehr in die Ueberschrift selbst schreibt, bekommt keinen Fehler, sondern das Wort verschwindet
  komplett aus der Ueberschrift (kein Treffer -> keine Hervorhebung -> aber eben auch keine
  Ergaenzung). Reine `help`-Text-Prosa in `edit.jsx` reichte nicht, um das zu verhindern -- jetzt
  zusaetzlich eine `Notice`-Warnung im Inspector (`findUnmatchedAccentWords()`), die jedes
  Akzent-Wort ohne Treffer im aktuellen Ueberschrift-Text sofort beim Editieren benennt.

### `--container-page`-Token gegen auseinanderlaufende `.wrapper`-Breite (Bugfix) (2026-09-22)

Bug: die Dot-Reihe in `template-parts/blocks/buehne/render.php` (siehe "Buehne: Dot-Navigation als
Text-Tabs statt Pillen") wurde schmaler dargestellt als die Content-Box daneben. Ursache: die
Dot-Reihe kann `.wrapper` nicht direkt nutzen (braucht `flex flex-wrap justify-center` statt
`.wrapper`s `grid`, siehe render.php-Kommentar), trug ihre Deckelung deshalb als eigenen
Arbitrary-Value `max-w-[1400px]` -- der urspruengliche `.wrapper`-Wert zum Zeitpunkt, als dieser
Code entstand. `.wrapper`s eigener Wert wurde seitdem manuell auf 1600px geaendert, der duplizierte
Wert in render.php aber nicht mitgezogen; die beiden liefen auseinander.

- **Fix**: `--container-page: 1600px` neues Token in `tokens.css`s `@theme static`-Block --
  Tailwinds `--container-*`-Namespace generiert daraus automatisch die Utility `max-w-page`.
  `.wrapper` (`app.css`) und die Dot-Reihe (`render.php`) nutzen jetzt beide `max-w-page` statt
  je einen eigenen `max-w-[1600px]`/`max-w-[1400px]`-Arbitrary-Value -- eine Quelle statt zweier
  unabhaengig gepflegter Zahlen, die sonst erneut auseinanderlaufen koennen.
- **`.wrapper-small` bekommt bewusst KEIN eigenes Token**: ihr Wert (1000px) wird bislang nur an
  der einen Stelle gebraucht, kein zweiter Verbraucher, kein Drift-Risiko -- Token erst anlegen,
  wenn ein zweiter Ort denselben Wert braucht (gleiche tokens.css-Konvention wie bei den
  Marken-Grautoenen, siehe deren Kopfkommentar).

---

### Buehne: Dot-Navigation als Text-Tabs statt Pillen (2026-09-22)

Auf explizite Design-Vorgabe (per Claude-Design-Canvas-Referenz geteilt) wurde die Dot-Navigation
von runden Pill-Buttons (Rahmen, Fuellung, `rounded-full`) auf schmale Text-Tabs mit Unterstrich
umgestellt -- **nur die Dots selbst**, Position/Layout des Wrappers (`data-buehne-dots`, zentriert,
nahe am unteren Rand), Autoplay-/Klick-Logik (`assets/js/template-parts/blocks/buehne.js`) und
alles andere am Block blieben unangetastet (expliziter Arbeitsauftrag).

- **Design**: fett/kursiv/Grossbuchstaben-Label mit `border-b-2`-Unterstrich statt Pille. In der
  Referenz traegt NUR der aktive Tab Farbe (Marken-Akzent von Text + Unterstrich), alle inaktiven
  bleiben einheitlich neutral-grau, unabhaengig von ihrer eigenen Marke -- 1:1 uebernommen:
  `$accent_dot_classes` (neue Map neben `$accent_border_classes`, gleiches Akzent-Vokabular
  henge-green/henge-blue/henge-grey wie die Content-Box-Border) faerbt per
  `data-[active=true]:text-*`/`data-[active=true]:border-*` NUR den aktiven Zustand; die Basis-
  Klassen (`text-grey-light/50`, `border-grey-light/25`) decken den inaktiven Zustand ab, keine
  weitere Fallunterscheidung noetig.
- **Referenz zeigt zusaetzlich eine zweite, kleinere Subtitle-Zeile pro Tab** (z. B. Markenname +
  Kategorie) -- NICHT uebernommen: `buehne`s Slide-Konfiguration (`block.json`/`edit.jsx`, "Rest
  lassen") hat kein zweites Textfeld fuer den Dot, nur `badgeText`/`kickerImage*`, aus denen
  `$dot_label` bereits abgeleitet wird. Eine echte zweite Zeile haette ein neues Attribut/Edit-UI-
  Feld gebraucht -- ausserhalb des erteilten Auftrags ("nur Klicker"); bei Bedarf separat
  nachreichen.
- **Tap-Target bewusst per unsichtbarem `pt-3` erhalten**: die Pille gab vorher ueber `h-11`/`px-4.5`
  eine grosszuegige Klickflaeche; ein reiner Text+Unterstrich-Tab waere ohne Gegenmassnahme deutlich
  kleiner als ein angemessenes Touch-Target. `pt-3` (nur oben, `pb-1.5` bleibt eng am Unterstrich)
  vergroessert die Klickflaeche unsichtbar, ohne den Abstand zwischen Text und Unterstrich optisch
  zu veraendern.
- **Kicker-Bild-Faelle** (`kickerImageId` gesetzt) behalten ihr `<img>` als Inhalt, bekommen aber
  denselben Rahmen-/Farb-Wechsel wie die Text-Variante (Unterstrich faerbt sich, wenn aktiv).

---

### Buehne: `justify-self-start` von der Content-Box entfernt (Bugfix, Mobile-Overflow) (2026-09-22)

Bug: auf schmalen Viewports konnte die Slide-Content-Box ueber den Bildschirmrand hinausragen.
Ursache war `justify-self-start`, das beim `.wrapper`-Umbau der Box (siehe "12-Spalten-Grid ueber
`.wrapper`" oben) mit angehaengt wurde -- CSS Grids Default `justify-self: stretch` verhaelt sich
bei einem Item mit `width: auto` + `max-width` (hier `max-w-xl`) exakt wie ein normaler Block:
Breite = `min(Grid-Zellen-Breite, max-w-xl)`, nie mehr. `justify-self: start` schaltet dagegen auf
Shrink-to-fit-Sizing um (wie `width: fit-content`) -- die Box wird dann so breit wie ihr Inhalt es
verlangt (bis zur `max-w-xl`-Deckelung), UND ein nicht umbrechbares Kind (`button.php`s
`whitespace-nowrap` + `shrink-0` auf einem laengeren CTA-Label) kann diese "gewuenschte" Breite
ueber die verfuegbare Viewport-Breite hinaus treiben, weil Shrink-to-fit-Boxen (anders als normale
Block-Boxen) ihre Groesse aktiv am Content-Minimum ausrichten statt einfach zu clippen. Die
Linksausrichtung, wegen der `justify-self-start` urspruenglich ergaenzt wurde, liefert `stretch`
bereits von selbst (siehe oben), das Attribut war fuer den sichtbaren Effekt nie noetig -- reines
Entfernen behebt den Bug ohne sichtbare Layout-Aenderung auf Desktop.

- **Zusaetzlich**: die Box-eigenen Innenabstaende (`p-6 md:px-6`, ein redundanter Rest aus einem
  frueheren Zwischenstand) sind jetzt eine echte progressive Skala (`p-4 sm:p-5 md:p-6`, 16/20/24px)
  statt eines auf allen Breakpoints fast identischen Werts -- mehr Content-Breite auf kleinen
  Screens, ohne die Deckkraft auf Desktop zu aendern.

---

### `button.php`: `wp-element-button` gegen die globale Link-Farbe (2026-09-22, aktualisiert)

Bug: im Editor-Preview (Bühne-Block, `@wordpress/server-side-render`) zeigte ein `href`-Button mit
`variant: 'henge-blue'` gruene statt der erwarteten hellen Schrift -- `theme.json`s
`styles.elements.link.color.text` (henge-green, siehe `tokens.css`-Kopfkommentar/"Design-Token-
System"-Eintrag unten) wird von WordPress' globalem Styles-Layer als
`a:where(:not(.wp-element-button)) { color: ...; }` ausgegeben. Diese `:where()`-Selektor-Form
traegt fuer den eigentlichen Treffer (`a`, ausserhalb von `:where()`) genau eine Element-Selektor-
Spezifitaet, aber der volle Regelblock wird ueblich unter einem `:root`-Praefix erzeugt --
`:root`s eigene Pseudoklassen-Spezifitaet zieht mit einer einzelnen Tailwind-Klasse
(`.text-henge-blue-foreground`, ebenfalls eine Stufe) gleich. Bei gleicher Spezifitaet gewinnt die
Regel, die zuletzt im Cascade-Order steht -- im Editor-iFrame liegt WordPress' eigenes
`global-styles-inline-css` nach `add_editor_style()`s `app.css`, auf dem Frontend offenbar in
umgekehrter Reihenfolge (dort bisher nicht als Bug aufgefallen).

- **Fix**: `button.php` haengt jetzt bei JEDER gerenderten Variante (nicht nur `href`, auch das
  native `<button>`) die Klasse `wp-element-button` an -- WordPress' eigener Opt-out aus der
  globalen Link-Farbe, denselben Weg, den jeder Core-Block mit einem echten Button-Element geht
  (Button-, Search-, Pagination-Block, ...). `<button>`-Elemente traf der Bug nie (der Selektor
  matched nur `a`), bekommen die Klasse trotzdem fuer Konsistenz -- semantisch ist `button.php`
  IMMER ein Button, nie ein Inline-Content-Link, unabhaengig vom gerenderten Tag.
- **Nicht als eigenes `elements.button` in `theme.json` geloest**: haette globale Standardfarben
  fuer JEDEN `.wp-element-button` gesetzt (auch WP-Core-Bloecke), waere also eine viel groessere
  Aenderung fuer einen Bug gewesen, der nur dieses eine Element betrifft.
- **Andere `href`-rendernde Base-Komponenten** (`attachment.php`, `navigation-menu-link.php`,
  `dropdown-menu-item.php`, `card.php`s optionales `href`, `pagination*.php`, `badge.php`,
  `carousel-previous.php`/`-next.php`, `toast.php`) sind potenziell vom selben
  Spezifitaets-Patt betroffen, wurden hier aber NICHT mitgeaendert -- die meisten davon sind
  semantisch tatsaechlich Links/Navigation, kein pauschales `wp-element-button` angebracht; nur
  bei konkretem Symptom (wie hier) gezielt nachziehen.

**Nachtrag/Regression (selbes Datum):** der `wp-element-button`-Fix oben loeste die Textfarbe, riss
aber im selben Editor-Preview eine ZWEITE, neue Regression auf: die Hintergrundfarbe zeigte danach
immer hellweiss statt der Variantenfarbe (`variant: 'henge-blue'` blieb weiss statt blau), waehrend
die Textfarbe jetzt korrekt war. Ursache: `.wp-element-button` ist WordPress' eigene generische
"erbt Button-Farben aus `theme.json`s `styles.elements.button`, sonst aus den Top-Level-
Farbeinstellungen"-Klasse -- da dieses Theme kein eigenes `elements.button` definiert, faellt der
generische Default auf die Top-Level-Hintergrundfarbe (`--color-background`, weiss) zurueck. Durch
das Anhaengen von `wp-element-button` ist `button.php` jetzt also NEU dieser zweiten globalen
Regel ausgesetzt -- exakt dasselbe Spezifitaets-Patt/Cascade-Order-Problem wie beim Link-Farbe-Bug
oben (0,1,0 gegen 0,1,0, im Editor-iFrame gewinnt WordPress' `global-styles-inline-css`, weil es
nach `add_editor_style()`s `app.css` laedt), diesmal aber auf `background-color`/`color` statt nur
`color`, und ein Ausschluss-Class-Trick wie oben existiert dafuer nicht (der Button SOLL ja als
`.wp-element-button` erkannt werden, das war der eigentliche Fix).

- **Fix**: alle bg-\*/text-\*-Utilities in `button.php`s `$variant_classes` sind jetzt `!`-markiert
  (Tailwinds eigener Important-Modifier, z. B. `!bg-henge-blue`) -- selber Mechanismus/dieselbe
  Begruendung wie `calendar.php`s eigener `!`-Praefix-Fix (siehe "toggle.php/toggle-group.php
  gestylt..."-Eintrag weiter unten): PHP hat kein `tailwind-merge`/`cn()`, das eine Kollision mit
  einer extern injizierten, gleich spezifischen Regel automatisch aufloest, `!important` erzwingt
  den Sieg unabhaengig von der Cascade-Reihenfolge. Betrifft ausschliesslich die tatsaechlich
  deklarierten Farb-Utilities (inkl. ihrer `hover:`-Pendants) -- border-/shadow-/underline-/
  focus-visible-Utilities auf denselben Zeilen blieben unangetastet, die kollidierten nie.
- **Kein eigenes `styles.elements.button` in `theme.json`**: aus demselben Grund wie beim
  Link-Farbe-Bug oben nicht gewaehlt -- waere eine globale Standardfarbe fuer JEDEN
  `.wp-element-button` (auch WP-Core-Bloecke), nicht nur fuer dieses eine Element.

### Buehne: Opacity-Crossfade statt Scroll-Snap (2026-09-22)

Kehrtwende gegenueber der urspruenglichen Buehne-Entscheidung (siehe "Phase-3-Block-Architektur"
weiter unten, Bullet "Verhalten bewusst an `carousel.php`s echtem CSS-Scroll-Snap-Verhalten
ausgerichtet") -- auf explizite Nachfrage soll der Folienwechsel jetzt als Opacity-Crossfade laufen,
kein Scroll-Effekt mehr. `template-parts/base/carousel/carousel.php` selbst bleibt unveraendert
(bleibt ein generischer, wiederverwendbarer Scroll-Snap-Baustein fuer andere/kuenftige Bloecke) --
`template-parts/blocks/buehne/render.php` komponiert ihn nur noch anders:

- **`carousel.php` (Root, `role="region"`) und `carousel-item.php` (je Folie `role="group"`)
  bleiben komponiert** -- beide sind transitions-agnostisch (reines Markup/ARIA, keine
  Scroll-spezifische Logik im PHP selbst), passen also unveraendert auch zu einem Crossfade.
  **`carousel-content.php` wird jetzt bewusst NICHT mehr genutzt**: dessen `tabindex="0"` existiert
  explizit fuer natives Tastatur-Scrolling (siehe dessen eigener Kopfkommentar) -- ohne
  Scroll-Container waere das nur noch ein totes, fokussierbares Element ohne Funktion.
- **Folien liegen absolut uebereinander gestapelt** (`absolute inset-0` statt vormals
  `carousel-item.php`s `basis: '100%'` + `snap-start` im `flex`-Track von `carousel-content.php`)
  direkt im `relative` Root von `carousel.php`. `data-state="active"/"inactive"` (per
  `data_attributes`-Config) steuert per `data-[state=active]:opacity-100` +
  `transition-opacity duration-700` die Blende.
- **Inaktive Folien bekommen zusaetzlich `aria-hidden="true"` + `inert`** (per `attributes`-Config)
  -- ohne Scroll-Container, der sie ausserhalb des sichtbaren Bereichs haelt, waeren ihre Buttons/
  Links sonst weiterhin per Tastatur/Screenreader erreichbar, obwohl sie unsichtbar hinter der
  aktiven Folie liegen.
- **`assets/js/template-parts/blocks/buehne.js` haelt den Aktiv-Index jetzt selbst** (`currentIndex`,
  von Dot-Klick/Autoplay direkt gesetzt) statt ihn wie zuvor per IntersectionObserver aus der
  Scroll-Position abzuleiten -- ohne Scrollen gibt es keine Position mehr, aus der sich das ableiten
  liesse. `goToIndex()` setzt `data-state`/`aria-hidden`/`inert` direkt; `assets/js/template-parts/
base/carousel.js` selbst bleibt unangetastet, wird jetzt schlicht nicht mehr eingebunden (kein
  `[data-slot="carousel-content"]` mehr im Markup, das seine Scroll-Snap-Verdrahtung braeuchte).
- **Kein `prefers-reduced-motion`-Sonderfall fuer die Opacity-Transition selbst** (anders als
  Autoplay, das weiterhin bei `prefers-reduced-motion: reduce` komplett pausiert) -- ein reiner
  Opacity-Fade gilt nicht als die Art vestibulaer-ausloesender Bewegung, die diese Media Query
  adressiert (anders als z. B. ein Slide/Parallax-Effekt); bei Bedarf spaeter separat entscheiden.

### 12-Spalten-Grid ueber `.wrapper` in `app.css`, kein eigener Template-Part (2026-09-18)

Fuer wiederverwendbare Block-Layouts (12 Spalten, max. 1400px, Seiten-Padding) stand zur Wahl, das
als PHP-Komponente (`template-parts/base` oder `template-parts/components`) mit eigener Config-API
zu bauen, oder als reine CSS-Klasse. Entscheidung: reine Tailwind-Klasse `.wrapper`
(`assets/css/app.css`, `@layer components`) statt eines Template-Parts -- ein Grid-Container hat
keine sinnvolle Config-API jenseits "welche Kinder bekommen welchen `col-span-*`", das ist bereits
Tailwinds eigenes Vokabular und braucht keine PHP-Abstraktion darueber. `.wrapper` war schon seit
dem initialen Vorlagen-Commit als leerer Platzhalter genau fuer diesen Zweck vorgesehen (ungenutzt
bis jetzt). `.wrapper-small` ist dieselbe Klasse mit `max-w-[900px]` statt `max-w-[1400px]` fuer
schmalere Block-Inhalte (z. B. Textabschnitte, Formulare), sonst identisches Grid-/Padding-Schema.

- **1400px als Arbitrary Value (`max-w-[1400px]`), kein eigenes `--container-*`-Token in
  `tokens.css`**: der Wert wird nur an dieser einen Stelle gebraucht; ein Token lohnt sich laut
  `tokens.css`s eigener Konvention erst, wenn eine Komponenten-API den Wert selbst braucht (siehe
  dortiger Kopfkommentar zu den Marken-Grautoenen).
- **Grid direkt auf dem Container** (`grid grid-cols-12`), kein zusaetzliches inneres
  Grid-Element -- Bloecke platzieren Kinder direkt mit Tailwinds `col-span-*`/`col-start-*` auf
  `.wrapper` als Grid-Eltern, ein Kind ohne `col-span-*` belegt wie bei jedem 12-Spalten-System nur
  eine Spalte.
- **Padding/Gap-Skala** (`px-4`/`gap-x-4` mobil, `sm:px-6`/`sm:gap-x-6`, `lg:px-8`/`lg:gap-x-8`) ist
  ein Standard-Tailwind-Rhythmus (16/24/32px), keine Design-Vorgabe aus einer Referenz -- bei Bedarf
  gezielt anpassen, betrifft dann alle Bloecke, die `.wrapper` nutzen.
- **Bewusst nicht auf `header.php`/`footer.php` angewendet**: der Header nutzt bereits einen
  eigenen, breiteren Container (`max-w-[2000px]`, siehe `header.php`) fuer die Sticky-Nav-Leiste --
  eigene, unabhaengige Design-Entscheidung, nicht Teil dieses Block-Grids.

### Header: Navigationsinhalt aus `wp_nav_menu` statt hartkodiert (2026-09-16)

Beim Umsetzen des Headers (`header.php`, Claude-Design-Referenz "Hengegroup") stand zur Wahl, die
Mega-Menue-Dropdowns (Produkte/Karriere/Unternehmen mit Untereintraegen) wie in der Referenz fest
im Template zu hartcodieren, oder ueber die bereits registrierte `primary`-Menu-Location
(`inc/setup/theme-setup.php`) redaktionell pflegbar zu machen. Entscheidung: dynamisch ueber
`wp_nav_menu` -- der Navigationsinhalt ist echte, redaktionell gepflegte Geschaeftsstruktur, keine
feste Chrome, gehoert also ins WP-Menu-Backend wie bei jeder anderen Seite auch, trotz des
Mehraufwands gegenueber einem hartkodierten Array.

`navigation-menu.php` erwartet ein `items`-Array mit vorgerendertem `content`-HTML pro Trigger
(kein `wp_nav_menu()`-Walker-Output) -- der neue `hengegroup_theme_primary_navigation_items()`
(`inc/template-parts/navigation.php`) uebersetzt die WP-Menuestruktur (nur eine Ebene tief, siehe
dessen eigenen Kopfkommentar) in dieses Format, inkl. "current item"-Erkennung ueber denselben
`wp_nav_menu_objects`-Kern-Filter, den `wp_nav_menu()` selbst nutzt, statt WordPress' eigene
Current-Item-Logik von Hand nachzubauen. Mobile Navigation (Hamburger/Off-Canvas) ist bewusst noch
nicht gebaut -- die Referenz zeigt kein Mobile-Layout dafuer, siehe `docs/to-do.md`.

### Header: Scroll-Verhalten aus dem Referenzdesign statt der Vorlagen-eigenen Pill-Logik (2026-09-16)

Die `base-theme`-Vorlage brachte bereits ein generisches Scroll-Verhalten mit (`header.js`/
`header.css`, schon in `app.js` eingebunden): ein zentrierter Header, der beim Scrollen zu einer
schmaleren, abgerundeten "Pill" schrumpft (`IntersectionObserver` + Sentinel-Element,
`is-floating`-Klasse). Die Hengegroup-Referenz zeigt stattdessen einen vollbreiten, fixierten,
dunklen Balken, der beim Scrollen nur teiltransparent wird und einen Backdrop-Blur bekommt (keine
Formaenderung), mit einem farbigen Gradient-Rand oben. Auf Rueckfrage: die Referenz wird 1:1
uebernommen, die Pill-Logik der Vorlage entfaellt fuer diesen Header vollstaendig.

`header.js` ist dadurch deutlich einfacher geworden: kein Sentinel/`IntersectionObserver`/
`top`-Offset-Tracking mehr noetig (der Header aendert seine Position nicht laenger), nur noch ein
`scroll`-Listener, der `data-scrolled` auf dem Header-Element toggelt. Der eigentliche visuelle
Uebergang (Hintergrundfarbe/Blur) liegt als `data-[scrolled=true]:`-Tailwind-Variante direkt in
`header.php` (CLAUDE.md Regel 1), keine neue Logik in `header.css` noetig.

### Header: Sprachumschalter als reines UI-Element (2026-09-16)

Der DE/English-Sprachumschalter aus der Referenz wurde als reine `dropdown-menu.php`-Komposition
umgesetzt (kein echtes Sprachwechsel-Backend dahinter, beide Eintraege verlinken aktuell auf `#`).
Grund: Mehrsprachigkeit ist fuer dieses Projekt ueber ein WordPress-Multisite-Netzwerk geplant (ein
Standort pro Sprache, siehe den Eintrag "Mehrsprachigkeit ueber Multisite statt Hreflang-Plugin"
weiter unten in dieser Datei) -- eine echte URL-Zuordnung zwischen Sprachstandorten laesst sich
sinnvoll erst bauen, sobald dieses Netzwerk existiert. Bis dahin ist der Umschalter bewusst nur
Optik, kein Deadcode-Feature-Flag und keine Wegwerf-Loesung, die spaeter wieder entfernt werden
muesste -- die Komponente selbst (`dropdown-menu.php`) bleibt unveraendert, nur ihr Inhalt
bekommt spaeter echte Links.

### Neuer Helper `hengegroup_theme_merge_data_attributes()`: identischer `data_attributes`-Merge-Loop aus 63 Base-Komponenten in `inc/template-parts/helpers.php` extrahiert (2026-09-16)

Review-Auftrag (gezielt nach dupliziertem Code statt nach Kompositions-Luecken gesucht): der
`foreach ($data_attributes as $attribute_key => $attribute_value) { ... }`-Block, der jede
Komponente eigenes `data_attributes`-Config in ihr Attribute-Array mischt (`data-`-Praefix,
Leer-Keys ueberspringen), lag Byte-fuer-Byte identisch in 63 Dateien unter `template-parts/base/`
(~500 Zeilen kopierte Logik, nur der Ziel-Variablenname unterschied sich je Datei) -- genau der
Fall, den Regel 7 ("Cross-cutting Logik lebt ausschliesslich in `inc/template-parts/helpers.php`,
nie kopieren") verhindern soll, ohne dass bislang ein Helper dafuer existierte.

Neue Funktion `hengegroup_theme_merge_data_attributes(array $attributes, array $data_attributes): array`
(siehe deren eigenen Docblock) ersetzt den Loop an allen 63 Fundstellen durch einen Einzeiler
(`$element_attributes = hengegroup_theme_merge_data_attributes($element_attributes, $data_attributes);`,
Ziel-Variable bleibt je Datei unveraendert). Mechanischer Refactor ohne Verhaltensaenderung:
`composer lint`/`composer test` (inkl. 4 neuer PHPUnit-Tests fuer den Helper selbst) laufen
unveraendert durch, jede betroffene Datei einzeln `php -l`-geprueft.

**Lehre aus einem Zwischenfall waehrend dieser Aenderung**: ein erster Versuch, die 63 Stellen per
PowerShell-Regex-Bulk-Skript zu ersetzen, hatte einen Bug (`[regex]$str1 + $str2 + ...`-Verkettung
schlaegt in PowerShell fehl, da `+` auf einem `[regex]`-Objekt nicht definiert ist) und hat dadurch
alle 64 betroffenen Dateien komplett geleert, bevor der Fehler auffiel -- nur im Arbeitsverzeichnis,
nichts committet, per `git checkout -- template-parts/base/` folgenlos wiederhergestellt. Der
zweite, tatsaechlich verwendete Versuch lief deshalb erst als Trockenlauf (kein Schreiben) gegen
eine Kopie in einem Scratch-Verzeichnis, mit Abbruch-Guards (leerer/verdaechtig kurzer Output wird
nicht geschrieben) und `php -l` ueber die Kopie, bevor irgendetwas am echten Repo geaendert wurde.

---

### `avatar.php`/`breadcrumb.php`: kein Bestandteil dieses Projekt-Themes, stale Referenzen bereinigt (2026-09-16)

Beide Komponenten wurden bereits im Commit `f3cc35e` (30.08.2026, Phase-2-Styling fuer
button.php/badge.php/typography.php) aus `template-parts/base/` entfernt -- vermutlich beim
Zuschneiden der generischen Vorlage auf dieses konkrete Projekt (Hengegroup braucht aktuell weder
Nutzer-Avatare noch eine Breadcrumb-Navigation), ohne dass diese Entscheidung damals hier
festgehalten wurde (Regel 12 haette das verlangt). In der Zwischenzeit hatten mehrere andere,
weiterhin aktive Dateien beide Komponenten in ihren Kopfkommentaren als bestehende, funktionierende
Geschwister-Komponenten zitiert (`card.php`, `attachment.php`, `kbd.php`, `switch.php`,
`toggle/toggle.php`, `pagination/pagination.php`), dazu `docs/neue-komponente-erstellen.md`,
`inc/template-parts/helpers.php` und ein eigener Abschnitt in
`docs/tastatur-screenreader-testplan.md` fuer `breadcrumb.php` -- reine Doku-/Kommentar-Drift nach
der Loeschung, kein Hinweis auf tatsaechlich fehlenden Code (keine `get_template_part()`-Aufrufe auf
`base/avatar`/`base/breadcrumb` existierten mehr).

Alle betroffenen Stellen wurden auf reale, weiterhin existierende Komponenten umgehaengt statt sie
mit erfundenem Kontext zu ersetzen (`card.php` beschreibt seinen eigenen `image.php`-Puffer-Trick
jetzt direkt statt per Analogie zu `avatar.php`, `pagination.php`s Ellipsis-Icon-Begruendung
verweist auf shadcn's eigenes `PaginationEllipsis` statt auf `breadcrumb.php`, etc.) --
`page-component-showcase-hover-card.php`s eigener Hinweis auf die fehlende `avatar.php` bleibt
bestehen (zeigt jetzt hierher statt auf `docs/to-do.md`, wo die Komponente nie gelistet war).

Beide Alt-Versionen (Phase 1, vor jeglichem Tailwind-Styling) sind bei Bedarf weiterhin aus der
Git-Historie rekonstruierbar (`git show f3cc35e^:template-parts/base/avatar.php` bzw.
`.../breadcrumb.php`) -- kein Wiederaufbau in diesem Pass, nur die Referenz-Bereinigung.

---

### `skeleton.php` gestylt, neue `shape`/`color`-Configs, erste `motion-reduce`-Nutzung (2026-09-16)

Phase-2-Styling auf Basis der Claude-Design-Referenz "Hengegroup"
(https://claude.ai/artifact/AgUuYjLPw8bHzEXojvtqyj)'s "Basis"/"Formen"/"Auf dunklem
Grund"-Abschnitte. Klassen-Herleitung/Deviationen stehen direkt in `skeleton.php`s eigenem
Kopfkommentar (Regel 12: kein Doppel-Text hier) -- dieser Eintrag haelt nur fest, was nicht schon
aus dem Diff folgt:

- **Referenz-Link in einem neuen Format** (`claude.ai/artifact/<kurze-id>` statt des bisherigen
  `claude.ai/code/artifact/<uuid>`) -- funktioniert im Browser identisch (per Claude-in-Chrome
  verifiziert), nur das `Artifact`-Tool/`WebFetch` dieser Session konnten ihn nicht direkt lesen
  (andere Produktoberflaeche als die Code-eigenen Artifacts). Design-Inhalt stattdessen per
  Browser-Screenshot/Zoom gesichtet, nicht per HTML-Extraktion.
- **`motion-reduce:animate-none` ergaenzt** -- die erste Phase-2-Animation dieses Themes ueberhaupt
  mit tatsaechlicher Reduced-Motion-Beruecksichtigung (spinner.php's `animate-spin`/progress.php's
  Streifen-Animation haben bislang keine). `docs/to-do.md`s eigener a11y-Abschnitt wollte das
  ausdruecklich "von Anfang an" fuer jede neue Phase-2-Animation, nicht nachtraeglich fuer alle auf
  einmal -- Tailwinds eingebaute `motion-reduce:`-Variante deckt das hier ab, ohne das dort
  zusaetzlich verlangte projektweite Reduced-Motion-Token vorwegzunehmen. Bestehende Animationen
  (spinner.php, progress.php, dialog.php, ...) bleiben bewusst unangetastet -- das waere ein
  separates Nachrüst-Vorhaben, nicht Teil dieser Komponente.
- **`page-component-showcase-skeleton.php` neu**, analog zu den anderen Showcase-Seiten. Die
  Referenz-Interaktion in "Übergang" (Toggle-Button Platzhalter/Inhalt) wurde als statischer
  Nebeneinander-Vergleich nachgebaut statt als echter Toggle -- der gezeigte Punkt (Platzhalter- und
  Inhalts-Maße stimmen exakt ueberein) braucht dafuer keine Interaktivitaet, und eine neue Show/Hide-
  Technik nur fuer eine Dev-only-Seite haette keinen weiteren Nutzen gehabt.

---

### `navigation-menu/*.php` gestylt, neues `color`-Config, kein Datei-pro-Variante-Split, kein Ordner-Umzug (2026-09-05)

Phase-2-Styling auf Basis der Claude-Design-Referenz "Hengegroup"
(https://claude.ai/code/artifact/5cb1e148-c394-4f71-bc4b-61912a213332)'s "Basis"/"Auf dunklem
Grund"-Abschnitte. Klassen-Herleitung/Deviationen stehen direkt in `navigation-menu.php`s/
`navigation-menu-link.php`s eigenen Kopfkommentaren (Regel 12: kein Doppel-Text hier) -- dieser
Eintrag haelt nur die Entscheidungen fest, die nicht schon aus dem Diff folgen:

- **Die Referenz war diesmal lesbar, trotz eines nicht-interaktiven Viewers.** Wie beim
  `dropdown-menu.php`-Eintrag unten reagierte der Artifact-Viewer nicht auf
  Hover-/Klick-/Scroll-Automatisierung (die eigentliche Vorschau steckt in einer Cross-Origin-
  iframe, auf die weder `read_page` noch injiziertes JS zugreifen konnten). Anders als dort liess
  sich der Artifact-Export selbst (per `WebFetch`) als Rohdatei lesen -- die Vorschau ist ein
  kleines eingebettetes React-artiges Prototyp-Programm (Farben, Abstaende, Radien, Schatten,
  Timing als literale Werte im Quelltext, keine kompilierten/verschleierten Klassen), nicht nur ein
  statisches Bild. Alle Werte unten sind dadurch Literale aus diesem Quelltext, keine Schaetzungen.
- **Kein Datei-pro-Variante-Split, kein weiterer Ordner-Umzug.** `navigation-menu/` ist bereits seit
  Phase 1 ein eigener Ordner (`navigation-menu.php` + `navigation-menu-link.php`) -- "Basis"/"Auf
  dunklem Grund" sind zudem kein Varianten-Split, sondern ein neuer `color`-Config-Wert (naechster
  Punkt), also ohnehin kein Kandidat fuer eine zweite Datei.
- **Neues `color`-Config (`default | light`)**, dieselbe Idee/dasselbe Vokabular wie
  `accordion.php`s/`typography.php`s eigenes `color: light` ("dieses Projekt hat noch keine
  Dark-Mode-Strategie", `docs/to-do.md` -- deshalb ein explizites Config statt eines
  `dark:`/`prefers-color-scheme`-Umschalters). Gleiche Vereinfachung wie `accordion.php`s Eintrag:
  die Referenz nutzt fuer den dunklen Hintergrund ein eigenes helleres Gruen (`#8fd6ab`) statt
  `henge-green`, hier bewusst NICHT uebernommen -- ein Marken-Gruenton fuer beide `color`-Werte,
  eine Ausnahme weniger im Design-System. Anders als bei `accordion.php` bekommt das Mega-Menu-
  Panel selbst trotzdem eine eigene dunkle Kartenflaeche (Hintergrund/Rand/Schatten) statt sich nur
  auf den Aufrufer zu verlassen -- ein Panel schwebt ueber beliebigem Seiteninhalt, anders als
  `accordion.php`s Inline-Content, das direkt im Elternhintergrund sitzt.
- **`navigation-menu-link.php`s Styling ist jetzt rollenabhaengig statt einheitlich.** Die Datei
  wird an zwei visuell komplett unterschiedlichen Stellen verwendet (Top-Level-Button vs.
  Panel-interner Listeneintrag) -- echtes shadcn loest denselben Konflikt identisch (ein
  `navigationMenuTriggerStyle()`-Helper fuer Top-Level, ein separater, aufrufer-eigener
  `ListItem`-Wrapper fuer Panel-Eintraege, nicht dieselbe Komponente fuer beides gestylt). Diese
  Datei traegt deshalb nur noch eine minimale, kontextneutrale Basis (Fokus-Ring, keine
  Hintergrund-/Text-Farbe); `navigation-menu.php` liefert das volle Top-Level-Rezept selbst per
  `class`, ein Panel-interner Listen-Look ist ein dokumentiertes Rezept fuer den Aufrufer (siehe
  `navigation-menu.php`s Kopfkommentar) -- vermeidet damit auch `button.php`s dokumentierte
  Klassen-Reihenfolge-Falle (ein per `class` uebergebenes, konfligierendes `bg-*`/`text-*` gewinnt
  nicht zuverlaessig).
- **`page-component-showcase-navigation-menu.php` neu**, analog zu den anderen Showcase-Seiten;
  Inhalte (Produkte/Anwendungen/Unternehmen, Beschreibungstexte) 1:1 aus der Referenz uebernommen,
  keine erfundenen Platzhalter.
- **Nutzer-Feedback nach dem ersten Durchlauf, direkt eingearbeitet (kein neuer Eintrag noetig,
  Regel 12 -- reine Korrektur derselben Aenderung, nicht der Beleg einer neuen Entscheidung):**
  Chevron flippt jetzt vertikal (`group-open:-scale-y-100`) statt zu rotieren
  (`group-open:rotate-180`, wie zuerst gebaut und wie `accordion.php`s eigener Chevron es weiterhin
  tut) -- eine Rotation dreht das Symbol sichtbar durch eine seitwaerts zeigende Zwischenposition,
  ein Flip quetscht es stattdessen flach und entfaltet es gespiegelt wieder, liest sich als "zeigt
  jetzt woanders hin" statt als Drehbewegung. Panel-Mindestbreite von `min-w-56` auf `min-w-72`
  angehoben, und wichtiger: die Showcase-Seite bekommt pro Grid-Spalte einen echten
  `minmax()`-Boden (`grid-cols-[repeat(2,minmax(13rem,1fr))]` statt nacktem `grid-cols-2`) --
  Tailwinds eigenes `minmax(0,1fr)` erlaubt Spalten, auf 0 zu schrumpfen, was bei laengeren
  deutschen Produktnamen/Beschreibungen zu wirklich zu schmalen Eintraegen fuehrte (Nutzer-Befund
  "Menuepunkte im Dropdown sind zu schmal").
- **Nicht visuell im Browser verifiziert** -- dieses Repo hat keine lauffaehige WordPress-Instanz
  in dieser Umgebung (siehe `docs/to-do.md`s a11y-/Visual-Regression-Punkt zur fehlenden
  `wp-env`-Infrastruktur). Verifiziert stattdessen: `composer lint`/`composer test`/
  `pnpm format:check`/`pnpm test` gruen, `pnpm exec vite build` erfolgreich, jede neue Utility-
  Klasse (u. a. `hover:bg-henge-green/10`, `group-open:rotate-180`, `bg-neutral-800`, `min-w-56`,
  beide `shadow-[...]`-Werte) im kompilierten `dist/assets/css/app-*.css` stichprobenartig
  bestaetigt.

---

### `dropdown-menu/*.php` gestylt, kein Datei-pro-Variante-Split, kein Ordner-Umzug -- Design-Referenz nicht lesbar (2026-09-05)

Phase-2-Styling auf Basis der Claude-Design-Referenz "Hengegroup"
(https://claude.ai/code/design/p/37768540-95a8-46e1-a647-33070ca71612?file=Dropdown+Menu.dc.html).
Klassen-Herleitung/Deviationen stehen direkt in `dropdown-menu.php`s eigenem Kopfkommentar (Regel
12: kein Doppel-Text hier) -- dieser Eintrag haelt nur die Entscheidungen fest, die nicht schon aus
dem Diff folgen:

- **Die Referenz konnte nicht vollstaendig gelesen werden**, anders als bei jedem bisherigen Phase-
  2-Eintrag. Weder der reine Artifact-Viewer (`.../code/artifact/...`) noch der eigentliche
  Design-Editor (`.../design/p/...`) reagierten in dieser Session auf Browser-Automatisierung --
  Klicks auf den Trigger oeffneten kein Panel, Scroll-/Drag-/Tastatur-Versuche bewegten den
  sichtbaren Ausschnitt nicht (der Present-Modus blieb schwarz), nur der eingebaute Zoom-Regler
  (50-200%) reagierte ueberhaupt. Sichtbar waren dadurch ausschliesslich die GESCHLOSSENEN Trigger
  ("Aktionen"/"Spalten"/"Teilen"-Buttons, drei Abschnittsueberschriften/-beschreibungen); die
  geoeffneten Panel-Zustaende (Item-Hover/Fokus, destructive/disabled/Checkbox/Radio-Optik, das
  Untermenue) blieben unsichtbar. Nutzer-Rueckfrage dazu gestellt (Screenshots schicken/Edit-Zugriff
  geben/ohne Rest weitermachen); Nutzer schickte stattdessen den Design-Editor-Link, der aber
  demselben Automatisierungs-Problem unterlag.
- **Konsequenz: die Panel-/Item-Klassen sind NICHT aus der Referenz hergeleitet**, sondern aus
  shadcns eigenem, live gegen aktuelle Docs geprueftem Stock-`DropdownMenuContent`/-`Item`/
  \-`CheckboxItem`/-`RadioItem`/-`Label`-Klassen-Rezept, adaptiert auf die bereits etablierten
  Projekt-Tokens (`border-border`/`bg-popover`/`rounded-2xl`/derselbe literale Schatten wie
  popover.php/hover-card.php). Aeussere Karte uebernimmt deren Look 1:1 fuer
  Cross-Komponenten-Konsistenz, aber `p-1` statt deren `p-4` -- popover.phps eigener Phase-2-Eintrag
  hatte diese Unterscheidung bereits vorweggenommen ("a menu-flavoured popover is just
  dropdown-menu.php's own item styling ... not a distinct popover variant"). Sollte sich die
  tatsaechliche Referenz-Optik spaeter doch noch erschliessen (z. B. wenn Edit-Zugriff moeglich
  wird), ist ein Abgleich/Nacharbeiten dieser Datei angezeigt -- kein stillschweigend akzeptierter
  Kompromiss.
- **Kein Datei-pro-Variante-Split, kein `dropdown-menu/`-Ordner-Umzug** (die Aufgabenstellung hat
  beides explizit an "sinnvoll oder notwendig" geknuepft, dieselbe Formulierung wie bei jedem
  bisherigen Phase-2-Eintrag) -- anders als bei jenen Eintraegen war die Komponente hier aber schon
  VOR diesem Auftrag (seit Phase 1) in einem eigenen Ordner UND in ein File pro Unterteil
  (item/checkbox-item/radio-item/radio-group/group/label) aufgeteilt, aus demselben "mehr als eine
  Datei" Grund wie toggle/radio/button-group (CLAUDE.md Regel 4). Diese Runde erfindet also keinen
  neuen Split, sondern stylt die bestehenden Dateien; jede shadcn-eigene Item-"Variante"
  (`default`/`destructive`) ist bereits ein `variant`-Config-Wert auf der einen bestehenden
  dropdown-menu-item.php, dieselbe Schlussfolgerung wie bei card.php/popover.php/hover-card.php.
- **Kein Pfeil** (anders als popover.php/hover-card.php/tooltip.php) -- shadcns eigenes reales
  `DropdownMenuContent` hat gar keinen Pfeil-Slot, und nichts am sichtbaren Teil der Referenz
  widerspricht dem.
- **`page-component-showcase-dropdown-menu.php` neu, analog zu den anderen Showcase-Seiten** --
  reproduziert nur, was tatsaechlich sichtbar/bekannt war (Aktionen-Menue mit Shortcut/Disabled/
  Destructive, Spalten/Sortierung-Menue mit Checkbox-/Radio-Items, Zeilenmenue in einer
  Produktliste). Die Referenz-eigene "Untermenue"-Sektion wurde NICHT nachgebaut -- DropdownMenuSub
  ist seit Phase 1 explizit out of scope (dropdown-menu.php eigener Kopfkommentar), unabhaengig vom
  Automatisierungs-Problem oben.
- Vier bislang fehlende Lucide-Icons synchronisiert (`pencil`, `copy`, `trash-2`, `eye`) fuer die
  neue Showcase-Seite, `archive`/`ellipsis`/`chevron-down` waren bereits vorhanden.

---

### `dialog.php` gestylt, kein Datei-Split, kein Ordner-Umzug (2026-09-05)

Phase-2-Styling auf Basis der Claude-Design-Referenz "Hengegroup"
(https://claude.ai/code/artifact/51dedf08-71e3-4deb-9e68-19256e4cfb39). Klassen-Herleitung/
Deviationen stehen direkt in `dialog.php`s eigenem Kopfkommentar (Regel 12: kein Doppel-Text hier)
-- dieser Eintrag haelt nur die Entscheidungen fest, die nicht schon aus dem Diff folgen:

- **Kein Datei-pro-Variante-Split, kein `dialog/`-Ordner-Umzug** (die Aufgabenstellung hat beides
  explizit an "sinnvoll oder notwendig" geknuepft) -- die Referenz zeigt genau einen Look (ein
  Kopf/Inhalt/Fusszeile-Beispiel plus eine Prosa-Anatomie-Anmerkung), keine Matrix strukturell
  unterschiedlicher Dialoge -- dieselbe Schlussfolgerung wie popover.phps/hover-card.phps eigene,
  fast identisch geformte Phase-2-Eintraege.
- **`m-auto` musste explizit ergaenzt werden**, obwohl das native `<dialog>` selbst schon
  `margin: auto`-Zentrierung mitbringt -- Tailwinds Preflight setzt `margin`/`padding` auf JEDEM
  Element (inkl. `<dialog>` und `::backdrop`) auf 0 zurueck, was die UA-Stylesheet-eigene
  Zentrierung sonst kommentarlos aufheben wuerde. Ein echter, nicht offensichtlicher Stolperstein
  beim Kombinieren von nativem `<dialog>` mit Tailwind, keine Stiloption -- `max-height`/`overflow`
  brauchten dagegen KEINE Ergaenzung, da Preflight beide Eigenschaften nie anfasst und die
  UA-eigene `dialog:modal { max-height: calc(100% - 6px - 2em); overflow: auto; }` unveraendert
  greift. Siehe `dialog.php`s eigenen Kopfkommentar fuer die volle Herleitung.
- **Bugfix (nach dem Deploy ueber https://dev.hengegroup.com/dialog-showcase/ live beobachtet:
  Dialoge verschwanden nicht vollstaendig, einige waren initial direkt sichtbar):** `flex` stand
  zunaechst bedingungslos auf `[data-slot="dialog-content"]` -- derselbe "Author schlaegt UA,
  unabhaengig von Spezifitaet"-Mechanismus wie beim `m-auto`-Punkt oben, nur diesmal bei `display`
  statt `margin`: ein bedingungsloses `flex` ist eine
  Autoren-Deklaration und schlaegt damit IMMER die UA-Stylesheet-eigene
  `dialog:not([open]) { display: none; }`, unabhaengig von Selektor-Spezifitaet -- jeder Dialog auf
  der Seite blieb dadurch dauerhaft sichtbar, offen oder nicht (genau das gemeldete "Dialoge
  verschwinden nicht vollstaendig"/"initial werden welche direkt geladen"). Fix: `open:flex` statt
  `flex` (Tailwinds `&[open]`-Variante) -- deklariert `display` nur, wenn `[open]` tatsaechlich
  gesetzt ist, laesst die UA-eigene `display: none` im geschlossenen Zustand also komplett
  unangetastet. `flex-col`/`gap-6`/etc. brauchten keine gleiche Behandlung -- die wirken erst,
  sobald `display` ueberhaupt `flex` ist, auf einem `display: none`-Element haben sie keinen
  Effekt.
- **Radius `rounded-2xl` (16&nbsp;px) statt der Referenz-eigenen literalen 18&nbsp;px**, gleiche
  Standardisierung wie bei popover.php/hover-card.php/toast.php/calendar.php. **Padding `p-8`
  (32&nbsp;px) dagegen 1:1 aus der Referenz uebernommen** -- trifft Tailwinds Skala exakt, kein
  Zielkonflikt zwischen literalem Referenzwert und System-Schritt wie beim Radius.
- **`bg-background`/`text-foreground` statt `bg-card`/`bg-popover`** -- shadcns eigene reale
  Stock-`DialogContent`-Klassen (live gegen aktuelle Docs geprueft); alle drei Tokens loesen aktuell
  auf denselben Literalwert auf, also eine "shadcns eigenes Vokabular treffen"-Entscheidung, kein
  sichtbarer Unterschied.
- **Kein `border-border`** (anders als popover.php/hover-card.php) -- die Referenz zeigt gegen ihren
  dunklen Demo-Rahmen keine sichtbare Kante, nur einen Schatten; bewusst nicht ergaenzt, obwohl
  shadcns eigenes reales `DialogContent` eine Border traegt, weil das gegen diese konkrete Referenz
  erfunden waere.
- **Eingebauter Schliessen-Button ist neues, eigenes Projekt-CSS** (`border-border`/`rounded-lg`/
  `size-9`), nicht shadcns randloser Ghost-Icon-Button -- die Referenz zeichnet explizit ein
  umrandetes, abgerundetes Quadrat; naeher an toast.phps eigenem `toast-close` (`rounded-lg`,
  `size-7`) als an einem randlosen Ghost-Icon, Border aus der Referenz uebernommen statt
  toast-closes randlosem Look, da die Referenz eindeutig eine zeigt.
- **Eintritts-Animation `hg-dialog-in`/`hg-dialog-overlay-in`** (Opacity+Scale ohne `translateY`-
  Achse, anders als `hg-popover-in`) -- ein Dialog wird nativ zentriert (`margin: auto`), nicht an
  ein Ankerelement positioniert wie ein Popover, daher passt ein reiner Zoom besser. Laeuft
  bedingungslos (kein JS-getoggelter State), da natives `<dialog>` bei `display: none` ist,
  waehrend es geschlossen ist -- dieselbe Begruendung wie popover.phps eigenes `hg-popover-in`.
- **Referenz-Abschnitt mit dunklem Demo-Rahmen fuer die Anatomie NICHT als echtes `::backdrop`
  nachgebaut** -- die "Aufbau"-Sektion der Showcase-Seite rendert stattdessen einen bewusst
  nicht-modalen (`modal: false`), statisch offenen (`open: true`) Dialog in einer eigenen,
  dekorativen dunklen Box (reines Showcase-Markup, keine dialog.php-eigene Funktion) -- dieselbe
  "device frame"-Konvention wie die Referenz selbst.
- **`page-component-showcase-dialog.php`s eigener `modal: false`-Abschnitt rendert `open: true`
  direkt statt ueber einen `command="show-modal"`-Ausloeser** -- dieser native Befehlswert ruft
  immer `showModal()` auf, unabhaengig von der Konfiguration des Ziel-Dialogs (echtes
  Plattformverhalten der HTML Invoker Commands API, keine Design-Entscheidung dieses Themes); ein
  `modal: false`-Dialog hat keinen passenden nativen Befehlswert zum Oeffnen.
- `page-component-showcase-dialog.php` neu, analog zu den anderen Showcase-Seiten.

---

### `hover-card.php` gestylt, kein Datei-Split, kein Ordner-Umzug (2026-09-05)

Phase-2-Styling auf Basis der Claude-Design-Referenz "Hengegroup"
(https://claude.ai/code/artifact/d9a5a3e2-3a09-494f-926b-206c5fa23e93). Klassen-Herleitung/
Deviationen stehen direkt in `hover-card.php`s eigenem Kopfkommentar (Regel 12: kein Doppel-Text
hier) -- dieser Eintrag haelt nur die Entscheidungen fest, die nicht schon aus dem Diff folgen:

- **Kein Datei-pro-Variante-Split, kein `hover-card/`-Ordner-Umzug** (die Aufgabenstellung hat
  beides explizit an "sinnvoll oder notwendig" geknuepft) -- alle Referenz-Beispiele (reiche
  Produktvorschau, Kontaktkarte, vier `side`s, eine Produktliste, die dieselbe Vorschau-Form
  wiederverwendet) sind Markup/Config, das die bestehende Ein-Datei-Komponente schon ueber
  `content`/`side`/`align` abbildet -- dieselbe Schlussfolgerung wie popover.phps/tooltip.phps
  eigene, fast identisch geformte Phase-2-Eintraege.
- **Karten-Look von popover.php uebernommen** (`bg-popover`/`border-border`/`rounded-2xl`/`p-4`/
  derselbe literale Schatten), nicht tooltip.phps dunkle `neutral-900`-Karte -- HoverCards Inhalt
  ist reiches, oft strukturiertes Vorschau-Material (Avatar-Zeile, Kennwerte-Liste,
  "Datenblatt öffnen"-Link), dieselbe Form wie popover.phps eigenes `content`, kein kurzer
  Text-Hinweis wie bei tooltip.php.
- **Abstand zum Trigger 12&nbsp;px statt der sonst ueblichen 10&nbsp;px** (popover.php/tooltip.php)
  -- die Referenz begruendet das explizit ("damit der Zeiger die Karte erreicht, ohne sie zu
  schließen"), ein bewusster, dokumentierter Unterschied, kein Rundungsfehler. Genutzt ueber
  `hengegroup_theme_floating_position_classes()`s (`inc/template-parts/helpers.php`) bereits
  vorhandenen, generischen dritten `$gap_px`-Parameter (Default 10, unveraendert fuer die anderen
  beiden Aufrufer) -- kein Aenderungsbedarf am Helper selbst. Breite `w-64` (shadcns eigener Stock-Default fuer `HoverCardContent`, schmaler als Popovers
  `w-72`) statt eines der Referenz-eigenen, pro Demo unterschiedlichen Pixelwerte (210-320px).
- **Eintritts-Uebergang ist Opacity+Scale, NICHT die Referenz-eigene woertliche
  `translateY(-4px)`-Achse** und kein `@keyframes` (anders als popover.phps `hg-popover-in`) --
  der Inhalt bleibt (wie bei tooltip.php) durchgehend im DOM, `data-state`-getoggelt statt per
  natives `<details>` ein-/ausgehaengt, daher ein CSS-`transition` statt eines nur einmal beim
  Laden abspielenden Keyframes. Die `translateY`-Achse zusaetzlich weggelassen, weil
  `utils/floating-position.js`s `positionFloatingElement()` bei jedem Oeffnen `content.style.translate
= "none"` als Inline-Style setzt, was ab dem ersten Oeffnen dauerhaft jede `translate-y-*`-Klasse
  gewinnt -- der Uebergang wuerde beim ersten Mal sichtbar animieren und danach still aufhoeren.
  `scale`/`opacity` bleiben von dieser Funktion unangetastet und animieren zuverlaessig bei jedem
  Oeffnen/Schliessen. Siehe `hover-card.php`s eigenen Kopfkommentar fuer die volle Herleitung.
- **Pfeil 8&nbsp;px (`size-2`)**, nicht die Referenz-eigenen 10&nbsp;px -- popover.phps eigener
  Phase-2-Eintrag hatte diese Zahl bereits vorab angekuendigt ("same size as tooltip.php's/
  hover-card.php's own arrow for cross-component consistency"), dieser Eintrag loest das nur ein.
  Bleibt anders als popover.phps eigener, einmalig berechneter Pfeil aber eine reaktive
  `group-data-[side=...]`-Matrix (tooltip.phps Technik), weil hover-card.js zur Laufzeit den `side`
  nach einem Viewport-Kollisions-Flip aendern kann, ohne dass die Positionierungs-Funktion den
  Pfeil selbst mitkorrigiert.
- **Referenz-Abschnitt "Auf dunklem Grund" NICHT uebernommen**, gleicher Grund wie bei jedem
  bisherigen Phase-2-Eintrag (popover.php/tooltip.php/card.php/etc.) -- dieses Theme hat noch keine
  Dark-Mode-/Dark-Surface-Strategie, siehe `docs/to-do.md`.
- `page-component-showcase-hover-card.php` neu, analog zu den anderen Showcase-Seiten.

### `card.php` gestylt, `media_badge`/`footer_divider`/`href` neu, kein Datei-Split (2026-09-05)

Phase-2-Styling auf Basis der Claude-Design-Referenz "Hengegroup"
(https://claude.ai/code/artifact/c2fdca5b-79fc-47b7-92c8-e861966ac106)'s "Basis"/"Mit Bild"/
"Kompakt"-Abschnitte ("Auf dunklem Grund" bewusst nicht uebernommen, siehe unten). Klassen-
Herleitung/Deviationen stehen direkt in `card.php`s eigenem Kopfkommentar (Regel 12: kein
Doppel-Text hier) -- dieser Eintrag haelt nur die Entscheidungen fest, die nicht schon aus dem Diff
folgen:

- **Kein Datei-pro-Variante-Split, kein `card/`-Ordner-Umzug** (die Aufgabenstellung hat das explizit
  zur Pruefung freigestellt, dieselbe Formulierung wie popover.phps eigener Eintrag). Alle vier
  Referenz-Varianten (Datenliste+Footer-Buttons, Formularkarte, klickbare Bild-Karte,
  Kennzahlen-Karte) sind Markup/Config, das die bestehende Ein-Datei-Komponente schon abbildet --
  keine strukturell andere Komposition, die einen eigenen Sub-Ordner (wie `toggle/`/`radio/`)
  rechtfertigen wuerde.
- **Drei neue, eng geschnittene Config-Keys statt puren Klassen** -- als einzige echte
  API-Erweiterung dieser Phase-2-Runde, jeweils weil reine Tailwind-Klassen auf bestehenden Slots
  die Referenz nicht abbilden konnten:
    - `href` (macht die ganze Karte zum `<a>`-Klickziel) -- selbes asChild/Slot-Idiom wie button.phps/
      badge.phps eigenes `href`, nicht neu erfunden.
    - `media_badge` (Overlay-Label auf dem Bild) -- einziger Weg, ein zweites Element neben `image`
      in denselben `card-media`-Wrapper zu bekommen, ohne image.php selbst zweckzuentfremden (siehe
      dessen eigenen Kopfkommentar: bleibt reine `<img>`-Plumbing).
    - `footer_divider` (bool) -- PHP hat kein `cn()`/tailwind-merge, um shadcns eigenen
      `[.border-t]:pt-6`-Trick (Trenner erkennen, den der Aufrufer selbst an sein Footer-`className`
      haengt) auf einen vom Aufrufer nicht kontrollierten Wrapper-`<div>` anzuwenden -- als expliziter
      Bool stattdessen, gleiche visuelle Wirkung, tatsaechlich erreichbar.
- **`size: 'sm'` jetzt real gestylt** (Gap/Padding einen Tailwind-Schritt kleiner als `default`) --
  shadcns eigene aktuelle Docs-Prosa erwaehnt diesen Prop plus eine `--card-spacing`-CSS-Variable,
  die live-geprüfte Quelldatei (`registry/new-york-v4/ui/card.tsx`, Stand 2026-09-05) zeigt aber
  keins von beidem. Statt einen unbestaetigten Mechanismus zu raten: eigener, einfacher
  Spacing-Schritt nach unten, trifft die dokumentierte Absicht ("uses smaller spacing").
- **Radius `rounded-2xl` statt shadcns Stock-`rounded-xl`**, **`border-border`/`bg-card`/
  `text-card-foreground`** statt impliziter Browser-Randfarbe -- dieselbe Standardisierung auf die
  bereits etablierten Karten-/Floating-Radien/-Tokens wie popover.php/toast.php/calendar.php, nicht
  die Referenz-eigenen 20px woertlich uebernommen.
- **Titel/Beschreibung bleiben bei `body-lg`/`body-sm`** (typography.phps eigene Groessen-Vokabel,
  aus Phase 1 uebernommen) statt shadcns tatsaechlich unsized `CardTitle`/`text-sm`
  `CardDescription` -- Phase-1-Entscheidung, hier nicht revidiert (ausserhalb des Auftrags,
  typography.php selbst wird nicht angefasst); lediglich `leading-none` (Titel) und `color: 'neutral'`
  (Beschreibung, = shadcns `text-muted-foreground`) ergaenzt.
- **Referenz-Abschnitt "Auf dunklem Grund" NICHT uebernommen**, gleicher Grund wie bei jedem bisherigen
  Phase-2-Eintrag (popover.php/toast.php/tooltip.php/etc.) -- dieses Theme hat noch keine
  Dark-Mode-/Dark-Surface-Strategie, siehe `docs/to-do.md`.
- `page-component-showcase-card.php` neu, analog zu den anderen Showcase-Seiten.

### `toggle.php`/`toggle-group.php` gestylt, drittes `variant` (`accent`), `!important`-Fix in `calendar.php` (2026-09-05)

Phase-2-Styling auf Basis der Claude-Design-Referenz "Hengegroup"
(https://claude.ai/code/artifact/120c0655-89f0-4c42-b99b-bb5227b96ccc)'s "Basis"/"Varianten"/
"Größen"-Abschnitte. Details/Klassen-Herleitung stehen direkt in `toggle.php`s/`toggle-group.php`s
eigenen Kopfkommentaren (Regel 12: kein Doppel-Text hier) -- dieser Eintrag haelt nur die
Entscheidungen fest, die nicht schon aus dem Diff folgen:

- **Kein Datei-pro-Variante-Split, kein weiterer Ordner-Umzug.** `toggle/` ist bereits seit Phase 1
  ein eigener Ordner (`toggle.php` + `toggle-group.php`, echte Sub-Komponenten mit eigenem Markup,
  siehe `kbd.php`-Eintrag weiter unten) -- `variant`/`size` bleiben reine Klassen-Varianten
  innerhalb je einer Datei, ueber `$variant_classes`/`$size_classes`-Arrays, dasselbe Ein-Datei-
  Muster wie button.php/badge.php/tabs.php.
- **Drittes `variant`: `accent`.** Die Referenz zeigt neben "Ohne Kante" (`default`, nie umrandet)
  und "Mit Kante" (`outline`, auch ungedrueckt umrandet) eine dritte Reihe "Akzent" ("primäre
  Auswahl", volltoniger henge-green-Fill gedrueckt). Benannt `accent` statt eines Marken-Farbnamens
  (anders als button.php's volles Marken-Farbvokabular) -- dieses Projekt nutzt "accent" bereits als
  generischen Namen fuer "dieses Element bekommt seine eine Betonungsstufe in Marken-Gruen"
  (separator.php's `style: 'accent'`, badge.php's `font: 'accent'`); `default`/`outline` bleiben
  shadcns eigenes Toggle-Vokabular. `default`s gedrueckter Fill ist `grey-dark` (nicht shadcns
  neutrales `bg-accent`) -- exakt dieselbe Marken-Grau-Uebernahme wie tabs.php's segmentierte
  `peer-checked:bg-grey-dark`-Variante, die dieser Referenz optisch fast 1:1 entspricht (volltonige
  anthrazitfarbene Pille). `outline`s Rahmen ist **`grey-dark`** (Nutzer-Korrektur nach dem ersten
  Review-Durchlauf, siehe Bugfix-Punkt weiter unten) -- nicht der urspruenglich gewaehlte, shadcn-
  eigene `border-input`, der im echten Build unsichtbar blieb; damit jetzt dieselbe Marken-Grau-
  Rahmenfarbe wie button.php/badge.php's eigenes `outline`.
- **Bugfix: `border-transparent` aus `$base_classes` entfernt, jede `$variant_classes`-Zeile setzt
  ihre eigene Randfarbe (`default`/`accent`: `border-transparent`, `outline`: `border-grey-dark`).**
  Vorher trugen `outline`-Toggles GLEICHZEITIG `$base_classes`s unbedingtes `border-transparent` UND
  ihre eigene Randfarbe (erst `border-input`, s.o.) auf demselben Element -- beides dieselbe
  CSS-Property bei gleicher Spezifitaet, Tailwinds generierte Stylesheet-Reihenfolge (nicht die
  Reihenfolge im `class`-Attribut) entscheidet dann den Gleichstand. Im echten, projektweiten Build
  gewann `border-transparent` (im isolierten Test-Compile dieser Session zunaechst nicht
  reproduzierbar, siehe Bugfix-Eintrag zum `!important`-Fix oben fuer denselben Mechanismus) --
  der Rahmen blieb unsichtbar. Jetzt traegt jedes Element genau eine Randfarben-Klasse, kein
  Gleichstand mehr moeglich, unabhaengig von Tailwinds Scan-Reihenfolge.
- **Groessen (`sm`/`default`/`lg`) treffen die Referenz-Hoehen (30px/38px/46px) exakt ueber
  Tailwinds fraktionale Spacing-Skala** (`h-7.5`/`h-9.5`/`h-11.5` = 1.875rem/2.375rem/2.875rem --
  echte Skalenstufen, derselbe Halbschritt wie badge.php's eigenes `py-0.75`, keine Arbitrary-
  Bracket-Werte). Schriftgroesse skaliert pro Groesse wie button.php's eigenes `sm`/`base`/`lg`
  (text-sm/text-base/text-lg) -- die Referenz zeigt sichtbar wachsenden Labeltext.
- **Bugfix: `toggle-group.php` wrappt jedes Item jetzt in einen eigenen
  `<span data-slot="toggle-group-item">`.** Ohne diesen Wrapper reihte die `foreach`-Schleife alle
  Item-`<input><label>`-Paare flach als direkte Geschwister im selben Wrapper-`<div>` aneinander --
  harmlos in Phase 1 (toggle.php hatte noch keine `peer-checked:`-Klassen), aber ein echter
  Phase-2-Bug: Tailwinds `peer-checked:`/`peer-disabled:`/`peer-focus-visible:` kompilieren zu einem
  GESCHWISTER-Selektor (`.peer:checked ~ *`, nicht nur das direkt folgende Element), der ohne
  Container ALLE spaeteren Items einer Gruppe mitfaerbte, sobald irgendein frueheres Item geprueft
  war -- gefunden, indem diese Datei gegen einen echten `@tailwindcss/node`-Compile-Lauf gerendert
  wurde (kein `wp-env` in diesem Projekt, siehe Test-Tooling-Eintrag unten, deshalb ein
  Wegwerf-PHP-Harness mit gestubbten `esc_attr()`/`get_template_part()`/etc. fuer diese eine
  Verifikation). Gleiche Loesung wie tabs.php's eigener `data-slot="tabs-trigger-item"`-Wrapper
  (siehe dessen Kopfkommentar) und calendar.php's eigene Pro-Tag-`<td>`-Isolation -- beide bereits
  bestehende Beispiele fuer denselben "ein Container pro wiederholtem Peer/Label-Paar"-Regel, jetzt
  auch hier angewandt.
- **`!important`-Fix in `calendar.php`s Tag-Zellen (Bugfix/Kompatibilitaet).** `calendar.php`
  verschachtelt `toggle.php` fuer seine Tag-Zellen mit einem vollstaendig eigenen `class`-Override
  (siehe dessen Kopfkommentar) -- geschrieben, als `toggle.php` selbst noch keine eigenen Klassen
  berechnete. Jetzt, wo `default`/`size: 'default'` eigene Form-/Farbklassen mitbringen, kollidieren
  mehrere davon mit `calendar.php`s eigenen auf derselben CSS-Property (`rounded-xl` vs. `rounded-
full`, `h-10` vs. `h-9.5`, `font-normal` vs. `font-medium`, `text-foreground` vs.
  `text-muted-foreground`, `peer-checked:bg-henge-green` vs. `peer-checked:bg-grey-dark`, dazu
  `toggle.php`s neuer `peer-checked:shadow-xs` und -- weil `peer-checked:hover:` hoehere Spezifitaet
  als blosses `peer-checked:` traegt (siehe `tabs.php`s eigene Begruendung dazu) -- auch
  `peer-checked:hover:text-grey-dark-foreground`). PHP hat kein `tailwind-merge`/`cn()`, um das
  aufzuloesen (reine String-Konkatenation, siehe Eintrag weiter unten); `calendar.php`s eigene
  `$day_classes` markieren ihre bewusst ueberschreibenden Utilities deshalb jetzt `!important`
  (Tailwinds eigener `!`-Praefix, z. B. `!rounded-xl`), empirisch gegen einen echten
  `@tailwindcss/node`-Compile-Lauf verifiziert (`!`-Praefix erzeugt zuverlaessig `!important`,
  unabhaengig von der Reihenfolge, in der Tailwinds Klassen-Scanner beide Dateien antrifft).
  `calendar.js`s `DAY_LABEL_CLASSES` (dieselbe "Formel-fuer-Formel"-Duplizierung wie beim
  Grid-Mathe selbst, siehe `calendar.php`s eigener Kommentar) wurde identisch nachgezogen.
- **`page-component-showcase-toggle.php` neu angelegt** (analog zu den bestehenden
  Showcase-Seiten), inkl. drei bislang fehlender Lucide-Icons nachsynchronisiert (`bold`, `italic`,
  `underline`, per `sync-lucide-icons.sh`/`icons:lucide`-Skript aus `node_modules/lucide-static`
  kopiert) fuers "Icon, Mehrfachauswahl & deaktiviert"-Beispiel.

---

### `hengegroup_theme_floating_position_classes()`: geteilte Side/Align-Positionierungslogik aus popover.php extrahiert, in tooltip.php eingesetzt (2026-09-05)

popover.php's eigene `side`/`align` -> Tailwind-Klassen-Lookup (siehe dessen Eintrag direkt
unterhalb) und tooltip.php's Content-Box brauchten am Ende exakt dieselbe Logik -- ausgelagert nach
`inc/template-parts/helpers.php` als `hengegroup_theme_floating_position_classes(string $side,
string $align = 'center', int $gap_px = 10): string`, mit PHPUnit-Tests in
`tests/Unit/HelpersTest.php` (reine Logik, keine WP-Funktionsaufrufe, keine Brain-Monkey-Stubs
noetig). Details/Begruendung stehen im Helper's eigenen Doc-Kommentar sowie in popover.php's/
tooltip.php's eigenen Kopfkommentaren (Regel 12: kein Doppel-Text hier) -- dieser Eintrag haelt nur
fest, was nicht schon aus dem Diff folgt:

- **Nur die CONTENT-Box, nicht der Pfeil.** Ein Pfeil braucht andersartige, nicht nur andere
  Klassen (Border-Seiten, Overlap-Margins) und ist bei tooltip.php zusaetzlich zur Laufzeit reaktiv
  (tooltip.js flippt `data-side` nach einer Viewport-Kollision, der Pfeil bekommt dafuer KEIN
  JS-Override, muss also weiterhin per `group-data-[side=...]` live reagieren) -- popover.php hat
  ueberhaupt keinen Flip. Der Helper deckt bewusst nur die Content-Box ab; jede Komponente behaelt
  ihre eigene Pfeil-Logik.
- **Bei tooltip.php ist das jetzt nachweislich korrekt, nicht nur "genauso gut wie vorher":**
  `positionFloatingElement()` (utils/floating-position.js) ueberschreibt die Content-Box-Position
  IMMER per Inline-Style, sobald ein Tooltip tatsaechlich oeffnet (`top`/`left`/`right`/`bottom`/
  `transform`/`translate` werden alle explizit gesetzt, nicht nur `top`/`left`) -- die statischen
  Klassen zaehlen nur fuer den Resting-/Vor-JS-Zustand, und dafuer ist ausschliesslich die
  KONFIGURIERTE `side`/`align`-Kombination relevant, nie eine andere. Die alte
  `group-data-[side=...]`-Matrix deckte trotzdem alle vier Seiten ab -- unnoetig, exakt dieselbe
  Erkenntnis, die zur Vereinfachung von popover.php's eigener Logik gefuehrt hat (siehe dessen
  Eintrag). Nebeneffekt: die alten statischen Klassen ignorierten `align` komplett (dokumentiert als
  "JS-only") -- der Resting-Zustand beruecksichtigt `align` jetzt korrekt mit, ohne Mehraufwand,
  weil der Helper beide Werte ohnehin nimmt.
- **hover-card.php/dropdown-menu.php bewusst NICHT angefasst.** Beide haben zwar dieselbe
  `side`/`align`-Config, aber noch KEIN Phase-2-Styling ueberhaupt (hover-card.php: komplett
  Phase 1; dropdown-menu.php: nur `data-side`/`data-align`-Hooks, "actual floating placement is
  project-CSS" steht dort noch als offener Punkt im eigenen Kopfkommentar) -- fuer beide gibt es
  aktuell keine bestehende Positionierungslogik zum Ersetzen. Sobald sie ihr eigenes Phase-2-Styling
  bekommen, ist dieser Helper der Startpunkt statt eine dritte eigene Lookup-Tabelle.

---

### `popover.php` gestylt, `side`/`align` jetzt echtes Positionierungs-CSS, kein Datei-Split/Ordner-Umzug (2026-09-04)

Phase-2-Styling auf Basis der Claude-Design-Referenz "Hengegroup"
(https://claude.ai/code/artifact/527a7d35-e7c6-43b4-ab6f-9f85baf2b43c). Details/Klassen-Herleitung
stehen direkt in `popover.php`s eigenem Kopfkommentar (Regel 12: kein Doppel-Text hier) -- dieser
Eintrag haelt nur die Entscheidungen fest, die nicht schon aus dem Diff folgen:

- **`side`/`align` sind jetzt echtes Positionierungs-CSS, nicht mehr nur `data-*`-Hooks.** Anders
  als tooltip.php/hover-card.php haengt popover.php an keinem JS-Flip-Modul
  (`utils/floating-position.js`) -- die Referenz zeigt auch keinen Flip, nur vier feste `side`s --
  also reine `data-[side=...]`/`data-[align=...]`-Attribut-Selektor-Klassen direkt auf
  `popover-content`, genau das Rezept, das dropdown-menu.php's eigener (noch Phase-1-)
  Kopfkommentar bereits skizziert hatte ("actual floating placement is project-CSS"). Betrifft
  NICHT dropdown-menu.php selbst -- das bleibt eigenes, noch offenes Phase-2-Ticket.
- **Farben/Radius/Schatten reuse `--color-popover`/`-foreground`/`--color-border`** (tokens.css hat
  diese Rolle bereits vorbereitet) statt der Referenz-Literalwerte -- anders als tooltip.php, das
  mangels passendem Projekt-Token auf Tailwinds neutral-Skala auswich. `rounded-2xl` vereinheitlicht
  die Referenz' eigene Inkonsistenz (14px meiste Karten, 12px eine Demo-Karte) auf denselben Wert,
  den toast.php/calendar.php bereits fuer Karten-Oberflaechen nutzen.
- **`w-72`/`p-4`/`outline-hidden` sind shadcns eigene reale `PopoverContent`-Stock-Defaults**,
  bewusst statt einer der Referenz-eigenen Pro-Demo-Breiten/-Paddings (200-300px/16-18px) gewaehlt --
  kein erfundenes Vokabular, dieselbe "Generalisieren statt eine Demo-Zeichenkette
  festnageln"-Logik wie tooltip.php's `max-w-xs`.
- **Der Referenz-"Sortierung"/"Standort waehlen"-Look (8px-Padding, Vollbild-Hover-Zeilen) wurde
  NICHT als zweite Optik nachgebaut.** v1 hat nur einen festen Content-Padding-Wert (`p-4`); ein
  menuefoermiger Popover-Inhalt ist dropdown-menu.php's eigenes Item-Styling, verschachtelt als
  `content`-String hier -- keine eigene Popover-Variante. Dieselbe Grenze hat shadcns echtes
  Popover auch. Bewusst zurueckgestellt, nicht stillschweigend fallengelassen.
- **Pfeil (`[data-slot="popover-arrow"]`) neu**, 8px (nicht der Referenz eigene 10px -- gleiche
  Groesse wie tooltip.php's/hover-card.php's Pfeil, projektweite Konsistenz). Border-Paar pro `side`
  aus der Rotations-Geometrie hergeleitet (welche zwei der vier Vor-Rotation-Kanten nach
  `rotate-45` die sichtbare Spitze bilden) statt, wie die Referenz es tut, denselben
  `border-left`+`border-top`-Wert auf allen vier Seiten hart zu kodieren (dort nur auf der einen
  `side="bottom"`-Beispielkarte tatsaechlich korrekt) -- kostet nichts extra, sieht auf jeder Seite
  richtig aus statt nur einer. `align`s 16px-Pfeil-Versatz reused tooltip.php's exakten Wert/Technik.
- **Kein Datei-pro-Variante-Split, kein neuer `popover/`-Ordner.** Der Auftrag bat explizit darum,
  das bei Bedarf zu pruefen. Jede in der Referenz gezeigte "Variante" (Formular-Popup, Info-Popup,
  vier `side`s, rechtsbuendiger Filter via `align="end"`) ist Markup/Config, die die bestehende
  Einzeldatei ueber `content`/`side`/`align` bereits abbildet -- keine strukturell andere
  Komposition wie z. B. separator.php + separator-label.php, dieselbe Schlussfolgerung wie
  tooltip.php's eigener, fast identisch geformter Phase-2-Eintrag.
- **Das Referenz-"Auf dunklem Grund"-Beispiel wurde NICHT uebernommen**, aus demselben Grund wie
  bei jedem bisherigen Phase-2-Eintrag -- kein Alleingang ohne projektweite Dark-Strategie.
- **`page-component-showcase-popover.php` neu angelegt** (analog zu den bestehenden
  Showcase-Seiten).
- **Nachtrag (2026-09-05): echter Bug in der Showcase-Seite gefunden und behoben, nicht in
  popover.php selbst.** Jeder Trigger dort wurde per `get_template_part('template-parts/base/
button', ...)` gebaut -- button.php rendert aber immer ein echtes interaktives `<button>`/`<a>`,
  und das landete verschachtelt IN `popover.php`s eigenem `<summary>`, das laut popover.php's/
  dropdown-menu.php's eigenem Kopfkommentar bereits das eine interaktive Element sein muss
  ("trigger must not itself be/contain a focusable element"). Interaktiver Inhalt in interaktivem
  Inhalt ist ungueltiges HTML -- Chrome oeffnet ein `<details>` mit einem echten `<button>` in
  seinem `<summary>` dadurch gar nicht mehr per Klick, kein Styling-Problem, sondern ein
  Funktions-Totalausfall. Per Vorher/Nachher-Repro (statisches HTML mit dem echten gebauten CSS,
  reales Chrome, kein Artifact-Preview) verifiziert: identisches Markup nur mit `<span>` statt
  `<button>` im Trigger oeffnet klaglos. Fix bleibt in der Showcase-Datei: eine kleine lokale
  Closure (`$render_popover_trigger_look`) rendert button.php normal (wiederverwendet dessen
  Variant-/Size-Klassen-Logik unveraendert, keine duplizierten Tailwind-Strings) und tauscht danach
  nur das aeussere `<button>`-Tag gegen ein inertes `<span>`. Kein Aenderungsbedarf an popover.php
  selbst -- dessen eigener API-Vertrag (`trigger` = escaped Text/Icon, kein verschachteltes
  interaktives Element) war schon immer korrekt dokumentiert, nur beim Bauen der Demo-Seite nicht
  befolgt.

---

### `tooltip.php` gestylt, `align`-Config neu, kein Datei-Split/Ordner-Umzug (2026-09-04)

Phase-2-Styling auf Basis der Claude-Design-Referenz "Hengegroup"
(https://claude.ai/code/artifact/ee1a05dc-6403-4338-85f2-9e7531331931). Details/Klassen-Herleitung
stehen direkt in `tooltip.php`s eigenem Kopfkommentar (Regel 12: kein Doppel-Text hier) -- dieser
Eintrag haelt nur die Entscheidungen fest, die nicht schon aus dem Diff folgen:

- **Zwei echte Positionierungs-Bugs**, per manueller Nachpruefung (statisches Test-HTML mit dem
  echten gebauten CSS/JS, `getBoundingClientRect()`/`getComputedStyle()`-Vergleich) gefunden und in
  `utils/floating-position.js` behoben, weil beide dort und nicht in `tooltip.php` selbst sitzen
  (betrifft daher auch `hover-card.php`, sobald das sein eigenes Phase-2-Styling bekommt):
  einerseits liess `positionFloatingElement()` nach dem Setzen von `position:fixed`/`top`/`left`
  die Klassen-seitig weiterhin aktiven `bottom`/`right`/`transform`-Werte (aus den `side`-Klassen)
  stehen -- ein stehengebliebenes `top`+`bottom` bei `height:auto` STRECKT eine absolut/fixed
  positionierte Box statt sie am Inhalt auszurichten (CSS 2.1 10.6.4), ein stehengebliebenes
  `transform` verschiebt sie zusaetzlich; andererseits setzt Tailwind v4 `-translate-x-1/2`/
  `-translate-y-1/2` (fuer die Ruhe-Zentrierung) ueber die eigenstaendige CSS-`translate`-Property,
  NICHT ueber `transform` (CSS Individual Transform Properties -- `translate`/`rotate`/`scale`
  komponieren zusaetzlich zu `transform`, sind kein Alias dafuer) -- ein reines
  `style.transform = "none"` liess dieses `translate` unbemerkt weiterlaufen und verschob die
  bereits korrekt berechnete Position um genau die halbe Content-Breite/-Hoehe. Beide Faelle jetzt
  explizit ueberschrieben (`right`/`bottom`: `"auto"`, `transform`/`translate`: `"none"`), mit
  Begruendung direkt im Code.
- **`tooltip-trigger`/`hover-card-trigger`-Spans bekommen `inline-flex`** (funktionale, keine
  optische Klasse -- Regel 1 erlaubt das ausdruecklich): ein unstylter `<span>` um ein
  Inline-Block-Kind (z. B. einen button.php-Button) uebernimmt sonst die umgebende Zeilenbox-
  Baseline/Line-Height-"Geisterluecke", wodurch `getBoundingClientRect()` auf genau dem Element,
  das die JS-Positionsmathematik als Trigger misst, ein paar Pixel zu hoch zurueckkommt.
- **`tooltip.js`'s `GAP` war 8, nicht die Referenz-eigenen 10px** (die die CSS-Ruheklassen
  `calc(100%+10px)` bereits korrekt hatten) -- jetzt konsistent 10.

- **Kein Datei-pro-Variante-Split, kein neuer `tooltip/`-Ordner.** Der Auftrag bat explizit darum,
  das bei Bedarf zu pruefen. Jede in der Referenz gezeigte "Variante" (vier `side`-Werte, ein
  `align`-Beispiel, ein reiner Icon-Trigger, ein gepunktet unterstrichener "Hilfe"-Trigger in einer
  Tabellenzeile) ist Markup/Config, die die bestehende Einzeldatei entweder schon abbildet oder mit
  einer kleinen Ergaenzung (`align`, siehe unten) abbildet -- keine strukturell andere Komposition
  wie z. B. separator.php + separator-label.php. Regel 4 greift entsprechend nicht.
- **Farben: `rgb(30,29,28)`/`rgb(250,249,245)` der Referenz sind naeher an Tailwinds eigenem
  `neutral-900`/`neutral-50` als an diesem Projekts bestehenden `--color-foreground`/`-grey-dark`**
  (beide auf `neutral-800` gepinnt) -- direkt referenziert (`bg-neutral-900 text-neutral-50`) statt
  ein neues Token anzulegen, dieselbe "Tailwinds Skalen referenzieren, wenn noch kein Projekt-Token
  passt"-Konvention wie tokens.css sie fuer toast.php's `warning` (amber-600) bereits dokumentiert.
  Bewusst NICHT shadcns eigene `bg-primary text-primary-foreground`-Stock-Klassen fuer
  TooltipContent -- `primary` ist in diesem Projekt die henge-green Markenfarbe, die Referenz zeigt
  auf jedem einzelnen Beispiel unzweideutig eine neutrale Nahezu-Schwarz-Karte, nie gruen.
- **`align` (start | center | end) ist eine neue Config, kam in Phase 1 nicht vor.** Die Referenz
  zeigt mit ihrem Tabellenzeilen-Beispiel echtes `align="start"` (Tooltip startet buendig an der
  linken Trigger-Kante statt zu zentrieren) -- echtes shadcn/Radix-TooltipContent-Vokabular, das
  diese Datei bisher schlicht nicht implementiert hatte (Phase 1 deckte nur das funktionale
  Minimum ab), kein erfundenes Vokabular. hover-card.php hat exakt dasselbe start/center/end-
  Vokabular bereits, jetzt ueber denselben gemeinsamen `utils/floating-position.js`-`align`-Parameter
  verdrahtet -- tooltip.js reicht `align` jetzt genauso durch wie hover-card.js es bereits tut.
- **Fixe `width:250px`/`240px`** der Referenz-Langtext-Beispiele (Tabellenzeile, Dunkel-Abschnitt)
  wurde zu `max-w-xs` (320px) + `text-pretty` verallgemeinert statt eine der beiden Zahlen zu
  kopieren -- ein Wert, der erst bei tatsaechlich langem Inhalt greift, bedient sowohl die kurzen
  als auch das lange Beispiel mit einer Regel, statt jeden Tooltip auf eine fuer eine bestimmte
  Demo-Zeichenkette passende Breite festzunageln.
- **Pfeil (`[data-slot="tooltip-arrow"]`) neu**, ein 8px-Quadrat mit `rotate-45 bg-inherit` (erbt
  die Kartenfarbe statt sie ein zweites Mal zu setzen, dieselbe Wiederverwendung wie toast.php's
  `text-current`/`bg-current`). `side` steuert seine Position vollstaendig per CSS
  (`group-data-[side=...]:`); `align` NUR beim Pfeil zusaetzlich per CSS (fixer 16px-Versatz von der
  Karten-Kante, exakt die Referenz' eigener `left:16px`-Wert) -- bei der Karte selbst bleibt
  `align`s Kreuzachsen-Verschiebung reines JS (`positionFloatingElement()` kennt die echte
  Trigger-Geometrie bereits fuer `side`s Flip-Logik; eine parallele _statische_ CSS-Verschiebung
  pro `align`-Wert würde nur den ohnehin unsichtbaren Vor-JS-Ruhezustand betreffen und den
  kombinatorischen Klassen-Umfang unnoetig aufblaehen).
- **Das Referenz-"Auf dunklem Grund"-Beispiel wurde NICHT uebernommen**, aus demselben Grund wie
  bei jedem bisherigen Phase-2-Eintrag -- kein Alleingang ohne projektweite Dark-Strategie.
- **`page-component-showcase-tooltip.php` neu angelegt** (analog zu den bestehenden
  Showcase-Seiten), inkl. eines eigenen `align`-Abschnitts, da diese Config in Phase 1 noch nicht
  existierte.

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

### Phase-3-Block-Architektur: natives Block + Vite-gebautes Editor-Script statt ACF/`@wordpress/scripts` (2026-09-16)

Erster Gutenberg-Block des Themes (`hengegroup-theme/buehne`, ein Hero-/Bild-Slider), damit auch
erste konkrete Antwort auf die in `docs/to-do.md` offen gelassene Frage "wie wird
`block.json`/Block-Registrierung strukturell organisiert". Entscheidung (mit dem Auftraggeber
abgestimmt): natives Block statt ACF Block (keine neue Plugin-Abhaengigkeit, insb. kein ACF Pro
fuer ein Repeater-Feld) und ein eigenes Editor-Script statt eines reinen InnerBlocks-Aufbaus mit
Core-Bloecken (gefuehrte, feste Felder pro Folie statt freier Komposition, naeher am
Mockup-Ausgangspunkt).

- **Ordner-Konvention**: jeder Block lebt unter `template-parts/blocks/<name>/` (block.json +
  render.php) — dieselbe Konvention wie `template-parts/base/<name>/` fuer mehrteilige
  Base-Komponenten. `template-parts/blocks/` existierte bereits als leerer, undokumentierter
  Ordner (siehe `docs/to-do.md`); ist jetzt gefuellt und damit kein stiller Claim mehr.
- **`"render": "file:./render.php"`** (Block API "render"-Property, seit WP 6.1) statt manuellem
  `render_callback` in PHP — WordPress injiziert `$attributes`/`$content`/`$block` automatisch in
  den Scope der Datei. `inc/setup/theme-blocks.php` ruft dafuer nur noch
  `register_block_type(get_template_directory() . '/template-parts/blocks/<name>')` auf.
- **render.php komponiert ausschliesslich `template-parts/base/*`** (image/badge/typography/
  button/carousel-Familie) — keine neue Markup-/Styling-Logik, gleiche Regel-1-Tailwind-Klassen
  direkt im PHP wie button.php/badge.php, kein separates Block-Stylesheet.
- **Kein `@wordpress/scripts`/Webpack als zweite Toolchain.** Das Editor-Script
  (`assets/js/blocks/<name>/edit.jsx`) wird stattdessen ueber eine EIGENE Vite-Config-Datei
  (`vite.config.editor.js`, per `pnpm build:assets`'s zweitem `vite build --config ...`-Aufruf)
  als klassisches, nicht-Modul-IIFE gebaut: jeder `@wordpress/*`-Import wird per
  `rollupOptions.external` + `output.globals` gegen WordPress' eigene `wp.*`-Globals aufgeloest
  (`@wordpress/element` -> `wp.element` usw.) statt eine zweite React-Instanz zu bundeln — Rollup/
  Rolldown loest dabei auch mehrteilige Global-Pfade wie `wp.blockEditor` korrekt auf (im gebauten
  Bundle verifiziert, IIFE-Aufruf am Dateiende mit genau dieser Argumentliste). JSX kompiliert
  ueber esbuilds klassischen Pragma-Modus (`esbuild.jsx: 'transform'` + `jsxFactory: 'el'`,
  WICHTIG: Vite 8s Rolldown-Standard ist der automatische React-17-Transform gegen
  `react/jsx-runtime`, das muss explizit auf klassisch zurueckgestellt werden, sonst schlaegt der
  Build fehl) gegen einen `createElement as el`-Import aus `@wordpress/element`. Ein einzelnes
  `vite.config.js` mit einem Array aus zwei
  Build-Configs wurde probiert und verworfen — Vite 8s CLI (`vite build`) akzeptiert dort nur ein
  einzelnes Objekt, keine zweite Config im selben Lauf; deshalb zwei Config-Dateien und zwei
  `vite build`-Aufrufe in `pnpm build:assets` (`emptyOutDir: false` in `vite.config.editor.js`,
  da beide `dist/assets` teilen und nur der erste Lauf leeren darf). Kein `.asset.php` mit
  automatisch extrahierten Script-Dependencies (das ist `@wordpress/scripts`-spezifisch) — die
  wp-blocks/wp-element/...-Dependency-Liste steht deshalb von Hand in
  `inc/setup/theme-blocks.php`s `wp_register_script()`-Aufruf.
- **Editor-Vorschau via `@wordpress/server-side-render`** (Core-Paket, immer als
  `wp-server-side-render`-Handle verfuegbar) statt eines zweiten, in JS nachgebauten Markups —
  render.php bleibt die einzige Stelle, die tatsaechlich Markup/Tailwind-Klassen erzeugt. Setzt
  voraus, dass der Editor-Canvas Tailwind-korrekt rendert: `inc/setup/theme-setup.php` ruft
  deshalb jetzt zusaetzlich `add_theme_support('editor-styles')` + `add_editor_style()` (ueber den
  neuen Helper `hengegroup_theme_get_vite_style_uri()` in `theme-assets.php`) auf und laedt damit
  dasselbe kompilierte `app.css` wie das Frontend in den iframe-isolierten Editor-Canvas — vormals
  bewusst zurueckgestellt (siehe `docs/to-do.md`), jetzt sinnvoll, seit es mit den
  Phase-2-gestylten Base-Komponenten echtes CSS zum Laden gibt.
- **Akzentfarbe je Folie ist dasselbe `henge-green`/`henge-blue`/`henge-grey`-Vokabular wie
  `button.php`/`badge.php`'s `variant`**, keine freie Farbauswahl — Konsistenz mit dem bestehenden
  Marken-Farbsystem statt eines eigenen Farbwaehlers.
- **Dot-Navigation ist eigenes, Block-spezifisches Markup**, kein weiterer
  `carousel-*.php`-Aufruf — `carousel.php`s eigener Kopfkommentar dokumentiert Dots explizit als
  nicht Teil der Komponente (nur optionale Previous-/Next-Buttons). Autoplay + Dot-Klick-Steuerung
  laufen ueber ein eigenes JS-Enhancement-Modul (`assets/js/template-parts/blocks/buehne.js`),
  `assets/js/template-parts/base/carousel.js` selbst bleibt unveraendert (siehe dessen eigenen
  Kopfkommentar-Anspruch, dass Base-Komponenten fuer Phase 2/3 nicht nochmal angefasst werden
  muessen).
- **Verhalten bewusst an `carousel.php`s echtem CSS-Scroll-Snap-Verhalten ausgerichtet statt am
  Opacity-Crossfade des urspruenglichen Mockups**: `carousel-item.php`s `basis: '100%'` gibt volle
  Folienbreite, das native Scroll-Snap-Verhalten (Swipe/Wheel/Tastatur) wird 1:1 uebernommen. Eine
  Crossfade-Animation haette entweder die Base-Komponente aendern (nicht erlaubt fuer diesen
  Auftrag) oder ihr dokumentiertes Scroll-Snap-Verhalten duplizieren/umgehen muessen.
  **Ueberholt seit 2026-09-22** -- auf explizite Nachfrage doch auf Crossfade umgestellt, siehe
  "Buehne: Opacity-Crossfade statt Scroll-Snap" oben.

### SVG-Upload-Support: Admin-only + `enshrined/svg-sanitize` (2026-09-17)

WordPress erlaubt `image/svg+xml` in der Media Library standardmaessig nicht -- eine SVG-Datei
kann `<script>`/Event-Handler-Attribute/externe Referenzen enthalten (gespeichertes XSS). Auf
explizite Nachfrage (zwei Optionen zur Wahl gestellt: nur Rollen-Einschraenkung vs. zusaetzlich
echtes Sanitizing) fiel die Entscheidung auf die staerkere Variante: SVG-Upload nur fuer Nutzer
mit `manage_options` **und** serverseitiges Sanitizing jeder hochgeladenen Datei ueber
`enshrined/svg-sanitize`, nicht die Rollen-Einschraenkung allein. Siehe
`inc/setup/theme-svg-support.php`s eigenen Kopfkommentar fuer die Hook-Details
(`upload_mimes`/`wp_check_filetype_and_ext`/`wp_handle_upload_prefilter`/
`wp_generate_attachment_metadata`).

- **Fail-closed statt fail-open**: `upload_mimes` schaltet `svg` nur frei, wenn
  `class_exists(\enshrined\svgSanitize\Sanitizer::class)` true ist -- fehlt die Bibliothek (z. B.
  `composer install` vergessen, `vendor/` nicht mitgeliefert), bleibt SVG fuer NIEMANDEN erlaubt,
  statt eine ungesanitizte Datei durchzulassen. `wp_handle_upload_prefilter` prueft
  Capability/Sanitizing-Erfolg ein zweites Mal (defense in depth) und setzt bei Fehlschlag
  `$file['error']`, statt die Originaldatei durchzulassen.
- **Capability ist filterbar** (`hengegroup_theme_svg_upload_capability`, Default
  `manage_options`) statt hart codiert -- Escape-Hatch fuers Projekt-Theme, siehe
  `docs/how-to.md` "SVG-Upload-Berechtigung anpassen".
- **`enshrined/svg-sanitize` ist die ERSTE echte Laufzeit-Composer-Abhaengigkeit dieses Themes**
  (`composer.json`s `require`, nicht `require-dev`) -- bisher war Composer laut `composer.json`s
  eigener (jetzt aktualisierter) Beschreibung reines Dev-Tooling (WPCS/PHPUnit, CLAUDE.md Regel
  11), nie zur Laufzeit geladen. `functions.php` laedt `vendor/autoload.php` deshalb jetzt bedingt
  (`file_exists()`-Guard).
- **`dist/vendor/` bekommt einen production-only Composer-Stand** (kein phpcs/PHPUnit/
  wordpress-stubs/brain-monkey): das Theme wird per FTP als fertiges Bundle deployt (`dist/`,
  siehe `package.json`s `deploy`/`deploy-changed`), keine Server-seitige `composer install`.
  `scripts/build.sh`/`build.ps1` swappen dafuer das REPO-EIGENE `vendor/` kurz auf
  `composer install --no-dev --optimize-autoloader`, kopieren es nach `dist/vendor/` und stellen
  danach den Dev-Stand wieder her (`trap ... EXIT` in `build.sh`, `try`/`finally` in `build.ps1`,
  jeweils in einer eigenen Subshell/einem eigenen `Push-Location`-Block) -- laeuft auch, wenn ein
  spaeterer Build-Schritt fehlschlaegt, damit `composer lint`/`composer test` danach lokal weiter
  funktionieren. Keine CI-Anpassung noetig: `.github/workflows/ci.yml` ruft nirgends
  `pnpm build`/die Build-Scripts auf, nur ein normales `composer install` (mit Dev-Deps) fuer
  Lint/Test.
- **`hengegroup_theme_get_svg_dimensions()`** (reine Funktion, kein WP-Aufruf, per PHPUnit direkt
  getestet statt Brain Monkey) liefert `width`/`height` aus dem `<svg>`-Root (Attribute, sonst
  `viewBox`-Fallback) fuer `wp_generate_attachment_metadata` -- ohne das haette
  `wp_get_attachment_image_src()`/damit `template-parts/base/image.php`'s
  `attachment_id`-Aufloesung kein `width`/`height` fuer SVG-Attachments (WordPresss eigene
  `getimagesize()`-basierte Metadaten-Generierung versteht kein SVG).
