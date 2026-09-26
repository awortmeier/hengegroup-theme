// Editor UI for template-parts/blocks/ueberschrift-text/block.json. Same Vite/@wordpress-globals
// architecture as blocks/buehne/edit.jsx (see that file's header comment + docs/entscheidungen.md's
// "Phase-3-Block-Architektur") -- built via vite.config.editor-ueberschrift-text.js, which shares
// its actual build config with vite.config.editor.js through vite.config.editor.factory.js instead
// of duplicating the @wordpress-globals list per block.
//
// Heading/text are edited DIRECTLY in the canvas via `RichText` (like core/heading, core/paragraph),
// not as sidebar TextControl/TextareaControl fields -- explicit request 2026-09-22, so editors type
// content where it visually appears instead of blind-editing a disconnected sidebar field. The
// surrounding markup below (section/wrapper/col-span/align classes) mirrors render.php's structure
// so the canvas already looks like the frontend while typing; `ServerSideRender` is gone because the
// canvas itself IS the live preview now (see docs/entscheidungen.md's `ueberschrift-text`-Vite-Build
// entry for why it existed for buehne's still-SSR-based preview).
//
// Ueberschrift-Element (`headingTag`, h1-h6 oder p, Default `p`) ist ueber ein
// `ToolbarDropdownMenu` in der Block-Toolbar waehlbar statt Sidebar (explizite Nachfrage
// 2026-09-23, gleiches Muster wie blocks/produkte/edit.jsx -- siehe dessen Kopfkommentar fuer die
// Begruendung/den identischen Aufbau). typography.php's `tag`/`variant` sind bewusst unabhaengige
// Achsen (siehe dessen Kopfkommentar), die visuelle Groesse (`headline-base`) bleibt deshalb immer
// gleich, nur `tagName` von `RichText` folgt `headingTag` direkt.
//
// Ausrichtung/Breite sind EBENFALLS aus der Sidebar in die Toolbar verlagert (explizite Nachfrage
// 2026-09-23). Ausrichtung (`textAlign`, nur 2 Werte) ist als zwei einzelne `ToolbarButton`s in
// EINER `ToolbarGroup` mit `isPressed` abgebildet. Breite (`containerWidth`) lief zunaechst
// genauso, ist auf erneute Nachfrage (2026-09-23) aber ein `ToolbarDropdownMenu` geworden --
// gleicher Aufbau wie beim Ueberschrift-Element oben (`CONTAINER_WIDTH_OPTIONS` statt
// `HEADING_TAG_OPTIONS`), Labels "Standard"/"Schmal" statt der vorherigen Button-Beschriftungen.
// Akzent-Woerter war kurzzeitig ebenfalls in einem Toolbar-Popover, ist aber auf erneute Nachfrage
// (2026-09-23) wieder zurueck in die Sidebar (`InspectorControls`/`PanelBody`) verlagert -- als
// einziges verbleibendes Freitextfeld bleibt es dort besser editierbar als in einem
// Toolbar-Popover.
//
// "Ausrichten" (Gutenberg's eigenes `supports.align`) ist aus der Toolbar entfernt (explizite
// Nachfrage 2026-09-23, wie bei blocks/produkte/edit.jsx) -- zu unterscheiden von der obigen,
// blockeigenen "Ausrichtung"(`textAlign`)-Toolbar-Gruppe: `supports.align`/das `align`-Attribut
// sind komplett aus block.json entfernt (render.php hat `$attributes['align']` ohnehin nie
// gelesen). Damit die Editor-Canvas trotzdem nicht auf theme.json's schmale `contentSize`-Spalte
// zusammenschrumpft -- noetig, damit `containerWidth` (`.wrapper` vs. `.wrapper-small`) im Editor
// ueberhaupt sichtbar unterscheidbar bleibt, siehe render.php's Kopfkommentar --, setzt
// `blockProps` unten `alignfull` HARDCODIERT als Klasse, rein visuell/CSS, ohne zugehoerige
// Toolbar-UI.
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import { BlockControls, InspectorControls, RichText, useBlockProps } from "@wordpress/block-editor";
import {
    Notice,
    PanelBody,
    TextControl,
    ToolbarButton,
    ToolbarDropdownMenu,
    ToolbarGroup,
} from "@wordpress/components";
import { __, sprintf } from "@wordpress/i18n";
import { create, toHTMLString } from "@wordpress/rich-text";
import metadata from "../../../../template-parts/blocks/ueberschrift-text/block.json";

// Gleiche Liste wie blocks/produkte/edit.jsx's HEADING_TAG_OPTIONS (siehe dessen Kopfkommentar) --
// hier dupliziert statt geteilt, gleiche "kein gemeinsames Editor-Helper-Modul"-Konvention wie im
// Rest dieses Ordners (jedes edit.jsx ist ein eigenstaendiger Vite-Entry).
const HEADING_TAG_OPTIONS = [
    { title: __("Überschrift 1 (H1)", "hengegroup-theme"), value: "h1" },
    { title: __("Überschrift 2 (H2)", "hengegroup-theme"), value: "h2" },
    { title: __("Überschrift 3 (H3)", "hengegroup-theme"), value: "h3" },
    { title: __("Überschrift 4 (H4)", "hengegroup-theme"), value: "h4" },
    { title: __("Überschrift 5 (H5)", "hengegroup-theme"), value: "h5" },
    { title: __("Überschrift 6 (H6)", "hengegroup-theme"), value: "h6" },
    { title: __("Absatz (P)", "hengegroup-theme"), value: "p" },
];

// Values match render.php's `$container_width`/`.wrapper`-`.wrapper-small` choice (assets/css/
// app.css, see docs/entscheidungen.md "12-Spalten-Grid ueber .wrapper") -- "default" is the wider
// `.wrapper` used by full-width block layouts, "small" this block's original, narrower default
// (schmale Textspalte).
const CONTAINER_WIDTH_OPTIONS = [
    { title: __("Standard", "hengegroup-theme"), value: "default" },
    { title: __("Schmal", "hengegroup-theme"), value: "small" },
];

function parseAccentWords(value) {
    return value
        .split(",")
        .map((word) => word.trim())
        .filter((word) => word !== "");
}

// render.php/typography.php's accent_words only WRAP words that already occur verbatim in the
// heading string -- they never insert anything. Editors coming from the old, hand-coded design
// (where "HENGEGROUP" was hardcoded as part of the heading) can easily type only the surrounding
// text and expect the accent word to be added by this field instead, which then silently renders
// nothing for it (see docs/entscheidungen.md's note on this block for the concrete case). Surfaced
// as an inline Notice instead of only prose in `help` below, since that prose alone wasn't enough
// to prevent this exact mistake.
function findUnmatchedAccentWords(heading, accentWords) {
    return accentWords.filter((word) => !heading.includes(word));
}

// RichText's `value`/`onChange` operate on HTML strings; `heading`/`text` themselves stay PLAIN
// strings (render.php passes them through typography.php's own esc_html()/accent_words escaping,
// see that file's header) -- round-tripping through @wordpress/rich-text's create()/toHTMLString()
// keeps a literal "&"/"<" typed by an editor correctly entity-escaped for the contenteditable
// element without ever letting real formatting markup (bold/links/...) leak into the stored
// attribute. `allowedFormats={[]}` on the RichText below is what actually blocks the format
// toolbar; this pair of helpers is only about the entity-escaping round-trip.
function toRichTextValue(text) {
    return toHTMLString({ value: create({ text }) });
}

function fromRichTextValue(html) {
    return create({ html }).text;
}

function Edit({ attributes, setAttributes }) {
    const { heading, headingTag, accentWords, text, textAlign, containerWidth } = attributes;
    const unmatchedAccentWords = findUnmatchedAccentWords(heading, accentWords);
    const alignClass = textAlign === "left" ? "text-left" : "text-center";
    const wrapperClass = containerWidth === "default" ? "wrapper" : "wrapper-small";
    // `alignfull` hardcodiert statt ueber `supports.align` -- siehe Kopfkommentar.
    const blockProps = useBlockProps({ className: "alignfull py-16 md:py-24 lg:py-35" });
    // Built as an array + join() instead of a template literal with an inline conditional
    // fragment -- `prettier-plugin-tailwindcss` (package.json) normalizes whitespace WITHIN each
    // template-literal segment it sorts, which silently ate a leading `" mb-7"` space here and
    // produced an invalid concatenated "leading-tightmb-7" class at runtime.
    const headingClassName = ["text-5xl font-semibold leading-tight", text !== "" ? "mb-7" : ""]
        .filter(Boolean)
        .join(" ");

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
                        icon="editor-alignleft"
                        label={__("Linksbündig", "hengegroup-theme")}
                        isPressed={textAlign === "left"}
                        onClick={() => setAttributes({ textAlign: "left" })}
                    />
                    <ToolbarButton
                        icon="editor-aligncenter"
                        label={__("Zentriert", "hengegroup-theme")}
                        isPressed={textAlign === "center"}
                        onClick={() => setAttributes({ textAlign: "center" })}
                    />
                </ToolbarGroup>
                <ToolbarGroup>
                    <ToolbarDropdownMenu
                        icon="editor-expand"
                        label={__("Breite ändern", "hengegroup-theme")}
                        controls={CONTAINER_WIDTH_OPTIONS.map((option) => ({
                            title: option.title,
                            isActive: option.value === containerWidth,
                            onClick: () => setAttributes({ containerWidth: option.value }),
                        }))}
                    />
                </ToolbarGroup>
            </BlockControls>
            <InspectorControls>
                <PanelBody title={__("Einstellungen", "hengegroup-theme")} initialOpen>
                    <TextControl
                        label={__("Akzent-Wörter", "hengegroup-theme")}
                        help={__(
                            "Kommagetrennt — hebt diese Wörter in der Akzent-Schrift hervor. Muss WÖRTLICH oben in der Überschrift vorkommen, wird nicht ergänzt.",
                            "hengegroup-theme"
                        )}
                        value={accentWords.join(", ")}
                        onChange={(value) =>
                            setAttributes({ accentWords: parseAccentWords(value) })
                        }
                    />
                    {unmatchedAccentWords.length > 0 && (
                        <Notice status="warning" isDismissible={false}>
                            {sprintf(
                                __(
                                    "Kommt nicht in der Überschrift vor und wird deshalb nicht angezeigt: %s",
                                    "hengegroup-theme"
                                ),
                                unmatchedAccentWords.join(", ")
                            )}
                        </Notice>
                    )}
                </PanelBody>
            </InspectorControls>
            <section {...blockProps}>
                <div className={wrapperClass}>
                    <div className={`col-span-12 ${alignClass}`}>
                        <RichText
                            tagName={headingTag}
                            className={headingClassName}
                            value={toRichTextValue(heading)}
                            onChange={(html) => setAttributes({ heading: fromRichTextValue(html) })}
                            placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                            disableLineBreaks
                        />
                        <RichText
                            tagName="p"
                            className="text-2xl leading-normal"
                            value={toRichTextValue(text)}
                            onChange={(html) => setAttributes({ text: fromRichTextValue(html) })}
                            placeholder={__("Text eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                            disableLineBreaks
                        />
                    </div>
                </div>
            </section>
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    save: () => null,
});
