// Editor for "Offene Stellen": contact heading as RichText, jobs and contact card via
// ServerSideRender of offene-stellen-vorschau (per `part`).
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls, RichText, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { create, toHTMLString } from "@wordpress/rich-text";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "../../../../template-parts/blocks/offene-stellen/block.json";
import vorschauMetadata from "../../../../template-parts/blocks/offene-stellen-vorschau/block.json";

// Plain string; render.php escapes it.
function toRichTextValue(text) {
    return toHTMLString({ value: create({ text }) });
}

function fromRichTextValue(html) {
    return create({ html }).text;
}

function Edit({ attributes, setAttributes }) {
    const { showContact, contactHeading } = attributes;
    // Full width in the editor iframe (theme.json contentSize would otherwise apply).
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

// Minimal client registration so ServerSideRender accepts the block.
registerBlockType(vorschauMetadata, {
    edit: () => null,
    save: () => null,
});
