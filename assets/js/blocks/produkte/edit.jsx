// Editor UI for template-parts/blocks/produkte/block.json. Same Vite/@wordpress-globals
// architecture as blocks/buehne/edit.jsx and blocks/ueberschrift-text/edit.jsx (see buehne's header
// comment + docs/entscheidungen.md's "Phase-3-Block-Architektur") -- built via
// vite.config.editor-produkte.js, which shares its actual build config with the other blocks
// through vite.config.editor.factory.js.
//
// Ueberschrift/Text/Button sind ALLE DIREKT im Editor-Canvas editierbar (explizite Nachfrage
// 2026-09-23), keine Sidebar-Felder fuer Inhalte mehr -- die Sidebar behaelt nur noch die
// Produktauswahl (Konfiguration des Produktrasters, kein Text-Inhalt). Ueberschrift/Text
// laufen als natives `RichText` (gleiches Muster wie ueberschrift-text/edit.jsx, siehe dessen
// Kopfkommentar fuer die Begruendung/den toRichTextValue()/fromRichTextValue()-Rundlauf). Das
// semantische Element der Ueberschrift (`headingTag`, h1-h6 oder p) ist ueber ein
// `ToolbarDropdownMenu` in der Block-Toolbar waehlbar (explizite Nachfrage 2026-09-23: Canvas statt
// Sidebar) -- typography.php's `tag`/`variant` sind bewusst unabhaengige Achsen (siehe dessen
// Kopfkommentar), die visuelle Groesse (`headline-base`) bleibt deshalb immer gleich, nur `tagName`
// von `RichText` folgt `headingTag` direkt. Bewusst NICHT an den Fokus des Ueberschrift-`RichText`
// gekoppelt (kein Ein-/Ausblenden per `onFocus`/`onBlur` der Ueberschrift selbst, obwohl das noch
// naeher am Wunsch "direkt an der Ueberschrift beim Anklicken" waere) -- ein Klick auf ein
// Toolbar-Control loest zuerst `onBlur` des `RichText` aus, BEVOR der Klick selbst verarbeitet wird;
// eine derart fokus-gekoppelte Toolbar wuerde deshalb verschwinden, bevor man sie anklicken kann
// (bekannte Gutenberg-Falle). Stattdessen bleibt die gesamte Toolbar (Ueberschrift-Element +
// Button-Link) an die BLOCK-Selektion gekoppelt, wie bei `BlockControls` ueblich -- solange der
// Produkte-Block ausgewaehlt ist, nicht nur wenn die Ueberschrift selbst fokussiert ist. Der
// Button-TEXT ist ebenfalls `RichText`
// (button.php's `grey-light`/`lg`-Klassen 1:1 gespiegelt, siehe
// BUTTON_PREVIEW_CLASSNAME unten), der Button-LINK laeuft ueber `@wordpress/block-editor`s eigene
// `LinkControl`-Komponente in einem `Popover` -- exakt dasselbe UX-Muster wie Cores eigener
// `core/button`-Block (Toolbar-Link-Icon oeffnet den Popover ueber dem Button, `LinkControl` sucht
// selbst ueber die WP-REST-Suche nach Seiten/Beitraegen, kein eigener `useSelect()`-Picker noetig
// wie noch bei buehne/edit.jsx's PageLinkControl). `isEditingButtonUrl`+`buttonRef` sind reiner
// UI-State fuer diesen Popover, kein Block-Attribut. Der Popover schliesst automatisch, wenn der
// Block die Selektion verliert (`useEffect` unten) -- sonst wuerde er beim naechsten Selektieren
// sofort wieder aufklappen, ohne dass jemand erneut auf das Link-Icon geklickt haette.
//
// Der umgebende Markup (section/wrapper/col-span/max-w-Klassen) spiegelt render.php's Struktur 1:1,
// damit die Canvas bereits wie das Frontend aussieht.
//
// Toolbar (explizite Nachfrage 2026-09-23): "Ausrichten" ist entfernt -- `supports.align` in
// block.json (vorher `["wide", "full"]`) war der einzige Grund fuer diese Kontrolle; block.json
// hat deshalb weder `supports.align` noch ein eigenes `align`-Attribut mehr (render.php hat
// `$attributes['align']` ohnehin nie gelesen). Damit die Editor-Canvas trotzdem nicht auf
// theme.json's schmale `contentSize`-Spalte zusammenschrumpft (derselbe Befund wie bei
// ueberschrift-text/render.php, nur dort noch ueber `supports.align` geloest), setzt `blockProps`
// unten `alignfull` HARDCODIERT als Klasse -- rein visuell/CSS, ohne die zugehoerige Toolbar-UI.
// "Umwandeln in" (der Block-Switcher/Transform-Dropdown) ist NICHT entfernt: Gutenberg zeigt dieses
// Icon fuer praktisch jeden entfernbaren Block unconditional (Core-Chrome aus
// `@wordpress/block-editor`s `BlockSwitcher`), es gibt keinen dokumentierten `supports`-Schalter
// dafuer -- ein Entfernen waere nur ueber eine block-spezifische CSS-/JS-Umgehung moeglich (z. B.
// per `editor.BlockListBlock`-Filter + CSS-Selektor), was hier bewusst NICHT gemacht wurde
// (Regel-1-Ausnahmen sind fuer Tailwind-Styling-Faelle gedacht, nicht fuer das Verstecken von
// Core-Editor-Chrome ueber einen fragilen Hack ohne offizielle API).
//
// Diese Version ersetzt eine fruehere, reine `ServerSideRender`-basierte Vorschau (wie
// buehne/edit.jsx): ein `ServerSideRender`-Aufruf gegen DIESEN Block wuerde render.php's eigene
// Ueberschrift/Text/Button ein zweites Mal statisch rendern, direkt neben den editierbaren Feldern
// -- doppelter Inhalt. Der Produktraster-Teil ist trotzdem weiterhin echte Live-Vorschau
// (WooCommerce-Daten, unveraendertes content-product.php), nur ausgelagert auf einen zweiten,
// inserter-versteckten Block `hengegroup-theme/produkte-raster`
// (template-parts/blocks/produkte-raster/render.php), den `ServerSideRender` unten gezielt NUR mit
// `productIds` aufruft -- siehe dessen Kopfkommentar fuer die volle Begruendung.
//
// Produktauswahl (explizite Nachfrage 2026-09-23): statt einer Kategorie+Anzahl-Filterung waehlen
// Redakteure die Produkte jetzt EINZELN und in beliebiger Reihenfolge/Anzahl aus den bestehenden
// WooCommerce-Produkten aus (`productIds`, Array von Produkt-IDs in Anzeigereihenfolge --
// render.php/produkte-raster/render.php geben es 1:1 als `post__in`+`orderby: post__in` an
// `WP_Query` weiter, siehe inc/template-parts/woocommerce-product-card.php's
// `hengegroup_theme_render_produkte_grid()`). `ProductPicker` unten laedt dafuer ALLE
// veroeffentlichten Produkte ueber den `core`-Datenstore (`getEntityRecords('postType', 'product',
// {per_page: -1, ...})`) -- derselbe "alles auf einmal laden, lokal filtern"-Ansatz wie die
// vorherige `ProductCategoryControl` fuer `product_cat`-Terms, nur jetzt gegen Produkte selbst statt
// Kategorien. Eine `ComboboxControl` durchsucht die noch nicht ausgewaehlten Produkte und haengt die
// gewaehlte ID an `productIds` an; darunter eine Liste der bereits gewaehlten Produkte mit
// Verschieben/Entfernen -- exakt dasselbe `PanelRow`+Pfeil-Icons-Muster wie buehne/edit.jsx's
// Folien-Liste (`SlideListItem`, siehe dessen Kopfkommentar), hier ohne "Bearbeiten"-Button, weil es
// pro Produkt keine eigenen Felder gibt (nur die Reihenfolge selbst ist editierbar).
//
// `ServerSideRender`s eigener Wrapper-`<div>` (kein `display: contents`) unterbricht sonst die
// `display: contents`-Kette zwischen `.wrapper`s `grid-cols-12` unten und den Produktkarten-`<li>`s
// des `produkte-raster`-Blocks -- die Karten erschienen im Editor-Canvas dadurch gestapelt statt im
// Raster (siehe produkte-raster/render.php's frueherer Kopfkommentar). Fix: der Tailwind-Selektor
// `[&>div]:contents` auf dem `ServerSideRender`-umschliessenden `<div>` unten macht auch DESSEN
// direktes `<div>`-Kind (den SSR-Wrapper) zu `display: contents`, die Kette bis zu den `<li>`s bleibt
// dadurch durchgaengig.
import { createElement as el, Fragment, useEffect, useRef, useState } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import {
    BlockControls,
    InspectorControls,
    LinkControl,
    RichText,
    useBlockProps,
} from "@wordpress/block-editor";
import {
    Button,
    ComboboxControl,
    PanelBody,
    PanelRow,
    Popover,
    ToolbarButton,
    ToolbarDropdownMenu,
    ToolbarGroup,
    __experimentalVStack as VStack,
} from "@wordpress/components";
import { useSelect } from "@wordpress/data";
import { decodeEntities } from "@wordpress/html-entities";
import { __, sprintf } from "@wordpress/i18n";
import { create, toHTMLString } from "@wordpress/rich-text";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "../../../../template-parts/blocks/produkte/block.json";
import produkteRasterMetadata from "../../../../template-parts/blocks/produkte-raster/block.json";

// Gleicher Rundlauf wie ueberschrift-text/edit.jsx (siehe dessen Kopfkommentar): `heading`/`text`/
// `buttonText` bleiben PLAIN strings (render.php reicht sie durch typography.php's/button.php's
// eigenes esc_html(), keine Formatierung), `create()`/`toHTMLString()` sorgt nur fuer korrektes
// Entity-Escaping im `RichText`-Editierbereich selbst.
function toRichTextValue(text) {
    return toHTMLString({ value: create({ text }) });
}

function fromRichTextValue(html) {
    return create({ html }).text;
}

// `tag`/`variant` sind bei typography.php bewusst unabhaengige Achsen (siehe dessen Kopfkommentar)
// -- die Ueberschrift bleibt visuell immer `headline-base` (siehe render.php), nur das SEMANTISCHE
// Element ist hier waehlbar. `p` steht mit in der Liste (explizite Nachfrage), obwohl es visuell
// identisch zu h1-h6 aussieht -- reine Semantik-/Outline-Entscheidung der Redaktion.
const HEADING_TAG_OPTIONS = [
    { title: __("Überschrift 1 (H1)", "hengegroup-theme"), value: "h1" },
    { title: __("Überschrift 2 (H2)", "hengegroup-theme"), value: "h2" },
    { title: __("Überschrift 3 (H3)", "hengegroup-theme"), value: "h3" },
    { title: __("Überschrift 4 (H4)", "hengegroup-theme"), value: "h4" },
    { title: __("Überschrift 5 (H5)", "hengegroup-theme"), value: "h5" },
    { title: __("Überschrift 6 (H6)", "hengegroup-theme"), value: "h6" },
    { title: __("Absatz (P)", "hengegroup-theme"), value: "p" },
];

// Siehe Kopfkommentar: laedt ALLE veroeffentlichten Produkte einmalig, danach rein lokale
// Combobox-Filterung/Zuordnung -- kein weiterer REST-Request pro Tastenanschlag.
function ProductPicker({ productIds, onChange }) {
    const products = useSelect(
        (select) =>
            select("core").getEntityRecords("postType", "product", {
                per_page: -1,
                status: "publish",
                orderby: "title",
                order: "asc",
            }),
        []
    );

    // Zuruecksetzen nach jeder Auswahl (siehe `addProduct` unten) -- die Combobox selbst speichert
    // keine der ausgewaehlten Produkt-IDs dauerhaft, nur `productIds` tut das.
    const [comboboxValue, setComboboxValue] = useState("");

    const productsById = new Map((products || []).map((product) => [product.id, product]));

    const addOptions = (products || [])
        .filter((product) => !productIds.includes(product.id))
        .map((product) => ({
            value: String(product.id),
            label: decodeEntities(product.title.rendered) || String(product.id),
        }));

    function productLabel(productId) {
        const product = productsById.get(productId);

        return product
            ? decodeEntities(product.title.rendered) || String(productId)
            : sprintf(__("Produkt #%d", "hengegroup-theme"), productId);
    }

    function addProduct(value) {
        const productId = parseInt(value, 10);

        if (!Number.isNaN(productId) && !productIds.includes(productId)) {
            onChange([...productIds, productId]);
        }

        setComboboxValue("");
    }

    function removeProduct(index) {
        const nextProductIds = productIds.slice();
        nextProductIds.splice(index, 1);
        onChange(nextProductIds);
    }

    function moveProduct(index, direction) {
        const targetIndex = index + direction;

        if (targetIndex < 0 || targetIndex >= productIds.length) {
            return;
        }

        const nextProductIds = productIds.slice();
        const [moved] = nextProductIds.splice(index, 1);
        nextProductIds.splice(targetIndex, 0, moved);
        onChange(nextProductIds);
    }

    return (
        <VStack spacing={3}>
            <ComboboxControl
                __nextHasNoMarginBottom
                label={__("Produkt hinzufügen", "hengegroup-theme")}
                value={comboboxValue}
                onChange={addProduct}
                options={addOptions}
            />
            {productIds.map((productId, index) => (
                <PanelRow key={productId}>
                    {productLabel(productId)}
                    <Button
                        icon="arrow-up-alt2"
                        label={__("Nach oben", "hengegroup-theme")}
                        onClick={() => moveProduct(index, -1)}
                        disabled={index === 0}
                    />
                    <Button
                        icon="arrow-down-alt2"
                        label={__("Nach unten", "hengegroup-theme")}
                        onClick={() => moveProduct(index, 1)}
                        disabled={index === productIds.length - 1}
                    />
                    <Button
                        icon="trash"
                        isDestructive
                        label={__("Entfernen", "hengegroup-theme")}
                        onClick={() => removeProduct(index)}
                    />
                </PanelRow>
            ))}
        </VStack>
    );
}

// 1:1 aus template-parts/base/button.php's `variant: 'grey-light'`/`size: 'lg'`-Klassen kopiert
// (siehe dessen Kopfkommentar) -- auf ein RichText-`<span>` angewendet, kein <button>/<a> mit
// eigenem Klickverhalten im Editor (das Link-Ziel wird ueber den Toolbar-Popover gepflegt, siehe
// Kopfkommentar).
const BUTTON_PREVIEW_CLASSNAME =
    "inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-full px-7 text-lg font-medium whitespace-nowrap !bg-grey-light !text-grey-light-foreground";

function Edit({ attributes, setAttributes, isSelected }) {
    const { heading, headingTag, text, buttonText, buttonUrl, productIds } = attributes;
    // `alignfull` HARDCODIERT statt ueber `supports.align`/das (entfernte) `align`-Attribut: der
    // Block ist immer volle Breite, ohne dass die "Ausrichten"-Toolbar dafuer eine UI braucht
    // (explizite Nachfrage 2026-09-23, siehe Kopfkommentar). `alignfull` bleibt trotzdem noetig --
    // rein als CSS-Klasse, unabhaengig davon, WIE sie zustande kommt --, weil theme.json's
    // `settings.layout.contentSize`/`wideSize` jeden Block ohne diese Klasse im Editor-Iframe auf
    // die schmale Standard-Spalte begrenzt (gleicher Befund wie bei ueberschrift-text/render.php,
    // nur dort noch ueber `supports.align` erreicht).
    const blockProps = useBlockProps({
        className: "alignfull bg-grey-dark py-16 md:py-24 lg:py-35",
    });
    const buttonRef = useRef();
    const [isEditingButtonUrl, setIsEditingButtonUrl] = useState(false);

    // Popover schliessen, sobald der Block die Selektion verliert -- ohne das bliebe
    // `isEditingButtonUrl` beim naechsten Selektieren stehen und der Popover ploppte sofort wieder
    // auf, ohne erneuten Klick auf das Link-Icon.
    useEffect(() => {
        if (!isSelected) {
            setIsEditingButtonUrl(false);
        }
    }, [isSelected]);

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <ToolbarDropdownMenu
                        icon="heading"
                        label={__("Überschrift-Element ändern", "hengegroup-theme")}
                        controls={HEADING_TAG_OPTIONS.map((option) => ({
                            title: option.title,
                            isActive: option.value === headingTag,
                            onClick: () => setAttributes({ headingTag: option.value }),
                        }))}
                    />
                </ToolbarGroup>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="admin-links"
                        label={__("Button-Link bearbeiten", "hengegroup-theme")}
                        onClick={() => setIsEditingButtonUrl(true)}
                        isPressed={isEditingButtonUrl}
                    />
                </ToolbarGroup>
            </BlockControls>
            <InspectorControls>
                <PanelBody title={__("Produkte", "hengegroup-theme")} initialOpen>
                    <ProductPicker
                        productIds={productIds}
                        onChange={(nextProductIds) => setAttributes({ productIds: nextProductIds })}
                    />
                </PanelBody>
            </InspectorControls>
            <section {...blockProps}>
                <div className="wrapper gap-y-10">
                    <div className="col-span-12 max-w-2xl">
                        <RichText
                            tagName={headingTag}
                            className="text-grey-light mb-4 text-5xl leading-tight font-semibold"
                            value={toRichTextValue(heading)}
                            onChange={(html) => setAttributes({ heading: fromRichTextValue(html) })}
                            placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                            disableLineBreaks
                        />
                        <RichText
                            tagName="p"
                            className={`text-grey-light text-lg leading-normal ${
                                buttonText.trim() !== "" ? "mb-8" : ""
                            }`}
                            value={toRichTextValue(text)}
                            onChange={(html) => setAttributes({ text: fromRichTextValue(html) })}
                            placeholder={__("Text eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                        />
                        <span ref={buttonRef} className="relative inline-block">
                            <RichText
                                tagName="span"
                                className={BUTTON_PREVIEW_CLASSNAME}
                                value={toRichTextValue(buttonText)}
                                onChange={(html) =>
                                    setAttributes({ buttonText: fromRichTextValue(html) })
                                }
                                placeholder={__("Button-Text eingeben…", "hengegroup-theme")}
                                allowedFormats={[]}
                                disableLineBreaks
                            />
                            {isEditingButtonUrl && isSelected && (
                                <Popover
                                    placement="bottom-start"
                                    anchor={buttonRef.current}
                                    onClose={() => setIsEditingButtonUrl(false)}
                                >
                                    <LinkControl
                                        value={{ url: buttonUrl }}
                                        onChange={(nextValue) =>
                                            setAttributes({ buttonUrl: nextValue.url || "" })
                                        }
                                        settings={[]}
                                    />
                                </Popover>
                            )}
                        </span>
                    </div>
                    {productIds.length === 0 ? (
                        <p className="text-grey-light col-span-12">
                            {__(
                                "Noch keine Produkte ausgewählt — im rechten Bereich Produkte hinzufügen.",
                                "hengegroup-theme"
                            )}
                        </p>
                    ) : (
                        <div className="contents [&>div]:contents">
                            <ServerSideRender
                                block="hengegroup-theme/produkte-raster"
                                attributes={{ productIds }}
                            />
                        </div>
                    )}
                </div>
            </section>
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    save: () => null,
});

// `hengegroup-theme/produkte-raster` (template-parts/blocks/produkte-raster/) ist serverseitig
// bereits via register_block_type() registriert (inc/setup/theme-blocks.php) -- das reicht fuer
// `ServerSideRender`s eigentlichen REST-Aufruf, aber NICHT fuer `ServerSideRender` selbst:
// `@wordpress/server-side-render` prueft den uebergebenen Blocknamen zuerst gegen die
// CLIENTSEITIGE Block-Registry (`wp.blocks.getBlockType()`) und wirft "Block type ... is not
// registered.", wenn dort nichts gefunden wird -- eine rein serverseitige Registrierung allein
// fuehrt genau zu diesem Fehler (siehe docs/entscheidungen.md's Nachtrag zum Produkte-Block-
// Eintrag). Deshalb hier eine minimale Client-Registrierung, huckepack in DIESEM Bundle statt
// einem eigenen `vite.config.editor-produkte-raster.js`-Eintrag -- produkte-raster braucht kein
// eigenes `edit`-UI (nie ueber den Inserter erreichbar, `"supports": {"inserter": false}` in
// dessen block.json), nur eine dem Client-Registry bekannte Definition; `edit`/`save` sind daher
// bewusst No-Ops.
registerBlockType(produkteRasterMetadata, {
    edit: () => null,
    save: () => null,
});
