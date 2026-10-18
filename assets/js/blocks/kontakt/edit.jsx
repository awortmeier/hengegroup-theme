// Editor for "Kontakt": heading/text as RichText, card data in the sidebar, card + form via
// ServerSideRender of kontakt-vorschau (a preview of this block would show the texts twice).
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls, RichText, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, SelectControl, TextControl, TextareaControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { create, toHTMLString } from "@wordpress/rich-text";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "../../../../template-parts/blocks/kontakt/block.json";
import vorschauMetadata from "../../../../template-parts/blocks/kontakt-vorschau/block.json";
import { ACCENT_FORMATS } from "../shared.jsx";

// Texts stay plain strings; render.php escapes them.
function toRichTextValue(text) {
    return toHTMLString({ value: create({ text }) });
}

function fromRichTextValue(html) {
    return create({ html }).text;
}

const ACCENT_OPTIONS = [
    { label: __("Blau", "hengegroup-theme"), value: "henge-blue" },
    { label: __("Grün", "hengegroup-theme"), value: "henge-green" },
    { label: __("Grau", "hengegroup-theme"), value: "henge-grey" },
    { label: __("Dunkelgrau", "hengegroup-theme"), value: "grey-dark" },
];

const CONTACT_FIELDS = [
    ["contactName", __("Name", "hengegroup-theme")],
    ["contactEmail", __("E-Mail", "hengegroup-theme")],
    ["contactPhone", __("Telefon", "hengegroup-theme")],
    ["contactFax", __("Fax", "hengegroup-theme")],
];

function Edit({ attributes, setAttributes }) {
    const { heading, text, accent, contactAddress } = attributes;
    const previewAttributes = {
        accent,
        contactAddress,
        ...Object.fromEntries(CONTACT_FIELDS.map(([key]) => [key, attributes[key]])),
    };
    const blockProps = useBlockProps({ className: "alignfull section-spacing" });

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={__("Kontaktkarte", "hengegroup-theme")} initialOpen>
                    <SelectControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__("Akzentfarbe", "hengegroup-theme")}
                        value={accent}
                        options={ACCENT_OPTIONS}
                        onChange={(value) => setAttributes({ accent: value })}
                    />
                    <p>
                        {__(
                            "Alle Felder leer = Daten aus Einstellungen > Footer.",
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
                    <TextareaControl
                        __nextHasNoMarginBottom
                        label={__("Adresse (eine Zeile pro Zeile)", "hengegroup-theme")}
                        value={contactAddress}
                        onChange={(value) => setAttributes({ contactAddress: value })}
                    />
                </PanelBody>
            </InspectorControls>
            <section {...blockProps}>
                <div className="wrapper mb-12">
                    <div className="col-span-12 max-w-3xl">
                        <RichText
                            tagName="h2"
                            className="mb-5 text-4xl leading-tight font-semibold lg:text-[42px]"
                            value={heading}
                            onChange={(html) => setAttributes({ heading: html })}
                            placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                            allowedFormats={ACCENT_FORMATS}
                            disableLineBreaks
                        />
                        <RichText
                            tagName="p"
                            className="text-lg leading-normal lg:text-[22px] lg:leading-[1.4]"
                            value={toRichTextValue(text)}
                            onChange={(html) => setAttributes({ text: fromRichTextValue(html) })}
                            placeholder={__("Text eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                        />
                    </div>
                </div>
                <ServerSideRender block={vorschauMetadata.name} attributes={previewAttributes} />
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
