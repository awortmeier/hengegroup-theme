// Editor for "Offene Stellen": contact heading as RichText, contact data and empty text in the
// sidebar, jobs and contact card via ServerSideRender of offene-stellen-vorschau (per `part`).
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls, RichText, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, TextareaControl, TextControl, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "../../../../template-parts/blocks/offene-stellen/block.json";
import vorschauMetadata from "../../../../template-parts/blocks/offene-stellen-vorschau/block.json";
import { ACCENT_FORMATS } from "../shared.jsx";

const CONTACT_FIELDS = [
    ["contactName", __("Name", "hengegroup-theme")],
    ["contactRole", __("Funktion", "hengegroup-theme")],
    ["contactEmail", __("E-Mail", "hengegroup-theme")],
    ["contactPhone", __("Telefon", "hengegroup-theme")],
];

function Edit({ attributes, setAttributes }) {
    const { showContact, contactHeading, emptyText } = attributes;
    const contactAttributes = Object.fromEntries(
        CONTACT_FIELDS.map(([key]) => [key, attributes[key]])
    );
    // Full width in the editor iframe (theme.json contentSize would otherwise apply).
    const blockProps = useBlockProps({
        className: "alignfull bg-grey-light section-spacing-bottom",
    });

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={__("Ansprechpartner", "hengegroup-theme")} initialOpen>
                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={__("Ansprechpartner-Karte anzeigen", "hengegroup-theme")}
                        checked={showContact}
                        onChange={(value) => setAttributes({ showContact: value })}
                    />
                    {showContact && (
                        <Fragment>
                            <p>
                                {__(
                                    "Name und E-Mail leer = Standard-Ansprechpartner aus Karriere > Einstellungen.",
                                    "hengegroup-theme"
                                )}
                            </p>
                            {CONTACT_FIELDS.map(([key, label]) => (
                                <TextControl
                                    key={key}
                                    __nextHasNoMarginBottom
                                    __next40pxDefaultSize
                                    label={label}
                                    value={attributes[key]}
                                    onChange={(value) => setAttributes({ [key]: value })}
                                />
                            ))}
                        </Fragment>
                    )}
                </PanelBody>
                <PanelBody
                    title={__("Keine offenen Stellen", "hengegroup-theme")}
                    initialOpen={false}
                >
                    <TextareaControl
                        __nextHasNoMarginBottom
                        label={__("Hinweistext", "hengegroup-theme")}
                        help={__("Leer lassen = kein Hinweis.", "hengegroup-theme")}
                        value={emptyText}
                        onChange={(value) => setAttributes({ emptyText: value })}
                    />
                </PanelBody>
            </InspectorControls>
            <section {...blockProps}>
                <div className="wrapper items-start gap-y-12">
                    <div className={`col-span-12 ${showContact ? "lg:col-span-8" : ""}`}>
                        <ServerSideRender
                            block={vorschauMetadata.name}
                            attributes={{ part: "list", emptyText }}
                        />
                    </div>
                    {showContact && (
                        <div className="col-span-12 lg:col-span-4">
                            <RichText
                                tagName="h2"
                                className="mb-5 text-3xl leading-tight font-semibold"
                                value={contactHeading}
                                onChange={(html) => setAttributes({ contactHeading: html })}
                                placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                                allowedFormats={ACCENT_FORMATS}
                                disableLineBreaks
                            />
                            <ServerSideRender
                                block={vorschauMetadata.name}
                                attributes={{ part: "contact", ...contactAttributes }}
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
