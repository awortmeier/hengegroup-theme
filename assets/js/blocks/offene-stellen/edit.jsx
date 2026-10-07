// Editor UI for template-parts/blocks/offene-stellen/block.json. Same Vite/@wordpress-globals
// architecture as the other blocks (see blocks/buehne/edit.jsx's header comment) -- built via
// vite.config.editor-offene-stellen.js.
//
// Die Ueberschrift ueber der Ansprechpartner-Karte ist direkt in der Canvas editierbar (`RichText`,
// explizite Nachfrage 2026-10-07), gleiches Muster wie produkte/edit.jsx: Stellenliste und Karte
// kommen als echte `ServerSideRender`-Vorschau ueber den inserter-versteckten Zwilling
// `hengegroup-theme/offene-stellen-vorschau` (je Spalte ein Aufruf mit `part`), weil ein
// `ServerSideRender` gegen diesen Block die Ueberschrift ein zweites Mal statisch zeigen wuerde.
// Die Stellen kommen aus dem Post-Type "stellenangebote", der Ansprechpartner aus
// Karriere > Einstellungen; die Sidebar haelt nur noch den Schalter fuer die Karte.
//
// Markup (section/wrapper/col-span) spiegelt render.php, damit die Canvas wie das Frontend aussieht.
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls, RichText, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { create, toHTMLString } from "@wordpress/rich-text";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "../../../../template-parts/blocks/offene-stellen/block.json";
import vorschauMetadata from "../../../../template-parts/blocks/offene-stellen-vorschau/block.json";

// Gleicher Plain-String-Rundlauf wie produkte/edit.jsx: `contactHeading` bleibt ein reiner String,
// render.php gibt ihn ueber typography.php's esc_html() aus.
function toRichTextValue(text) {
    return toHTMLString({ value: create({ text }) });
}

function fromRichTextValue(html) {
    return create({ html }).text;
}

function Edit({ attributes, setAttributes }) {
    const { showContact, contactHeading } = attributes;
    // Volle Breite im Editor-Iframe, gleiche Begruendung wie produkte/edit.jsx's `alignfull`.
    const blockProps = useBlockProps({
        className: "alignfull bg-grey-light py-16 md:py-20 lg:py-25",
    });

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={__("Ansprechpartner", "hengegroup-theme")} initialOpen>
                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={__("Ansprechpartner-Karte anzeigen", "hengegroup-theme")}
                        help={__(
                            "Name, Funktion und E-Mail werden unter Karriere > Einstellungen gepflegt.",
                            "hengegroup-theme"
                        )}
                        checked={showContact}
                        onChange={(value) => setAttributes({ showContact: value })}
                    />
                </PanelBody>
            </InspectorControls>
            <section {...blockProps}>
                <div className="wrapper items-start gap-y-12">
                    <div className={`col-span-12 ${showContact ? "lg:col-span-8" : ""}`}>
                        <ServerSideRender
                            block={vorschauMetadata.name}
                            attributes={{ part: "list" }}
                        />
                    </div>
                    {showContact && (
                        <div className="col-span-12 lg:col-span-4">
                            <RichText
                                tagName="h2"
                                className="mb-5 text-3xl leading-tight font-semibold"
                                value={toRichTextValue(contactHeading)}
                                onChange={(html) =>
                                    setAttributes({ contactHeading: fromRichTextValue(html) })
                                }
                                placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                                allowedFormats={[]}
                                disableLineBreaks
                            />
                            <ServerSideRender
                                block={vorschauMetadata.name}
                                attributes={{ part: "contact" }}
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

// Clientseitige Minimal-Registrierung des Vorschau-Zwillings, siehe produkte/edit.jsx.
registerBlockType(vorschauMetadata, {
    edit: () => null,
    save: () => null,
});
